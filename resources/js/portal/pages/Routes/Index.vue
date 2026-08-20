<template>
    <div class="flex flex-col h-screen bg-slate-50 overflow-hidden font-sans">
        <!-- Top Header -->
        <header class="p-6 pb-2 shrink-0 flex justify-between items-end">
            <div>
                <h1 class="text-3xl font-black text-slate-800 tracking-tight uppercase italic leading-none">Route Architect</h1>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-1">Geofencing & Path Design</p>
            </div>

            <!-- Contextual Header Action -->
            <div class="flex gap-3">
                <button v-if="currentView === 'list'" @click="createNewRoute"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-2xl text-xs font-black shadow-lg shadow-blue-200 active:scale-95 transition-all uppercase">
                    + Create New Route
                </button>
                <button v-else @click="closeForm"
                        class="bg-slate-200 hover:bg-slate-300 text-slate-700 px-8 py-3 rounded-2xl text-xs font-black transition-all active:scale-95 uppercase">
                    &larr; Back to List
                </button>
            </div>
        </header>

        <div class="flex flex-1 overflow-hidden p-6 pt-4 gap-6">

            <!-- Map Canvas (Left Side) -->
            <div class="flex-1 bg-white rounded-[3rem] shadow-sm border border-slate-200 overflow-hidden relative z-10">
                <div id="map" class="h-full w-full"></div>

                <!-- Floating Map Toolbar (form mode only) -->
                <div v-if="currentView === 'form' && !loadingRouteDetails" class="absolute top-4 left-4 z-[1000] flex gap-2">
                    <button @click="clearAllMapLayers(); resetFormState();"
                            class="bg-white/90 backdrop-blur-sm hover:bg-red-50 text-slate-600 hover:text-red-600 px-3 py-2 rounded-xl text-[10px] font-black uppercase tracking-wider shadow-lg border border-slate-200 hover:border-red-300 transition-all active:scale-95"
                            title="Clear map">
                        <svg class="w-4 h-4 inline -mt-0.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Clear
                    </button>
                </div>

                <!-- Route Legend (when alternatives exist) -->
                <div v-if="currentView === 'form' && osrmRoutesCache && osrmRoutesCache.length > 1" class="absolute bottom-4 left-4 z-[1000] bg-white/90 backdrop-blur-sm rounded-xl shadow-lg border border-slate-200 px-3 py-2">
                    <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1">Route Legend</p>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="w-4 h-0.5 bg-blue-600 inline-block rounded"></span>
                        <span class="text-[9px] font-bold text-slate-600">Selected Route</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-4 h-0.5 bg-amber-500 inline-block rounded border-dashed" style="border-top: 2px dashed #f59e0b; height:0; background:none;"></span>
                        <span class="text-[9px] font-bold text-slate-600">Alternatives (click to select)</span>
                    </div>
                </div>

                <!-- Overlays -->
                <div v-if="!mapReady || loadingRouteDetails" class="absolute inset-0 bg-slate-50/70 backdrop-blur-md z-[2000] flex items-center justify-center">
                    <div class="flex flex-col items-center gap-4 bg-white p-6 rounded-3xl shadow-2xl border border-slate-100">
                        <div class="w-10 h-10 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
                        <p class="text-[10px] font-black text-slate-600 uppercase tracking-widest">
                            {{ loadingRouteDetails ? 'Loading Route Geometry...' : 'Initializing Map Engine...' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Single Master/Detail Sidebar (Right Side) -->
            <div class="w-[400px] flex flex-col z-20 shrink-0">

                <!-- VIEW 1: Route Directory (List) -->
                <div v-if="currentView === 'list'" class="flex flex-col h-full bg-white rounded-[2.5rem] shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-6 border-b border-slate-100 bg-slate-50/50 shrink-0">
                        <h2 class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Route Directory</h2>
                    </div>

                    <div class="flex-1 overflow-y-auto custom-scrollbar p-5 space-y-3">
                        <div v-if="loadingRoutes" class="flex justify-center p-8">
                            <div class="w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
                        </div>

                        <div v-else-if="routes.length === 0" class="text-center p-8 text-xs text-slate-400 font-bold uppercase">
                            No routes found. Create one to begin.
                        </div>

                        <div v-else v-for="route in routes" :key="route.id"
                             class="group bg-white p-5 rounded-2xl border border-slate-200 hover:border-blue-400 hover:shadow-md cursor-pointer transition-all relative overflow-hidden"
                             @click="editRoute(route.id)">

                            <!-- Small accent bar on hover -->
                            <div class="absolute left-0 top-0 bottom-0 w-1 bg-blue-500 opacity-0 group-hover:opacity-100 transition-opacity"></div>

                            <div class="flex justify-between items-start mb-3">
                                <h3 class="text-sm font-black text-slate-800 truncate pr-2 group-hover:text-blue-600 transition-colors">{{ route.name }}</h3>
                                <span class="text-[9px] font-black px-2.5 py-1 rounded-md bg-slate-100 text-slate-600">{{ route.fleet_key }}</span>
                            </div>
                            <div class="flex justify-between items-end">
                                <div>
                                    <p class="text-[9px] font-bold text-slate-400 uppercase mb-0.5">Estimated Distance</p>
                                    <p class="text-sm font-black italic">{{ route.estimated_distance_km }} KM</p>
                                </div>
                                <button @click.stop="confirmDelete(route)"
                                        class="text-red-400 hover:text-red-600 hover:bg-red-50 p-1.5 rounded-lg transition-colors active:scale-95"
                                        title="Delete">
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- VIEW 2: Route Editor (Form) -->
                <div v-else class="flex flex-col h-full bg-white rounded-[2.5rem] shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-6 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center shrink-0">
                        <h2 class="text-[10px] font-black text-slate-500 uppercase tracking-widest">
                            {{ editingMode ? 'Edit Route' : 'New Route Setup' }}
                        </h2>
                        <span v-if="editingMode" class="text-[9px] font-black px-2.5 py-1 rounded bg-amber-100 text-amber-700 uppercase">Editing Mode</span>
                    </div>

                    <div class="flex-1 overflow-y-auto custom-scrollbar p-6 space-y-6">
                        <!-- Inputs -->
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1 block mb-2 tracking-wide">Route Name</label>
                            <input v-model="form.name" type="text" placeholder="e.g. Kigali - Mombasa Main"
                                   class="border-slate-200 px-4 py-3.5 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-500 outline-none bg-slate-50 w-full transition-all" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="flex flex-col" v-if="editingMode">
                                <label class="text-[10px] font-black text-slate-400 uppercase ml-1 block mb-2 tracking-wide">Fleet Key</label>
                                <div class="px-4 py-3.5 rounded-xl text-sm font-bold bg-slate-100 text-slate-500 truncate cursor-not-allowed border border-slate-200">
                                    {{ selectedRoute.fleet_key }}
                                </div>
                            </div>
                            <div class="flex flex-col" :class="{'col-span-2': !editingMode}">
                                <label class="text-[10px] font-black text-slate-400 uppercase ml-1 block mb-2 tracking-wide">Deviation (m)</label>
                                <input v-model="form.allowed_deviation_meters" type="number"
                                       class="border-slate-200 px-4 py-3.5 rounded-xl text-sm font-bold bg-slate-50 focus:ring-2 focus:ring-blue-500 outline-none transition-all w-full" />
                            </div>
                        </div>

                        <!-- Dynamic Stats -->
                        <div class="bg-slate-900 p-6 rounded-[2rem] shadow-xl border border-slate-800 text-white mt-6 relative overflow-hidden">
                            <!-- Decor -->
                            <div class="absolute -right-4 -top-4 w-24 h-24 bg-blue-500/10 rounded-full blur-2xl"></div>

                            <p class="text-[10px] font-black text-blue-400 uppercase tracking-widest mb-3 relative z-10">Live Path Geometry</p>
                            <div class="flex justify-between items-end relative z-10">
                                <div>
                                    <span class="text-5xl font-black italic tracking-tighter">{{ routeDistance || totalDistance }}</span>
                                    <span class="text-xs font-bold text-slate-400 ml-1 uppercase">KM</span>
                                </div>
                                <div class="flex gap-3">
                                    <div v-if="routeDuration" class="text-right bg-white/10 px-3 py-2 rounded-xl backdrop-blur-sm border border-white/5">
                                        <p class="text-[8px] font-black text-slate-300 uppercase">Duration</p>
                                        <p class="text-xl font-black leading-none mt-0.5">{{ routeDuration }}<span class="text-xs text-slate-400 ml-0.5">min</span></p>
                                    </div>
                                    <div class="text-right bg-white/10 px-3 py-2 rounded-xl backdrop-blur-sm border border-white/5">
                                        <p class="text-[8px] font-black text-slate-300 uppercase">Waypoints</p>
                                        <p class="text-xl font-black leading-none mt-0.5">{{ pathData.length }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Action Area -->
                    <div class="p-6 border-t border-slate-100 bg-slate-50 shrink-0 space-y-3">
                        <div class="flex gap-3">
                            <button @click="saveRoute"
                                    :disabled="saving || pathData.length < 2"
                                    class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-4 rounded-2xl text-xs font-black transition-all shadow-lg shadow-blue-200 active:scale-95 disabled:opacity-40 disabled:shadow-none uppercase tracking-widest">
                                {{ saving ? 'Processing...' : (editingMode ? 'Update Route' : 'Save & Publish Route') }}
                            </button>
                            <button v-if="startMarker && endMarker && !addingWaypoint"
                                    @click="addingWaypoint = true"
                                    class="bg-slate-200 hover:bg-slate-300 text-slate-700 px-4 py-4 rounded-2xl text-xs font-black transition-all active:scale-95 uppercase tracking-wider shrink-0">
                                + Waypoint
                            </button>
                        </div>

                        <p class="text-[9px] text-center font-bold text-slate-400 uppercase px-2 leading-relaxed">
                            <template v-if="addingWaypoint">
                                Click the map to place an intermediate waypoint.
                            </template>
                            <template v-else-if="!startMarker">
                                Click the map to place your <span class="text-green-600">start point</span>.
                            </template>
                            <template v-else-if="!endMarker">
                                Now click to place your <span class="text-red-600">end point</span>. Route follows roads automatically.
                            </template>
                            <template v-else>
                                Drag markers or handles to adjust. Use + Waypoint to add intermediate stops.
                            </template>
                            <span v-if="pathData.length < 2" class="text-amber-500 block mt-1">Requires at least 2 waypoints.</span>
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <Teleport to="body">
        <div v-if="confirmDialog.show"
             class="fixed inset-0 z-[60] flex items-center justify-center bg-black/40 backdrop-blur-sm">
            <div class="bg-white rounded-[2.5rem] shadow-2xl border border-slate-200 w-full max-w-sm mx-4 overflow-hidden">
                <div class="p-6 text-center">
                    <div class="mx-auto w-12 h-12 rounded-full bg-red-100 flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider mb-2">Delete Route</h3>
                    <p class="text-xs font-bold text-slate-500">Delete <span class="text-slate-700">{{ confirmDialog.route?.name }}</span>? This cannot be undone.</p>
                </div>
                <div class="px-6 pb-6 flex gap-3">
                    <button @click="confirmDialog.show = false"
                            class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-700 py-3.5 rounded-2xl text-xs font-black transition-all active:scale-95 uppercase tracking-wider">
                        Cancel
                    </button>
                    <button @click="executeDelete"
                            :disabled="confirmDialog.deleting"
                            class="flex-1 bg-red-600 hover:bg-red-700 text-white py-3.5 rounded-2xl text-xs font-black transition-all shadow-lg shadow-red-200 active:scale-95 disabled:opacity-40 disabled:shadow-none uppercase tracking-wider">
                        {{ confirmDialog.deleting ? 'Deleting...' : 'Delete' }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>

    <Teleport to="body">
        <div v-if="alertDialog.show"
             class="fixed inset-0 z-[60] flex items-center justify-center bg-black/40 backdrop-blur-sm"
             @click.self="alertDialog.show = false">
            <div class="bg-white rounded-[2.5rem] shadow-2xl border border-slate-200 w-full max-w-sm mx-4 overflow-hidden">
                <div class="p-6 text-center">
                    <div class="mx-auto w-12 h-12 rounded-full bg-amber-100 flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider mb-2">Error</h3>
                    <p class="text-xs font-bold text-slate-500">{{ alertDialog.message }}</p>
                </div>
                <div class="px-6 pb-6">
                    <button @click="alertDialog.show = false"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3.5 rounded-2xl text-xs font-black transition-all shadow-lg shadow-blue-200 active:scale-95 uppercase tracking-wider">
                        OK
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, reactive, onMounted, computed, nextTick } from 'vue';
import { routesApi } from "../../api/routes";
import { Trash2 } from 'lucide-vue-next';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

// State
const routes = ref([]);
const selectedRoute = ref(null);
const loadingRoutes = ref(true);
const loadingRouteDetails = ref(false);
const saving = ref(false);
const mapReady = ref(false);
const pathData = ref([]);
const addingWaypoint = ref(false);
const routeDistance = ref('');
const routeDuration = ref('');
const selectedAlternativeIndex = ref(0);
const osrmRoutesCache = ref(null);

const confirmDialog = reactive({ show: false, route: null, deleting: false });
const alertDialog = reactive({ show: false, message: '' });
const currentView = ref('list');
const form = ref({ name: '', allowed_deviation_meters: 500 });
const editingMode = computed(() => !!selectedRoute.value);

let map = null;
let userMarker = null;
let startMarker = null;
let endMarker = null;
let intermediateMarkers = [];
let routeLayer = null;
let alternativeLayers = [];
let routeHandles = [];

const totalDistance = computed(() => {
    if (pathData.value.length < 2) return '0.00';
    let dist = 0;
    for (let i = 0; i < pathData.value.length - 1; i++) {
        const p1 = L.latLng(pathData.value[i].lat, pathData.value[i].lng);
        const p2 = L.latLng(pathData.value[i+1].lat, pathData.value[i+1].lng);
        dist += p1.distanceTo(p2);
    }
    return (dist / 1000).toFixed(2);
});

// --- Icon helpers ---
function divIcon(html, size = 30) {
    return L.divIcon({
        className: '',
        html,
        iconSize: [size, size],
        iconAnchor: [size / 2, size / 2],
    });
}

function startEndIcon(color, label) {
    return divIcon(`<div style="width:30px;height:30px;background:${color};border-radius:50%;border:3px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,.3);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:900;font-size:12px">${label}</div>`);
}

function waypointIcon(num) {
    return divIcon(`<div style="width:26px;height:26px;background:#2563eb;border-radius:50%;border:3px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,.3);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:900;font-size:11px">${num}</div>`, 26);
}

function handleIcon(hovered = false) {
    const s = hovered ? 22 : 18;
    const bg = hovered ? 'rgba(37,99,235,.7)' : 'rgba(37,99,235,.45)';
    return divIcon(`<div style="width:${s}px;height:${s}px;background:${bg};border:2.5px solid #2563eb;border-radius:50%;cursor:grab;transition:all .15s;box-shadow:0 1px 4px rgba(0,0,0,.2)"></div>`, s);
}

// --- Map ---
const initMap = () => {
    map = L.map('map', { zoomControl: false }).setView([-1.9441, 30.0619], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap',
    }).addTo(map);
    L.control.zoom({ position: 'bottomright' }).addTo(map);

    map.on('click', onMapClick);

    setTimeout(() => {
        map.invalidateSize();
        mapReady.value = true;
        locateUser();
    }, 400);
};

const locateUser = () => {
    if (!navigator.geolocation) return;
    navigator.geolocation.getCurrentPosition(
        (pos) => {
            const { latitude, longitude } = pos.coords;
            map.flyTo([latitude, longitude], 17, { duration: 3 });
            const icon = L.icon({
                iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
                iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
                shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
                iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41],
            });
            if (userMarker) map.removeLayer(userMarker);
            userMarker = L.marker([latitude, longitude], { icon }).addTo(map).bindPopup('Your Location');
        },
        () => {},
        { enableHighAccuracy: true, timeout: 10000 }
    );
};

// --- Click-to-place markers ---
const onMapClick = (e) => {
    if (currentView.value !== 'form') return;
    const { lat, lng } = e.latlng;

    if (addingWaypoint.value) {
        addIntermediateMarker(lat, lng);
        addingWaypoint.value = false;
        return;
    }

    if (!startMarker) {
        placeStartMarker(lat, lng);
    } else if (!endMarker) {
        placeEndMarker(lat, lng);
        fetchRoute();
    }
};

const placeStartMarker = (lat, lng) => {
    if (startMarker) map.removeLayer(startMarker);
    startMarker = L.marker([lat, lng], { icon: startEndIcon('#16a34a', 'A'), draggable: true })
        .addTo(map)
        .bindPopup('Start — drag to adjust');
    startMarker.on('dragend', () => { if (endMarker) fetchRoute(); });
};

const placeEndMarker = (lat, lng) => {
    if (endMarker) map.removeLayer(endMarker);
    endMarker = L.marker([lat, lng], { icon: startEndIcon('#dc2626', 'B'), draggable: true })
        .addTo(map)
        .bindPopup('End — drag to adjust');
    endMarker.on('dragend', () => { if (startMarker) fetchRoute(); });
};

// --- Intermediate waypoints ---
const addIntermediateMarker = (lat, lng) => {
    const idx = intermediateMarkers.length + 1;
    const marker = L.marker([lat, lng], { icon: waypointIcon(idx), draggable: true })
        .addTo(map);
    marker._wpIndex = intermediateMarkers.length;

    const refreshPopup = () => {
        const n = marker._wpIndex + 1;
        marker.setPopupContent(`<div class="text-center"><b>Waypoint ${n}</b><br/><button class="text-red-500 text-xs font-bold mt-1 cursor-pointer" onclick="document.dispatchEvent(new CustomEvent('remove-wp',{detail:${marker._wpIndex}}))">Remove</button></div>`);
    };
    refreshPopup();
    marker.bindPopup();
    marker.on('popupopen', refreshPopup);

    marker.on('dragend', () => { reindexWaypoints(); if (startMarker && endMarker) fetchRoute(); });

    intermediateMarkers.push(marker);
    if (startMarker && endMarker) fetchRoute();
};

const removeIntermediateMarker = (index) => {
    if (intermediateMarkers[index]) {
        map.removeLayer(intermediateMarkers[index]);
        intermediateMarkers.splice(index, 1);
        reindexWaypoints();
        if (startMarker && endMarker) fetchRoute();
    }
};

const reindexWaypoints = () => {
    intermediateMarkers.forEach((m, i) => {
        m._wpIndex = i;
        m.setIcon(waypointIcon(i + 1));
    });
};

// --- OSRM routing ---
const fetchRoute = async () => {
    if (!startMarker || !endMarker) return;

    const from = `${startMarker.getLatLng().lat},${startMarker.getLatLng().lng}`;
    const to = `${endMarker.getLatLng().lat},${endMarker.getLatLng().lng}`;
    const waypoints = intermediateMarkers.length
        ? intermediateMarkers.map(m => `${m.getLatLng().lat},${m.getLatLng().lng}`)
        : undefined;

    try {
        const response = await routesApi.getRouteFromOsrm({ from, to, waypoints });
        const data = response.data;
        if (data.routes && data.routes.length > 0) {
            osrmRoutesCache.value = data.routes;
            selectedAlternativeIndex.value = 0;
            renderRoutes();
        }
    } catch (e) {
        console.error('OSRM routing failed', e);
        alertDialog.message = 'Could not calculate route. The routing service may be unavailable.';
        alertDialog.show = true;
    }
};

const renderRoutes = () => {
    if (!osrmRoutesCache.value || !osrmRoutesCache.value.length) return;

    clearRouteLayers();

    // Draw alternatives (non-selected) as faded amber dashed lines
    osrmRoutesCache.value.forEach((route, idx) => {
        if (idx === selectedAlternativeIndex.value) return;
        const latlngs = route.path.map(p => [p.lat, p.lng]);
        const layer = L.polyline(latlngs, { color: '#f59e0b', weight: 4, dashArray: '10,8', opacity: 0.6 }).addTo(map);
        layer.on('click', () => { selectedAlternativeIndex.value = idx; renderRoutes(); });
        alternativeLayers.push(layer);
    });

    // Draw selected route as solid blue
    const selected = osrmRoutesCache.value[selectedAlternativeIndex.value] || osrmRoutesCache.value[0];
    const latlngs = selected.path.map(p => [p.lat, p.lng]);
    routeLayer = L.polyline(latlngs, { color: '#2563eb', weight: 6, opacity: 0.9 }).addTo(map);

    pathData.value = selected.path;
    routeDistance.value = selected.distance_km;
    routeDuration.value = selected.duration_min;

    placeHandles(latlngs);

    try { map.fitBounds(routeLayer.getBounds(), { padding: [50, 50] }); } catch (_) {}
};

const clearRouteLayers = () => {
    if (routeLayer) { map.removeLayer(routeLayer); routeLayer = null; }
    alternativeLayers.forEach(l => map.removeLayer(l));
    alternativeLayers = [];
    clearHandles();
};

// --- Draggable route handles ---
const placeHandles = (latlngs) => {
    clearHandles();
    if (latlngs.length < 2) return;

    const count = Math.min(6, latlngs.length);
    const step = count > 1 ? Math.floor((latlngs.length - 1) / (count - 1)) : 0;

    for (let i = 0; i < count; i++) {
        const idx = i === 0 ? 0 : Math.min(i * step, latlngs.length - 1);
        const pos = latlngs[idx];

        const handle = L.marker(pos, { icon: handleIcon(), draggable: true, zIndexOffset: 1000 })
            .addTo(map)
            .bindTooltip('Drag to adjust route', { direction: 'top', offset: [0, -14] });

        handle.on('mouseover', function () { this.setIcon(handleIcon(true)); });
        handle.on('mouseout', function () { this.setIcon(handleIcon(false)); });

        handle.on('dragend', function () {
            const ll = this.getLatLng();
            addIntermediateMarker(ll.lat, ll.lng);
        });

        routeHandles.push(handle);
    }
};

const clearHandles = () => {
    routeHandles.forEach(h => { try { map.removeLayer(h); } catch (_) {} });
    routeHandles = [];
};

// --- Load existing route (view mode — no handles) ---
const loadPathOnMap = (pathArray) => {
    if (!map) return;
    clearAllMapLayers();

    if (!pathArray || pathArray.length < 2) return;

    const latlngs = pathArray.map(p => [p.lat, p.lng]);
    routeLayer = L.polyline(latlngs, { color: '#2563eb', weight: 5 }).addTo(map);
    pathData.value = [...pathArray];
    routeDistance.value = '';
    routeDuration.value = '';

    try { map.fitBounds(routeLayer.getBounds(), { padding: [50, 50] }); } catch (_) {}
};

// --- Cleanup helpers ---
const clearAllMapLayers = () => {
    clearRouteLayers();
    if (startMarker) { map.removeLayer(startMarker); startMarker = null; }
    if (endMarker) { map.removeLayer(endMarker); endMarker = null; }
    intermediateMarkers.forEach(m => { try { map.removeLayer(m); } catch (_) {} });
    intermediateMarkers = [];
};

const resetFormState = () => {
    pathData.value = [];
    routeDistance.value = '';
    routeDuration.value = '';
    addingWaypoint.value = false;
    selectedAlternativeIndex.value = 0;
    osrmRoutesCache.value = null;
};

// --- API actions ---
const fetchRoutes = async () => {
    loadingRoutes.value = true;
    try {
        const response = await routesApi.getAllRoutes();
        routes.value = response.data;
    } catch (e) {
        console.error('Failed to fetch routes', e);
    } finally {
        loadingRoutes.value = false;
    }
};

const editRoute = async (id) => {
    currentView.value = 'form';
    loadingRouteDetails.value = true;
    try {
        const response = await routesApi.getRoute(id);
        const routeData = response.data;
        selectedRoute.value = routeData;
        form.value = { name: routeData.name, allowed_deviation_meters: routeData.allowed_deviation_meters };
        resetFormState();
        nextTick(() => {
            if (map) map.invalidateSize();
            loadPathOnMap(routeData.path);
        });
    } catch (e) {
        console.error('Failed to load route details', e);
        alertDialog.message = 'Failed to load route details.';
        alertDialog.show = true;
        closeForm();
    } finally {
        loadingRouteDetails.value = false;
    }
};

const createNewRoute = () => {
    currentView.value = 'form';
    selectedRoute.value = null;
    form.value = { name: '', allowed_deviation_meters: 500 };
    resetFormState();
    nextTick(() => {
        if (map) {
            map.invalidateSize();
            clearAllMapLayers();
            if (userMarker) map.setView(userMarker.getLatLng(), 15);
            else map.setView([-1.9441, 30.0619], 13);
        }
    });
};

const closeForm = () => {
    currentView.value = 'list';
    selectedRoute.value = null;
    resetFormState();
    if (map) {
        clearAllMapLayers();
        map.setView([-1.9441, 30.0619], 7);
        setTimeout(() => map.invalidateSize(), 300);
    }
};

const confirmDelete = (route) => {
    confirmDialog.route = route;
    confirmDialog.show = true;
};

const executeDelete = async () => {
    confirmDialog.deleting = true;
    try {
        await routesApi.deleteRoute(confirmDialog.route.id);
        routes.value = routes.value.filter(r => r.id !== confirmDialog.route.id);
        confirmDialog.show = false;
        confirmDialog.route = null;
    } catch (e) {
        console.error('Failed to delete route', e);
        confirmDialog.show = false;
        confirmDialog.route = null;
        alertDialog.message = 'Failed to delete route.';
        alertDialog.show = true;
    } finally {
        confirmDialog.deleting = false;
    }
};

const saveRoute = async () => {
    saving.value = true;
    try {
        const payload = { ...form.value, path: pathData.value };
        if (editingMode.value) {
            await routesApi.updateRoute(selectedRoute.value.id, payload);
        } else {
            await routesApi.createRoute(payload);
        }
        await fetchRoutes();
        closeForm();
    } catch (e) {
        console.error('Failed to save route', e);
        alertDialog.message = 'Failed to save route. Please check the inputs.';
        alertDialog.show = true;
    } finally {
        saving.value = false;
    }
};

// Global listener for waypoint remove buttons inside popups
if (typeof document !== 'undefined') {
    document.addEventListener('remove-wp', (e) => removeIntermediateMarker(e.detail));
}

onMounted(() => {
    fetchRoutes();
    setTimeout(initMap, 500);
});
</script>

<style>
.leaflet-container { font-family: inherit; z-index: 1 !important; border-radius: 3rem !important; }
.route-handle { cursor: grab; }
.route-handle:active { cursor: grabbing; }
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
</style>
