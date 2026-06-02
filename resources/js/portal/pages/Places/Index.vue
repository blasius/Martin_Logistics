<template>
    <div class="flex flex-col h-screen bg-slate-50 overflow-hidden font-sans">
        <header class="p-6 pb-2 shrink-0 flex justify-between items-end">
            <div>
                <h1 class="text-3xl font-black text-slate-800 tracking-tight uppercase italic leading-none">Places Directory</h1>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-1">Geofenced Points of Interest</p>
            </div>

            <div class="flex gap-3">
                <button v-if="currentView === 'list'" @click="createNewPlace"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-2xl text-xs font-black shadow-lg shadow-blue-200 active:scale-95 transition-all uppercase">
                    + Add New Place
                </button>
                <button v-else @click="closeForm"
                        class="bg-slate-200 hover:bg-slate-300 text-slate-700 px-8 py-3 rounded-2xl text-xs font-black transition-all active:scale-95 uppercase">
                    &larr; Back to List
                </button>
            </div>
        </header>

        <div class="flex flex-1 overflow-hidden p-6 pt-4 gap-6">
            <div class="flex-1 bg-white rounded-[3rem] shadow-sm border border-slate-200 overflow-hidden relative z-10">
                <div id="map" class="h-full w-full"></div>

                <div v-if="!mapReady" class="absolute inset-0 bg-slate-50/70 backdrop-blur-md z-[2000] flex items-center justify-center">
                    <div class="flex flex-col items-center gap-4 bg-white p-6 rounded-3xl shadow-2xl border border-slate-100">
                        <div class="w-10 h-10 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
                        <p class="text-[10px] font-black text-slate-600 uppercase tracking-widest">Initializing Map Engine...</p>
                    </div>
                </div>
            </div>

            <div class="w-[400px] flex flex-col z-20 shrink-0">
                <div v-if="currentView === 'list'" class="flex flex-col h-full bg-white rounded-[2.5rem] shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-6 border-b border-slate-100 bg-slate-50/50 shrink-0">
                        <h2 class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Place Directory</h2>
                    </div>

                    <div class="flex-1 overflow-y-auto custom-scrollbar p-5 space-y-3">
                        <div v-if="loadingPlaces" class="flex justify-center p-8">
                            <div class="w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
                        </div>

                        <div v-else-if="places.length === 0" class="text-center p-8 text-xs text-slate-400 font-bold uppercase">
                            No places recorded. Add one to begin.
                        </div>

                        <template v-else>
                            <div v-for="place in places" :key="place.id"
                                 class="group bg-white p-5 rounded-2xl border border-slate-200 hover:border-blue-400 hover:shadow-md cursor-pointer transition-all relative overflow-hidden"
                                 @click="editPlace(place.id)">

                                <div class="absolute left-0 top-0 bottom-0 w-1 bg-blue-500 opacity-0 group-hover:opacity-100 transition-opacity"></div>

                                <div class="flex justify-between items-start mb-2">
                                    <h3 class="text-sm font-black text-slate-800 truncate pr-2 group-hover:text-blue-600 transition-colors">{{ place.name }}</h3>
                                    <span class="text-[9px] font-black px-2.5 py-1 rounded-md bg-slate-100 text-slate-600 whitespace-nowrap">{{ place.place_key }}</span>
                                </div>

                                <div class="flex items-center gap-2 mb-2">
                                    <span class="text-[9px] font-black px-2 py-0.5 rounded-full uppercase"
                                          :class="typeBadgeClass(place.type)">{{ place.type }}</span>
                                    <span v-if="place.country" class="text-[9px] font-bold text-slate-400">{{ place.county ? place.county + ', ' : '' }}{{ place.country }}</span>
                                </div>

                                <p v-if="place.description" class="text-[10px] text-slate-500 font-medium truncate mb-2">{{ place.description }}</p>

                                <div class="flex justify-between items-center">
                                    <span class="text-[9px] font-bold text-slate-400">{{ place.latitude?.toFixed(4) }}, {{ place.longitude?.toFixed(4) }}</span>
                                    <span class="text-[9px] font-bold text-slate-400">{{ place.radius_meters }}m radius</span>
                                    <button @click.stop="deletePlace(place.id)"
                                            class="text-[10px] font-black text-red-400 hover:text-red-600 uppercase px-3 py-1.5 rounded-lg hover:bg-red-50 transition-colors active:scale-95">
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <div v-else class="flex flex-col h-full bg-white rounded-[2.5rem] shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-6 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center shrink-0">
                        <h2 class="text-[10px] font-black text-slate-500 uppercase tracking-widest">
                            {{ editingMode ? 'Edit Place' : 'New Place' }}
                        </h2>
                        <span v-if="editingMode" class="text-[9px] font-black px-2.5 py-1 rounded bg-amber-100 text-amber-700 uppercase">Editing Mode</span>
                    </div>

                    <div class="flex-1 overflow-y-auto custom-scrollbar p-6 space-y-5">
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1 block mb-2 tracking-wide">Place Name</label>
                            <input v-model="form.name" type="text" placeholder="e.g. Warehouse B"
                                   class="border-slate-200 px-4 py-3.5 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-500 outline-none bg-slate-50 w-full transition-all" />
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1 block mb-2 tracking-wide">Type</label>
                            <select v-model="form.type"
                                    class="border-slate-200 px-4 py-3.5 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-500 outline-none bg-slate-50 w-full transition-all">
                                <option value="warehouse">Warehouse</option>
                                <option value="factory">Factory</option>
                                <option value="depot">Depot</option>
                                <option value="yard">Yard</option>
                                <option value="checkpoint">Checkpoint</option>
                                <option value="terminal">Terminal</option>
                                <option value="office">Office</option>
                                <option value="fuel_station">Fuel Station</option>
                                <option value="rest_stop">Rest Stop</option>
                                <option value="border">Border Crossing</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1 block mb-2 tracking-wide">Description</label>
                            <textarea v-model="form.description" rows="2" placeholder="e.g. Factory yard in Miwani County, Kenya"
                                      class="border-slate-200 px-4 py-3.5 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-500 outline-none bg-slate-50 w-full transition-all resize-none"></textarea>
                        </div>

                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase ml-1 block mb-2 tracking-wide">Country</label>
                                <input v-model="form.country" type="text" placeholder="Kenya"
                                       class="border-slate-200 px-4 py-3.5 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-500 outline-none bg-slate-50 w-full transition-all" />
                            </div>
                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase ml-1 block mb-2 tracking-wide">County</label>
                                <input v-model="form.county" type="text" placeholder="Miwani"
                                       class="border-slate-200 px-4 py-3.5 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-500 outline-none bg-slate-50 w-full transition-all" />
                            </div>
                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase ml-1 block mb-2 tracking-wide">City</label>
                                <input v-model="form.city" type="text" placeholder="Kisumu"
                                       class="border-slate-200 px-4 py-3.5 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-500 outline-none bg-slate-50 w-full transition-all" />
                            </div>
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1 block mb-2 tracking-wide">Address</label>
                            <input v-model="form.address" type="text" placeholder="Mombasa Road, Industrial Area"
                                   class="border-slate-200 px-4 py-3.5 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-500 outline-none bg-slate-50 w-full transition-all" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase ml-1 block mb-2 tracking-wide">Latitude</label>
                                <input :value="form.latitude" readonly
                                       class="border-slate-200 px-4 py-3.5 rounded-xl text-sm font-bold bg-slate-100 text-slate-500 cursor-not-allowed w-full border" />
                            </div>
                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase ml-1 block mb-2 tracking-wide">Longitude</label>
                                <input :value="form.longitude" readonly
                                       class="border-slate-200 px-4 py-3.5 rounded-xl text-sm font-bold bg-slate-100 text-slate-500 cursor-not-allowed w-full border" />
                            </div>
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1 block mb-2 tracking-wide">Geofence Radius (meters)</label>
                            <input v-model="form.radius_meters" type="number" min="0"
                                   class="border-slate-200 px-4 py-3.5 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-500 outline-none bg-slate-50 w-full transition-all" />
                        </div>

                        <div class="bg-slate-900 p-5 rounded-[2rem] shadow-xl border border-slate-800 text-white relative overflow-hidden">
                            <div class="absolute -right-4 -top-4 w-24 h-24 bg-blue-500/10 rounded-full blur-2xl"></div>
                            <p class="text-[10px] font-black text-blue-400 uppercase tracking-widest mb-3 relative z-10">Place Info</p>
                            <div class="relative z-10">
                                <p v-if="form.name" class="text-lg font-black italic">{{ form.name }}</p>
                                <p class="text-[10px] font-bold text-slate-400 mt-1">
                                    Click on the map to set the location. Coordinates auto-fill.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 border-t border-slate-100 bg-slate-50 shrink-0">
                        <button @click="savePlace"
                                :disabled="saving || !form.latitude || !form.longitude"
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white py-4 rounded-2xl text-xs font-black transition-all shadow-lg shadow-blue-200 active:scale-95 disabled:opacity-40 disabled:shadow-none uppercase tracking-widest">
                            {{ saving ? 'Processing...' : (editingMode ? 'Update Place' : 'Save Place') }}
                        </button>

                        <p v-if="!form.latitude || !form.longitude"
                           class="text-[9px] text-center font-bold text-amber-500 uppercase mt-4">
                            Click on the map to select a location.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed, nextTick } from 'vue';
