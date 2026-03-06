<template>
    <div class="flex flex-col h-screen bg-slate-50">
        <header class="p-6 pb-0">
            <h1 class="text-3xl font-black text-slate-800 tracking-tight uppercase">Route Architect</h1>
            <p class="text-sm font-bold text-slate-400 uppercase">Geofencing & Path Design</p>
        </header>

        <div class="flex flex-1 overflow-hidden p-6 gap-6">
            <div class="w-96 flex flex-col gap-4 overflow-y-auto custom-scrollbar">
                <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
                    <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Route Name</label>
                    <input v-model="form.name" type="text" placeholder="e.g. Kigali - Mombasa Main"
                           class="mb-4 border-slate-200 px-4 py-2 rounded-xl text-sm font-medium focus:ring-2 focus:ring-blue-500 outline-none bg-slate-50 w-full" />

                    <div class="grid grid-cols-2 gap-3">
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Fleet Key</label>
                            <input v-model="form.fleet_key" type="text" placeholder="FR-01"
                                   class="border-slate-200 px-3 py-2 rounded-xl text-sm font-bold bg-slate-50 outline-none" />
                        </div>
                        <div class="flex flex-col gap-1">
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
                        :disabled="loading || pathData.length < 2"
                        class="bg-blue-600 hover:bg-blue-700 text-white p-4 rounded-2xl text-xs font-black transition-all shadow-md active:scale-95 disabled:opacity-50 disabled:grayscale">
                    {{ loading ? 'PROCESSING...' : 'SAVE & PUBLISH ROUTE' }}
                </button>

                <p class="text-[10px] text-center font-bold text-slate-400 uppercase px-4 leading-relaxed">
                    Use the line tool on the map to draw the path. Double-click to finish.
                </p>
            </div>

            <div class="flex-1 bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden relative">
                <div id="map" class="h-full w-full z-10"></div>

                <div v-if="!mapReady" class="absolute inset-0 bg-slate-50 z-20 flex items-center justify-center">
                    <div class="flex flex-col items-center gap-2">
                        <div class="w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Initializing Engine</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { api } from "../../../plugins/axios";
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import '@geoman-io/leaflet-geoman-free';
import '@geoman-io/leaflet-geoman-free/dist/leaflet-geoman.css';

const loading = ref(false);
const mapReady = ref(false);
const pathData = ref([]);
const form = ref({
    name: '',
    fleet_key: '',
    allowed_deviation_meters: 500
});

let map = null;

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
        const layer = e.layer;
        updatePath(layer);

        layer.on('pm:edit', () => updatePath(layer));
    });

    map.on('pm:remove', () => pathData.ref = []);

    mapReady.value = true;
};

const updatePath = (layer) => {
    const latlngs = layer.getLatLngs();
    pathData.value = latlngs.map(ll => ({ lat: ll.lat, lng: ll.lng }));
};

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

const saveRoute = async () => {
    loading.value = true;
    try {
        await api.post('/portal/routes/store', {
            ...form.value,
            path: pathData.value
        });
        alert('Route saved successfully');
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
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
</style>
