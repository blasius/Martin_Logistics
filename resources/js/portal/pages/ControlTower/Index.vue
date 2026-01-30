<template>
    <div class="p-8 bg-[#0a0c10] min-h-screen text-slate-300 font-sans selection:bg-indigo-500 selection:text-white">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-10 gap-4">
            <div>
                <h1 class="text-4xl font-black text-white tracking-tighter uppercase leading-none">Fleet Command</h1>
                <p class="text-indigo-500 font-bold text-[10px] tracking-[0.3em] mt-2 flex items-center gap-2">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-indigo-500"></span>
                    </span>
                    LIVE TELEMETRY SNAPSHOT
                </p>
            </div>
            <div class="bg-slate-900/40 border border-slate-800 px-4 py-2 rounded-2xl text-right">
                <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Active Fleet</p>
                <p class="text-xl font-black text-emerald-500">{{ totalUnits }} Units</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
            <div class="space-y-6">
                <div class="flex items-center gap-3 border-b border-slate-800/50 pb-4">
                    <div class="p-2 bg-indigo-500/10 rounded-xl"><Fuel class="text-indigo-500 w-5 h-5" /></div>
                    <h2 class="text-lg font-black text-white uppercase tracking-tight">Fuel Intelligence</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div @click="openModal('Critical Fuel Levels', 'critical-fuel')"
                         class="bg-slate-900/40 border border-slate-800 p-5 rounded-[2rem] cursor-pointer hover:bg-slate-800/60 hover:border-rose-500/50 transition-all duration-300 group">
                        <span class="text-4xl font-black block leading-none mb-1 text-rose-500 group-hover:scale-110 transition-transform origin-left">{{ stats.criticalFuel.length }}</span>
                        <p class="text-[11px] font-black uppercase tracking-widest text-slate-400 leading-tight">Critical Low</p>
                        <div class="mt-4 space-y-1.5 pt-4 border-t border-slate-800/50">
                            <div v-for="v in stats.criticalFuel.slice(0, 5)" :key="v.id" class="flex justify-between text-[10px] font-bold uppercase tracking-tighter">
                                <span class="text-slate-500">{{ v.plate }}</span><span class="text-rose-400">{{ v.val }}%</span>
                            </div>
                        </div>
                    </div>

                    <div @click="openModal('Fuel Drains & Theft', 'thefts')"
                         class="bg-slate-900/40 border border-rose-900/20 p-5 rounded-[2rem] cursor-pointer hover:bg-rose-950/20 hover:border-rose-500/50 transition-all duration-300 group">
                        <span class="text-4xl font-black block leading-none mb-1 text-rose-600 group-hover:animate-pulse">{{ stats.thefts.length }}</span>
                        <p class="text-[11px] font-black uppercase tracking-widest text-slate-400 leading-tight">Thefts/Drains</p>
                        <div class="mt-4 space-y-1.5 pt-4 border-t border-slate-800/50">
                            <div v-for="(v, i) in stats.thefts.slice(0, 5)" :key="i" class="flex justify-between text-[10px] font-bold uppercase tracking-tighter">
                                <span class="text-slate-500">{{ v.plate }}</span><span class="text-rose-500">-{{ v.val }}L</span>
                            </div>
                        </div>
                    </div>

                    <div @click="openModal('Recent Fillings', 'fillings')"
                         class="bg-slate-900/40 border border-slate-800 p-5 rounded-[2rem] cursor-pointer hover:bg-slate-800/60 hover:border-emerald-500/50 transition-all duration-300 group">
                        <span class="text-4xl font-black block leading-none mb-1 text-emerald-500">{{ stats.fillings.length }}</span>
                        <p class="text-[11px] font-black uppercase tracking-widest text-slate-400 leading-tight">Recent Refills</p>
                        <div class="mt-4 space-y-1.5 pt-4 border-t border-slate-800/50">
                            <div v-for="(v, i) in stats.fillings.slice(0, 5)" :key="i" class="flex justify-between text-[10px] font-bold uppercase tracking-tighter text-slate-500">
                                <span>{{ v.plate }}</span><span class="text-emerald-500">+{{ v.val }}L</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="flex items-center gap-3 border-b border-slate-800/50 pb-4">
                    <div class="p-2 bg-amber-500/10 rounded-xl"><Clock class="text-amber-500 w-5 h-5" /></div>
                    <h2 class="text-lg font-black text-white uppercase tracking-tight">Operations & Delays</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div @click="openModal('Active Breakdowns', 'breakdowns')" class="bg-slate-900/40 border border-slate-800 p-6 rounded-[2rem] cursor-pointer hover:bg-slate-800/60 transition-all group">
                        <div class="flex justify-between items-start">
                            <div><span class="text-4xl font-black block leading-none mb-1 text-rose-500">{{ stats.breakdowns.length }}</span><p class="text-[11px] font-black uppercase tracking-widest text-slate-400">Breakdowns</p></div>
                            <Activity class="text-slate-700 group-hover:text-rose-500 transition-colors" />
                        </div>
                    </div>

                    <div @click="openModal('Stationary Vehicles (24h+)', 'long-stops')" class="bg-slate-900/40 border border-slate-800 p-6 rounded-[2rem] cursor-pointer hover:bg-slate-800/60 transition-all group">
                        <div class="flex justify-between items-start">
                            <div><span class="text-4xl font-black block leading-none mb-1 text-white">{{ stats.longStops.length }}</span><p class="text-[11px] font-black uppercase tracking-widest text-slate-400">24h+ Stops</p></div>
                            <Timer class="text-slate-700" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Transition name="fade">
            <div v-if="activeModal" class="fixed inset-0 bg-black/90 backdrop-blur-md flex items-center justify-center p-6 z-50">
                <div class="bg-[#0f1115] border border-slate-800 w-full max-w-5xl rounded-[3rem] p-10 shadow-2xl flex flex-col max-h-[90vh]">
                    <div class="flex justify-between items-start mb-6">
                        <div><p class="text-indigo-500 text-[10px] font-black uppercase tracking-[0.4em] mb-2">Detailed Report</p><h3 class="text-4xl font-black text-white uppercase tracking-tighter">{{ activeModalTitle }}</h3></div>
                        <button @click="activeModal = null" class="bg-slate-800 hover:bg-rose-600 text-white p-3 rounded-2xl transition-all"><X /></button>
                    </div>

                    <div class="flex flex-wrap gap-4 mb-8">
                        <div class="flex-1 flex gap-2">
                            <div class="bg-slate-900 border border-slate-800 rounded-2xl px-4 py-3 flex gap-4 items-center">
                                <input type="date" v-model="filters.date" class="bg-transparent text-xs text-white outline-none">
                            </div>
                            <button @click="fetchReport" class="bg-indigo-600 px-6 rounded-2xl text-[10px] font-black uppercase tracking-widest">Update View</button>
                        </div>
                        <input type="text" placeholder="Search plate or driver..." class="bg-slate-900 border border-slate-800 rounded-2xl px-6 py-4 text-sm outline-none w-full md:w-64">
                    </div>

                    <div class="bg-black/40 rounded-[2rem] border border-slate-800/50 p-6 overflow-y-auto flex-1">
                        <div v-if="loading" class="flex flex-col items-center py-20">
                            <div class="w-10 h-10 border-4 border-indigo-500 border-t-transparent rounded-full animate-spin mb-4"></div>
                            <p class="text-[10px] font-black tracking-widest text-slate-500 uppercase">Consulting Telemetry Tables...</p>
                        </div>
                        <table v-else class="w-full text-left border-separate border-spacing-y-2">
                            <thead>
                            <tr class="text-[10px] uppercase text-slate-500 font-black tracking-widest">
                                <th class="px-4">Vehicle</th><th class="px-4">Driver</th><th class="px-4">Detail</th><th class="px-4">Time</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="row in modalData" :key="row.time" class="bg-slate-900/40 hover:bg-slate-800 transition-colors">
                                <td class="p-4 rounded-l-2xl font-black text-white">{{ row.plate_number }}</td>
                                <td class="p-4 text-xs font-bold text-slate-400">{{ row.driver_name || 'Unassigned' }}</td>
                                <td class="p-4 font-black text-indigo-400">{{ row.val }}L</td>
                                <td class="p-4 rounded-r-2xl text-[10px] text-slate-500">{{ new Date(row.time).toLocaleString() }}</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue';
import axios from 'axios';
import { Fuel, Clock, Activity, ShieldAlert, Timer, CreditCard, X, ArrowRight } from 'lucide-vue-next';

const totalUnits = ref(0);
const activeModal = ref(null);
const activeModalTitle = ref('');
const modalData = ref([]);
const loading = ref(false);
const filters = reactive({ date: new Date().toISOString().split('T')[0] });

const stats = ref({
    criticalFuel: [], thefts: [], fillings: [],
    breakdowns: [], grounded: [], longStops: [], fuelRequests: []
});

const loadSnapshot = async () => {
    const { data } = await axios.get('/api/portal/control-tower');
    totalUnits.value = data.totalUnits;
    stats.value = data.stats;
};

const openModal = (title, type) => {
    activeModalTitle.value = title;
    activeModal.value = type;
    fetchReport();
};

const fetchReport = async () => {
    loading.value = true;
    try {
        const { data } = await axios.get(`/api/portal/report/${activeModal.value}`, {
            params: { start: filters.date + ' 00:00:00', end: filters.date + ' 23:59:59' }
        });
        modalData.value = data;
    } finally { loading.value = false; }
};

onMounted(loadSnapshot);
</script>