import { placesApi } from "../../api/places";
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const places = ref([]);
const selectedPlace = ref(null);
const loadingPlaces = ref(true);
const saving = ref(false);
const mapReady = ref(false);

const currentView = ref('list');

const form = ref({
    name: '',
    type: 'warehouse',
    description: '',
    country: '',
    county: '',
    city: '',
    address: '',
    latitude: null,
    longitude: null,
    radius_meters: 50,
});

const editingMode = computed(() => !!selectedPlace.value);

let map = null;
let currentMarker = null;
let currentCircle = null;

const typeBadgeClass = (type) => {
    const classes = {
        warehouse: 'bg-purple-100 text-purple-700',
        factory: 'bg-blue-100 text-blue-700',
        depot: 'bg-amber-100 text-amber-700',
        yard: 'bg-green-100 text-green-700',
        checkpoint: 'bg-red-100 text-red-700',
        terminal: 'bg-indigo-100 text-indigo-700',
        office: 'bg-slate-100 text-slate-700',
        fuel_station: 'bg-orange-100 text-orange-700',
        rest_stop: 'bg-teal-100 text-teal-700',
        border: 'bg-rose-100 text-rose-700',
    };
    return classes[type] || 'bg-slate-100 text-slate-700';
};

const initMap = () => {
    map = L.map('map', { zoomControl: false }).setView([-1.9441, 30.0619], 6);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap'
    }).addTo(map);

    L.control.zoom({ position: 'bottomright' }).addTo(map);

    map.on('click', (e) => {
        if (currentView.value === 'form') {
            placeMarker(e.latlng);
        }
    });

    setTimeout(() => {
        map.invalidateSize();
        mapReady.value = true;
    }, 400);
};

