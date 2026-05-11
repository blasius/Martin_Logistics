<template>
    <div class="flex flex-col h-screen bg-slate-50 overflow-hidden font-sans">

        <!-- Updated Horizontal Summary Banner -->
        <transition name="slide-down">
            <div v-if="hasAnySelection" class="fixed top-0 left-0 right-0 bg-slate-900 text-white px-8 py-4 flex items-center justify-between shadow-xl z-50">
                <div class="flex items-center gap-8 overflow-x-auto custom-scrollbar-light">
                    <!-- Order Summary -->
                    <div class="flex flex-col border-r border-slate-700 pr-8 min-w-max">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Order ({{ selectedOrder?.reference }})</span>
                        <span v-if="selectedOrder" class="text-base font-bold truncate">
                            {{ selectedOrder.client_name }}
                        </span>
                        <span v-else class="text-sm italic text-slate-500">Pending</span>
                    </div>

                    <!-- Execution Resource Summary -->
                    <div class="flex flex-col border-r border-slate-700 pr-8 min-w-max">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Assigned Resource</span>
                        <div v-if="searchQueries.assignment" class="flex flex-col">
                             <span class="text-base font-bold truncate text-blue-400">
                                {{ searchQueries.assignment }} {{ selectedVehicle.trailer_plate }}
                             </span>
                             <div class="flex gap-2 text-xs text-slate-300 mt-1">
                                 <span v-if="selectedVehicle.driver_name" class="text-emerald-400">Driver: {{ selectedVehicle.driver_name }}</span>
                                 <span v-if="selectedVehicle.trailer_plate" class="text-amber-400"></span>
                                 <span v-if="selectedVehicle.capacity" class="text-blue-300">Cap: {{ selectedVehicle.capacity }}T</span>
                             </div>
                        </div>
                        <span v-else class="text-sm italic text-slate-500">Pending</span>
                    </div>

                    <!-- Route Summary -->
                    <div class="flex flex-col min-w-max">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Corridor</span>
                        <div v-if="selectedRoute" class="flex items-baseline gap-4">
                            <span class="text-base font-bold truncate">
                                {{ selectedRoute.name }}
                            </span>
                            <div class="flex gap-4 border-l border-slate-700 pl-4">
                                <div class="flex items-baseline gap-1">
                                    <span class="text-xs text-slate-400 font-bold uppercase tracking-wider">Dist:</span>
                                    <span class="text-sm font-black">{{ selectedRouteDistance }}<span class="text-[10px] ml-0.5 text-slate-400">km</span></span>
                                </div>
                                <div class="flex items-baseline gap-1">
                                    <span class="text-xs text-slate-400 font-bold uppercase tracking-wider">Fuel:</span>
                                    <span class="text-sm font-black text-amber-400">{{ calculatedFuel > 0 ? calculatedFuel : '-' }}<span class="text-[10px] ml-0.5 text-slate-400">L</span></span>
                                </div>
                            </div>
                        </div>
                        <span v-else class="text-sm italic text-slate-500">Pending</span>
                    </div>

                    <!-- Weight Summary -->
                    <div class="flex flex-col min-w-max">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Load</span>
                        <div v-if="form.allocated_weight > 0" class="flex items-baseline gap-4">
                            <span class="text-base font-bold text-emerald-400">
                                {{ form.allocated_weight }}<span class="text-[10px] ml-0.5 text-slate-400">T</span>
                            </span>
                            <div class="flex gap-2 border-l border-slate-700 pl-4">
                                <span v-if="selectedOrder" class="text-xs text-slate-300">Order: {{ selectedOrder.remaining_tonnage }}T</span>
                                <span v-if="weightImpact > 0" class="text-xs text-orange-400">+{{ weightImpact }}%</span>
                            </div>
                        </div>
                        <span v-else class="text-sm italic text-slate-500">Set Weight</span>
                    </div>
                </div>

                <!-- Actions -->
                <div class="ml-8 shrink-0 flex items-center gap-4">
                    <span v-if="isFormValid" class="px-3 py-1 bg-green-500/20 text-green-400 border border-green-500/30 rounded-lg text-[10px] font-black uppercase tracking-wider">Ready</span>
                    <span v-else class="px-3 py-1 bg-slate-800 text-slate-400 rounded-lg text-[10px] font-bold uppercase tracking-wider">Draft</span>

                    <button @click="confirmTrip" :disabled="!isFormValid || saving"
                            class="bg-blue-600 hover:bg-blue-500 text-white px-6 py-2 rounded-xl text-xs font-black shadow-lg active:scale-95 disabled:opacity-30 disabled:hover:bg-blue-600 transition-all uppercase tracking-wider">
                        {{ saving ? 'Processing...' : 'Dispatch' }}
                    </button>
                </div>
            </div>
        </transition>

        <!-- Main Content Area -->
        <div class="flex flex-1 overflow-hidden" :class="{ 'pt-24': hasAnySelection }">

            <!-- Controls Sidebar (Now on the LEFT) -->
            <!-- Changed border-l to border-r and removed ml-6 -->
            <div class="w-[450px] flex flex-col z-20 shrink-0 bg-white border-r border-slate-200 shadow-xl overflow-y-auto custom-scrollbar">
                <header class="p-6 pb-4 border-b border-slate-100 bg-slate-50/50 shrink-0">
                    <h1 class="text-2xl font-black text-slate-800 tracking-tight uppercase italic leading-none">New Dispatch</h1>
                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-2">Resource Allocation Matrix</p>
                </header>

                <div class="flex-1 p-6 space-y-8">
                    <!-- 1. Order Context -->
                    <div class="space-y-4">
                        <label class="text-[10px] font-black text-blue-600 uppercase tracking-widest block border-b border-slate-100 pb-2">1. Order Context</label>
                        <div class="relative">
                            <input type="text" v-model="searchQueries.order" @input="searchOrders" placeholder="Search Order Ref or Client..."
                                   class="w-full bg-slate-50 border border-slate-200 p-4 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all" />
                            <div v-if="searchResults.orders.length" class="absolute left-0 right-0 z-[2000] mt-2 bg-white rounded-xl shadow-2xl border border-slate-100 overflow-hidden max-h-60 overflow-y-auto">
                                <div v-for="order in searchResults.orders" :key="order.id" @click="selectOrder(order)" class="p-4 hover:bg-blue-50 cursor-pointer border-b border-slate-50 transition-colors">
                                    <span class="font-black text-sm uppercase block text-slate-800">{{ order.client_name }}</span>
                                    <span class="text-slate-500 text-xs font-medium">{{ order.reference }} &bull; Rem: <span class="text-emerald-600 font-bold">{{ order.remaining_tonnage }}T</span></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Execution Resource -->
                    <div class="space-y-4">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block border-b border-slate-100 pb-2">2. Execution Resource</label>
                        <div class="relative">
                            <input type="text" v-model="searchQueries.assignment" @input="searchAssignments" placeholder="Search Vehicle Plate..."
                                   class="w-full bg-slate-50 border border-slate-200 p-4 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all" />
                            <div v-if="searchResults.assignments.length" class="absolute left-0 right-0 z-[2000] mt-2 bg-white rounded-xl shadow-2xl border border-slate-100 overflow-hidden">
                                <div v-for="res in searchResults.assignments" :key="res.id" @click="selectAssignment(res)" class="p-4 hover:bg-slate-50 cursor-pointer border-b border-slate-50 transition-colors">
                                    <div class="flex justify-between items-center mb-1">
                                        <span class="text-sm font-black text-slate-800 uppercase">{{ res.label }}</span>
                                        <span class="text-[8px] font-black px-2 py-0.5 rounded bg-slate-100 text-slate-500 uppercase">{{ res.type }}</span>
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        <span v-if="res.capacity" class="font-bold text-blue-600 border-slate-200 pr-2 mr-2">Cap: {{ res.capacity }}T</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Route Corridor -->
                    <div class="space-y-4">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block border-b border-slate-100 pb-2">3. Route Corridor</label>
                        <div class="relative">
                            <input type="text" v-model="searchQueries.route" @input="searchRoutes" placeholder="Search Route..."
                                   class="w-full bg-slate-50 border border-slate-200 p-4 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all" />
                            <div v-if="searchResults.routes.length" class="absolute left-0 right-0 z-[2000] mt-2 bg-white rounded-xl shadow-2xl border border-slate-100 overflow-hidden max-h-60 overflow-y-auto">
                                <div v-for="r in searchResults.routes" :key="r.id" @click="selectRoute(r)" class="p-4 hover:bg-slate-50 cursor-pointer border-b border-slate-50 transition-colors">
                                    <span class="text-sm font-bold text-slate-700 block">{{ r.name }}</span>
                                    <span class="text-[10px] text-slate-400 font-medium">{{ r.estimated_distance_km }} km &bull; Deviation: {{ r.allowed_deviation_meters }}m</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 4. Allocated Weight -->
                    <div class="space-y-4">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block border-b border-slate-100 pb-2">4. Allocated Weight (Tons)</label>
                        <div class="relative">
                            <input type="number" v-model.number="form.allocated_weight" min="0" step="0.1" placeholder="Enter allocated weight..."
                                   class="w-full bg-slate-50 border border-slate-200 p-4 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all" />
                            <div v-if="selectedOrder" class="mt-2 text-[9px] text-slate-500 font-medium">
                                Order Remaining: <span class="text-emerald-600 font-bold">{{ selectedOrder.remaining_tonnage }}T</span>
                                <span v-if="selectedVehicle.capacity" class="ml-3">Vehicle Capacity: <span class="text-blue-600 font-bold">{{ selectedVehicle.capacity }}T</span></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Map Canvas (Now on the RIGHT) -->
            <!-- Swapped mr-0 for ml-6 to keep spacing consistent -->
            <div class="flex-1 bg-slate-200 relative z-10 m-6 ml-6 rounded-[3rem] shadow-sm border border-slate-200 overflow-hidden">
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

        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed, nextTick } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { tripsApi } from "../../api/trips";

