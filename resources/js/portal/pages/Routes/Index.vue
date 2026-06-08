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
                                <button @click.stop="deleteRoute(route.id)"
                                        class="text-[10px] font-black text-red-400 hover:text-red-600 uppercase px-3 py-1.5 rounded-lg hover:bg-red-50 transition-colors active:scale-95">
                                    Delete
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
                                    <span class="text-5xl font-black italic tracking-tighter">{{ totalDistance }}</span>
                                    <span class="text-xs font-bold text-slate-400 ml-1 uppercase">KM</span>
                                </div>
                                <div class="text-right bg-white/10 px-3 py-2 rounded-xl backdrop-blur-sm border border-white/5">
                                    <p class="text-[8px] font-black text-slate-300 uppercase">Waypoints</p>
                                    <p class="text-xl font-black leading-none mt-0.5">{{ pathData.length }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Action Area -->
                    <div class="p-6 border-t border-slate-100 bg-slate-50 shrink-0">
                        <button @click="saveRoute"
                                :disabled="saving || pathData.length < 2"
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white py-4 rounded-2xl text-xs font-black transition-all shadow-lg shadow-blue-200 active:scale-95 disabled:opacity-40 disabled:shadow-none uppercase tracking-widest">
                            {{ saving ? 'Processing...' : (editingMode ? 'Update Route' : 'Save & Publish Route') }}
                        </button>

                        <p class="text-[9px] text-center font-bold text-slate-400 uppercase mt-4 px-2 leading-relaxed">
                            Use the map tools to trace the path. Double-click to finish drawing.
                            <br/>
                            <span v-if="pathData.length < 2" class="text-amber-500 block mt-1">Requires at least 2 waypoints.</span>
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed, nextTick } from 'vue';
import { routesApi } from "../../api/routes";
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import '@geoman-io/leaflet-geoman-free';
import '@geoman-io/leaflet-geoman-free/dist/leaflet-geoman.css';

// State
const routes = ref([]);
const selectedRoute = ref(null);
const loadingRoutes = ref(true);
const loadingRouteDetails = ref(false);
const saving = ref(false);
const mapReady = ref(false);
const pathData = ref([]);

// View Toggle: 'list' or 'form'
const currentView = ref('list');

const form = ref({
    name: '',
    allowed_deviation_meters: 500
});

const editingMode = computed(() => !!selectedRoute.value);

let map = null;
let currentLayer = null;
let userMarker = null;

// Map Initialization
const initMap = () => {
    map = L.map('map', { zoomControl: false }).setView([-1.9441, 30.0619], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap'
    }).addTo(map);

    L.control.zoom({ position: 'bottomright' }).addTo(map);

    // Setup Geoman
    map.pm.addControls({
        position: 'topleft',
        drawMarker: false,
        drawCircle: false,
        drawPolyline: true,
        drawRectangle: false,
        drawPolygon: false,
        editMode: true,
        removalMode: true,
    });

    // Capture path data
    map.on('pm:create', (e) => {
        if (currentLayer) {
            map.removeLayer(currentLayer); // Keep only one line at a time
        }
        currentLayer = e.layer;
        updatePath(currentLayer);

        currentLayer.on('pm:edit', () => updatePath(currentLayer));
    });

    map.on('pm:remove', () => {
        pathData.value = [];
        currentLayer = null;
    });

    // Let the DOM settle
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
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
                shadowSize: [41, 41],
            });

            if (userMarker) map.removeLayer(userMarker);
            userMarker = L.marker([latitude, longitude], { icon })
                .addTo(map)
                .bindPopup('Your Location');
        },
        () => {},
        { enableHighAccuracy: true, timeout: 10000 }
    );
};

const updatePath = (layer) => {
    const latlngs = layer.getLatLngs();
    // Geoman sometimes returns nested arrays depending on shape type
    const points = Array.isArray(latlngs[0]) ? latlngs[0] : latlngs;
    pathData.value = points.map(ll => ({ lat: ll.lat, lng: ll.lng }));
};

