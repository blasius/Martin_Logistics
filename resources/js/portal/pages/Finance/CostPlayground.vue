<script setup lang="ts">
import { ref, reactive, computed, onMounted, onUnmounted, nextTick } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import { routesApi } from '../../api/routes'
import { Fuel, MapPin, Plus, Trash2, DollarSign, Clock, Route as RouteIcon, Info, RotateCcw } from 'lucide-vue-next'

// ── Map State ──
let map: L.Map | null = null
let originMarker: L.Marker | null = null
let destMarker: L.Marker | null = null
let routeLine: L.Polyline | null = null
const mapReady = ref(false)

// ── Route Data ──
const origin = ref<{ lat: number; lng: number; label?: string } | null>(null)
const destination = ref<{ lat: number; lng: number; label?: string } | null>(null)
const routeDistance = ref(0)
const routeDuration = ref(0)
const routePath = ref<{ lat: number; lng: number }[]>([])
const routeLoading = ref(false)
const mapClickMode = ref<'origin' | 'destination'>('origin')

// ── Cost Inputs ──
const fuel = reactive({
    distance_km: 0,
    consumption_l_per_100km: 32,
    price_per_liter: 1.25,
})

const costs = reactive({
    driver_per_km: 0.45,
    driver_flat: 0,
    use_driver_flat: false,
    tolls: 0,
    insurance_per_trip: 0,
    maintenance_per_km: 0.08,
    loading_offloading: 0,
    customs_documentation: 0,
})

const otherCosts = ref<{ name: string; amount: number }[]>([])

// ── UI State ──

// ── Derived Values ──
const fuelLiters = computed(() => {
    return fuel.distance_km > 0 ? (fuel.distance_km / 100) * fuel.consumption_l_per_100km : 0
})

const fuelCost = computed(() => fuelLiters.value * fuel.price_per_liter)

const driverCost = computed(() => {
    if (costs.use_driver_flat) return costs.driver_flat
    return fuel.distance_km * costs.driver_per_km
})

const maintenanceCost = computed(() => fuel.distance_km * costs.maintenance_per_km)

const otherTotal = computed(() => otherCosts.value.reduce((sum, c) => sum + (c.amount || 0), 0))

const subtotal = computed(() =>
    fuelCost.value +
    driverCost.value +
    costs.tolls +
    costs.insurance_per_trip +
    maintenanceCost.value +
    costs.loading_offloading +
    costs.customs_documentation +
    otherTotal.value
)

const costPerKm = computed(() => fuel.distance_km > 0 ? subtotal.value / fuel.distance_km : 0)

const estimatedFuelCostPercent = computed(() =>
    subtotal.value > 0 ? (fuelCost.value / subtotal.value) * 100 : 0
)

const costBreakdown = computed(() => [
    { label: 'Fuel', value: fuelCost.value, color: 'bg-emerald-500', pct: estimatedFuelCostPercent.value },
    { label: 'Driver', value: driverCost.value, color: 'bg-blue-500', pct: subtotal.value > 0 ? (driverCost.value / subtotal.value) * 100 : 0 },
    { label: 'Tolls', value: costs.tolls, color: 'bg-amber-500', pct: subtotal.value > 0 ? (costs.tolls / subtotal.value) * 100 : 0 },
    { label: 'Insurance', value: costs.insurance_per_trip, color: 'bg-violet-500', pct: subtotal.value > 0 ? (costs.insurance_per_trip / subtotal.value) * 100 : 0 },
    { label: 'Maintenance', value: maintenanceCost.value, color: 'bg-rose-500', pct: subtotal.value > 0 ? (maintenanceCost.value / subtotal.value) * 100 : 0 },
    { label: 'Loading', value: costs.loading_offloading, color: 'bg-cyan-500', pct: subtotal.value > 0 ? (costs.loading_offloading / subtotal.value) * 100 : 0 },
    { label: 'Customs', value: costs.customs_documentation, color: 'bg-orange-500', pct: subtotal.value > 0 ? (costs.customs_documentation / subtotal.value) * 100 : 0 },
    ...(otherCosts.value.map((c, i) => ({
        label: c.name || `Other ${i + 1}`,
        value: c.amount || 0,
        color: 'bg-slate-400',
        pct: subtotal.value > 0 ? ((c.amount || 0) / subtotal.value) * 100 : 0,
    }))),
].filter(item => item.value > 0))

