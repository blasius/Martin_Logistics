<template>
    <div class="p-6 space-y-6 bg-slate-50 min-h-screen font-sans">

        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-slate-800 flex items-center gap-3">
                    <div class="relative flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-indigo-600"></span>
                    </div>
                    Live Control Tower
                </h1>
                <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mt-1">Fleet Readiness & Operational Health</p>
            </div>

            <div class="flex items-center gap-3">
                <button @click="refreshData" class="px-4 py-2 bg-white border border-slate-200 rounded-xl shadow-sm hover:bg-slate-50 transition font-bold text-sm flex items-center gap-2">
                    <i class="lucide lucide-refresh-cw w-4 h-4" :class="{'animate-spin': loading}"></i>
                    Sync Fleet
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <div v-for="card in summary" :key="card.id"
                 class="p-4 bg-white shadow-sm rounded-2xl border border-slate-100 flex items-center gap-4 border-b-4"
                 :style="{ borderBottomColor: getCardColor(card.color) }">
                <div :class="card.color" class="p-3 bg-slate-50 rounded-xl">
                    <i :class="card.icon" class="w-6 h-6"></i>
                </div>
                <div>
                    <p class="text-slate-400 text-[10px] font-black uppercase tracking-wider">{{ card.title }}</p>
                    <p class="text-2xl font-black text-slate-800">{{ card.value }}</p>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            <button v-for="f in filterConfigs" :key="f.id"
                    @click="activeFilter = f.id"
                    :class="activeFilter === f.id ? 'bg-slate-800 text-white shadow-lg scale-105' : 'bg-white text-slate-600 border-slate-200 hover:border-indigo-300'"
                    class="px-4 py-2 rounded-xl border shadow-sm text-[11px] font-black transition-all flex items-center gap-2 uppercase tracking-tight">
                <i :class="f.icon" class="w-4 h-4"></i>
                {{ f.label }}
            </button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-4 border-b border-slate-50 bg-slate-50/50 flex justify-between items-center">
                        <h2 class="text-sm font-black text-slate-700 uppercase">Live Unit Status</h2>
                        <input v-model="search" type="text" placeholder="Filter plate..." class="px-3 py-1.5 bg-white border rounded-lg text-xs outline-none focus:ring-2 ring-indigo-500 w-40">
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-slate-50 text-[10px] font-black text-slate-400 uppercase border-b">
                            <tr>
                                <th class="p-4">Unit</th>
                                <th class="p-4">Health / Compliance</th>
                                <th class="p-4">Telemetry</th>
                                <th class="p-4">Actions</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                            <tr v-for="v in filteredVehicles" :key="v.id" class="hover:bg-slate-50/50 transition-colors">
                                <td class="p-4">
                                    <div class="font-black text-slate-800 uppercase">{{ v.plate }}</div>
                                    <div class="text-[10px] font-bold text-slate-400">{{ v.type }}</div>
                                </td>
                                <td class="p-4">
                                    <div class="flex flex-wrap gap-1">
                                        <span v-if="v.in_garage" class="px-2 py-0.5 rounded bg-orange-100 text-orange-700 text-[9px] font-black uppercase">Garage</span>
                                        <span v-if="v.has_fines" class="px-2 py-0.5 rounded bg-red-100 text-red-700 text-[9px] font-black uppercase">Unpaid Fines</span>
                                        <span v-if="v.license_warning" class="px-2 py-0.5 rounded bg-amber-100 text-amber-700 text-[9px] font-black uppercase">Doc Expiry</span>
                                        <span v-if="!v.in_garage && !v.has_fines && !v.license_warning" class="px-2 py-0.5 rounded bg-green-100 text-green-700 text-[9px] font-black uppercase">Ready</span>
                                    </div>
                                </td>
                                <td class="p-4">
                                    <div class="flex flex-col">
                                        <span class="text-xs font-black text-slate-800">{{ v.speed || 0 }} km/h</span>
                                        <span class="text-[10px] font-bold" :class="v.ignition ? 'text-green-500' : 'text-slate-300'">
                                                {{ v.ignition ? 'Engine Running' : 'Stopped' }}
                                            </span>
                                    </div>
                                </td>
                                <td class="p-4">
                                    <button class="p-2 hover:bg-indigo-50 rounded-lg text-indigo-600 transition">
                                        <i class="lucide lucide-arrow-right-circle w-5 h-5"></i>
                                    </button>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="bg-indigo-900 rounded-2xl p-5 shadow-xl text-white">
                    <h2 class="text-xs font-black uppercase tracking-widest mb-4 flex items-center gap-2 opacity-80">
                        <i class="lucide lucide-navigation w-4 h-4"></i> Active Trip Progress
                    </h2>
                    <div class="space-y-6">
                        <div v-for="trip in trips" :key="trip.id" class="space-y-2">
                            <div class="flex justify-between text-xs">
                                <span class="font-black">{{ trip.vehicle }}</span>
                                <span class="font-bold opacity-60">{{ trip.destination }}</span>
                            </div>
                            <div class="w-full bg-white/10 rounded-full h-1.5">
                                <div class="bg-indigo-400 h-1.5 rounded-full shadow-[0_0_8px_rgba(129,140,248,0.8)]" :style="{ width: trip.progress + '%' }"></div>
                            </div>
                            <div class="flex justify-between text-[10px] font-bold opacity-50 uppercase">
                                <span>Status: {{ trip.status }}</span>
                                <span>{{ trip.progress }}%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                    <h2 class="text-xs font-black text-slate-800 uppercase tracking-widest mb-4">Immediate Attention</h2>
                    <div class="space-y-3">
                        <div v-for="alert in alerts" :key="alert.id" class="p-3 bg-red-50 border-l-4 border-red-500 rounded-r-xl">
                            <p class="text-xs font-black text-slate-800">{{ alert.plate }}</p>
                            <p class="text-[10px] font-bold text-red-600 uppercase">{{ alert.reason }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { api } from "@/plugins/axios.js";

// State
const summary = ref([]);
const vehicles = ref([]);
const trips = ref([]);
const alerts = ref([]);
const loading = ref(false);
const search = ref('');
const activeFilter = ref('all');

const filterConfigs = [
    { id: 'all', label: 'All Units', icon: 'lucide-layout-grid' },
    { id: 'moving', label: 'Moving', icon: 'lucide-zap' },
    { id: 'garage', label: 'In Garage', icon: 'lucide-wrench' },
    { id: 'warning', label: 'Issues', icon: 'lucide-alert-octagon' },
];

// Computed
const filteredVehicles = computed(() => {
    let list = vehicles.value;
    if (activeFilter.value === 'moving') list = list.filter(v => v.speed > 0);
    if (activeFilter.value === 'garage') list = list.filter(v => v.in_garage);
    if (activeFilter.value === 'warning') list = list.filter(v => v.has_fines || v.license_warning);

    if (search.value) {
        list = list.filter(v => v.plate.toLowerCase().includes(search.value.toLowerCase()));
    }
    return list;
});

// Helper for dynamic colors
const getCardColor = (colorClass) => {
    if (colorClass.includes('indigo')) return '#4f46e5';
    if (colorClass.includes('orange')) return '#f97316';
    if (colorClass.includes('green')) return '#22c55e';
    if (colorClass.includes('red')) return '#ef4444';
    return '#64748b';
};

// Actions
async function refreshData() {
    loading.value = true;
    try {
        const { data } = await api.get('/api/portal/control-tower/stats');
        summary.value = data.summary;
        // In real app, these come from your controller too
        vehicles.value = data.vehicles || [];
        trips.value = data.trips || [];
        alerts.value = data.alerts || [];
    } catch (error) {
        console.error("Fetch failed", error);
    } finally {
        loading.value = false;
    }
}

onMounted(() => {
    refreshData();
    // Auto-refresh every 30 seconds
    setInterval(refreshData, 30000);
});
</script>

<style scoped>
.lucide { width: 1.25rem; height: 1.25rem; stroke-width: 2.5px; }
</style>
