<template>
    <div class="flex flex-col h-screen bg-slate-50">
        <header class="p-6 pb-0 flex justify-between items-end">
            <div>
                <h1 class="text-3xl font-black text-slate-800 tracking-tight uppercase">Route Architect</h1>
                <p class="text-sm font-bold text-slate-400 uppercase">Geofencing & Path Design</p>
            </div>
            <button @click="createNewRoute" v-if="editingMode"
                    class="bg-slate-800 hover:bg-slate-900 text-white px-6 py-2 rounded-xl text-xs font-black transition-all shadow-md active:scale-95 uppercase">
                + Create New Route
            </button>
        </header>

        <div class="flex flex-1 overflow-hidden p-6 gap-6">
            <!-- Left Sidebar: Route List -->
            <div class="w-80 flex flex-col gap-4 overflow-y-auto custom-scrollbar border-r border-slate-200 pr-4">
                <h2 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Existing Routes</h2>

                <div v-if="loadingRoutes" class="flex justify-center p-4">
                    <div class="w-6 h-6 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
                </div>

                <div v-else-if="routes.length === 0" class="text-center p-4 text-sm text-slate-500 font-medium">
                    No routes found.
                </div>

                <div v-else v-for="route in routes" :key="route.id"
                     class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200 cursor-pointer hover:border-blue-300 transition-colors"
                     :class="{'border-blue-500 ring-1 ring-blue-500': selectedRoute && selectedRoute.id === route.id}"
                     @click="editRoute(route.id)">
                    <div class="flex justify-between items-start mb-2">
                        <h3 class="text-sm font-black text-slate-800 truncate pr-2">{{ route.name }}</h3>
                        <span class="text-[9px] font-black px-2 py-0.5 rounded bg-slate-100 text-slate-500">{{ route.fleet_key }}</span>
                    </div>
                    <div class="flex justify-between items-center text-[10px] font-bold text-slate-400 uppercase">
                        <span>{{ route.estimated_distance_km }} KM</span>
                        <button @click.stop="deleteRoute(route.id)" class="text-red-500 hover:text-red-700">Delete</button>
                    </div>
                </div>
            </div>

            <!-- Middle/Right: Map & Form -->
            <div class="flex-1 flex gap-6">
                <!-- Form Area -->
                <div class="w-80 flex flex-col gap-4 overflow-y-auto custom-scrollbar shrink-0">
                    <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
                        <div class="flex justify-between items-center mb-4">
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Route Details</label>
                            <span v-if="editingMode" class="text-[9px] font-black px-2 py-0.5 rounded bg-amber-100 text-amber-700 uppercase">Editing Mode</span>
                        </div>

                        <input v-model="form.name" type="text" placeholder="e.g. Kigali - Mombasa Main"
                               class="mb-4 border-slate-200 px-4 py-2 rounded-xl text-sm font-medium focus:ring-2 focus:ring-blue-500 outline-none bg-slate-50 w-full" />

                        <div class="grid grid-cols-2 gap-3">
                            <div class="flex flex-col gap-1" v-if="editingMode">
                                <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Fleet Key</label>
                                <div class="border-slate-200 px-3 py-2 rounded-xl text-sm font-bold bg-slate-100 text-slate-500 truncate cursor-not-allowed">
                                    {{ selectedRoute.fleet_key }}
                                </div>
                            </div>
                            <div class="flex flex-col gap-1" :class="{'col-span-2': !editingMode}">
                                <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Deviation (m)</label>
                                <input v-model="form.allowed_deviation_meters" type="number"
                                       class="border-slate-200 px-3 py-2 rounded-xl text-sm font-bold bg-slate-50 outline-none" />
                            </div>
                        </div>
                    </div>

                    <div v-if="pathData.length > 0" class="bg-slate-800 p-5 rounded-2xl shadow-lg border border-slate-700 text-white">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Path Statistics</p>
                        <div class="flex justify-between items-end">
                            <div>
                                <span class="text-3xl font-black italic tracking-tighter">{{ totalDistance }}</span>
                                <span class="text-xs font-bold text-slate-400 ml-1">KM</span>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] font-black text-blue-400 uppercase">Waypoints</p>
                                <p class="text-lg font-black">{{ pathData.length }}</p>
                            </div>
                        </div>
                    </div>

                    <button @click="saveRoute"
                            :disabled="saving || pathData.length < 2"
                            class="bg-blue-600 hover:bg-blue-700 text-white p-4 rounded-2xl text-xs font-black transition-all shadow-md active:scale-95 disabled:opacity-50 disabled:grayscale uppercase">
                        {{ saving ? 'PROCESSING...' : (editingMode ? 'UPDATE ROUTE' : 'SAVE & PUBLISH ROUTE') }}
                    </button>

                    <p class="text-[10px] text-center font-bold text-slate-400 uppercase px-4 leading-relaxed">
                        Use the line tool on the map to draw the path. Double-click to finish.
                    </p>
                </div>

                <!-- Map Area -->
                <div class="flex-1 bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden relative">
                    <div id="map" class="h-full w-full z-10"></div>

                    <div v-if="!mapReady || loadingRouteDetails" class="absolute inset-0 bg-slate-50/80 backdrop-blur-sm z-20 flex items-center justify-center">
                        <div class="flex flex-col items-center gap-2">
                            <div class="w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                {{ loadingRouteDetails ? 'Loading Route Data...' : 'Initializing Engine' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
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

