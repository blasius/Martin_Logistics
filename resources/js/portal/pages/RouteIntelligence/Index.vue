<template>
    <div class="p-6 space-y-6 bg-slate-50 min-h-screen font-sans">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-slate-800 flex items-center gap-2 uppercase tracking-tight">
                    <i class="lucide lucide-route text-indigo-600"></i>
                    Route Intelligence
                </h1>
                <p class="text-sm text-slate-500 font-medium italic">Deviation detection, trip oversight & auto-ticketing</p>
            </div>
            <div class="flex items-center gap-2">
                <button @click="batchCheck" :disabled="batchLoading" class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition font-bold text-sm flex items-center gap-2 shadow-lg">
                    <i class="lucide lucide-search w-4 h-4" :class="{'animate-spin': batchLoading}"></i> Check All Active
                </button>
                <button @click="fetchData" class="px-4 py-2 bg-slate-900 text-white rounded-lg hover:bg-black transition font-bold text-sm flex items-center gap-2 shadow-lg">
                    <i class="lucide lucide-refresh-cw w-4 h-4" :class="{'animate-spin': loading}"></i> Refresh
                </button>
            </div>
        </div>

        <div v-if="error" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm font-bold">
            {{ error }}
        </div>

        <div v-if="batchResult" class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-xl text-sm font-bold flex items-center gap-2">
            <i class="lucide lucide-activity w-4 h-4"></i>
            Checked {{ batchResult.checked }} active trips — found {{ batchResult.deviations_found }} deviation(s)
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white p-5 rounded-2xl shadow-sm border-t-4 border-t-red-600">
                <div class="text-[10px] font-black uppercase text-red-600 tracking-widest">Active Deviations</div>
                <div class="mt-1 text-3xl font-black text-slate-900">{{ stats.active_deviations || 0 }}</div>
                <div class="text-[10px] text-slate-400 mt-2 font-bold uppercase italic">Trips currently off-route</div>
            </div>
            <div class="bg-white p-5 rounded-2xl shadow-sm border-t-4 border-t-amber-500">
                <div class="text-[10px] font-black uppercase text-amber-600 tracking-widest">Unresolved Logs</div>
                <div class="mt-1 text-3xl font-black text-slate-900">{{ stats.unresolved_logs || 0 }}</div>
                <div class="text-[10px] text-slate-400 mt-2 font-bold uppercase italic">Not yet back on route</div>
            </div>
            <div class="bg-white p-5 rounded-2xl shadow-sm border-t-4 border-t-orange-500">
                <div class="text-[10px] font-black uppercase text-orange-600 tracking-widest">Total Deviation Logs</div>
                <div class="mt-1 text-3xl font-black text-slate-900">{{ stats.total_deviation_logs || 0 }}</div>
                <div class="text-[10px] text-slate-400 mt-2 font-bold uppercase italic">All-time records</div>
            </div>
            <div class="bg-white p-5 rounded-2xl shadow-sm border-t-4 border-t-red-600">
                <div class="text-[10px] font-black uppercase text-red-600 tracking-widest">Excessive Fuel Trips</div>
                <div class="mt-1 text-3xl font-black text-slate-900">{{ stats.excessive_fuel_trips || 0 }}</div>
                <div class="text-[10px] text-slate-400 mt-2 font-bold uppercase italic">>15% variance flagged</div>
            </div>
        </div>

        <div v-if="!loading" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="p-4 border-b bg-slate-50/50">
                    <h2 class="font-black text-slate-800 text-sm uppercase tracking-widest flex items-center gap-2">
                        <i class="lucide lucide-map-pin-off w-4 h-4 text-red-500"></i>
                        Recent Deviations
                    </h2>
                </div>
                <div v-if="stats.recent_deviations?.length" class="divide-y divide-slate-100">
                    <div v-for="d in stats.recent_deviations" :key="d.id" class="p-4 hover:bg-slate-50/80 transition">
                        <div class="flex justify-between items-start">
                            <div>
                                <div class="font-black text-slate-800 text-sm uppercase">{{ d.vehicle_plate || 'N/A' }}</div>
                                <div class="text-[10px] text-slate-400 font-bold">{{ d.driver_name || 'Unknown driver' }}</div>
                            </div>
                            <span :class="d.resolved_at ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600'" class="px-2 py-1 rounded-lg text-[9px] font-black uppercase">
                                {{ d.resolved_at ? 'Resolved' : 'Active' }}
                            </span>
                        </div>
                        <div class="mt-2 flex gap-4 text-[10px] font-bold text-slate-500">
                            <span>{{ Number(d.distance_meters).toFixed(0) }}m off route</span>
                            <span>{{ formatDate(d.detected_at) }}</span>
                        </div>
                    </div>
                </div>
                <div v-else class="p-8 text-center text-slate-300 italic text-sm">
                    No deviations recorded.
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="p-4 border-b bg-slate-50/50">
                    <h2 class="font-black text-slate-800 text-sm uppercase tracking-widest flex items-center gap-2">
                        <i class="lucide lucide-ticket w-4 h-4 text-amber-500"></i>
                        Auto-Created Fuel Tickets
                    </h2>
                </div>
                <div v-if="stats.recent_auto_tickets?.length" class="divide-y divide-slate-100">
                    <div v-for="t in stats.recent_auto_tickets" :key="t.id" class="p-4 hover:bg-slate-50/80 transition">
                        <div class="flex justify-between items-start">
                            <div>
                                <div class="font-black text-slate-800 text-sm">{{ t.reference }} — {{ t.title }}</div>
                                <div class="text-[10px] text-slate-400 font-bold">{{ t.status }}</div>
                            </div>
                            <span :class="priorityBadge(t.priority)" class="px-2 py-1 rounded-lg text-[9px] font-black uppercase">
                                {{ t.priority }}
                            </span>
                        </div>
                        <div class="mt-1 text-[10px] font-bold text-slate-500">{{ formatDate(t.created_at) }}</div>
                    </div>
                </div>
                <div v-else class="p-8 text-center text-slate-300 italic text-sm">
                    No fuel abuse tickets yet.
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { api } from '../../../plugins/axios';

const loading = ref(false);
const batchLoading = ref(false);
const stats = ref({});
const batchResult = ref(null);
const error = ref(null);

onMounted(() => fetchData());

async function fetchData() {
    loading.value = true;
    error.value = null;
    try {
        const { data } = await api.get('/portal/intelligence/dashboard');
        stats.value = data;
    } catch (e) {
        error.value = 'Failed to load route intelligence data.';
        console.error(e);
    }
    finally { loading.value = false; }
}

async function batchCheck() {
    batchLoading.value = true;
    batchResult.value = null;
    error.value = null;
    try {
        const { data } = await api.post('/portal/intelligence/batch-check');
        batchResult.value = data;
        await fetchData();
    } catch (e) {
        error.value = 'Batch check failed.';
        console.error(e);
    }
    finally { batchLoading.value = false; }
}

function formatDate(d) {
    if (!d) return '—';
    return new Date(d).toLocaleString();
}

function priorityBadge(p) {
    if (p === 'urgent') return 'bg-red-600 text-white';
    if (p === 'high') return 'bg-orange-500 text-white';
    return 'bg-slate-100 text-slate-600';
}
</script>
