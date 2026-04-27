<template>
    <div class="flex flex-col h-screen bg-slate-50 overflow-hidden font-sans">
        <header class="p-6 pb-2 shrink-0 flex justify-between items-end">
            <div>
                <h1 class="text-3xl font-black text-slate-800 tracking-tight uppercase italic leading-none">Dispatch Command</h1>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-1">Resource Allocation & Multi-Vehicle Sync</p>
            </div>
            <div class="flex gap-3">
                <button @click="confirmTrip" :disabled="!isFormValid"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-2xl text-xs font-black shadow-lg shadow-blue-200 active:scale-95 disabled:opacity-20 transition-all uppercase">
                    Authorize Dispatch
                </button>
            </div>
        </header>

        <div class="flex flex-1 overflow-hidden p-6 pt-2 gap-6">

            <div class="w-[400px] flex flex-col gap-4 overflow-y-auto custom-scrollbar shrink-0 pr-1">

                <div class="bg-white p-5 rounded-[2rem] shadow-sm border border-slate-200">
                    <label class="text-[10px] font-black text-blue-600 uppercase tracking-widest mb-3 block">1. Client Context</label>
                    <div class="relative mb-4">
                        <input type="text" v-model="searchQueries.client" @input="searchClients" placeholder="Search Client Name..."
                               class="w-full bg-slate-50 border-0 p-4 rounded-2xl text-sm font-bold focus:ring-2 focus:ring-blue-500 outline-none" />

                        <div v-if="searchResults.clients.length" class="absolute left-0 right-0 z-[2000] mt-2 bg-white rounded-2xl shadow-2xl border border-slate-100 overflow-hidden">
                            <div v-for="c in searchResults.clients" :key="c.id" @click="selectClient(c)" class="p-4 hover:bg-blue-50 cursor-pointer text-xs font-black border-b border-slate-50 uppercase">
                                {{ c.name }}
                            </div>
                        </div>
                    </div>

                    <transition name="slide">
                        <div v-if="selectedOrder" class="p-5 bg-slate-900 rounded-[1.5rem] text-white space-y-3">
                            <div class="flex justify-between items-start">
                                <span class="text-[10px] font-black text-slate-500 uppercase">{{ selectedOrder.reference }}</span>
                                <span class="px-2 py-0.5 bg-blue-600 rounded text-[9px] font-black uppercase">Active Order</span>
                            </div>
                            <h3 class="text-lg font-black italic leading-tight">{{ selectedOrder.goods_type }}</h3>
                            <div class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-800">
                                <div>
                                    <p class="text-[8px] font-bold text-slate-500 uppercase">Total Payload</p>
                                    <p class="text-xs font-black">{{ selectedOrder.tonnage }} T</p>
                                </div>
                                <div>
                                    <p class="text-[8px] font-bold text-slate-500 uppercase text-emerald-400">Remaining</p>
                                    <p class="text-xs font-black text-emerald-400">{{ selectedOrder.remaining_tonnage }} T</p>
                                </div>
                            </div>
                        </div>
                    </transition>
                </div>

                <div class="bg-white p-5 rounded-[2rem] shadow-sm border border-slate-200 space-y-4">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block">2. Execution Resource</label>

                    <div class="relative">
                        <input type="text" v-model="searchQueries.assignment" @input="searchAssignments" placeholder="Search Vehicle Plate..."
                               class="w-full bg-slate-50 border-0 p-4 rounded-2xl text-sm font-bold focus:ring-2 focus:ring-blue-500 outline-none" />

                        <div v-if="searchResults.assignments.length" class="absolute left-0 right-0 z-[2000] mt-2 bg-white rounded-2xl shadow-2xl border border-slate-100 overflow-hidden">
                            <div v-for="res in searchResults.assignments" :key="res.id" @click="selectAssignment(res)" class="p-4 hover:bg-slate-50 cursor-pointer flex justify-between items-center border-b border-slate-50">
                                <span class="text-xs font-black text-slate-800 uppercase">{{ res.label }}</span>
                                <span class="text-[8px] font-black px-2 py-0.5 rounded bg-blue-50 text-blue-600 uppercase">{{ res.type }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="flex flex-col gap-1">
                            <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Allocated Tonnage</label>
                            <input type="number" v-model="form.allocated_weight" placeholder="0.00"
                                   class="bg-slate-50 border-0 p-3 rounded-xl text-sm font-black focus:ring-2 focus:ring-blue-500 outline-none" />
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Route Corridor</label>
                            <select v-model="form.route_id" @change="onRouteChange"
                                    class="bg-slate-50 border-0 p-3 rounded-xl text-sm font-black outline-none appearance-none">
                                <option value="">Select...</option>
                                <option v-for="r in routes" :key="r.id" :value="r.id">{{ r.name }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="bg-slate-900 p-6 rounded-[2.5rem] shadow-xl border border-slate-800 text-white mt-auto">
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <p class="text-[10px] font-black text-blue-400 uppercase tracking-widest mb-1">Fuel Authorization</p>
                            <div class="flex items-baseline">
                                <span class="text-5xl font-black italic tracking-tighter">{{ calculatedFuel }}</span>
                                <span class="text-xs font-bold text-slate-500 ml-2 uppercase">Liters</span>
                            </div>
                        </div>
                        <div class="bg-blue-600/10 p-3 rounded-2xl border border-blue-500/20">
                            <p class="text-[8px] font-black text-blue-400 uppercase text-center">Distance</p>
                            <p class="text-lg font-black text-center italic leading-none">{{ selectedRouteDistance }}<span class="text-[8px] ml-0.5 uppercase">km</span></p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <div class="flex justify-between text-[10px] font-bold py-2 border-b border-slate-800">
                            <span class="text-slate-500 uppercase">Consumption Ratio</span>
                            <span>{{ selectedVehicle.ratio || 0 }} L / 100km</span>
                        </div>
                        <div class="flex justify-between text-[10px] font-bold py-2">
                            <span class="text-slate-500 uppercase">Weight Adjustment</span>
                            <span class="text-blue-400">+ {{ weightImpact }}%</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex-1 bg-white rounded-[3rem] shadow-sm border border-slate-200 overflow-hidden relative">
                <div id="map" class="h-full w-full z-10"></div>
                <div v-if="!mapReady" class="absolute inset-0 bg-slate-50 z-[2000] flex items-center justify-center">
                    <div class="w-10 h-10 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
                </div>
            </div>

        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed, nextTick } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { api } from "../../../plugins/axios";

// State
const mapReady = ref(false);
const searchQueries = ref({ client: '', assignment: '' });
const searchResults = ref({ clients: [], assignments: [] });
const selectedOrder = ref(null);
const routes = ref([]);
const selectedRoute = ref(null);
const selectedVehicle = ref({ ratio: 0, age: 0 });

const form = ref({
    order_id: '',
    assignment: '',
    route_id: '',
    allocated_weight: 0,
    status: 'pending'
});

// Logic & Calculations
const selectedRouteDistance = computed(() => selectedRoute.value?.total_distance_km || 0);

// Logic: Fuel consumption increases by 0.5% for every ton of weight allocated
const weightImpact = computed(() => {
    return (form.value.allocated_weight * 0.5).toFixed(1);
});

const calculatedFuel = computed(() => {
    if (!selectedRouteDistance.value || !selectedVehicle.value.ratio) return 0;

    let baseFuel = (selectedRouteDistance.value / 100) * selectedVehicle.value.ratio;

    // Apply weight impact
    const multiplier = 1 + (parseFloat(weightImpact.value) / 100);
    return Math.round(baseFuel * multiplier);
});

const isFormValid = computed(() => {
    return form.value.order_id && form.value.assignment && form.value.route_id && form.value.allocated_weight > 0;
});

let map = null;
let routeLayer = null;

const initMap = () => {
    map = L.map('map', { zoomControl: false }).setView([-1.9441, 30.0619], 7);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
    setTimeout(() => { map.invalidateSize(); mapReady.value = true; }, 600);
};

// Search Functions
const searchClients = async () => {
    if (searchQueries.value.client.length < 2) return;
    const res = await api.get('/portal/mock/clients', { params: { q: searchQueries.value.client } });
    searchResults.value.clients = res.data;
};

const selectClient = async (client) => {
    searchQueries.value.client = client.name;
    searchResults.value.clients = [];
    const res = await api.get(`/portal/mock/clients/${client.id}/order`);
    selectedOrder.value = res.data;
    form.value.order_id = res.data.id;
    // Auto-fill tonnage with remaining amount as a starting point
    form.value.allocated_weight = res.data.remaining_tonnage;
};

const searchAssignments = async () => {
    if (searchQueries.value.assignment.length < 2) return;
    const res = await api.get('/portal/mock/assignments', { params: { q: searchQueries.value.assignment } });
    searchResults.value.assignments = res.data;
};

const selectAssignment = (res) => {
    form.value.assignment = res.id;
    searchQueries.value.assignment = res.label;
    searchResults.value.assignments = [];
    if (res.type === 'vehicle') {
        selectedVehicle.value = { ratio: res.ratio, age: res.age };
    }
};

const onRouteChange = () => {
    selectedRoute.value = routes.value.find(r => r.id === form.value.route_id);
    if (!selectedRoute.value || !selectedRoute.value.geometry) return;
    if (routeLayer) map.removeLayer(routeLayer);
    routeLayer = L.geoJSON(selectedRoute.value.geometry, { style: { color: '#2563eb', weight: 5 } }).addTo(map);
    map.fitBounds(routeLayer.getBounds(), { padding: [50, 50] });
};

const confirmTrip = () => {
    alert(`Success: ${form.value.allocated_weight} Tons dispatched with ${calculatedFuel.value}L of fuel.`);
};

onMounted(async () => {
    const res = await api.get('/portal/mock/routes');
    routes.value = res.data;
    nextTick(() => { initMap(); });
});
</script>

<style>
.leaflet-container { z-index: 1 !important; border-radius: 3rem; }
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.slide-enter-active, .slide-leave-active { transition: all 0.3s ease; }
.slide-enter-from, .slide-leave-to { opacity: 0; transform: translateY(-10px); }
</style>