const loadPathOnMap = (pathArray) => {
    if (!map) return;

    // Clear existing
    if (currentLayer) {
        map.removeLayer(currentLayer);
        currentLayer = null;
    }

    // Clear any remaining Geoman layers
    map.pm.getGeomanDrawLayers().forEach(layer => map.removeLayer(layer));

    if (!pathArray || pathArray.length < 2) return;

    const latlngs = pathArray.map(p => [p.lat, p.lng]);

    currentLayer = L.polyline(latlngs, { color: '#2563eb', weight: 5 }).addTo(map);

    // Attach edit listener
    currentLayer.on('pm:edit', () => updatePath(currentLayer));

    // Fit bounds safely
    try {
        map.fitBounds(currentLayer.getBounds(), { padding: [50, 50] });
    } catch (e) {
        console.warn("Could not fit bounds", e);
    }

    // Update local state
    pathData.value = [...pathArray];
};


// Computed
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

// API Actions
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
    currentView.value = 'form'; // Switch view immediately
    loadingRouteDetails.value = true;

    try {
        const response = await routesApi.getRoute(id);
        const routeData = response.data;

        selectedRoute.value = routeData;
        form.value = {
            name: routeData.name,
            allowed_deviation_meters: routeData.allowed_deviation_meters
        };

        // Ensure map is ready before drawing
        nextTick(() => {
            if (map) map.invalidateSize();
            loadPathOnMap(routeData.path);
        });

    } catch (e) {
        console.error('Failed to load route details', e);
        alert('Failed to load route details.');
        closeForm(); // Go back if it fails
    } finally {
        loadingRouteDetails.value = false;
    }
};

const createNewRoute = () => {
    currentView.value = 'form';
    selectedRoute.value = null;
    form.value = { name: '', allowed_deviation_meters: 500 };
    pathData.value = [];

    nextTick(() => {
        if (map) {
            map.invalidateSize();
            if (currentLayer) map.removeLayer(currentLayer);
            map.pm.getGeomanDrawLayers().forEach(layer => map.removeLayer(layer));
            if (userMarker) map.setView(userMarker.getLatLng(), 15);
            else map.setView([-1.9441, 30.0619], 13);
        }
    });
};

const closeForm = () => {
    currentView.value = 'list';
    selectedRoute.value = null;

    // Clean up map
    if (map) {
        if (currentLayer) map.removeLayer(currentLayer);
        map.pm.getGeomanDrawLayers().forEach(layer => map.removeLayer(layer));
        map.setView([-1.9441, 30.0619], 7);
        setTimeout(() => map.invalidateSize(), 300);
    }
};

const deleteRoute = async (id) => {
    if (!confirm('Are you sure you want to delete this route? This cannot be undone.')) return;

    try {
        await routesApi.deleteRoute(id);

        // Remove from list
        routes.value = routes.value.filter(r => r.id !== id);

    } catch (e) {
        console.error('Failed to delete route', e);
        alert('Failed to delete route.');
    }
};

const saveRoute = async () => {
    saving.value = true;
    try {
        const payload = {
            ...form.value,
            path: pathData.value
        };

        if (editingMode.value) {
            await routesApi.updateRoute(selectedRoute.value.id, payload);
        } else {
            await routesApi.createRoute(payload);
        }

        await fetchRoutes(); // Refresh the list
        closeForm(); // Return to list view

    } catch (e) {
        console.error('Failed to save route', e);
        alert('Failed to save route. Please check the inputs.');
    } finally {
        saving.value = false;
    }
};

onMounted(() => {
    fetchRoutes();
    setTimeout(initMap, 500); // Allow DOM to stabilize
});
</script>

<style>
.leaflet-container { font-family: inherit; z-index: 1 !important; border-radius: 3rem !important; }
/* Style Geoman buttons to match your UI */
.leaflet-pm-toolbar .leaflet-buttons-control-button {
    border-radius: 8px !important;
    border: 1px solid #e2e8f0 !important;
    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1) !important;
}
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
</style>
