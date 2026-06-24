<template>
    <div class="p-6 bg-slate-50 min-h-screen space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-black text-slate-800 tracking-tight uppercase">Repair Requests</h1>
            <button @click="openCreateModal" class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl shadow-lg font-black text-xs flex items-center gap-2 uppercase">
                <Plus class="w-4 h-4" /> New Request
            </button>
        </div>

        <div class="grid grid-cols-5 gap-4">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 text-center">
                <p class="text-2xl font-black text-amber-600">{{ stats.pending_approval }}</p>
                <p class="text-[10px] font-black text-slate-400 uppercase">Pending</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 text-center">
                <p class="text-2xl font-black text-blue-600">{{ stats.in_progress }}</p>
                <p class="text-[10px] font-black text-slate-400 uppercase">In Progress</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 text-center">
                <p class="text-2xl font-black text-emerald-600">{{ stats.completed }}</p>
                <p class="text-[10px] font-black text-slate-400 uppercase">Completed</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 text-center">
                <p class="text-2xl font-black text-green-600">{{ stats.released }}</p>
                <p class="text-[10px] font-black text-slate-400 uppercase">Released</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 text-center" :class="stats.critical > 0 ? 'bg-rose-50 border-rose-200' : ''">
                <p class="text-2xl font-black text-rose-600">{{ stats.critical }}</p>
                <p class="text-[10px] font-black text-slate-400 uppercase">Critical</p>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex flex-wrap gap-4">
            <div class="relative flex-1">
                <Search class="absolute left-3 top-2.5 w-4 h-4 text-slate-400" />
                <input v-model="filters.search" @input="debounceSearch" type="text" placeholder="Search reference, vehicle, description..." class="w-full pl-10 p-2 bg-slate-50 border-none rounded-xl text-xs font-bold outline-none">
            </div>
            <select v-model="filters.status" @change="fetch" class="bg-slate-50 border-none rounded-xl text-xs font-bold px-3 outline-none">
                <option value="">All Statuses</option>
                <option value="draft">Draft</option>
                <option value="pending_approval">Pending (Logistics)</option>
                <option value="pending_ops_approval">Pending (Operations)</option>
                <option value="approved">Approved</option>
                <option value="in_progress">In Progress</option>
                <option value="completed">Completed</option>
                <option value="released">Released</option>
                <option value="cancelled">Cancelled</option>
            </select>
            <select v-model="filters.priority" @change="fetch" class="bg-slate-50 border-none rounded-xl text-xs font-bold px-3 outline-none">
                <option value="">All Priorities</option>
                <option value="critical">Critical</option>
                <option value="high">High</option>
                <option value="medium">Medium</option>
                <option value="low">Low</option>
            </select>
        </div>

        <div v-if="loading" class="grid gap-4">
            <div v-for="i in 5" class="h-20 bg-white rounded-2xl animate-pulse border border-slate-100"></div>
        </div>

        <div v-else class="grid gap-3">
            <div v-for="rr in requests" :key="rr.id" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 hover:shadow-md transition-shadow">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3">
                        <router-link :to="`/workshop/repair-requests/${rr.id}`" class="font-black text-indigo-600 hover:text-indigo-800 text-sm">{{ rr.reference }}</router-link>
                        <span class="text-[10px] font-black px-2 py-1 rounded-full" :class="priorityBadge(rr.priority)">{{ rr.priority }}</span>
                        <span class="text-[10px] font-black px-2 py-1 rounded-full" :class="statusBadge(rr.status)">{{ displayStatus(rr.status) }}</span>
                    </div>
                    <div class="text-xs font-bold text-slate-400">
                        {{ rr.vehicle?.plate_number }}
                    </div>
                </div>
                <p class="text-xs text-slate-500 mt-2 line-clamp-1">{{ rr.description }}</p>
                <div class="flex gap-4 mt-2 text-[10px] text-slate-400 font-medium">
                    <span v-if="rr.mechanic">Mechanic: {{ rr.mechanic.name }}</span>
                    <span>Type: {{ rr.type }}</span>
                </div>
            </div>
            <div v-if="requests.length === 0" class="text-center py-20">
                <p class="text-slate-400 font-bold text-xs uppercase">No repair requests found</p>
            </div>
        </div>

        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" @click.self="showCreateModal = false">
            <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full border border-slate-200 max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center">
                    <h3 class="font-black text-slate-800 uppercase text-sm">New Repair Request</h3>
                    <button @click="showCreateModal = false" class="text-slate-400 hover:text-slate-600"><X class="w-5 h-5" /></button>
                </div>
                <form @submit.prevent="createRequest" class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="relative">
                            <label class="text-[10px] font-black text-slate-400 uppercase">Vehicle</label>
                            <input ref="vehicleSearchRef" v-model="vehicleSearchQuery" @input="onVehicleSearchInput" @focus="onVehicleSearchFocus" @blur="onVehicleSearchBlur" type="text" placeholder="Type to search vehicle..." required class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                            <input v-model="createForm.vehicle_id" type="hidden">
                            <ul v-if="showVehicleDropdown" class="absolute z-10 mt-1 w-full bg-white border border-slate-200 rounded-xl shadow-lg overflow-hidden">
                                <li v-for="v in vehicleSearchResults" :key="v.id" @mousedown.prevent="selectVehicle(v)" class="px-3 py-2 text-xs font-bold hover:bg-indigo-50 cursor-pointer border-b border-slate-100 last:border-0">
                                    <span class="text-slate-800">{{ v.plate_number }}</span>
                                    <span class="text-slate-400 ml-2">({{ v.status }})</span>
                                    <span v-if="v.current_driver" class="text-slate-400 ml-2">— {{ v.current_driver }}</span>
                                </li>
                                <li v-if="vehicleSearchResults.length === 0 && vehicleSearchQuery.length > 0" class="px-3 py-3 text-xs text-slate-400 font-bold text-center">No vehicles found</li>
                            </ul>
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase">Type</label>
                            <input v-model="createForm.type" required class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase">Priority</label>
                            <select v-model="createForm.priority" required class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase">Driver</label>
                            <input v-model="createForm.driver_name" readonly class="w-full mt-1 p-2.5 bg-slate-100 border border-slate-200 rounded-xl text-xs font-bold cursor-not-allowed">
                        </div>
                    </div>
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase">Description</label>
                        <textarea v-model="createForm.description" required rows="3" class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold"></textarea>
                    </div>
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase">Estimated Items</label>
                        <div v-for="(item, i) in createForm.items" :key="i" class="flex gap-2 mt-2 items-start">
                            <div class="flex-1 relative">
                                <input v-model="item.part_search" @input="onPartSearchInput(i)" @focus="onPartSearchFocus(i)" @blur="onPartSearchBlur(i)" type="text" placeholder="Search part or type description..." class="w-full p-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                                <ul v-if="item.show_part_dropdown && item.part_results?.length" class="absolute z-10 mt-1 w-full bg-white border border-slate-200 rounded-xl shadow-lg overflow-hidden">
                                    <li v-for="p in item.part_results" :key="p.id" @mousedown.prevent="selectPart(i, p)" class="px-3 py-2 text-xs font-bold hover:bg-indigo-50 cursor-pointer border-b border-slate-100">
                                        <span class="text-slate-800">{{ p.name }}</span>
                                        <span class="text-slate-400 ml-1">({{ p.sku }})</span>
                                    </li>
                                </ul>
                            </div>
                            <input v-model.number="item.estimated_quantity" type="number" step="0.01" placeholder="Qty" class="w-20 p-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                            <input v-model.number="item.estimated_unit_price" type="number" step="0.01" placeholder="Price" class="w-24 p-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                            <button type="button" @click="createForm.items.splice(i, 1)" class="mt-2 text-rose-500"><X class="w-4 h-4" /></button>
                        </div>
                        <button type="button" @click="addItem" class="mt-2 text-xs font-bold text-indigo-600">+ Add Item</button>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="showCreateModal = false" class="flex-1 px-4 py-3 bg-slate-100 text-slate-600 text-xs font-black rounded-xl uppercase">Cancel</button>
                        <button type="submit" class="flex-1 px-4 py-3 bg-indigo-600 text-white text-xs font-black rounded-xl shadow-lg uppercase" :disabled="creating">
                            {{ creating ? 'Creating...' : 'Create Request' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { repairRequestsApi } from '../../../api/workshop/repair-requests';
import { partsApi } from '../../../api/workshop/parts';
import { Plus, Search, X } from 'lucide-vue-next';

const loading = ref(true);
const creating = ref(false);
const showCreateModal = ref(false);
const requests = ref([]);
const stats = ref({ pending_approval: 0, in_progress: 0, completed: 0, released: 0, critical: 0 });
const filters = ref({ search: '', status: '', priority: '' });
const createForm = ref({
    vehicle_id: '',
    driver_id: '',
    driver_name: '',
    type: '',
    priority: 'medium',
    description: '',
    items: [],
});

const vehicleSearchQuery = ref('');
const vehicleSearchResults = ref([]);
const showVehicleDropdown = ref(false);
const vehicleSearchRef = ref(null);
const partCatalog = ref([]);
let debounceTimer;
let vehicleSearchTimer;

function debounceSearch() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(fetch, 300);
}

async function fetch() {
    loading.value = true;
    try {
        const params = { ...filters.value };
        Object.keys(params).forEach(k => { if (!params[k]) delete params[k]; });
        const { data } = await repairRequestsApi.index(params);
        requests.value = data.repair_requests?.data || [];
        stats.value = data.stats || stats.value;
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
}

function onVehicleSearchInput() {
    clearTimeout(vehicleSearchTimer);
    if (vehicleSearchQuery.value.length < 1) {
        vehicleSearchResults.value = [];
        showVehicleDropdown.value = false;
        return;
    }
    vehicleSearchTimer = setTimeout(async () => {
        try {
            const { data } = await repairRequestsApi.searchVehicles(vehicleSearchQuery.value);
            vehicleSearchResults.value = data;
            showVehicleDropdown.value = true;
        } catch (e) {
            console.error(e);
        }
    }, 250);
}

function onVehicleSearchFocus() {
    if (vehicleSearchResults.value.length) {
        showVehicleDropdown.value = true;
    } else if (!vehicleSearchQuery.value) {
        loadAllVehicles();
    }
}

async function loadAllVehicles() {
    try {
        const { data } = await repairRequestsApi.searchVehicles('');
        vehicleSearchResults.value = data;
        showVehicleDropdown.value = true;
    } catch (e) {
        console.error(e);
    }
}

function onVehicleSearchBlur() {
    setTimeout(() => { showVehicleDropdown.value = false; }, 200);
}

function selectVehicle(v) {
    createForm.value.vehicle_id = v.id;
    createForm.value.driver_id = v.current_driver_id || '';
    createForm.value.driver_name = v.current_driver || '';
    vehicleSearchQuery.value = v.plate_number;
    showVehicleDropdown.value = false;
}

function openCreateModal() {
    showCreateModal.value = true;
    createForm.value = { vehicle_id: '', driver_id: '', driver_name: '', type: '', priority: 'medium', description: '', items: [] };
        vehicleSearchQuery.value = '';
        vehicleSearchResults.value = [];
        showVehicleDropdown.value = true;
        partCatalog.value = [];
        setTimeout(() => vehicleSearchRef.value?.focus(), 100);
    }

    function addItem() {
        createForm.value.items.push({
            description: '', estimated_quantity: null, estimated_unit_price: null, part_id: null, part_search: '',
            part_results: [], show_part_dropdown: false,
        });
    }

    let partSearchTimers = {};

    function onPartSearchInput(i) {
        const item = createForm.value.items[i];
        clearTimeout(partSearchTimers[i]);
        if (item.part_search.length < 1) {
            item.part_results = [];
            item.show_part_dropdown = false;
            return;
        }
        partSearchTimers[i] = setTimeout(() => {
            const q = item.part_search.toLowerCase();
            item.part_results = partCatalog.value.filter(p =>
                p.name.toLowerCase().includes(q) || (p.sku && p.sku.toLowerCase().includes(q))
            ).slice(0, 8);
            item.show_part_dropdown = item.part_results.length > 0;
        }, 200);
    }

    function onPartSearchFocus(i) {
        const item = createForm.value.items[i];
        if (item.part_results.length) item.show_part_dropdown = true;
    }

    function onPartSearchBlur(i) {
        setTimeout(() => { createForm.value.items[i].show_part_dropdown = false; }, 200);
    }

    function selectPart(i, p) {
        const item = createForm.value.items[i];
        item.part_id = p.id;
        item.part_search = `${p.name} (${p.sku})`;
        item.description = p.name;
        item.estimated_unit_price = p.unit_price || item.estimated_unit_price;
        item.show_part_dropdown = false;
    }

async function createRequest() {
    creating.value = true;
    try {
        await repairRequestsApi.store(createForm.value);
        showCreateModal.value = false;
        await fetch();
    } catch (e) {
        console.error(e);
    } finally {
        creating.value = false;
    }
}

function displayStatus(s) {
    const map = {
        draft: 'Draft',
        pending_approval: 'Pending (Logistics)',
        pending_ops_approval: 'Pending (Operations)',
        approved: 'Approved',
        in_progress: 'In Progress',
        completed: 'Completed',
        released: 'Released',
        cancelled: 'Cancelled',
    };
    return map[s] || s;
}

function statusBadge(s) {
    const map = {
        draft: 'bg-slate-100 text-slate-600',
        pending_approval: 'bg-amber-100 text-amber-700',
        pending_ops_approval: 'bg-orange-100 text-orange-700',
        approved: 'bg-blue-100 text-blue-700',
        in_progress: 'bg-indigo-100 text-indigo-700',
        completed: 'bg-emerald-100 text-emerald-700',
        released: 'bg-green-100 text-green-700',
        cancelled: 'bg-rose-100 text-rose-700',
    };
    return map[s] || 'bg-slate-100 text-slate-600';
}

function priorityBadge(p) {
    const map = {
        critical: 'bg-rose-100 text-rose-700',
        high: 'bg-orange-100 text-orange-700',
        medium: 'bg-blue-100 text-blue-700',
        low: 'bg-slate-100 text-slate-500',
    };
    return map[p] || 'bg-slate-100 text-slate-600';
}

async function loadParts() {
    try {
        const { data } = await partsApi.index({ per_page: 500 });
        partCatalog.value = data.parts?.data || [];
    } catch (e) { console.error(e); }
}

onMounted(async () => {
    await loadParts();
    await fetch();
});
</script>