const form = ref({
    name: '',
    allowed_deviation_meters: 500
});

const editingMode = computed(() => !!selectedRoute.value);

let map = null;
let currentLayer = null;

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

    mapReady.value = true;
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

    currentLayer = L.polyline(latlngs, { color: '#3388ff', weight: 4 }).addTo(map);

    // Attach edit listener
    currentLayer.on('pm:edit', () => updatePath(currentLayer));

    // Fit bounds
    map.fitBounds(currentLayer.getBounds(), { padding: [50, 50] });

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
    loadingRouteDetails.value = true;
    try {
        const response = await routesApi.getRoute(id);
        const routeData = response.data;

        selectedRoute.value = routeData;
        form.value = {
            name: routeData.name,
            allowed_deviation_meters: routeData.allowed_deviation_meters
        };

        loadPathOnMap(routeData.path);

    } catch (e) {
        console.error('Failed to load route details', e);
        alert('Failed to load route details.');
    } finally {
        loadingRouteDetails.value = false;
    }
};

const createNewRoute = () => {
    selectedRoute.value = null;
    form.value = { name: '', allowed_deviation_meters: 500 };
    pathData.value = [];

    if (map) {
        if (currentLayer) map.removeLayer(currentLayer);
        map.pm.getGeomanDrawLayers().forEach(layer => map.removeLayer(layer));
        map.setView([-1.9441, 30.0619], 13); // Reset to default view
    }
};

const deleteRoute = async (id) => {
    if (!confirm('Are you sure you want to delete this route? This cannot be undone.')) return;

    try {
        await routesApi.deleteRoute(id);

        // Remove from list
        routes.value = routes.value.filter(r => r.id !== id);

        // Reset form if the deleted route was currently selected
        if (selectedRoute.value && selectedRoute.value.id === id) {
            createNewRoute();
        }

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
            alert('Route updated successfully');
        } else {
            await routesApi.createRoute(payload);
            alert('Route created successfully');
            createNewRoute(); // Reset form for the next one
        }

        await fetchRoutes(); // Refresh the list

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
.leaflet-container { font-family: inherit; }
/* Style Geoman buttons to match your UI */
.leaflet-pm-toolbar .leaflet-buttons-control-button {
    border-radius: 8px !important;
    border: 1px solid #e2e8f0 !important;
}
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
</style>