const placeMarker = (latlng) => {
    form.value.latitude = parseFloat(latlng.lat.toFixed(7));
    form.value.longitude = parseFloat(latlng.lng.toFixed(7));

    if (currentMarker) {
        currentMarker.setLatLng(latlng);
    } else {
        currentMarker = L.marker(latlng, { draggable: true }).addTo(map);
        currentMarker.on('dragend', () => {
            const pos = currentMarker.getLatLng();
            placeMarker(pos);
        });
    }

    if (currentCircle) {
        currentCircle.setLatLng(latlng);
    } else {
        currentCircle = L.circle(latlng, {
            radius: form.value.radius_meters,
            color: '#2563eb',
            fillColor: '#3b82f6',
            fillOpacity: 0.1,
            weight: 2,
        }).addTo(map);
    }

    map.setView(latlng, map.getZoom() < 13 ? 13 : map.getZoom());
};

const updateCircleRadius = () => {
    if (currentCircle && form.value.latitude && form.value.longitude) {
        currentCircle.setRadius(form.value.radius_meters);
    }
};

const loadPlaceOnMap = (place) => {
    if (!map) return;

    clearMap();

    if (!place.latitude || !place.longitude) return;

    const latlng = L.latLng(place.latitude, place.longitude);
    placeMarker(latlng);

    try {
        map.fitBounds(currentCircle.getBounds().pad(0.5), { padding: [50, 50] });
    } catch (e) {
        map.setView(latlng, 13);
    }
};

