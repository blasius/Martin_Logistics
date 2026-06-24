<template>
    <div class="p-6 space-y-6 bg-slate-50 min-h-screen font-sans">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-slate-800 flex items-center gap-2 uppercase tracking-tight">
                    <i class="lucide lucide-trending-up text-emerald-600"></i>
                    Driver Fuel Efficiency
                </h1>
                <p class="text-sm text-slate-500 font-medium italic">Ratings, rankings & consumption analysis</p>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <select v-model="ratingFilter" @change="fetchRankings" class="border-slate-200 px-3 py-2 rounded-lg bg-white shadow-sm text-sm font-bold text-slate-700 outline-none border focus:ring-2 ring-indigo-500">
                    <option value="">All Ratings</option>
                    <option value="good">Good</option>
                    <option value="average">Average</option>
                    <option value="poor">Poor</option>
                </select>
                <select v-model="periodRange" @change="recalcDriverRatings" class="border-slate-200 px-3 py-2 rounded-lg bg-white shadow-sm text-sm font-bold text-slate-700 outline-none border focus:ring-2 ring-indigo-500">
                    <option value="7">Last 7 days</option>
                    <option value="30">Last 30 days</option>
                    <option value="90">Last 90 days</option>
                </select>
                <button @click="recalcDriverRatings" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-bold text-sm flex items-center gap-2 shadow-lg">
                    <i class="lucide lucide-refresh-cw w-4 h-4" :class="{'animate-spin': recalcLoading}"></i> Recalculate
                </button>
            </div>
        </div>

        <div v-if="!loading && !rankings.length && !consumptionData.length" class="text-center py-16 text-slate-400 italic text-sm">
            No driver efficiency data available for the selected period.
        </div>

        <div v-if="rankings.length" class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="p-4 border-b bg-slate-50/50">
                <h2 class="font-black text-slate-800 text-sm uppercase tracking-widest flex items-center gap-2">
                    <i class="lucide lucide-users w-4 h-4 text-indigo-500"></i>
                    Driver Rankings
                    <span class="text-[10px] font-normal text-slate-400 normal-case">(ordered by avg variance)</span>
                </h2>
            </div>
            <table class="w-full text-left">
                <thead class="text-[10px] font-black text-slate-400 uppercase border-b">
                    <tr>
                        <th class="p-4">Driver</th>
                        <th class="p-4 text-center">Rating</th>
                        <th class="p-4 text-right">Avg Variance %</th>
                        <th class="p-4 text-right">Total Trips</th>
                        <th class="p-4 text-right">Flagged Trips</th>
                        <th class="p-4 text-right">Period</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="r in rankings" :key="r.id" class="hover:bg-slate-50/80 transition">
                        <td class="p-4">
                            <div class="font-black text-slate-800 text-sm">{{ r.driver?.user?.name || 'Unknown' }}</div>
                        </td>
                        <td class="p-4 text-center">
                            <span :class="ratingBadge(r.rating)" class="px-2 py-1 rounded-lg text-[9px] font-black uppercase">
                                {{ r.rating }}
                            </span>
                        </td>
                        <td class="p-4 text-right font-mono font-bold" :class="Math.abs(r.avg_variance_percent) > 5 ? 'text-red-600' : 'text-emerald-600'">
                            {{ Number(r.avg_variance_percent).toFixed(2) }}%
                        </td>
                        <td class="p-4 text-right font-mono font-bold text-slate-700">{{ r.total_trips }}</td>
                        <td class="p-4 text-right font-mono font-bold" :class="r.flagged_trips > 0 ? 'text-red-600' : 'text-slate-500'">{{ r.flagged_trips }}</td>
                        <td class="p-4 text-right text-[10px] text-slate-400 font-bold">{{ r.period_start }} — {{ r.period_end }}</td>
                    </tr>
                </tbody>
            </table>
            <div v-if="pagination.last_page > 1" class="p-4 border-t flex justify-between items-center bg-slate-50">
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Page {{ pagination.current_page }} / {{ pagination.last_page }}</span>
                <div class="flex gap-2">
                    <button :disabled="!pagination.prev_page_url" @click="fetchRankings(pagination.current_page - 1)" class="px-4 py-2 border bg-white rounded-xl text-[10px] font-black disabled:opacity-30 uppercase">Prev</button>
                    <button :disabled="!pagination.next_page_url" @click="fetchRankings(pagination.current_page + 1)" class="px-4 py-2 border bg-white rounded-xl text-[10px] font-black disabled:opacity-30 uppercase">Next</button>
                </div>
            </div>
        </div>

        <div v-if="consumptionData.length" class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="p-4 border-b bg-slate-50/50 flex justify-between items-center">
                <h2 class="font-black text-slate-800 text-sm uppercase tracking-widest flex items-center gap-2">
                    <i class="lucide lucide-bar-chart-3 w-4 h-4 text-amber-500"></i>
                    Trip Consumption Analysis
                </h2>
                <div class="flex gap-2">
                    <select v-model="consumptionFlag" @change="fetchConsumption" class="border px-2 py-1 rounded-lg text-[10px] font-bold bg-white">
                        <option value="">All Flags</option>
                        <option value="normal">Normal</option>
                        <option value="caution">Caution</option>
                        <option value="excessive">Excessive</option>
                    </select>
                </div>
            </div>
            <table class="w-full text-left">
                <thead class="text-[10px] font-black text-slate-400 uppercase border-b">
                    <tr>
                        <th class="p-4">Vehicle</th>
                        <th class="p-4 text-right">Distance</th>
                        <th class="p-4 text-right">Fuel Used</th>
                        <th class="p-4 text-right">Expected</th>
                        <th class="p-4 text-right">Variance</th>
                        <th class="p-4 text-right">Var %</th>
                        <th class="p-4 text-center">Flag</th>
                        <th class="p-4 text-right">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="c in consumptionData" :key="c.id" class="hover:bg-slate-50/80 transition">
                        <td class="p-4">
                            <div class="font-black text-slate-800 text-sm uppercase">{{ c.vehicle?.plate_number || '—' }}</div>
                        </td>
                        <td class="p-4 text-right font-mono font-bold text-slate-700">{{ Number(c.distance_km).toLocaleString() }} km</td>
                        <td class="p-4 text-right font-mono font-bold text-blue-600">{{ Number(c.fuel_used).toFixed(1) }} L</td>
                        <td class="p-4 text-right font-mono font-bold text-slate-600">{{ Number(c.expected_consumption).toFixed(1) }} L</td>
                        <td class="p-4 text-right font-mono font-bold" :class="Number(c.variance_liters) >= 0 ? 'text-red-600' : 'text-emerald-600'">
                            {{ Number(c.variance_liters) >= 0 ? '+' : '' }}{{ Number(c.variance_liters).toFixed(1) }}
                        </td>
                        <td class="p-4 text-right font-mono font-bold" :class="flagColor(c.flag)">{{ Number(c.variance_percent).toFixed(2) }}%</td>
                        <td class="p-4 text-center">
                            <span :class="flagBadge(c.flag)" class="px-2 py-0.5 rounded text-[9px] font-black uppercase">{{ c.flag }}</span>
                        </td>
                        <td class="p-4 text-right text-[10px] text-slate-400 font-bold">{{ c.created_at?.slice(0, 10) }}</td>
                    </tr>
                </tbody>
            </table>
            <div v-if="consumptionPagination.last_page > 1" class="p-4 border-t flex justify-between items-center bg-slate-50">
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Page {{ consumptionPagination.current_page }} / {{ consumptionPagination.last_page }}</span>
                <div class="flex gap-2">
                    <button :disabled="!consumptionPagination.prev_page_url" @click="fetchConsumption(consumptionPagination.current_page - 1)" class="px-4 py-2 border bg-white rounded-xl text-[10px] font-black disabled:opacity-30 uppercase">Prev</button>
                    <button :disabled="!consumptionPagination.next_page_url" @click="fetchConsumption(consumptionPagination.current_page + 1)" class="px-4 py-2 border bg-white rounded-xl text-[10px] font-black disabled:opacity-30 uppercase">Next</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { fuelAnalyticsApi } from '../../../api/fuel/analytics';