// ── Map Init ──
function initMap() {
    map = L.map('cost-map', { zoomControl: false }).setView([-1.9441, 30.0619], 7)
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap',
    }).addTo(map)
    L.control.zoom({ position: 'bottomright' }).addTo(map)

    map.on('click', onMapClick)

    setTimeout(() => {
        map?.invalidateSize()
        mapReady.value = true
    }, 400)
}

function onMapClick(e: L.LeafletMouseEvent) {
    const { lat, lng } = e.latlng

    if (mapClickMode.value === 'origin') {
        setOrigin(lat, lng)
        if (!destination.value) {
            mapClickMode.value = 'destination'
        }
    } else {
        setDestination(lat, lng)
        mapClickMode.value = 'origin'
    }
}

function setOrigin(lat: number, lng: number) {
    if (originMarker) map!.removeLayer(originMarker)

    const icon = L.divIcon({
        className: '',
        html: `<div style="width:28px;height:28px;background:#2563eb;border:3px solid white;border-radius:50%;box-shadow:0 2px 8px rgba(0,0,0,0.3);display:flex;align-items:center;justify-content:center;">
            <div style="width:8px;height:8px;background:white;border-radius:50%;"></div>
        </div>`,
        iconSize: [28, 28],
        iconAnchor: [14, 14],
    })

    originMarker = L.marker([lat, lng], { icon }).addTo(map!).bindPopup('Origin')
    origin.value = { lat, lng }
    fuel.distance_km = 0
    routeDuration.value = 0
    routePath.value = []
    if (routeLine) { map!.removeLayer(routeLine); routeLine = null }

    if (destination.value) fetchRoute()
}

function setDestination(lat: number, lng: number) {
    if (destMarker) map!.removeLayer(destMarker)

    const icon = L.divIcon({
        className: '',
        html: `<div style="width:28px;height:28px;background:#dc2626;border:3px solid white;border-radius:50%;box-shadow:0 2px 8px rgba(0,0,0,0.3);display:flex;align-items:center;justify-content:center;">
            <div style="width:8px;height:8px;background:white;border-radius:50%;"></div>
        </div>`,
        iconSize: [28, 28],
        iconAnchor: [14, 14],
    })

    destMarker = L.marker([lat, lng], { icon }).addTo(map!).bindPopup('Destination')
    destination.value = { lat, lng }
    fuel.distance_km = 0
    routeDuration.value = 0
    routePath.value = []
    if (routeLine) { map!.removeLayer(routeLine); routeLine = null }

    if (origin.value) fetchRoute()
}

// ── OSRM Routing ──
async function fetchRoute() {
    if (!origin.value || !destination.value) return
    routeLoading.value = true
    try {
        const from = `${origin.value.lat},${origin.value.lng}`
        const to = `${destination.value.lat},${destination.value.lng}`
        const res = await routesApi.getRouteFromOsrm({ from, to })
        const routes = res.data.routes
        if (routes && routes.length > 0) {
            const best = routes[0]
            fuel.distance_km = best.distance_km
            routeDuration.value = best.duration_min
            routePath.value = best.path
            drawRoute(best.path)
        }
    } catch (e) {
        console.error('Routing failed', e)
    } finally {
        routeLoading.value = false
    }
}

function drawRoute(path: { lat: number; lng: number }[]) {
    if (!map) return
    if (routeLine) map.removeLayer(routeLine)

    const latlngs = path.map(p => [p.lat, p.lng] as [number, number])
    routeLine = L.polyline(latlngs, { color: '#2563eb', weight: 5, opacity: 0.85 }).addTo(map)

    const bounds = L.latLngBounds(latlngs)
    map.fitBounds(bounds.pad(0.2), { padding: [40, 40], maxZoom: 15 })
}

// ── Actions ──
function clearMap() {
    if (!map) return
    if (originMarker) { map.removeLayer(originMarker); originMarker = null }
    if (destMarker) { map.removeLayer(destMarker); destMarker = null }
    if (routeLine) { map.removeLayer(routeLine); routeLine = null }
    origin.value = null
    destination.value = null
    fuel.distance_km = 0
    routeDuration.value = 0
    routePath.value = []
    mapClickMode.value = 'origin'
}