const clearMap = () => {
    if (currentMarker) {
        map.removeLayer(currentMarker);
        currentMarker = null;
    }
    if (currentCircle) {
        map.removeLayer(currentCircle);
        currentCircle = null;
    }
};

const fetchPlaces = async () => {
    loadingPlaces.value = true;
    try {
        const response = await placesApi.getAllPlaces();
        places.value = response.data;
    } catch (e) {
        console.error('Failed to fetch places', e);
    } finally {
        loadingPlaces.value = false;
    }
};

const editPlace = async (id) => {
    currentView.value = 'form';

    try {
        const response = await placesApi.getPlace(id);
        const placeData = response.data;

        selectedPlace.value = placeData;
        form.value = {
            name: placeData.name,
            type: placeData.type,
            description: placeData.description || '',
            country: placeData.country || '',
            county: placeData.county || '',
            city: placeData.city || '',
            address: placeData.address || '',
            latitude: placeData.latitude,
            longitude: placeData.longitude,
            radius_meters: placeData.radius_meters || 50,
        };

        nextTick(() => {
            if (map) map.invalidateSize();
            loadPlaceOnMap(placeData);
        });

    } catch (e) {
        console.error('Failed to load place details', e);
        alert('Failed to load place details.');
        closeForm();
    }
};

const createNewPlace = () => {
    currentView.value = 'form';
    selectedPlace.value = null;
    form.value = {
        name: '',
        type: 'warehouse',
        description: '',
        country: '',
        county: '',
        city: '',
        address: '',
        latitude: null,
        longitude: null,
        radius_meters: 50,
    };

    nextTick(() => {
        if (map) {
            map.invalidateSize();
            clearMap();
            map.setView([-1.9441, 30.0619], 6);
        }
    });
};

const closeForm = () => {
    currentView.value = 'list';
    selectedPlace.value = null;

    if (map) {
        clearMap();
        map.setView([-1.9441, 30.0619], 6);
        setTimeout(() => map.invalidateSize(), 300);
    }
};

const deletePlace = async (id) => {
    if (!confirm('Are you sure you want to delete this place? This cannot be undone.')) return;

    try {
        await placesApi.deletePlace(id);
        places.value = places.value.filter(p => p.id !== id);
    } catch (e) {
        console.error('Failed to delete place', e);
        alert('Failed to delete place.');
    }
};

const savePlace = async () => {
    saving.value = true;
    try {
        const payload = {
            ...form.value,
            radius_meters: form.value.radius_meters || 50,
        };

        if (editingMode.value) {
            await placesApi.updatePlace(selectedPlace.value.id, payload);
        } else {
            await placesApi.createPlace(payload);
        }

        await fetchPlaces();
        closeForm();

    } catch (e) {
        console.error('Failed to save place', e);
        alert('Failed to save place. Please check the inputs.');
    } finally {
        saving.value = false;
    }
};

onMounted(() => {
    fetchPlaces();
    setTimeout(initMap, 500);
});
</script>

<style>
.leaflet-container { font-family: inherit; z-index: 1 !important; border-radius: 3rem !important; }
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
</style>
