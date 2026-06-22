<template>
    <div class="p-6 bg-slate-50 min-h-screen space-y-6">
        <header class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-black text-slate-800 tracking-tight uppercase">Workshop Dashboard</h1>
                <p class="text-xs font-bold text-slate-400">Maintenance & Repair Overview</p>
            </div>
            <button @click="load" class="p-2 bg-white rounded-xl border border-slate-200 hover:bg-slate-50">
                <RefreshCw class="w-4 h-4 text-slate-500" />
            </button>
        </header>

        <div v-if="loading" class="grid grid-cols-5 gap-4">
            <div v-for="i in 5" class="h-24 bg-white rounded-2xl animate-pulse border border-slate-100"></div>
        </div>

        <div v-else class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <p class="text-[10px] font-black text-slate-400 uppercase">Pending Approval</p>
                <p class="text-3xl font-black text-amber-600 mt-1">{{ data.stats.pending_approval }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <p class="text-[10px] font-black text-slate-400 uppercase">In Progress</p>
                <p class="text-3xl font-black text-blue-600 mt-1">{{ data.stats.in_progress }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <p class="text-[10px] font-black text-slate-400 uppercase">Completed</p>
                <p class="text-3xl font-black text-emerald-600 mt-1">{{ data.stats.completed }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <p class="text-[10px] font-black text-slate-400 uppercase">Released</p>
                <p class="text-3xl font-black text-indigo-600 mt-1">{{ data.stats.released }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5" :class="data.stats.critical > 0 ? 'border-rose-300 bg-rose-50' : ''">
                <p class="text-[10px] font-black text-slate-400 uppercase">Critical</p>
                <p class="text-3xl font-black mt-1" :class="data.stats.critical > 0 ? 'text-rose-600' : 'text-slate-800'">{{ data.stats.critical }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h3 class="text-xs font-black text-slate-500 uppercase tracking-wider mb-4">Recent Repair Requests</h3>
                <div v-if="data.recent_requests.length === 0" class="text-center py-8">
                    <p class="text-slate-400 font-bold text-xs uppercase">No requests yet</p>
                </div>
                <div v-for="rr in data.recent_requests" :key="rr.id" class="flex items-center justify-between py-3 border-b border-slate-50 last:border-0">
                    <div>
                        <router-link :to="`/workshop/repair-requests/${rr.id}`" class="font-black text-sm text-indigo-600 hover:text-indigo-800">{{ rr.reference }}</router-link>
                        <p class="text-xs text-slate-400 font-medium">{{ rr.vehicle?.plate_number }}</p>
                    </div>
                    <span class="text-[10px] font-black px-2 py-1 rounded-full uppercase" :class="statusBadge(rr.status)">
                        {{ rr.status }}
                    </span>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h3 class="text-xs font-black text-slate-500 uppercase tracking-wider mb-4">Released Pool</h3>
                <div v-if="data.released_pool.length === 0" class="text-center py-8">
                    <p class="text-slate-400 font-bold text-xs uppercase">No vehicles released</p>
                </div>
                <div v-for="rl in data.released_pool" :key="rl.id" class="flex items-center justify-between py-3 border-b border-slate-50 last:border-0">
                    <div>
                        <p class="font-black text-sm text-slate-800">{{ rl.repair_request?.vehicle?.plate_number || 'N/A' }}</p>
                        <p class="text-xs text-slate-400">by {{ rl.released_by?.name }} · {{ formatDate(rl.released_at) }}</p>
                    </div>
                    <span v-if="rl.unresolved_issues" class="text-[10px] text-rose-500 font-black">Has issues</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h3 class="text-xs font-black text-slate-500 uppercase tracking-wider mb-4">Low Stock</h3>
                <div v-if="data.low_stock.length === 0" class="text-center py-8">
                    <p class="text-slate-400 font-bold text-xs uppercase">All stock levels healthy</p>
                </div>
                <div v-for="item in data.low_stock" :key="item.id" class="flex items-center justify-between py-2 border-b border-slate-50 last:border-0">
                    <div>
                        <p class="font-bold text-xs text-slate-800">{{ item.part?.name }}</p>
                        <p class="text-[10px] text-slate-400">{{ item.warehouse?.name }}</p>
                    </div>
                    <span class="text-xs font-black text-amber-600">{{ item.quantity }} / {{ item.min_quantity }}</span>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h3 class="text-xs font-black text-slate-500 uppercase tracking-wider mb-4">Out of Stock</h3>
                <div v-if="data.out_of_stock.length === 0" class="text-center py-8">
                    <p class="text-slate-400 font-bold text-xs uppercase">Nothing out of stock</p>
                </div>
                <div v-for="item in data.out_of_stock" :key="item.id" class="flex items-center justify-between py-2 border-b border-slate-50 last:border-0">
                    <div>
                        <p class="font-bold text-xs text-slate-800">{{ item.part?.name }}</p>
                        <p class="text-[10px] text-slate-400">{{ item.warehouse?.name }}</p>
                    </div>
                    <span class="text-xs font-black text-rose-600">0</span>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h3 class="text-xs font-black text-slate-500 uppercase tracking-wider mb-4">Mechanics Workload</h3>
                <div v-if="data.mechanics_workload.length === 0" class="text-center py-8">
                    <p class="text-slate-400 font-bold text-xs uppercase">No mechanics found</p>
                </div>
                <div v-for="m in data.mechanics_workload" :key="m.id" class="flex items-center justify-between py-2 border-b border-slate-50 last:border-0">
                    <p class="font-bold text-xs text-slate-800">{{ m.name }}</p>
                    <span class="text-xs font-black" :class="m.active_jobs > 2 ? 'text-rose-600' : 'text-emerald-600'">{{ m.active_jobs }} active</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { workshopDashboardApi } from '../../api/workshop/dashboard';
import { RefreshCw } from 'lucide-vue-next';

const loading = ref(true);
const data = ref({
    stats: { pending_approval: 0, in_progress: 0, completed: 0, released: 0, critical: 0 },
    recent_requests: [],
    released_pool: [],
    low_stock: [],
    out_of_stock: [],
    mechanics_workload: [],
});

async function load() {
    loading.value = true;
    try {
        const { data: res } = await workshopDashboardApi.get();
        data.value = res;
    } catch (e) {
        console.error('Failed to load workshop dashboard:', e);
    } finally {
        loading.value = false;
    }
}

function statusBadge(status) {
    const map = {
        draft: 'bg-slate-100 text-slate-600',
        pending_approval: 'bg-amber-100 text-amber-700',
        approved: 'bg-blue-100 text-blue-700',
        in_progress: 'bg-indigo-100 text-indigo-700',
        completed: 'bg-emerald-100 text-emerald-700',
        released: 'bg-green-100 text-green-700',
        cancelled: 'bg-rose-100 text-rose-700',
    };
    return map[status] || 'bg-slate-100 text-slate-600';
}

function formatDate(date) {
    if (!date) return '';
    return new Date(date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
}

onMounted(load);
</script>
