<template>
    <div class="p-6 bg-slate-50 min-h-screen space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-black text-slate-800 tracking-tight uppercase">Stock Levels</h1>
            <div class="flex gap-2">
                <button @click="openAdjustModal" class="px-4 py-2 bg-amber-600 text-white rounded-xl shadow-lg font-black text-xs uppercase flex items-center gap-2">
                    <Move class="w-4 h-4" /> Adjust Stock
                </button>
                <button @click="openCreateModal" class="px-4 py-2 bg-indigo-600 text-white rounded-xl shadow-lg font-black text-xs uppercase flex items-center gap-2">
                    <Plus class="w-4 h-4" /> Add Stock
                </button>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex flex-wrap gap-4">
            <select v-model="filters.warehouse_id" @change="fetch" class="bg-slate-50 border-none rounded-xl text-xs font-bold px-3 py-2 outline-none">
                <option value="">All Warehouses</option>
                <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">{{ wh.name }}</option>
            </select>
            <label class="flex items-center gap-2 text-xs font-bold text-slate-600">
                <input type="checkbox" v-model="filters.low_stock" @change="fetch" class="rounded border-slate-300">
                Low Stock Only
            </label>
        </div>

        <div v-if="loading" class="grid gap-4">
            <div v-for="i in 5" class="h-14 bg-white rounded-2xl animate-pulse border border-slate-100"></div>
        </div>

        <div v-else class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-slate-50/50 text-[10px] font-black text-slate-400 uppercase border-b">
                    <tr>
                        <th class="p-4">Part</th>
                        <th class="p-4">SKU</th>
                        <th class="p-4">Warehouse</th>
                        <th class="p-4 text-right">Quantity</th>
                        <th class="p-4 text-right">Min Qty</th>
                        <th class="p-4 text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="sl in stockLevels" :key="sl.id" class="border-b border-slate-50 hover:bg-slate-50/50">
                        <td class="p-4 font-black text-sm text-slate-800">{{ sl.part?.name }}</td>
                        <td class="p-4 text-xs font-mono font-bold text-indigo-600">{{ sl.part?.sku }}</td>
                        <td class="p-4 text-xs font-bold text-slate-500">{{ sl.warehouse?.name }}</td>
                        <td class="p-4 text-right font-black text-sm" :class="sl.quantity <= sl.min_quantity ? 'text-rose-600' : 'text-emerald-600'">{{ sl.quantity }}</td>
                        <td class="p-4 text-right font-bold text-sm text-slate-500">{{ sl.min_quantity }}</td>
                        <td class="p-4 text-center">
                            <span class="text-[10px] font-black px-2 py-1 rounded-full" :class="sl.quantity <= 0 ? 'bg-rose-100 text-rose-700' : sl.quantity <= sl.min_quantity ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700'">
                                {{ sl.quantity <= 0 ? 'Out' : sl.quantity <= sl.min_quantity ? 'Low' : 'OK' }}
                            </span>
                        </td>
                    </tr>
                    <tr v-if="stockLevels.length === 0">
                        <td colspan="6" class="p-10 text-center text-slate-400 font-bold text-xs uppercase">No stock levels found</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Create Stock Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" @click.self="showCreateModal = false">
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full border border-slate-200">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center">
                    <h3 class="font-black text-slate-800 uppercase text-sm">Add Stock Level</h3>
                    <button @click="showCreateModal = false" class="text-slate-400 hover:text-slate-600"><X class="w-5 h-5" /></button>
                </div>
                <form @submit.prevent="createStock" class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase">Warehouse</label>
                            <select v-model="createForm.warehouse_id" required class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                                <option value="">Select</option>
                                <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">{{ wh.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase">Part</label>
                            <select v-model="createForm.part_id" required class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                                <option value="">Select</option>
                                <option v-for="p in parts" :key="p.id" :value="p.id">{{ p.name }} ({{ p.sku }})</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase">Quantity</label>
                            <input v-model.number="createForm.quantity" type="number" min="0" step="0.01" required class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase">Min Quantity</label>
                            <input v-model.number="createForm.min_quantity" type="number" min="0" step="0.01" required class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                        </div>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="showCreateModal = false" class="flex-1 px-4 py-3 bg-slate-100 text-slate-600 text-xs font-black rounded-xl uppercase">Cancel</button>
                        <button type="submit" class="flex-1 px-4 py-3 bg-indigo-600 text-white text-xs font-black rounded-xl shadow-lg uppercase" :disabled="saving">
                            {{ saving ? 'Creating...' : 'Create' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Adjust Stock Modal -->
        <div v-if="showAdjustModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" @click.self="showAdjustModal = false">
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full border border-slate-200">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center">
                    <h3 class="font-black text-slate-800 uppercase text-sm">Adjust Stock</h3>
                    <button @click="showAdjustModal = false" class="text-slate-400 hover:text-slate-600"><X class="w-5 h-5" /></button>
                </div>
                <form @submit.prevent="adjustStock" class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase">Warehouse</label>
                            <select v-model="adjustForm.warehouse_id" required class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                                <option value="">Select</option>
                                <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">{{ wh.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase">Part</label>
                            <select v-model="adjustForm.part_id" required class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                                <option value="">Select</option>
                                <option v-for="p in parts" :key="p.id" :value="p.id">{{ p.name }} ({{ p.sku }})</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase">Type</label>
                            <select v-model="adjustForm.type" required class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                                <option value="in">Stock In (Add)</option>
                                <option value="out">Stock Out (Remove)</option>
                                <option value="adjust">Set Exact Quantity</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase">Quantity</label>
                            <input v-model.number="adjustForm.quantity" type="number" min="0.01" step="0.01" required class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                        </div>
                    </div>
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase">Notes (optional)</label>
                        <input v-model="adjustForm.notes" class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="showAdjustModal = false" class="flex-1 px-4 py-3 bg-slate-100 text-slate-600 text-xs font-black rounded-xl uppercase">Cancel</button>
                        <button type="submit" class="flex-1 px-4 py-3 bg-amber-600 text-white text-xs font-black rounded-xl shadow-lg uppercase" :disabled="saving">
                            {{ saving ? 'Processing...' : 'Apply' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { stockLevelsApi } from '../../../api/workshop/stock-levels';
import { warehousesApi } from '../../../api/workshop/warehouses';
import { partsApi } from '../../../api/workshop/parts';
import { Plus, Search, X, Move } from 'lucide-vue-next';

const loading = ref(true);
const saving = ref(false);
const stockLevels = ref([]);
const warehouses = ref([]);
const parts = ref([]);
const filters = ref({ warehouse_id: '', low_stock: false });
const showCreateModal = ref(false);
const showAdjustModal = ref(false);
const createForm = ref({ warehouse_id: '', part_id: '', quantity: 0, min_quantity: 0 });
const adjustForm = ref({ warehouse_id: '', part_id: '', type: 'in', quantity: 0, notes: '' });

async function fetch() {
    loading.value = true;
    try {
        const params = { ...filters.value };
        if (!params.warehouse_id) delete params.warehouse_id;
        if (!params.low_stock) delete params.low_stock;
        else params.low_stock = 1;
        const { data } = await stockLevelsApi.index(params);
        stockLevels.value = data.stock_levels?.data || [];
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
}

async function loadWarehouses() {
    try {
        const { data } = await warehousesApi.list();
        warehouses.value = data;
    } catch (e) { console.error(e); }
}

async function loadParts() {
    try {
        const { data } = await partsApi.index({ per_page: 500 });
        parts.value = data.parts?.data || [];
    } catch (e) { console.error(e); }
}

function openCreateModal() {
    createForm.value = { warehouse_id: '', part_id: '', quantity: 0, min_quantity: 0 };
    showCreateModal.value = true;
}

function openAdjustModal() {
    adjustForm.value = { warehouse_id: '', part_id: '', type: 'in', quantity: 0, notes: '' };
    showAdjustModal.value = true;
}

async function createStock() {
    saving.value = true;
    try {
        await stockLevelsApi.store(createForm.value);
        showCreateModal.value = false;
        await fetch();
    } catch (e) {
        console.error(e);
    } finally {
        saving.value = false;
    }
}

async function adjustStock() {
    saving.value = true;
    try {
        await stockLevelsApi.adjust(adjustForm.value);
        showAdjustModal.value = false;
        await fetch();
    } catch (e) {
        console.error(e);
    } finally {
        saving.value = false;
    }
}

onMounted(async () => {
    await loadWarehouses();
    await loadParts();
    await fetch();
});
</script>