function addOtherCost() {
    otherCosts.value.push({ name: '', amount: 0 })
}

function removeOtherCost(index: number) {
    otherCosts.value.splice(index, 1)
}

function formatCurrency(val: number) {
    return val.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function formatDuration(minutes: number) {
    const h = Math.floor(minutes / 60)
    const m = Math.round(minutes % 60)
    return h > 0 ? `${h}h ${m}m` : `${m}m`
}

// ── Lifecycle ──
onMounted(async () => {
    await nextTick()
    initMap()
})

onUnmounted(() => {
    if (map) { map.remove(); map = null }
})
</script>

<template>
    <div class="flex flex-col h-screen bg-slate-50 overflow-hidden font-sans">
        <!-- Header -->
        <header class="p-6 pb-2 shrink-0 flex justify-between items-end">
            <div>
                <h1 class="text-3xl font-black text-slate-800 tracking-tight uppercase italic leading-none">Trip Cost Estimator</h1>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-1">Finance Playground &middot; Estimate trip costs before dispatch</p>
            </div>
            <div class="flex gap-3">
                <button @click="clearMap"
                        class="bg-slate-200 hover:bg-slate-300 text-slate-700 px-5 py-2.5 rounded-2xl text-[10px] font-black transition-all active:scale-95 uppercase">
                    <RotateCcw class="w-3.5 h-3.5 inline -mt-0.5 mr-1" /> Reset All
                </button>
            </div>
        </header>

        <div class="flex flex-1 overflow-hidden p-6 pt-4 gap-6">
            <!-- Map (Left) -->
            <div class="flex-1 bg-white rounded-[3rem] shadow-sm border border-slate-200 overflow-hidden relative z-10">
                <div id="cost-map" class="h-full w-full"></div>

                <!-- Map Click Hint -->
                <div v-if="!origin || !destination"
                     class="absolute top-4 left-4 z-[1000] bg-white/90 backdrop-blur-sm rounded-xl shadow-lg border border-slate-200 px-4 py-2.5">
                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-wider">
                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full" :class="mapClickMode === 'origin' ? 'bg-blue-600' : 'bg-red-600'"></span>
                            Click map to place {{ mapClickMode === 'origin' ? 'ORIGIN' : 'DESTINATION' }}
                        </span>
                    </p>
                </div>

                <!-- Route Stats Overlay -->
                <div v-if="fuel.distance_km > 0"
                     class="absolute top-4 left-4 z-[1000] bg-white/90 backdrop-blur-sm rounded-xl shadow-lg border border-slate-200 px-4 py-3">
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-2">
                            <RouteIcon class="w-4 h-4 text-blue-600" />
                            <span class="text-sm font-black text-slate-800">{{ fuel.distance_km }} km</span>
                        </div>
                        <div class="w-px h-4 bg-slate-200"></div>
                        <div class="flex items-center gap-2">
                            <Clock class="w-4 h-4 text-amber-600" />
                            <span class="text-sm font-black text-slate-800">{{ formatDuration(routeDuration) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Loading Overlay -->
                <div v-if="!mapReady || routeLoading"
                     class="absolute inset-0 bg-slate-50/70 backdrop-blur-md z-[2000] flex items-center justify-center">
                    <div class="flex flex-col items-center gap-3 bg-white p-5 rounded-3xl shadow-2xl border border-slate-100">
                        <div class="w-9 h-9 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
                        <p class="text-[10px] font-black text-slate-600 uppercase tracking-widest">
                            {{ routeLoading ? 'Calculating Route...' : 'Loading Map...' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Cost Panel (Right) -->
            <div class="w-[440px] flex flex-col z-20 shrink-0">
                <div class="flex flex-col h-full bg-white rounded-[2.5rem] shadow-sm border border-slate-200 overflow-hidden">

                    <!-- Panel Header -->
                    <div class="p-5 border-b border-slate-100 bg-slate-50/50 shrink-0">
                        <div class="flex items-center justify-between">
                            <h2 class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Cost Breakdown</h2>
                            <div v-if="fuel.distance_km > 0" class="flex items-center gap-1.5 bg-emerald-50 px-3 py-1 rounded-full">
                                <span class="text-[9px] font-black text-emerald-600 uppercase">{{ costPerKm.toFixed(2) }} /km</span>
                            </div>
                        </div>
                    </div>

                    <!-- Scrollable Content -->
                    <div class="flex-1 overflow-y-auto custom-scrollbar p-5 space-y-5">

                        <!-- ─── Route Info ─── -->
                        <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100">
                            <div class="flex items-center gap-2 mb-3">
                                <MapPin class="w-4 h-4 text-slate-400" />
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Route</span>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <p class="text-[9px] font-bold text-slate-400 uppercase mb-0.5">Origin</p>
                                    <p class="text-xs font-bold text-slate-700 truncate" :title="origin ? `${origin.lat.toFixed(5)}, ${origin.lng.toFixed(5)}` : '—'">
                                        {{ origin ? `${origin.lat.toFixed(4)}, ${origin.lng.toFixed(4)}` : 'Click map' }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-[9px] font-bold text-slate-400 uppercase mb-0.5">Destination</p>
                                    <p class="text-xs font-bold text-slate-700 truncate" :title="destination ? `${destination.lat.toFixed(5)}, ${destination.lng.toFixed(5)}` : '—'">
                                        {{ destination ? `${destination.lat.toFixed(4)}, ${destination.lng.toFixed(4)}` : 'Click map' }}
                                    </p>
                                </div>
                            </div>
                            <div v-if="fuel.distance_km > 0" class="mt-3 pt-3 border-t border-slate-200 grid grid-cols-2 gap-3">
                                <div>
                                    <p class="text-[9px] font-bold text-slate-400 uppercase mb-0.5">Distance</p>
                                    <p class="text-lg font-black text-slate-800">{{ fuel.distance_km }} km</p>
                                </div>
                                <div>
                                    <p class="text-[9px] font-bold text-slate-400 uppercase mb-0.5">Est. Duration</p>
                                    <p class="text-lg font-black text-slate-800">{{ formatDuration(routeDuration) }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- ─── Fuel Cost ─── -->
                        <div class="bg-white rounded-2xl p-4 border border-slate-200">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-2">
                                    <div class="p-1.5 bg-emerald-100 rounded-lg">
                                        <Fuel class="w-4 h-4 text-emerald-600" />
                                    </div>
                                    <span class="text-xs font-black text-slate-700 uppercase">Fuel</span>
                                </div>
                                <span class="text-sm font-black text-emerald-600">{{ formatCurrency(fuelCost) }}</span>
                            </div>
                            <div class="grid grid-cols-3 gap-3">
                                <div>
                                    <label class="text-[9px] font-bold text-slate-400 uppercase block mb-1">Distance (km)</label>
                                    <input v-model.number="fuel.distance_km" type="number" min="0" step="0.1"
                                           class="w-full border border-slate-200 rounded-lg px-2.5 py-2 text-xs font-bold text-slate-700 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 bg-slate-50" />
                                </div>
                                <div>
                                    <label class="text-[9px] font-bold text-slate-400 uppercase block mb-1">Consumption L/100km</label>
                                    <input v-model.number="fuel.consumption_l_per_100km" type="number" min="0" step="0.1"
                                           class="w-full border border-slate-200 rounded-lg px-2.5 py-2 text-xs font-bold text-slate-700 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 bg-slate-50" />
                                </div>
                                <div>
                                    <label class="text-[9px] font-bold text-slate-400 uppercase block mb-1">Price / Liter</label>
                                    <input v-model.number="fuel.price_per_liter" type="number" min="0" step="0.01"
                                           class="w-full border border-slate-200 rounded-lg px-2.5 py-2 text-xs font-bold text-slate-700 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 bg-slate-50" />
                                </div>
                            </div>
                            <div class="mt-3 pt-3 border-t border-slate-100 flex justify-between items-center">
                                <span class="text-[9px] font-bold text-slate-400 uppercase">Estimated Fuel Needed</span>
                                <span class="text-xs font-black text-slate-600">{{ fuelLiters.toFixed(1) }} liters</span>
                            </div>
                        </div>

                        <!-- ─── Driver Cost ─── -->
                        <div class="bg-white rounded-2xl p-4 border border-slate-200">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-2">
                                    <div class="p-1.5 bg-blue-100 rounded-lg">
                                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    </div>
                                    <span class="text-xs font-black text-slate-700 uppercase">Driver Allowance</span>
                                </div>
                                <span class="text-sm font-black text-blue-600">{{ formatCurrency(driverCost) }}</span>
                            </div>
                            <div class="flex items-center gap-3 mb-3">
                                <button @click="costs.use_driver_flat = false"
                                        class="flex-1 py-2 rounded-lg text-[10px] font-black uppercase tracking-wider transition-all"
                                        :class="!costs.use_driver_flat ? 'bg-blue-600 text-white shadow-md' : 'bg-slate-100 text-slate-500 hover:bg-slate-200'">
                                    Per KM
                                </button>
                                <button @click="costs.use_driver_flat = true"
                                        class="flex-1 py-2 rounded-lg text-[10px] font-black uppercase tracking-wider transition-all"
                                        :class="costs.use_driver_flat ? 'bg-blue-600 text-white shadow-md' : 'bg-slate-100 text-slate-500 hover:bg-slate-200'">
                                    Flat Rate
                                </button>
                            </div>
                            <div v-if="!costs.use_driver_flat">
                                <label class="text-[9px] font-bold text-slate-400 uppercase block mb-1">Rate per KM</label>
                                <input v-model.number="costs.driver_per_km" type="number" min="0" step="0.01"
                                       class="w-full border border-slate-200 rounded-lg px-2.5 py-2 text-xs font-bold text-slate-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-slate-50" />
                            </div>
                            <div v-else>
                                <label class="text-[9px] font-bold text-slate-400 uppercase block mb-1">Flat Amount</label>
                                <input v-model.number="costs.driver_flat" type="number" min="0" step="0.01"
                                       class="w-full border border-slate-200 rounded-lg px-2.5 py-2 text-xs font-bold text-slate-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-slate-50" />
                            </div>
                        </div>

                        <!-- ─── Standard Costs ─── -->
                        <div class="bg-white rounded-2xl p-4 border border-slate-200 space-y-4">
                            <div class="flex items-center gap-2">
                                <div class="p-1.5 bg-amber-100 rounded-lg">
                                    <DollarSign class="w-4 h-4 text-amber-600" />
                                </div>
                                <span class="text-xs font-black text-slate-700 uppercase">Other Standard Costs</span>
                            </div>

                            <!-- Tolls -->
                            <div>
                                <label class="text-[9px] font-bold text-slate-400 uppercase block mb-1">Tolls & Road Charges</label>
                                <input v-model.number="costs.tolls" type="number" min="0" step="0.01" placeholder="0.00"
                                       class="w-full border border-slate-200 rounded-lg px-2.5 py-2 text-xs font-bold text-slate-700 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 bg-slate-50" />
                            </div>

                            <!-- Insurance -->
                            <div>
                                <label class="text-[9px] font-bold text-slate-400 uppercase block mb-1">Insurance (per trip)</label>
                                <input v-model.number="costs.insurance_per_trip" type="number" min="0" step="0.01" placeholder="0.00"
                                       class="w-full border border-slate-200 rounded-lg px-2.5 py-2 text-xs font-bold text-slate-700 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 bg-slate-50" />
                            </div>

                            <!-- Maintenance -->
                            <div>
                                <label class="text-[9px] font-bold text-slate-400 uppercase block mb-1">Maintenance & Tyres (per km)</label>
                                <input v-model.number="costs.maintenance_per_km" type="number" min="0" step="0.01" placeholder="0.00"
                                       class="w-full border border-slate-200 rounded-lg px-2.5 py-2 text-xs font-bold text-slate-700 focus:ring-2 focus:ring-rose-500 focus:border-rose-500 bg-slate-50" />
                            </div>

                            <!-- Loading -->
                            <div>
                                <label class="text-[9px] font-bold text-slate-400 uppercase block mb-1">Loading / Offloading</label>
                                <input v-model.number="costs.loading_offloading" type="number" min="0" step="0.01" placeholder="0.00"
                                       class="w-full border border-slate-200 rounded-lg px-2.5 py-2 text-xs font-bold text-slate-700 focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 bg-slate-50" />
                            </div>

                            <!-- Customs -->
                            <div>
                                <label class="text-[9px] font-bold text-slate-400 uppercase block mb-1">Customs / Documentation</label>
                                <input v-model.number="costs.customs_documentation" type="number" min="0" step="0.01" placeholder="0.00"
                                       class="w-full border border-slate-200 rounded-lg px-2.5 py-2 text-xs font-bold text-slate-700 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 bg-slate-50" />
                            </div>
                        </div>

                        <!-- ─── Other Costs (Dynamic) ─── -->
                        <div class="bg-white rounded-2xl p-4 border border-slate-200">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-xs font-black text-slate-700 uppercase">Custom Costs</span>
                                <button @click="addOtherCost"
                                        class="flex items-center gap-1 bg-slate-100 hover:bg-slate-200 text-slate-600 px-3 py-1.5 rounded-lg text-[10px] font-black uppercase transition-all active:scale-95">
                                    <Plus class="w-3 h-3" /> Add
                                </button>
                            </div>
                            <div v-if="otherCosts.length === 0" class="text-center py-4">
                                <p class="text-[10px] font-bold text-slate-300 uppercase">No custom costs added</p>
                            </div>
                            <div v-else class="space-y-2">
                                <div v-for="(item, idx) in otherCosts" :key="idx" class="flex items-center gap-2">
                                    <input v-model="item.name" type="text" placeholder="Cost name"
                                           class="flex-1 border border-slate-200 rounded-lg px-2.5 py-2 text-xs font-bold text-slate-700 focus:ring-2 focus:ring-slate-400 bg-slate-50" />
                                    <input v-model.number="item.amount" type="number" min="0" step="0.01" placeholder="0.00"
                                           class="w-24 border border-slate-200 rounded-lg px-2.5 py-2 text-xs font-bold text-slate-700 focus:ring-2 focus:ring-slate-400 bg-slate-50" />
                                    <button @click="removeOtherCost(idx)"
                                            class="text-red-400 hover:text-red-600 hover:bg-red-50 p-1.5 rounded-lg transition-colors active:scale-95 shrink-0">
                                        <Trash2 class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- ─── Visual Breakdown Bar ─── -->
                        <div v-if="subtotal > 0 && costBreakdown.length > 1" class="bg-white rounded-2xl p-4 border border-slate-200">
                            <div class="flex items-center gap-2 mb-3">
                                <div class="p-1.5 bg-slate-100 rounded-lg">
                                    <Info class="w-4 h-4 text-slate-500" />
                                </div>
                                <span class="text-xs font-black text-slate-700 uppercase">Cost Distribution</span>
                            </div>
                            <div class="h-3 rounded-full bg-slate-100 overflow-hidden flex">
                                <div v-for="(item, i) in costBreakdown" :key="i"
                                     :class="item.color"
                                     class="h-full transition-all duration-300"
                                     :style="{ width: `${Math.max(item.pct, 1)}%` }"
                                     :title="`${item.label}: ${formatCurrency(item.value)} (${item.pct.toFixed(0)}%)`">
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-x-4 gap-y-1 mt-2.5">
                                <div v-for="(item, i) in costBreakdown" :key="i" class="flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full" :class="item.color"></span>
                                    <span class="text-[9px] font-bold text-slate-500">{{ item.label }}: {{ formatCurrency(item.value) }}</span>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- ─── Grand Total Footer ─── -->
                    <div class="p-5 border-t border-slate-100 bg-slate-50/50 shrink-0">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Estimated Total</p>
                                <p class="text-2xl font-black text-slate-800 tracking-tight mt-0.5">
                                    ${{ formatCurrency(subtotal) }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-[9px] font-bold text-slate-400 uppercase">Cost per km</p>
                                <p class="text-sm font-black text-emerald-600">${{ costPerKm.toFixed(2) }}</p>
                                <p v-if="fuel.distance_km > 0" class="text-[9px] font-bold text-slate-400 mt-1">
                                    {{ formatDuration(routeDuration) }} drive time
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
.leaflet-container {
    font-family: inherit;
    z-index: 1 !important;
    border-radius: 3rem !important;
}
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}
</style>