// State
const mapReady = ref(false);
const saving = ref(false);
const searchQueries = ref({ order: '', assignment: '', route: '' });
const searchResults = ref({ orders: [], assignments: [], routes: [] });
const selectedOrder = ref(null);
const allRoutes = ref([]); // Store all routes for client-side filtering if needed, or fetch from API
const selectedRoute = ref(null);
const loadingRouteDetails = ref(false);
const selectedVehicle = ref({ ratio: 0, age: 0, capacity: null, driver_name: null, trailer_plate: null });

const form = ref({
    order_id: '',
    assignment: '',
    route_id: '',
    allocated_weight: 0,
    status: 'assigned'
});

// Determine if any selection has been made to show the banner
const hasAnySelection = computed(() => {
    return selectedOrder.value !== null || searchQueries.value.assignment !== '' || selectedRoute.value !== null || form.value.allocated_weight > 0;
});

// Logic & Calculations
const selectedRouteDistance = computed(() => selectedRoute.value?.estimated_distance_km || 0);

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
let currentLayer = null;

const initMap = () => {
    map = L.map('map', { zoomControl: false }).setView([-1.9441, 30.0619], 7);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
    setTimeout(() => { map.invalidateSize(); mapReady.value = true; }, 600);
};

// Search Functions
const searchOrders = async () => {
    if (searchQueries.value.order.length < 2) {
        searchResults.value.orders = [];
        return;
    }
    try {
        const res = await tripsApi.searchOrders(searchQueries.value.order);
        searchResults.value.orders = res.data;
    } catch (e) {
        console.error('Error searching orders', e);
    }
};

