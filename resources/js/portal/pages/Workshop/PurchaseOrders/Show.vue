<template>
    <div class="p-6 bg-slate-50 min-h-screen space-y-6">
        <div class="flex items-center gap-3">
            <router-link to="/workshop/purchase-orders" class="text-indigo-600 hover:text-indigo-800"><ArrowLeft class="w-5 h-5" /></router-link>
            <h1 class="text-2xl font-black text-slate-800 tracking-tight uppercase">{{ po.reference }}</h1>
            <span class="text-[10px] font-black px-3 py-1.5 rounded-full" :class="statusBadge(po.status)">{{ po.status }}</span>
        </div>

        <div v-if="loading" class="h-64 bg-white rounded-2xl animate-pulse border border-slate-100"></div>

        <template v-else-if="po">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                        <h2 class="text-xs font-black text-slate-500 uppercase tracking-wider mb-4">Order Details</h2>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-[10px] font-black text-slate-400 uppercase">Vendor</span>
                                <p class="font-bold text-slate-800">{{ po.vendor?.name }}</p>
                                <p v-if="po.vendor?.tin" class="text-xs text-slate-400 font-mono">TIN: {{ po.vendor.tin }}</p>
                            </div>
                            <div>
                                <span class="text-[10px] font-black text-slate-400 uppercase">Order Date</span>
                                <p class="font-bold text-slate-800">{{ formatDate(po.order_date) }}</p>
                            </div>
                            <div>
                                <span class="text-[10px] font-black text-slate-400 uppercase">Expected Date</span>
                                <p class="font-bold text-slate-800">{{ formatDate(po.expected_date) || 'Not set' }}</p>
                            </div>
                            <div>
                                <span class="text-[10px] font-black text-slate-400 uppercase">Total Amount</span>
                                <p class="font-black text-xl text-slate-800">{{ formatAmount(po.total_amount) }}</p>
                            </div>
                        </div>
                        <div v-if="po.notes" class="mt-4">
                            <span class="text-[10px] font-black text-slate-400 uppercase">Notes</span>
                            <p class="text-sm text-slate-600 mt-1">{{ po.notes }}</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                        <h3 class="text-xs font-black text-slate-500 uppercase tracking-wider mb-4">Items</h3>
                        <table class="w-full text-left">
                            <thead class="text-[10px] font-black text-slate-400 uppercase border-b">
                                <tr>
                                    <th class="pb-3">Description</th>
                                    <th class="pb-3 text-right">Qty</th>
                                    <th class="pb-3 text-right">Received</th>
                                    <th class="pb-3 text-right">Unit Price</th>
                                    <th class="pb-3 text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in po.items" :key="item.id" class="border-b border-slate-50">
                                    <td class="py-3 text-sm font-bold text-slate-800">{{ item.description }}</td>
                                    <td class="py-3 text-right text-sm">{{ item.quantity }}</td>
                                    <td class="py-3 text-right text-sm font-bold" :class="item.received_quantity >= item.quantity ? 'text-emerald-600' : 'text-amber-600'">
                                        {{ item.received_quantity || 0 }}
                                    </td>
                                    <td class="py-3 text-right text-sm">{{ formatAmount(item.unit_price) }}</td>
                                    <td class="py-3 text-right text-sm font-bold">{{ formatAmount(item.total) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                        <h3 class="text-xs font-black text-slate-500 uppercase tracking-wider mb-4">Workflow</h3>
                        <div class="space-y-3">
                            <button v-if="can('send')" @click="sendPO" class="w-full py-3 bg-blue-600 text-white rounded-xl font-black text-xs uppercase shadow-lg">Send to Vendor</button>
                            <button v-if="can('confirm')" @click="confirmPO" class="w-full py-3 bg-indigo-600 text-white rounded-xl font-black text-xs uppercase">Confirm Order</button>
                            <div v-if="can('receive')">
                                <div v-for="(item, i) in receiveItems" :key="i" class="flex gap-2 mb-2">
                                    <span class="flex-1 text-xs font-bold text-slate-600 truncate">{{ item.description }}</span>
                                    <input v-model.number="item.receive_qty" type="number" step="0.01" :max="item.remaining" :placeholder="`Max ${item.remaining}`" class="w-20 p-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-bold">
                                </div>
                                <select v-model="receiveWarehouse" class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold mb-2">
                                    <option value="">Select Warehouse</option>
                                    <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">{{ wh.name }}</option>
                                </select>
                                <button @click="receivePO" :disabled="!receiveWarehouse" class="w-full py-3 bg-emerald-600 text-white rounded-xl font-black text-xs uppercase">Receive Items</button>
                            </div>
                            <button v-if="can('cancel')" @click="cancelPO" class="w-full py-3 bg-rose-100 text-rose-700 rounded-xl font-black text-xs uppercase">Cancel PO</button>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="po.repair_request" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h3 class="text-xs font-black text-slate-500 uppercase tracking-wider mb-2">Linked Repair Request</h3>
                <router-link :to="`/workshop/repair-requests/${po.repair_request.id}`" class="text-indigo-600 font-bold text-sm">{{ po.repair_request.reference }}</router-link>
            </div>
        </template>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { purchaseOrdersApi } from '../../../api/workshop/purchase-orders';
import { warehousesApi } from '../../../api/workshop/warehouses';
import { ArrowLeft } from 'lucide-vue-next';

const route = useRoute();
const loading = ref(true);
const po = ref(null);
const warehouses = ref([]);
const receiveWarehouse = ref('');

const receiveItems = computed(() => {
    if (!po.value?.items) return [];
    return po.value.items.map(item => ({
        po_item_id: item.id,
        description: item.description,
        remaining: item.quantity - (item.received_quantity || 0),
        receive_qty: 0,
    }));
});

async function load() {
    loading.value = true;
    try {
        const { data } = await purchaseOrdersApi.show(route.params.id);
        po.value = data;
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

function can(action) {
    if (!po.value) return false;
    const s = po.value.status;
    switch (action) {
        case 'send': return s === 'draft';
        case 'confirm': return s === 'sent';
        case 'receive': return s === 'confirmed' || s === 'partially_received';
        case 'cancel': return ['draft', 'sent', 'confirmed'].includes(s);
        default: return false;
    }
}

async function sendPO() {
    try {
        const { data } = await purchaseOrdersApi.send(po.value.id);
        po.value = data;
    } catch (e) { console.error(e); }
}

async function confirmPO() {
    try {
        const { data } = await purchaseOrdersApi.confirm(po.value.id);
        po.value = data;
    } catch (e) { console.error(e); }
}

async function receivePO() {
    const items = receiveItems.value.filter(i => i.receive_qty > 0);
    if (!items.length || !receiveWarehouse.value) return;
    try {
        const { data } = await purchaseOrdersApi.receive(po.value.id, {
            warehouse_id: receiveWarehouse.value,
            items: items.map(i => ({ po_item_id: i.po_item_id, quantity: i.receive_qty })),
        });
        po.value = data;
        receiveWarehouse.value = '';
    } catch (e) { console.error(e); }
}

async function cancelPO() {
    if (!confirm('Cancel this purchase order?')) return;
    try {
        const { data } = await purchaseOrdersApi.cancel(po.value.id);
        po.value = data;
    } catch (e) { console.error(e); }
}

function statusBadge(s) {
    const map = { draft: 'bg-slate-100 text-slate-600', sent: 'bg-blue-100 text-blue-700', confirmed: 'bg-indigo-100 text-indigo-700', partially_received: 'bg-amber-100 text-amber-700', received: 'bg-emerald-100 text-emerald-700', cancelled: 'bg-rose-100 text-rose-700' };
    return map[s] || 'bg-slate-100 text-slate-600';
}

function formatAmount(v) { return (v || v === 0) ? v.toLocaleString('en-US', { minimumFractionDigits: 2 }) : '-'; }
function formatDate(d) { return d ? new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : ''; }

onMounted(async () => {
    await loadWarehouses();
    await load();
});
</script>
