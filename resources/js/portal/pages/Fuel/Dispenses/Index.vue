<template>
    <div class="p-6 bg-slate-50 min-h-screen space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-black text-slate-800 tracking-tight uppercase">Fuel Dispensing</h1>
                <p class="text-xs font-bold text-slate-400">Record &amp; calculate fuel dispenses</p>
            </div>
            <div class="flex gap-2">
                <button @click="showCreateModal = true" class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl shadow-lg font-black text-xs flex items-center gap-2 uppercase">
                    <Plus class="w-4 h-4" /> New Dispense
                </button>
                <button @click="load" class="p-2.5 bg-white rounded-xl border border-slate-200 hover:bg-slate-50">
                    <RefreshCw class="w-4 h-4 text-slate-500" />
                </button>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex flex-wrap gap-4">
            <input v-model="filters.date_from" type="date" @change="load" class="bg-slate-50 border-none rounded-xl text-xs font-bold px-3 py-2 outline-none">
            <input v-model="filters.date_to" type="date" @change="load" class="bg-slate-50 border-none rounded-xl text-xs font-bold px-3 py-2 outline-none">
            <select v-model="filters.tank_id" @change="load" class="bg-slate-50 border-none rounded-xl text-xs font-bold px-3 py-2 outline-none">
                <option value="">All Tanks</option>
                <option v-for="t in tanks" :key="t.id" :value="t.id">{{ t.code }}</option>
            </select>
        </div>

        <div v-if="loading" class="text-center py-20"><p class="text-slate-400 font-bold text-xs uppercase">Loading...</p></div>

        <div v-else-if="!dispenses.length" class="text-center py-20">
            <p class="text-slate-400 font-bold text-xs uppercase">No dispense records</p>
        </div>

        <div v-else class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-slate-50/50 text-[10px] font-black text-slate-400 uppercase border-b">
                    <tr>
                        <th class="p-4">Date</th>
                        <th class="p-4">Vehicle</th>
                        <th class="p-4">Driver</th>
                        <th class="p-4">Tank</th>
                        <th class="p-4">Qty (L)</th>
                        <th class="p-4">Odometer</th>
                        <th class="p-4">Dispenser</th>
                        <th class="p-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="d in dispenses" :key="d.id" class="border-b border-slate-50 hover:bg-slate-50/50 text-xs">
                        <td class="p-4 font-bold text-slate-600">{{ formatDate(d.dispensed_at) }}</td>
                        <td class="p-4 font-bold text-indigo-600 font-mono">{{ d.vehicle?.plate_number }}</td>
                        <td class="p-4 text-slate-600">{{ d.driver?.user?.name || '—' }}</td>
                        <td class="p-4 text-slate-600">{{ d.tank?.code || '—' }}</td>
                        <td class="p-4 font-bold text-slate-800">{{ d.quantity?.toLocaleString() }}</td>
                        <td class="p-4 text-slate-400">{{ d.odometer_at_dispense ? d.odometer_at_dispense.toLocaleString() + ' km' : '—' }}</td>
                        <td class="p-4 text-slate-600">{{ d.dispenser?.name }}</td>
                        <td class="p-4 text-right">
                            <button @click="deleteDispense(d.id)" class="text-rose-500 hover:text-rose-700" title="Delete">
                                <Trash2 class="w-4 h-4 inline" />
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" @click.self="showCreateModal = false">
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full border border-slate-200 max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center">
                    <h3 class="font-black text-slate-800 uppercase text-sm">New Dispense</h3>
                    <button @click="showCreateModal = false" class="text-slate-400 hover:text-slate-600"><X class="w-5 h-5" /></button>
                </div>
                <form @submit.prevent="createDispense" class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="relative">
                            <label class="text-[10px] font-black text-slate-400 uppercase">Vehicle</label>
                            <input v-model="vehicleSearch" @input="onVehicleSearchInput" @focus="onVehicleSearchFocus" @blur="onVehicleSearchBlur" placeholder="Search plate number..." class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                            <div v-if="selectedVehicle" class="mt-1 px-2.5 py-1.5 bg-indigo-50 border border-indigo-200 rounded-lg text-xs font-bold text-indigo-700 flex items-center gap-2">
                                <span>{{ selectedVehicle.plate_number }}</span>
                                <span class="text-indigo-400">·</span>
                                <span class="font-medium">{{ selectedVehicle.current_driver || 'No driver' }}</span>
                                <button @click="clearVehicle" class="ml-auto text-indigo-400 hover:text-indigo-600"><X class="w-3.5 h-3.5" /></button>
                            </div>
                            <ul v-if="vehicleSuggestions.length && vehicleFocused" class="absolute z-10 mt-1 w-full bg-white border border-slate-200 rounded-xl shadow-lg max-h-48 overflow-y-auto">
                                <li v-for="v in vehicleSuggestions" :key="v.id" @mousedown.prevent="selectVehicle(v)" class="px-3 py-2.5 hover:bg-indigo-50 cursor-pointer text-xs border-b border-slate-50 last:border-0">
                                    <span class="font-bold text-indigo-600">{{ v.plate_number }}</span>
                                    <span v-if="v.current_driver" class="text-slate-400 ml-2">— {{ v.current_driver }}</span>
                                </li>
                            </ul>
                        </div>
                        <div class="relative">
                            <label class="text-[10px] font-black text-slate-400 uppercase">Driver</label>
                            <input v-model="driverSearch" @input="onDriverSearchInput" @focus="onDriverSearchFocus" @blur="onDriverSearchBlur" placeholder="Search driver name..." class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                            <div v-if="selectedDriver" class="mt-1 px-2.5 py-1.5 bg-indigo-50 border border-indigo-200 rounded-lg text-xs font-bold text-indigo-700 flex items-center gap-2">
                                <span>{{ selectedDriver.current_driver }}</span>
                                <span class="text-indigo-400">·</span>
                                <span class="font-medium">{{ selectedDriver.plate_number || 'No vehicle' }}</span>
                                <button @click="clearDriver" class="ml-auto text-indigo-400 hover:text-indigo-600"><X class="w-3.5 h-3.5" /></button>
                            </div>
                            <ul v-if="driverSuggestions.length && driverFocused" class="absolute z-10 mt-1 w-full bg-white border border-slate-200 rounded-xl shadow-lg max-h-48 overflow-y-auto">
                                <li v-for="v in driverSuggestions" :key="v.id" @mousedown.prevent="selectDriver(v)" class="px-3 py-2.5 hover:bg-indigo-50 cursor-pointer text-xs border-b border-slate-50 last:border-0">
                                    <span class="font-bold text-slate-700">{{ v.current_driver }}</span>
                                    <span class="text-slate-400 ml-2">— {{ v.plate_number }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase">Tank</label>
                            <select v-model="dispenseForm.tank_id" class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                                <option value="">Select</option>
                                <option v-for="t in tanks" :key="t.id" :value="t.id">{{ t.code }} ({{ t.current_level }}L)</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase">Route (for calc)</label>
                            <select v-model="dispenseForm.route_id" @change="calculateAmount" class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                                <option value="">Manual entry</option>
                                <option v-for="r in routes" :key="r.id" :value="r.id">{{ r.name }}</option>
                            </select>
                        </div>
                    </div>
                    <div v-if="calculation.can_calculate" class="bg-indigo-50 border border-indigo-200 rounded-xl p-4">
                        <p class="text-[10px] font-black text-indigo-600 uppercase">Suggested Amount</p>
                        <p class="text-2xl font-black text-indigo-800">{{ calculation.suggested_amount }} L</p>
                        <div class="mt-2 text-[10px] text-indigo-600 space-y-1">
                            <p>Route distance: {{ calculation.breakdown?.distance_km }} km</p>
                            <p>Consumption rate: {{ calculation.breakdown?.km_per_liter }} km/L</p>
                            <p>Route consumption: {{ calculation.breakdown?.route_consumption }} L</p>
                            <p>Current level: {{ calculation.breakdown?.current_level }} L</p>
                            <p>Min reserve: {{ calculation.breakdown?.min_reserve }} L</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase">Quantity (L)</label>
                            <input v-model.number="dispenseForm.quantity" type="number" min="0" step="0.01" required class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase">Odometer (km)</label>
                            <input v-model.number="dispenseForm.odometer_at_dispense" type="number" min="0" step="0.1" class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                        </div>
                    </div>
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase">Dispensed At</label>
                        <input v-model="dispenseForm.dispensed_at" type="datetime-local" required class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                    </div>
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase">Override Reason (if different from suggestion)</label>
                        <input v-model="dispenseForm.override_reason" class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                    </div>
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase">Notes</label>
                        <textarea v-model="dispenseForm.notes" class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold"></textarea>
                    </div>
                    <button type="submit" class="w-full py-3 bg-indigo-600 text-white rounded-xl font-black text-xs uppercase">Record Dispense</button>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { fuelDispenseApi } from '../../../api/fuel/dispenses';
import { fuelTankApi } from '../../../api/fuel/tanks';
import { api } from '../../../../plugins/axios';
import { Plus, RefreshCw, X, Trash2 } from 'lucide-vue-next';

const loading = ref(true);
const dispenses = ref([]);
const tanks = ref([]);
const routes = ref([]);
const filters = ref({ date_from: '', date_to: '', tank_id: '' });
const showCreateModal = ref(false);
const dispenseForm = ref({
    vehicle_id: '', driver_id: '', tank_id: '', route_id: '',
    quantity: 0, odometer_at_dispense: null,
    dispensed_at: new Date().toISOString().slice(0, 16),
    override_reason: '', notes: '',
});
const calculation = ref({ can_calculate: false, suggested_amount: 0, breakdown: null });

const vehicleSearch = ref('');
const driverSearch = ref('');
const vehicleSuggestions = ref([]);
const driverSuggestions = ref([]);
const vehicleFocused = ref(false);
const driverFocused = ref(false);
const selectedVehicle = ref(null);
const selectedDriver = ref(null);
let vehicleSearchTimer = null;
let driverSearchTimer = null;

async function load() {
    loading.value = true;
    try {
        const params = {};
        Object.entries(filters.value).forEach(([k, v]) => { if (v) params[k] = v; });
        const [dispRes, tankRes] = await Promise.all([
            fuelDispenseApi.index(params),
            fuelTankApi.index(),
        ]);
        dispenses.value = dispRes.data.data || [];
        tanks.value = tankRes.data || [];
    } catch (e) { console.error(e); }
    finally { loading.value = false; }
}

async function loadDropdowns() {
    try {
        const res = await api.get('/portal/routes');
        routes.value = res.data?.data || res.data || [];
    } catch (e) { console.error(e); }
}

function doVehicleSearch(q) {
    clearTimeout(vehicleSearchTimer);
    vehicleSearchTimer = setTimeout(async () => {
        if (!q || q.length < 1) { vehicleSuggestions.value = []; return; }
        try {
            const { repairRequestsApi } = await import('../../../api/workshop/repair-requests');
            const res = await repairRequestsApi.searchVehicles(q);
            vehicleSuggestions.value = res.data || [];
        } catch (e) { console.error(e); }
    }, 250);
}

function doDriverSearch(q) {
    clearTimeout(driverSearchTimer);
    driverSearchTimer = setTimeout(async () => {
        if (!q || q.length < 1) { driverSuggestions.value = []; return; }
        try {
            const { repairRequestsApi } = await import('../../../api/workshop/repair-requests');
            const res = await repairRequestsApi.searchVehicles(q);
            driverSuggestions.value = (res.data || []).filter(v => v.current_driver);
        } catch (e) { console.error(e); }
    }, 250);
}

function onVehicleSearchInput() { doVehicleSearch(vehicleSearch.value); }
function onVehicleSearchFocus() { vehicleFocused.value = true; if (vehicleSearch.value) doVehicleSearch(vehicleSearch.value); }
function onVehicleSearchBlur() { setTimeout(() => { vehicleFocused.value = false; }, 200); }
function onDriverSearchInput() { doDriverSearch(driverSearch.value); }
function onDriverSearchFocus() { driverFocused.value = true; if (driverSearch.value) doDriverSearch(driverSearch.value); }
function onDriverSearchBlur() { setTimeout(() => { driverFocused.value = false; }, 200); }

function selectVehicle(v) {
    selectedVehicle.value = v;
    dispenseForm.value.vehicle_id = v.id;
    dispenseForm.value.driver_id = v.current_driver_id || '';
    vehicleSearch.value = '';
    vehicleSuggestions.value = [];
    selectedDriver.value = v.current_driver ? v : null;
    driverSearch.value = '';
    driverSuggestions.value = [];
    calculation.value = { can_calculate: false, suggested_amount: 0, breakdown: null };
}

function selectDriver(v) {
    selectedDriver.value = v;
    dispenseForm.value.driver_id = v.current_driver_id || '';
    dispenseForm.value.vehicle_id = v.id;
    driverSearch.value = '';
    driverSuggestions.value = [];
    selectedVehicle.value = v;
    vehicleSearch.value = '';
    vehicleSuggestions.value = [];
    calculation.value = { can_calculate: false, suggested_amount: 0, breakdown: null };
}

function clearVehicle() {
    selectedVehicle.value = null;
    dispenseForm.value.vehicle_id = '';
    dispenseForm.value.driver_id = '';
    selectedDriver.value = null;
    calculation.value = { can_calculate: false, suggested_amount: 0, breakdown: null };
}

function clearDriver() {
    selectedDriver.value = null;
    dispenseForm.value.driver_id = '';
    dispenseForm.value.vehicle_id = '';
    selectedVehicle.value = null;
    calculation.value = { can_calculate: false, suggested_amount: 0, breakdown: null };
}

async function calculateAmount() {
    if (!dispenseForm.value.vehicle_id || !dispenseForm.value.route_id) {
        calculation.value = { can_calculate: false, suggested_amount: 0, breakdown: null };
        return;
    }
    try {
        const res = await fuelDispenseApi.calculate({
            vehicle_id: dispenseForm.value.vehicle_id,
            route_id: dispenseForm.value.route_id,
        });
        calculation.value = res.data;
        if (res.data.can_calculate && res.data.suggested_amount) {
            dispenseForm.value.quantity = res.data.suggested_amount;
        }
    } catch (e) { console.error(e); }
}

async function createDispense() {
    try {
        if (calculation.value.can_calculate) {
            dispenseForm.value.calculated_amount = calculation.value.suggested_amount;
        }
        await fuelDispenseApi.store(dispenseForm.value);
        showCreateModal.value = false;
        dispenseForm.value = {
            vehicle_id: '', driver_id: '', tank_id: '', route_id: '',
            quantity: 0, odometer_at_dispense: null,
            dispensed_at: new Date().toISOString().slice(0, 16),
            override_reason: '', notes: '',
        };
        calculation.value = { can_calculate: false, suggested_amount: 0, breakdown: null };
        selectedVehicle.value = null;
        selectedDriver.value = null;
        vehicleSearch.value = '';
        driverSearch.value = '';
        await load();
    } catch (e) { console.error(e); }
}

async function deleteDispense(id) {
    if (!confirm('Delete this dispense record?')) return;
    try { await fuelDispenseApi.destroy(id); await load(); }
    catch (e) { console.error(e); }
}

function formatDate(d) {
    if (!d) return '';
    return new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
}

onMounted(() => { load(); loadDropdowns(); });
</script>