const selectOrder = (order) => {
    searchQueries.value.order = order.reference; // Or client name if you prefer
    searchResults.value.orders = [];
    selectedOrder.value = order;
    form.value.order_id = order.id;
    // Auto-fill tonnage with remaining amount as a starting point
    // If a vehicle is already selected, respect its capacity
    if (selectedVehicle.value.capacity) {
         form.value.allocated_weight = Math.min(Number(selectedVehicle.value.capacity), Number(order.remaining_tonnage));
    } else {
         form.value.allocated_weight = Number(order.remaining_tonnage);
    }
};

const searchAssignments = async () => {
    if (searchQueries.value.assignment.length < 2) {
        searchResults.value.assignments = [];
        return;
    }
    try {
        const res = await tripsApi.searchAssignments(searchQueries.value.assignment);
        searchResults.value.assignments = res.data;
    } catch (e) {
        console.error('Error searching assignments', e);
    }
};

const selectAssignment = (res) => {
    form.value.assignment = res.id;
    searchQueries.value.assignment = res.label; // Changed to just plate in backend
    searchResults.value.assignments = [];
    if (res.type === 'vehicle') {
        selectedVehicle.value = {
            ratio: res.ratio,
            age: res.age,
            capacity: res.capacity,
            driver_name: res.driver_name,
            trailer_plate: res.trailer_plate
        };
        // Auto populate weight based on vehicle capacity if it exists
        if (res.capacity) {
            if (selectedOrder.value) {
                // If order is selected, take the minimum of capacity and remaining tonnage
                form.value.allocated_weight = Math.min(Number(res.capacity), Number(selectedOrder.value.remaining_tonnage));
            } else {
                // If no order selected yet, just set it to the full vehicle capacity
                form.value.allocated_weight = Number(res.capacity);
            }
        }
    } else {
         selectedVehicle.value = { ratio: 0, age: 0, capacity: null, driver_name: null, trailer_plate: null };
         // Don't reset allocated_weight to 0 when driver is selected
         // Keep existing value or set to order remaining if no value exists
         if (selectedOrder.value && form.value.allocated_weight === 0) {
            form.value.allocated_weight = Number(selectedOrder.value.remaining_tonnage);
         }
    }
};

