<template>
    <div class="p-6 space-y-6 bg-slate-50 min-h-screen font-sans">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-slate-800 flex items-center gap-2 uppercase tracking-tight">
                    <i class="lucide lucide-gauge text-amber-600"></i>
                    Pump-to-Tank Variance
                </h1>
                <p class="text-sm text-slate-500 font-medium italic">Reconciliation: book stock vs physical stock</p>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <select v-model="tankFilter" @change="fetchData" class="border-slate-200 px-3 py-2 rounded-lg bg-white shadow-sm text-sm font-bold text-slate-700 outline-none border focus:ring-2 ring-indigo-500">
                    <option value="">All Tanks</option>
                    <option v-for="t in tanks" :key="t.id" :value="t.id">{{ t.name }}</option>
                </select>
                <input type="date" v-model="dateFrom" @change="fetchData" class="border-slate-200 px-3 py-2 rounded-lg bg-white shadow-sm text-sm font-bold text-slate-700 outline-none border focus:ring-2 ring-indigo-500" />
                <input type="date" v-model="dateTo" @change="fetchData" class="border-slate-200 px-3 py-2 rounded-lg bg-white shadow-sm text-sm font-bold text-slate-700 outline-none border focus:ring-2 ring-indigo-500" />
                <button @click="fetchData" class="px-4 py-2 bg-slate-900 text-white rounded-lg hover:bg-black transition font-bold text-sm flex items-center gap-2 shadow-lg">
                    <i class="lucide lucide-refresh-cw w-4 h-4" :class="{'animate-spin': loading}"></i> Refresh
                </button>
            </div>
        </div>

        <div v-if="!loading && !rows.length" class="text-center py-16 text-slate-400 italic text-sm">
            No fuel transactions found for the selected period.
        </div>

        <div v-if="rows.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white p-5 rounded-2xl shadow-sm border-t-4 border-t-emerald-500">
                <div class="text-[10px] font-black uppercase text-emerald-600 tracking-widest">Total Delivered</div>
                <div class="mt-1 text-3xl font-black text-slate-900">{{ formatLitres(summary.totalDelivered) }}</div>
            </div>
            <div class="bg-white p-5 rounded-2xl shadow-sm border-t-4 border-t-blue-500">
                <div class="text-[10px] font-black uppercase text-blue-600 tracking-widest">Total Dispensed</div>
                <div class="mt-1 text-3xl font-black text-slate-900">{{ formatLitres(summary.totalDispensed) }}</div>
            </div>
            <div class="bg-white p-5 rounded-2xl shadow-sm border-t-4 border-t-amber-500">
                <div class="text-[10px] font-black uppercase text-amber-600 tracking-widest">Net Variance</div>
                <div class="mt-1 text-3xl font-black" :class="summary.netVariance >= 0 ? 'text-red-600' : 'text-emerald-600'">
                    {{ summary.netVariance >= 0 ? '+' : '' }}{{ formatLitres(summary.netVariance) }}
                </div>
            </div>
            <div class="bg-white p-5 rounded-2xl shadow-sm border-t-4" :class="summary.avgVariancePercent > 2 ? 'border-t-red-600' : 'border-t-emerald-500'">
                <div class="text-[10px] font-black uppercase tracking-widest" :class="summary.avgVariancePercent > 2 ? 'text-red-600' : 'text-emerald-600'">Avg Variance %</div>
                <div class="mt-1 text-3xl font-black" :class="summary.avgVariancePercent > 2 ? 'text-red-600' : 'text-emerald-600'">{{ summary.avgVariancePercent }}%</div>
            </div>
        </div>

        <div v-if="rows.length" class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <table class="w-full text-left">
                <thead class="text-[10px] font-black text-slate-400 uppercase border-b bg-slate-50/50">
                    <tr>
                        <th class="p-4">Tank</th>
                        <th class="p-4 text-right">Opening</th>
                        <th class="p-4 text-right">Delivered</th>
                        <th class="p-4 text-right">Dispensed</th>
                        <th class="p-4 text-right">Closing</th>
                        <th class="p-4 text-right">Book Stock</th>
                        <th class="p-4 text-right">Physical Stock</th>
                        <th class="p-4 text-right">Variance</th>
                        <th class="p-4 text-right">Var %</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="r in rows" :key="r.tank_id" class="hover:bg-slate-50/80 transition">
                        <td class="p-4">
                            <div class="font-black text-slate-800 text-sm">{{ r.tank_name }}</div>
                            <div class="text-[10px] text-slate-400 font-bold uppercase">{{ r.fuel_type }}</div>
                        </td>
                        <td class="p-4 text-right font-mono font-bold text-slate-700">{{ formatLitres(r.opening_level) }}</td>
                        <td class="p-4 text-right font-mono font-bold text-emerald-600">{{ formatLitres(r.delivered) }}</td>
                        <td class="p-4 text-right font-mono font-bold text-blue-600">{{ formatLitres(r.dispensed) }}</td>
                        <td class="p-4 text-right font-mono font-bold text-slate-700">{{ formatLitres(r.closing_level) }}</td>
                        <td class="p-4 text-right font-mono font-bold text-slate-700">{{ formatLitres(r.book_stock) }}</td>
                        <td class="p-4 text-right font-mono font-bold text-slate-700">{{ formatLitres(r.physical_stock) }}</td>
                        <td class="p-4 text-right font-mono font-bold" :class="r.variance >= 0 ? 'text-red-600' : 'text-emerald-600'">
                            {{ r.variance >= 0 ? '+' : '' }}{{ formatLitres(r.variance) }}
                        </td>
                        <td class="p-4 text-right font-mono font-bold" :class="Math.abs(r.variance_percent) > 2 ? 'text-red-600' : 'text-slate-500'">
                            {{ r.variance_percent }}%
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { fuelAnalyticsApi } from '../../../api/fuel/analytics';
import { fuelTankApi } from '../../../api/fuel/tanks';

const loading = ref(false);
const rows = ref([]);
const tanks = ref([]);
const tankFilter = ref('');
const dateFrom = ref(new Date(Date.now() - 30 * 86400000).toISOString().slice(0, 10));
const dateTo = ref(new Date().toISOString().slice(0, 10));

const summary = computed(() => {
    const totalDelivered = rows.value.reduce((s, r) => s + Number(r.delivered), 0);
    const totalDispensed = rows.value.reduce((s, r) => s + Number(r.dispensed), 0);
    const totalVariance = rows.value.reduce((s, r) => s + Number(r.variance), 0);
    const avgPct = rows.value.length
        ? rows.value.reduce((s, r) => s + Math.abs(Number(r.variance_percent)), 0) / rows.value.length
        : 0;
    return {
        totalDelivered: Math.round(totalDelivered * 100) / 100,
        totalDispensed: Math.round(totalDispensed * 100) / 100,
        netVariance: Math.round(totalVariance * 100) / 100,
        avgVariancePercent: Math.round(avgPct * 100) / 100,
    };
});

onMounted(async () => {
    try {
        const { data } = await fuelTankApi.index();
        tanks.value = data;
    } catch (e) { console.error(e); }
    await fetchData();
});

async function fetchData() {
    loading.value = true;
    try {
        const params = {
            date_from: dateFrom.value,
            date_to: dateTo.value,
        };
        if (tankFilter.value) params.tank_id = tankFilter.value;
        const { data } = await fuelAnalyticsApi.pumpToTankVariance(params);
        rows.value = data;
    } catch (e) { console.error(e); }
    finally { loading.value = false; }
}

function formatLitres(n) {
    return Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + ' L';
}
</script>