const loading = ref(false);
const recalcLoading = ref(false);
const rankings = ref([]);
const consumptionData = ref([]);
const ratingFilter = ref('');
const periodRange = ref('30');
const consumptionFlag = ref('');

const pagination = ref({ current_page: 1, last_page: 1, prev_page_url: null, next_page_url: null });
const consumptionPagination = ref({ current_page: 1, last_page: 1, prev_page_url: null, next_page_url: null });

onMounted(() => {
    fetchRankings();
    fetchConsumption();
});

async function fetchRankings(page = 1) {
    loading.value = true;
    try {
        const params = { page, per_page: 20 };
        if (ratingFilter.value) params.rating = ratingFilter.value;
        const { data } = await fuelAnalyticsApi.driverRankings(params);
        rankings.value = data.data || [];
        pagination.value = {
            current_page: data.current_page,
            last_page: data.last_page,
            prev_page_url: data.prev_page_url,
            next_page_url: data.next_page_url,
        };
    } catch (e) { console.error(e); }
    finally { loading.value = false; }
}

async function fetchConsumption(page = 1) {
    try {
        const params = { page, per_page: 20 };
        if (consumptionFlag.value) params.flag = consumptionFlag.value;
        const { data } = await fuelAnalyticsApi.consumptionReport(params);
        consumptionData.value = data.data || [];
        consumptionPagination.value = {
            current_page: data.current_page,
            last_page: data.last_page,
            prev_page_url: data.prev_page_url,
            next_page_url: data.next_page_url,
        };
    } catch (e) { console.error(e); }
}

async function recalcDriverRatings() {
    recalcLoading.value = true;
    try {
        const days = Number(periodRange.value);
        const periodEnd = new Date().toISOString().slice(0, 10);
        const periodStart = new Date(Date.now() - days * 86400000).toISOString().slice(0, 10);
        await fuelAnalyticsApi.rateDriver({ driver_id: 0, period_start: periodStart, period_end: periodEnd });
        await fetchRankings();
    } catch (e) { console.error(e); }
    finally { recalcLoading.value = false; }
}

function ratingBadge(rating) {
    if (rating === 'good') return 'bg-emerald-50 text-emerald-600';
    if (rating === 'poor') return 'bg-red-50 text-red-600';
    return 'bg-amber-50 text-amber-600';
}

function flagBadge(flag) {
    if (flag === 'normal') return 'bg-emerald-50 text-emerald-600';
    if (flag === 'excessive') return 'bg-red-50 text-red-600';
    return 'bg-amber-50 text-amber-600';
}

function flagColor(flag) {
    if (flag === 'normal') return 'text-emerald-600';
    if (flag === 'excessive') return 'text-red-600';
    return 'text-amber-600';
}
</script>