const searchRoutes = () => {
    const q = searchQueries.value.route.toLowerCase();
    if (q.length < 1) {
        searchResults.value.routes = [];
        return;
    }
    // Client-side filtering of routes since we likely loaded them all on mount
    searchResults.value.routes = allRoutes.value.filter(r => r.name.toLowerCase().includes(q));
};

const selectRoute = async (route) => {
    // 1. Update UI state immediately
    searchQueries.value.route = route.name;
    searchResults.value.routes = [];
    form.value.route_id = route.id;

    // 2. We need the full route object including the geometry/path to draw it.
    // The list API might not include the heavy geometry. Let's fetch it.
    loadingRouteDetails.value = true;
    try {
        // We reuse the existing getRoute method which fetches the geometry
        const res = await tripsApi.getRoute(route.id);
        selectedRoute.value = res.data;

        // 3. Draw on map
        if (selectedRoute.value && selectedRoute.value.path) {
            if (currentLayer) map.removeLayer(currentLayer);

            // Clear Geoman layers just in case (if we were using drawing tools here)
            if(map.pm) map.pm.getGeomanDrawLayers().forEach(layer => map.removeLayer(layer));

            const latlngs = selectedRoute.value.path.map(p => [p.lat, p.lng]);
            currentLayer = L.polyline(latlngs, { color: '#2563eb', weight: 5 }).addTo(map);

            try {
                // Fix map drawing issue by ensuring map is fully updated before fitting bounds
                nextTick(() => {
                     map.fitBounds(currentLayer.getBounds(), { padding: [50, 50] });
                });
            } catch (e) {
                console.warn("Could not fit bounds", e);
            }
        }
    } catch (e) {
         console.error('Error fetching full route details for map', e);
         // Fallback: just use the basic route info without drawing
         selectedRoute.value = route;
    } finally {
        loadingRouteDetails.value = false;
    }
};

const confirmTrip = async () => {
    saving.value = true;
    try {
        await tripsApi.createTrip(form.value);
        // We don't use alert anymore, maybe a toast in real app. For now just clear.

        // Reset form
        form.value = {
            order_id: '',
            assignment: '',
            route_id: '',
            allocated_weight: 0,
            status: 'assigned'
        };
        searchQueries.value = { order: '', assignment: '', route: '' };
        selectedOrder.value = null;
        selectedRoute.value = null;
        selectedVehicle.value = { ratio: 0, age: 0, capacity: null, driver_name: null, trailer_plate: null };
        if (currentLayer) {
            map.removeLayer(currentLayer);
            currentLayer = null;
            map.setView([-1.9441, 30.0619], 7);
        }
    } catch (error) {
        console.error('Error creating trip', error);
        alert('Failed to authorize dispatch.');
    } finally {
        saving.value = false;
    }
};

onMounted(async () => {
    try {
        const res = await tripsApi.getAllRoutes();
        allRoutes.value = res.data;
    } catch (e) {
        console.error('Error loading routes', e);
    }
    nextTick(() => { initMap(); });
});
</script>

<style>
/* Re-enable rounded corners by removing the override */
/* .leaflet-container { z-index: 1 !important; border-radius: 0; } */
.leaflet-container { z-index: 1 !important; border-radius: 3rem; }
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.custom-scrollbar-light::-webkit-scrollbar { height: 4px; }
.custom-scrollbar-light::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.3); border-radius: 10px; }

/* Banner Transition */
.slide-down-enter-active,
.slide-down-leave-active {
  transition: all 0.3s ease-out;
}
.slide-down-enter-from,
.slide-down-leave-to {
  transform: translateY(-100%);
  opacity: 0;
}
</style>
