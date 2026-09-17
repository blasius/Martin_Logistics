<template>
    <div class="h-screen flex flex-col bg-slate-50 overflow-hidden relative">
        <header class="bg-white border-b border-slate-200 px-8 py-4 flex flex-wrap items-center justify-between gap-4 shadow-sm z-10 no-print">
            <div class="flex items-center gap-4">
                <div class="p-2 bg-indigo-600 rounded-lg"><PauseCircle class="w-6 h-6 text-white" /></div>
                <div>
                    <h1 class="text-xl font-black text-slate-800 uppercase leading-none">Driver Stops</h1>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1.5">Rest &amp; stop monitoring from live telemetry</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <button @click="exportCsv" :disabled="!report?.stops.length" class="flex items-center gap-2 px-4 py-2 bg-emerald-700 text-white text-xs font-black rounded-lg hover:bg-emerald-800 transition-all disabled:opacity-40 disabled:cursor-not-allowed">
                    <Download class="w-4 h-4" /> CSV
                </button>
                <button @click="generate" class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-xs font-black rounded-lg hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-200">
                    <RefreshCw v-if="!loading" class="w-4 h-4" />
                    <span v-else class="w-3 h-3 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                    {{ loading ? 'Loading...' : 'Refresh' }}
                </button>
            </div>
        </header>

        <div class="px-8 py-3 bg-white border-b border-slate-200 z-10 no-print">
            <div class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="block text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">From</label>
                    <input v-model="filters.from" type="date" class="pl-3 pr-3 py-2 bg-slate-100 border-none rounded-lg text-xs font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500 outline-none" />
                </div>
                <div>
                    <label class="block text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">To</label>
                    <input v-model="filters.to" type="date" class="pl-3 pr-3 py-2 bg-slate-100 border-none rounded-lg text-xs font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500 outline-none" />
                </div>
                <div>
                    <label class="block text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">Truck</label>
                    <select v-model="filters.vehicle_id" class="w-40 pl-3 pr-3 py-2 bg-slate-100 border-none rounded-lg text-xs font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500 outline-none">
                        <option value="">All Trucks</option>
                        <option v-for="v in options.vehicles" :key="v.id" :value="v.id">{{ v.plate_number }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">Driver</label>
                    <select v-model="filters.driver_id" class="w-40 pl-3 pr-3 py-2 bg-slate-100 border-none rounded-lg text-xs font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500 outline-none">
                        <option value="">All Drivers</option>
                        <option v-for="d in options.drivers" :key="d.id" :value="d.id">{{ d.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">Dispatcher</label>
                    <select v-model="filters.dispatcher_id" class="w-40 pl-3 pr-3 py-2 bg-slate-100 border-none rounded-lg text-xs font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500 outline-none">
                        <option value="">All Dispatchers</option>
                        <option v-for="d in options.dispatchers" :key="d.id" :value="d.id">{{ d.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">Classification</label>
                    <select v-model="filters.classification" class="w-40 pl-3 pr-3 py-2 bg-slate-100 border-none rounded-lg text-xs font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500 outline-none">
                        <option value="">All Stops</option>
                        <option value="expected">Expected</option>
                        <option value="unexpected">Unexpected</option>
                    </select>
                </div>
                <button @click="reset" class="px-4 py-2 bg-slate-100 text-slate-600 text-xs font-black rounded-lg hover:bg-slate-200 transition-all">Reset</button>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto px-8 py-6 custom-scrollbar">
            <div v-if="loading" class="flex flex-col items-center justify-center py-24 gap-3">
                <span class="w-8 h-8 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin"></span>
                <p class="text-xs font-black text-slate-400 uppercase tracking-widest">Loading stops...</p>
            </div>

            <template v-else-if="report">
                <div>
                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4 mb-6">
                        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Total Stops</p>
                            <p class="text-xl font-black text-slate-900">{{ report.summary.total_stops }}</p>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mt-1">{{ report.summary.total_rest_hours }} rest hrs</p>
                        </div>
                        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Expected</p>
                            <p class="text-xl font-black text-emerald-600">{{ report.summary.expected_stops }}</p>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mt-1">Delivery / yard</p>
                        </div>
                        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Unexpected</p>
                            <p class="text-xl font-black text-rose-600">{{ report.summary.unexpected_stops }}</p>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mt-1">{{ report.summary.longest_u_stop_minutes }} min longest</p>
                        </div>
                        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Alerts Raised</p>
                            <p class="text-xl font-black text-orange-500">{{ report.summary.alerts_raised }}</p>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mt-1">Dispatcher tickets</p>
                        </div>
                        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Off-Corridor</p>
                            <p class="text-xl font-black text-cyan-600">{{ report.summary.off_corridor_stops }}</p>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mt-1">Off planned route</p>
                        </div>
                        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Avg Stop</p>
                            <p class="text-xl font-black text-slate-900">{{ report.summary.avg_stop_minutes }} min</p>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mt-1">Per stop</p>
                        </div>
                        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Rest Hours</p>
                            <p class="text-xl font-black text-indigo-600">{{ report.summary.total_rest_hours }} h</p>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mt-1">Stationary time</p>
                        </div>
                    </div>

                    <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
                        <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                            <h3 class="text-[10px] font-black uppercase tracking-widest text-slate-500">Stops Log</h3>
                            <span class="text-xs font-black text-slate-500">{{ report.stops.length }} entries</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-slate-800 text-white">
                                    <tr>
                                        <th class="px-4 py-3 text-[10px] font-black uppercase tracking-widest text-left">#</th>
                                        <th class="px-4 py-3 text-[10px] font-black uppercase tracking-widest text-left">Trip</th>
                                        <th class="px-4 py-3 text-[10px] font-black uppercase tracking-widest text-left">Truck</th>
                                        <th class="px-4 py-3 text-[10px] font-black uppercase tracking-widest text-left">Driver</th>
                                        <th class="px-4 py-3 text-[10px] font-black uppercase tracking-widest text-left">Dispatcher</th>
                                        <th class="px-4 py-3 text-[10px] font-black uppercase tracking-widest text-left">Started</th>
                                        <th class="px-4 py-3 text-[10px] font-black uppercase tracking-widest text-right">Minutes</th>
                                        <th class="px-4 py-3 text-[10px] font-black uppercase tracking-widest text-left">Type</th>
                                        <th class="px-4 py-3 text-[10px] font-black uppercase tracking-widest text-center">Corridor</th>
                                        <th class="px-4 py-3 text-[10px] font-black uppercase tracking-widest text-center">Alert</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(s, i) in report.stops" :key="s.id" class="border-t border-slate-100 hover:bg-indigo-50/60 transition-colors" :class="s.classification === 'unexpected' ? 'bg-rose-50/40' : ''">
                                        <td class="px-4 py-3 text-xs font-bold text-slate-400">{{ i + 1 }}</td>
                                        <td class="px-4 py-3 text-xs font-black text-slate-800">{{ s.trip_reference }}</td>
                                        <td class="px-4 py-3 text-xs text-slate-600">{{ s.plate_number }}</td>
                                        <td class="px-4 py-3 text-xs text-slate-600">{{ s.driver_name || '—' }}</td>
                                        <td class="px-4 py-3 text-xs text-slate-600">{{ s.dispatcher_name || '—' }}</td>
                                        <td class="px-4 py-3 text-xs text-slate-600 whitespace-nowrap">{{ fmtDate(s.started_at) }}</td>
                                        <td class="px-4 py-3 text-xs text-right font-bold text-slate-700">{{ s.duration_minutes ?? 'open' }}</td>
                                        <td class="px-4 py-3 text-left">
                                            <span class="text-[9px] font-black px-2 py-1 rounded-full uppercase whitespace-nowrap" :class="s.classification === 'expected' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'">{{ s.reason || s.classification }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span v-if="s.is_off_corridor" class="text-[9px] font-black px-2 py-1 rounded-full uppercase bg-cyan-100 text-cyan-700">Off</span>
                                            <span v-else class="text-[9px] font-black px-2 py-1 rounded-full uppercase bg-slate-100 text-slate-500">On</span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span v-if="s.alerted" class="text-[9px] font-black px-2 py-1 rounded-full uppercase bg-orange-100 text-orange-600">Yes</span>
                                            <span v-else class="text-[9px] font-black px-2 py-1 rounded-full uppercase bg-slate-100 text-slate-400">—</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-if="!report.stops.length" class="px-4 py-12 text-center">
                            <p class="text-xs font-black text-slate-400 uppercase tracking-widest">No stops found for the selected filters</p>
                        </div>
                    </div>
                </div>
            </template>

            <div v-else class="flex flex-col items-center justify-center py-24 gap-3">
                <PauseCircle class="w-10 h-10 text-slate-300" />
                <p class="text-sm font-black text-slate-400 uppercase tracking-widest">Set filters and load stop records</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { Download, RefreshCw, PauseCircle } from 'lucide-vue-next';
import dayjs from 'dayjs';
import { reportsApi } from '../../api/reports';

const options = reactive({ vehicles: [], drivers: [], dispatchers: [] });
const filters = reactive({
    from: dayjs().subtract(30, 'day').format('YYYY-MM-DD'),
    to: dayjs().format('YYYY-MM-DD'),
    vehicle_id: '',
    driver_id: '',
    dispatcher_id: '',
    classification: '',
});
const loading = ref(false);
const report = ref(null);

const fetchOptions = async () => {
    const { data } = await reportsApi.stopsOptions();
    Object.assign(options, data);
};

const generate = async () => {
    loading.value = true;
    try {
        const params = {};
        Object.keys(filters).forEach((k) => {
            if (filters[k] !== '' && filters[k] !== null) params[k] = filters[k];
        });
        const { data } = await reportsApi.stops(params);
        report.value = data;
    } catch (e) {
        alert('Failed to load stops report.');
    } finally {
        loading.value = false;
    }
};

const reset = () => {
    filters.vehicle_id = '';
    filters.driver_id = '';
    filters.dispatcher_id = '';
    filters.classification = '';
    generate();
};

const fmtDate = (d) => (d ? dayjs(d).format('DD MMM YYYY HH:mm') : '—');

const exportCsv = () => {
    const rows = report.value?.stops || [];
    if (!rows.length) return;
    const headers = ['Trip', 'Truck', 'Driver', 'Dispatcher', 'Started', 'Ended', 'Minutes', 'Classification', 'Reason', 'Off Corridor', 'Distance(m)', 'Alerted'];
    const csv = [
        headers.join(','),
        ...rows.map((r) => [
            r.trip_reference, r.plate_number, r.driver_name, r.dispatcher_name,
            fmtDate(r.started_at), fmtDate(r.ended_at), r.duration_minutes,
            r.classification, r.reason, r.is_off_corridor ? 'yes' : 'no',
            r.distance_from_route_meters, r.alerted ? 'yes' : 'no',
        ].map((c) => `"${String(c ?? '').replace(/"/g, '""')}"`).join(',')),
    ].join('\n');
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `driver_stops_${filters.from}_${filters.to}.csv`;
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
};

onMounted(() => {
    fetchOptions();
    generate();
});
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
</style>