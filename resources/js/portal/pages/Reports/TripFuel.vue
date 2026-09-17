<template>
    <div class="h-screen flex flex-col bg-slate-50 overflow-hidden">
        <!-- Header -->
        <div class="bg-white border-b border-slate-200 px-6 py-4 flex items-center gap-3">
            <span class="p-2 bg-emerald-600 rounded-lg text-white">
                <Fuel class="w-5 h-5" />
            </span>
            <div class="flex-1">
                <h1 class="text-lg font-black text-slate-800 uppercase tracking-tight">Trip Fuel Report</h1>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                    Dispenses vs expected consumption per trip
                </p>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white border-b border-slate-200 px-6 py-3 flex flex-wrap items-end gap-3">
            <div class="space-y-1">
                <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest block">Vehicle</label>
                <select v-model="filters.vehicle_id" class="p-2.5 bg-slate-50 rounded-xl text-xs font-bold border border-slate-100 outline-none">
                    <option value="">All Vehicles</option>
                    <option v-for="v in vehicles" :key="v.id" :value="v.id">{{ v.plate_number }}</option>
                </select>
            </div>
            <div class="space-y-1">
                <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest block">Driver</label>
                <select v-model="filters.driver_id" class="p-2.5 bg-slate-50 rounded-xl text-xs font-bold border border-slate-100 outline-none">
                    <option value="">All Drivers</option>
                    <option v-for="d in drivers" :key="d.id" :value="d.id">{{ d.name }}</option>
                </select>
            </div>
            <div class="space-y-1">
                <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest block">Flag</label>
                <select v-model="filters.flag" class="p-2.5 bg-slate-50 rounded-xl text-xs font-bold border border-slate-100 outline-none">
                    <option value="">All Flags</option>
                    <option value="normal">Normal</option>
                    <option value="caution">Caution</option>
                    <option value="excessive">Excessive</option>
                </select>
            </div>
            <div class="space-y-1">
                <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest block">From</label>
                <input type="date" v-model="filters.date_from" class="p-2.5 bg-slate-50 rounded-xl text-xs font-bold border border-slate-100 outline-none" />
            </div>
            <div class="space-y-1">
                <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest block">To</label>
                <input type="date" v-model="filters.date_to" class="p-2.5 bg-slate-50 rounded-xl text-xs font-bold border border-slate-100 outline-none" />
            </div>
            <button @click="load" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-[10px] font-black uppercase tracking-wider shadow-lg transition-colors">
                Apply
            </button>
        </div>

        <!-- Stat cards -->
        <div class="px-6 pt-4 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4">
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Trips</p>
                <p class="text-2xl font-black text-slate-800 mt-1">{{ totals.trips }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4">
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Fuel Dispensed (L)</p>
                <p class="text-2xl font-black text-emerald-600 mt-1">{{ totals.fuel_used }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4">
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Expected (L)</p>
                <p class="text-2xl font-black text-slate-800 mt-1">{{ totals.expected_consumption }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4">
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Variance (L)</p>
                <p class="text-2xl font-black mt-1" :class="varianceClass(totals.variance_liters)">{{ totals.variance_liters }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4">
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Avg Variance %</p>
                <p class="text-2xl font-black mt-1" :class="varianceClass(totals.avg_variance_percent)">
                    {{ totals.avg_variance_percent === null ? '—' : totals.avg_variance_percent + '%' }}
                </p>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-slate-800 mx-6 mt-4 px-4 py-3 text-white text-[10px] font-black uppercase tracking-widest grid grid-cols-12 gap-4 shadow-lg rounded-t-lg">
            <div class="col-span-2">Trip</div>
            <div class="col-span-2">Unit / Driver</div>
            <div class="col-span-2">Route</div>
            <div class="col-span-2">Distance / Used / Expected</div>
            <div class="col-span-3">Variance</div>
            <div class="col-span-1 text-right">Flag</div>
        </div>

        <div class="flex-1 overflow-y-auto px-6 py-3 space-y-2 mb-6 custom-scrollbar">
            <div v-for="row in rows" :key="row.trip_id"
                 class="bg-white border border-slate-200 rounded-lg p-3 grid grid-cols-12 gap-4 items-center hover:border-indigo-300 transition-all">
                <div class="col-span-2">
                    <p class="font-black text-slate-900 text-sm uppercase tracking-tight">{{ row.reference }}</p>
                    <div class="flex items-center gap-1.5">
                        <p class="text-[9px] font-bold text-slate-400">{{ dayjs(row.created_at).format('DD MMM YYYY') }}</p>
                        <span class="text-[8px] font-black px-1.5 py-0.5 rounded uppercase"
                              :class="statusClass(row.status)">{{ row.status }}</span>
                    </div>
                </div>
                <div class="col-span-2">
                    <p class="text-xs font-black text-slate-700 uppercase">{{ row.plate_number || '—' }}</p>
                    <p class="text-[9px] font-bold text-slate-400">{{ row.driver_name || 'No driver' }}</p>
                </div>
                <div class="col-span-2 text-xs font-bold text-slate-600">{{ row.route_name || '—' }}</div>
                <div class="col-span-2 text-xs font-bold text-slate-600">
                    <p>{{ row.distance_km }} km</p>
                    <p class="text-[10px] font-black" :class="{ 'text-emerald-600': row.expected_consumption !== null }">
                        {{ row.fuel_used }}L used · {{ row.expected_consumption ?? '?' }}L exp
                    </p>
                </div>
                <div class="col-span-3">
                    <template v-if="row.expected_consumption !== null">
                        <div class="flex items-center gap-2">
                            <p class="text-xs font-black" :class="varianceClass(row.variance_liters)">
                                {{ row.variance_liters >= 0 ? '+' : '' }}{{ row.variance_liters }}L
                            </p>
                            <span class="text-[9px] font-black px-1.5 py-0.5 rounded"
                                  :class="varianceClass(row.variance_percent)">{{ row.variance_percent }}%</span>
                        </div>
                    </template>
                    <p v-else class="text-[10px] font-bold text-slate-300 uppercase">No ratio configured</p>
                </div>
                <div class="col-span-1 flex justify-end items-center gap-2">
                    <span class="text-[8px] font-black px-1.5 py-0.5 rounded uppercase"
                          :class="flagClass(row.flag)">{{ row.flag || 'n/a' }}</span>
                    <button @click="openDetail(row.trip_id)" class="p-1.5 rounded-lg hover:bg-indigo-50 text-slate-300 hover:text-indigo-600 transition-colors" title="Dispenses">
                        <FileText class="w-4 h-4" />
                    </button>
                </div>
            </div>

            <div v-if="!rows.length" class="py-16 text-center text-slate-400 text-xs font-black uppercase tracking-wider">
                No trips match the selected filters
            </div>

            <!-- Pagination -->
            <div v-if="pagination.last_page > 1" class="pt-3 flex items-center justify-center gap-2">
                <button @click="goPage(pagination.current_page - 1)" :disabled="pagination.current_page <= 1"
                        class="px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-[10px] font-black text-slate-500 disabled:opacity-30">
                    Prev
                </button>
                <span class="text-[10px] font-black text-slate-500 uppercase tracking-wider">
                    Page {{ pagination.current_page }} / {{ pagination.last_page }}
                </span>
                <button @click="goPage(pagination.current_page + 1)" :disabled="pagination.current_page >= pagination.last_page"
                        class="px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-[10px] font-black text-slate-500 disabled:opacity-30">
                    Next
                </button>
            </div>
        </div>

        <!-- Detail modal -->
        <Transition name="fade">
            <div v-if="detail.show" class="fixed inset-0 z-[160] flex items-center justify-center">
                <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="detail.show = false"></div>
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden max-h-[85vh] flex flex-col">
                    <div class="bg-slate-50 p-5 border-b border-slate-200 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-black text-slate-800 uppercase tracking-wide">Trip {{ detail.data.trip?.reference }}</h3>
                            <p class="text-[10px] font-bold text-slate-400">
                                {{ detail.data.trip?.plate_number || '—' }} · {{ detail.data.trip?.driver_name || 'No driver' }} · {{ detail.data.trip?.route_name || '—' }}
                            </p>
                        </div>
                        <button @click="detail.show = false" class="p-1.5 rounded-lg hover:bg-slate-200 text-slate-400 transition-colors">
                            <X class="w-4 h-4" />
                        </button>
                    </div>
                    <div class="p-5 overflow-y-auto space-y-4">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <div class="bg-slate-50 rounded-xl p-3">
                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Dispensed</p>
                                <p class="text-lg font-black text-emerald-600">{{ detail.data.total_fuel_used }} L</p>
                            </div>
                            <div class="bg-slate-50 rounded-xl p-3">
                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Expected</p>
                                <p class="text-lg font-black text-slate-700">{{ detail.data.expected_consumption ?? '—' }} L</p>
                            </div>
                            <div class="bg-slate-50 rounded-xl p-3">
                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Variance</p>
                                <p class="text-lg font-black" :class="varianceClass(detail.data.variance_liters)">{{ detail.data.variance_liters ?? '—' }} L</p>
                            </div>
                            <div class="bg-slate-50 rounded-xl p-3">
                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Ratio</p>
                                <p class="text-lg font-black text-slate-700">{{ detail.data.ratio_km_per_liter ?? '—' }} km/L</p>
                            </div>
                        </div>

                        <div v-if="detail.data.flag" class="text-[10px] font-black uppercase tracking-wider inline-block px-2 py-1 rounded"
                             :class="flagClass(detail.data.flag)">
                            Flagged: {{ detail.data.flag }}
                        </div>

                        <div>
                            <div class="bg-slate-800 px-4 py-2.5 text-white text-[10px] font-black uppercase tracking-widest grid grid-cols-12 gap-4 rounded-t-lg">
                                <div class="col-span-3">Date</div>
                                <div class="col-span-2">Quantity</div>
                                <div class="col-span-3">Tank</div>
                                <div class="col-span-2">Odometer</div>
                                <div class="col-span-2">By</div>
                            </div>
                            <div class="divide-y divide-slate-50 border border-slate-100 rounded-b-lg">
                                <div v-for="d in detail.data.dispenses" :key="d.id"
                                     class="grid grid-cols-12 gap-4 px-4 py-2.5 items-center text-xs">
                                    <div class="col-span-3 text-[10px] font-bold text-slate-500">{{ dayjs(d.dispensed_at).format('DD MMM YYYY HH:mm') }}</div>
                                    <div class="col-span-2 font-black text-slate-800">{{ d.quantity }} L</div>
                                    <div class="col-span-3 font-bold text-slate-600">{{ d.tank }} <span class="text-slate-300">({{ d.tank_fuel_type || '—' }})</span></div>
                                    <div class="col-span-2 font-bold text-slate-600">{{ d.odometer_at_dispense }} km</div>
                                    <div class="col-span-2 font-bold text-slate-500 truncate">{{ d.dispensed_by || '—' }}</div>
                                </div>
                                <div v-if="!detail.data.dispenses.length" class="px-4 py-8 text-center text-slate-400 text-[10px] font-black uppercase tracking-wider">
                                    No dispense entries recorded for this trip
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { api } from "../../../plugins/axios";
import { Fuel, FileText, X } from 'lucide-vue-next';
import dayjs from 'dayjs';

const vehicles = ref([]);
const drivers = ref([]);
const rows = ref([]);
const totals = reactive({ trips: 0, fuel_used: 0, expected_consumption: 0, variance_liters: 0, avg_variance_percent: null });
const pagination = reactive({ current_page: 1, per_page: 20, total: 0, last_page: 1 });
const filters = reactive({ vehicle_id: '', driver_id: '', flag: '', date_from: '', date_to: '' });
const detail = reactive({ show: false, data: {} });

function varianceClass(v) {
    if (v === null || v === undefined) return 'text-slate-500';
    if (v > 5) return 'text-rose-600 bg-rose-50';
    if (v < -5) return 'text-amber-600 bg-amber-50';
    return 'text-emerald-600 bg-emerald-50';
}

function flagClass(flag) {
    if (flag === 'excessive') return 'text-rose-700 bg-rose-100';
    if (flag === 'caution') return 'text-amber-700 bg-amber-100';
    if (flag === 'normal') return 'text-emerald-700 bg-emerald-100';
    return 'text-slate-400 bg-slate-100';
}

function statusClass(status) {
    return {
        'text-emerald-700 bg-emerald-100': status === 'delivered',
        'text-amber-700 bg-amber-100': status === 'assigned' || status === 'pre_departure',
        'text-indigo-700 bg-indigo-100': status === 'on_route' || status === 'pending',
        'text-rose-700 bg-rose-100': status === 'cancelled',
    }[status] || 'text-slate-400 bg-slate-100';
}

async function load(page = 1) {
    const { data } = await api.get('/portal/reports/trip-fuel', { params: { ...filters, page } });
    rows.value = data.data;
    Object.assign(totals, data.totals);
    Object.assign(pagination, data.pagination);
}

function goPage(page) {
    if (page < 1 || page > pagination.last_page) return;
    load(page);
}

async function openDetail(tripId) {
    const { data } = await api.get(`/portal/reports/trip-fuel/${tripId}`);
    detail.data = data;
    detail.show = true;
}

async function loadOptions() {
    const { data } = await api.get('/portal/reports/profitability/options');
    vehicles.value = data.vehicles;
    drivers.value = data.drivers;
}

onMounted(async () => {
    await loadOptions();
    await load();
});
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>