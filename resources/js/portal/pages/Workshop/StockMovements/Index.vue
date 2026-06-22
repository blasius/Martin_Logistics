<template>
    <div class="p-6 bg-slate-50 min-h-screen space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-black text-slate-800 tracking-tight uppercase">Stock Movements</h1>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex flex-wrap gap-4">
            <select v-model="filters.type" @change="fetch" class="bg-slate-50 border-none rounded-xl text-xs font-bold px-3 py-2 outline-none">
                <option value="">All Types</option>
                <option value="in">Stock In</option>
                <option value="out">Stock Out</option>
            </select>
            <select v-model="filters.warehouse_id" @change="fetch" class="bg-slate-50 border-none rounded-xl text-xs font-bold px-3 py-2 outline-none">
                <option value="">All Warehouses</option>
                <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">{{ wh.name }}</option>
            </select>
        </div>

        <div v-if="loading" class="grid gap-3">
            <div v-for="i in 5" class="h-16 bg-white rounded-2xl animate-pulse border border-slate-100"></div>
        </div>

        <div v-else class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-slate-50/50 text-[10px] font-black text-slate-400 uppercase border-b">
                    <tr>
                        <th class="p-4">Date</th>
                        <th class="p-4">Part</th>
                        <th class="p-4">Warehouse</th>
                        <th class="p-4 text-right">Quantity</th>
                        <th class="p-4 text-center">Type</th>
                        <th class="p-4">By</th>
                        <th class="p-4">Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="m in movements" :key="m.id" class="border-b border-slate-50 hover:bg-slate-50/50">
                        <td class="p-4 text-xs font-bold text-slate-500">{{ formatDate(m.created_at) }}</td>
                        <td class="p-4 font-bold text-sm text-slate-800">{{ m.part?.name }}</td>
                        <td class="p-4 text-xs font-bold text-slate-500">{{ m.warehouse?.name }}</td>
                        <td class="p-4 text-right font-black text-sm" :class="m.type === 'in' ? 'text-emerald-600' : 'text-rose-600'">
                            {{ m.type === 'in' ? '+' : '-' }}{{ m.quantity }}
                        </td>
                        <td class="p-4 text-center">
                            <span class="text-[10px] font-black px-2 py-1 rounded-full" :class="m.type === 'in' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'">
                                {{ m.type === 'in' ? 'IN' : 'OUT' }}
                            </span>
                        </td>
                        <td class="p-4 text-xs text-slate-500">{{ m.user?.name }}</td>
                        <td class="p-4 text-xs text-slate-400 max-w-xs truncate">{{ m.notes }}</td>
                    </tr>
                    <tr v-if="movements.length === 0">
                        <td colspan="7" class="p-10 text-center text-slate-400 font-bold text-xs uppercase">No movements found</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { stockMovementsApi } from '../../../api/workshop/stock-movements';
import { warehousesApi } from '../../../api/workshop/warehouses';

const loading = ref(true);
const movements = ref([]);
const warehouses = ref([]);
const filters = ref({ type: '', warehouse_id: '' });

async function fetch() {
    loading.value = true;
    try {
        const params = { ...filters.value };
        if (!params.type) delete params.type;
        if (!params.warehouse_id) delete params.warehouse_id;
        const { data } = await stockMovementsApi.index(params);
        movements.value = data.movements?.data || [];
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
    } catch (e) {
        console.error(e);
    }
}

function formatDate(date) {
    if (!date) return '';
    return new Date(date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
}

onMounted(async () => {
    await loadWarehouses();
    await fetch();
});
</script>
