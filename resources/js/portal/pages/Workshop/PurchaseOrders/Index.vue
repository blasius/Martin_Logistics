<template>
    <div class="p-6 bg-slate-50 min-h-screen space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-black text-slate-800 tracking-tight uppercase">Purchase Orders</h1>
            <button @click="showCreateModal = true" class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl shadow-lg font-black text-xs flex items-center gap-2 uppercase">
                <Plus class="w-4 h-4" /> New PO
            </button>
        </div>

        <div class="grid grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 text-center">
                <p class="text-2xl font-black text-slate-800">{{ stats.total }}</p>
                <p class="text-[10px] font-black text-slate-400 uppercase">Total</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 text-center">
                <p class="text-2xl font-black text-amber-600">{{ stats.draft }}</p>
                <p class="text-[10px] font-black text-slate-400 uppercase">Draft</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 text-center">
                <p class="text-2xl font-black text-blue-600">{{ stats.sent }}</p>
                <p class="text-[10px] font-black text-slate-400 uppercase">Sent</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 text-center">
                <p class="text-2xl font-black text-emerald-600">{{ stats.received }}</p>
                <p class="text-[10px] font-black text-slate-400 uppercase">Received</p>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex flex-wrap gap-4">
            <div class="relative flex-1">
                <Search class="absolute left-3 top-2.5 w-4 h-4 text-slate-400" />
                <input v-model="filters.search" @input="debounceSearch" type="text" placeholder="Search reference or vendor..." class="w-full pl-10 p-2 bg-slate-50 border-none rounded-xl text-xs font-bold outline-none">
            </div>
            <select v-model="filters.status" @change="fetch" class="bg-slate-50 border-none rounded-xl text-xs font-bold px-3 outline-none">
                <option value="">All Statuses</option>
                <option value="draft">Draft</option>
                <option value="sent">Sent</option>
                <option value="confirmed">Confirmed</option>
                <option value="partially_received">Partially Received</option>
                <option value="received">Received</option>
                <option value="cancelled">Cancelled</option>
            </select>
        </div>

        <div v-if="loading" class="grid gap-4">
            <div v-for="i in 5" class="h-20 bg-white rounded-2xl animate-pulse border border-slate-100"></div>
        </div>

        <div v-else class="grid gap-3">
            <div v-for="po in purchaseOrders" :key="po.id" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 hover:shadow-md transition-shadow">
                <div class="flex items-start justify-between">
                    <div>
                        <router-link :to="`/workshop/purchase-orders/${po.id}`" class="font-black text-indigo-600 hover:text-indigo-800 text-sm">{{ po.reference }}</router-link>
                        <p class="text-xs font-bold text-slate-500">{{ po.vendor?.name }}</p>
                    </div>
                    <span class="text-[10px] font-black px-2 py-1 rounded-full" :class="statusBadge(po.status)">{{ po.status }}</span>
                </div>
                <div class="flex gap-4 mt-2 text-xs text-slate-400">
                    <span>Order: {{ formatDate(po.order_date) }}</span>
                    <span v-if="po.expected_date">Expected: {{ formatDate(po.expected_date) }}</span>
                    <span class="font-bold">Total: {{ formatAmount(po.total_amount) }}</span>
                </div>
            </div>
            <div v-if="purchaseOrders.length === 0" class="text-center py-20">
                <p class="text-slate-400 font-bold text-xs uppercase">No purchase orders found</p>
            </div>
        </div>

        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm overflow-y-auto" @click.self="showCreateModal = false">
            <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full border border-slate-200 my-8">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center">
                    <h3 class="font-black text-slate-800 uppercase text-sm">New Purchase Order</h3>
                    <button @click="showCreateModal = false" class="text-slate-400 hover:text-slate-600"><X class="w-5 h-5" /></button>
                </div>
                <form @submit.prevent="createPO" class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase">Vendor</label>
                            <select v-model="poForm.vendor_id" required class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                                <option value="">Select Vendor</option>
                                <option v-for="v in vendors" :key="v.id" :value="v.id">{{ v.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase">Expected Date</label>
                            <input v-model="poForm.expected_date" type="date" class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                        </div>
                    </div>
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase">Notes</label>
                        <textarea v-model="poForm.notes" rows="2" class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold"></textarea>
                    </div>
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase">Items</label>
                        <div v-for="(item, i) in poForm.items" :key="i" class="flex gap-2 mt-2">
                            <input v-model="item.description" placeholder="Description" required class="flex-1 p-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                            <input v-model.number="item.quantity" type="number" step="0.01" placeholder="Qty" required class="w-20 p-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                            <input v-model.number="item.unit_price" type="number" step="0.01" placeholder="Price" required class="w-24 p-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                            <button type="button" @click="poForm.items.splice(i, 1)" class="text-rose-500"><X class="w-4 h-4" /></button>
                        </div>
                        <button type="button" @click="poForm.items.push({ description: '', quantity: null, unit_price: null, part_id: null })" class="mt-2 text-xs font-bold text-indigo-600">+ Add Item</button>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="showCreateModal = false" class="flex-1 px-4 py-3 bg-slate-100 text-slate-600 text-xs font-black rounded-xl uppercase">Cancel</button>
                        <button type="submit" class="flex-1 px-4 py-3 bg-indigo-600 text-white text-xs font-black rounded-xl shadow-lg uppercase" :disabled="saving">
                            {{ saving ? 'Creating...' : 'Create PO' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { purchaseOrdersApi } from '../../../api/workshop/purchase-orders';
import { vendorsApi } from '../../../api/workshop/vendors';
import { Plus, Search, X } from 'lucide-vue-next';

const loading = ref(true);
const saving = ref(false);
const showCreateModal = ref(false);
const purchaseOrders = ref([]);
const vendors = ref([]);
const stats = ref({ total: 0, draft: 0, sent: 0, received: 0 });
const filters = ref({ search: '', status: '' });
const poForm = ref({ vendor_id: '', expected_date: '', notes: '', items: [] });

let debounceTimer;

function debounceSearch() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(fetch, 300);
}

async function fetch() {
    loading.value = true;
    try {
        const params = { ...filters.value };
        Object.keys(params).forEach(k => { if (!params[k]) delete params[k]; });
        const { data } = await purchaseOrdersApi.index(params);
        purchaseOrders.value = data.purchase_orders?.data || [];
        stats.value = data.stats || stats.value;
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
}

async function loadVendors() {
    try {
        const { data } = await vendorsApi.list();
        vendors.value = data;
    } catch (e) { console.error(e); }
}

async function createPO() {
    saving.value = true;
    try {
        await purchaseOrdersApi.store(poForm.value);
        showCreateModal.value = false;
        poForm.value = { vendor_id: '', expected_date: '', notes: '', items: [] };
        await fetch();
    } catch (e) {
        console.error(e);
    } finally {
        saving.value = false;
    }
}

function statusBadge(s) {
    const map = { draft: 'bg-slate-100 text-slate-600', sent: 'bg-blue-100 text-blue-700', confirmed: 'bg-indigo-100 text-indigo-700', partially_received: 'bg-amber-100 text-amber-700', received: 'bg-emerald-100 text-emerald-700', cancelled: 'bg-rose-100 text-rose-700' };
    return map[s] || 'bg-slate-100 text-slate-600';
}

function formatAmount(v) { return (v || v === 0) ? v.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '-'; }
function formatDate(d) { return d ? new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : ''; }

onMounted(async () => {
    await loadVendors();
    await fetch();
});
</script>
