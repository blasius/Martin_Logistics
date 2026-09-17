<template>
    <div class="p-6 bg-slate-50 min-h-screen space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-black text-slate-800 tracking-tight uppercase">Fuel Lifecycle</h1>
                <p class="text-xs font-bold text-slate-400">Purchase → tank inventory → dispense → trip consumption</p>
            </div>
            <div class="flex items-center gap-2">
                <select v-model="days" @change="load" class="p-2.5 bg-white rounded-xl border border-slate-200 text-xs font-bold text-slate-600">
                    <option :value="7">Last 7 days</option>
                    <option :value="30">Last 30 days</option>
                    <option :value="90">Last 90 days</option>
                </select>
                <button @click="load" class="p-2.5 bg-white rounded-xl border border-slate-200 hover:bg-slate-50 shadow-sm">
                    <RefreshCw class="w-4 h-4 text-slate-500" />
                </button>
            </div>
        </div>

        <!-- Period Stat Cards -->
        <div v-if="overviewLoading" class="grid grid-cols-4 gap-4">
            <div v-for="i in 4" :key="i" class="h-24 bg-white rounded-2xl animate-pulse border border-slate-100"></div>
        </div>
        <div v-else class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Purchased (period)</p>
                <p class="text-3xl font-black text-emerald-600 mt-1">{{ fmt(overview.totals?.purchased_liters) }} L</p>
                <p class="text-xs font-bold text-slate-400 mt-1">{{ fmtCurrency(overview.totals?.purchase_amount) }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Dispensed (period)</p>
                <p class="text-3xl font-black text-amber-500 mt-1">{{ fmt(overview.totals?.dispensed_liters) }} L</p>
                <p class="text-xs font-bold text-slate-400 mt-1">{{ overview.totals?.dispensed_liters > 0 ? Math.round((overview.totals.dispensed_liters / (overview.totals.purchased_liters || 1)) * 100) + '% of purchases' : '' }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Trips Analysed</p>
                <p class="text-3xl font-black text-indigo-600 mt-1">{{ overview.trip_totals?.trips || 0 }}</p>
                <p class="text-xs font-bold text-slate-400 mt-1">{{ fmt(overview.trip_totals?.fuel_used) }} L consumed</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Trip Variance</p>
                <p class="text-3xl font-black mt-1" :class="(overview.trip_totals?.variance_liters || 0) > 0 ? 'text-rose-600' : 'text-emerald-600'">{{ fmt(overview.trip_totals?.variance_liters) }} L</p>
                <p class="text-xs font-bold text-slate-400 mt-1">Avg {{ overview.trip_totals?.avg_variance_percent || 0 }}%</p>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <!-- Purchases -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-black text-xs text-slate-500 uppercase tracking-wider">Bulk Fuel Purchases</h2>
                    <span class="px-2.5 py-1 bg-slate-100 rounded-lg text-[10px] font-black text-slate-500 uppercase">{{ overview.deliveries?.length || 0 }} deliveries</span>
                </div>

                <div v-if="!overview.deliveries?.length" class="text-center py-10">
                    <p class="text-slate-400 font-bold text-xs uppercase">No deliveries in period</p>
                </div>

                <div v-else class="space-y-2.5 max-h-[420px] overflow-y-auto custom-scrollbar">
                    <div v-for="d in overview.deliveries" :key="d.id" class="border border-slate-100 rounded-xl p-3.5 hover:border-amber-200 transition-colors">
                        <div class="flex justify-between items-start gap-2">
                            <div>
                                <p class="font-black text-sm text-slate-800">{{ d.tank_name }}</p>
                                <p class="text-[10px] font-bold text-slate-400 uppercase">{{ d.fuel_type }} · {{ d.supplier || '—' }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-black text-emerald-600 text-sm">{{ fmt(d.quantity) }} L</p>
                                <p class="text-[10px] font-black text-slate-500">{{ fmtCurrency(d.total_amount) }}</p>
                            </div>
                        </div>
                        <div class="mt-2 flex items-center justify-between text-[10px] font-bold text-slate-400">
                            <span>Invoice {{ d.invoice_reference || '—' }} · Received by {{ d.received_by || '—' }}</span>
                            <span>{{ dateShort(d.delivered_at) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tank levels -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-black text-xs text-slate-500 uppercase tracking-wider">Tank Inventory</h2>
                    <span class="px-2.5 py-1 bg-slate-100 rounded-lg text-[10px] font-black text-slate-500 uppercase">{{ overview.tanks?.length || 0 }} tanks</span>
                </div>

                <div v-if="!overview.tanks?.length" class="text-center py-10">
                    <p class="text-slate-400 font-bold text-xs uppercase">No active tanks</p>
                </div>

                <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div v-for="t in overview.tanks" :key="t.id" class="border border-slate-200 rounded-xl p-4">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-black text-sm text-slate-800">{{ t.name }}</p>
                                <p class="text-[10px] font-bold text-slate-400 uppercase">{{ t.fuel_type }}</p>
                            </div>
                            <span v-if="t.is_low" class="px-2 py-0.5 bg-rose-100 text-rose-700 rounded-full text-[9px] font-black uppercase">Low</span>
                        </div>
                        <div class="mt-3">
                            <div class="h-2.5 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all" :class="t.is_low ? 'bg-rose-500' : 'bg-emerald-500'" :style="{ width: Math.min(100, t.percent) + '%' }"></div>
                            </div>
                        </div>
                        <div class="mt-2 flex justify-between items-center text-xs font-bold">
                            <span class="text-slate-600">{{ fmt(t.current_level) }} / {{ fmt(t.capacity) }} L</span>
                            <span class="font-black" :class="t.is_low ? 'text-rose-600' : 'text-emerald-600'">{{ t.percent }}%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Trip expected vs actual -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-black text-xs text-slate-500 uppercase tracking-wider">Expected vs Actual — Per Trip</h2>
                <router-link to="/reports/trip-fuel" class="text-[10px] font-black uppercase text-amber-600 hover:text-amber-700">Full report →</router-link>
            </div>

            <div v-if="!overview.trips?.length" class="text-center py-10">
                <p class="text-slate-400 font-bold text-xs uppercase">No trips with fuel data in period</p>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-slate-100 text-[9px] font-black uppercase tracking-widest text-slate-400">
                            <th class="pb-2">Trip</th>
                            <th class="pb-2">Vehicle</th>
                            <th class="pb-2 text-right">Distance</th>
                            <th class="pb-2 text-right">Fuel Used</th>
                            <th class="pb-2 text-right">Expected</th>
                            <th class="pb-2 text-right">Variance</th>
                            <th class="pb-2 text-right">%</th>
                            <th class="pb-2 text-right">Flag</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="t in overview.trips" :key="t.trip_id" class="border-b border-slate-50 last:border-0">
                            <td class="py-2.5 pr-3">
                                <p class="font-black text-xs text-slate-800">{{ t.reference }}</p>
                                <p class="text-[9px] font-bold text-slate-400">{{ t.route_name || '—' }}</p>
                            </td>
                            <td class="py-2.5 pr-3 text-xs font-bold text-slate-600">{{ t.plate_number }}</td>
                            <td class="py-2.5 pr-3 text-right text-xs font-bold text-slate-600">{{ fmt(t.distance_km) }} km</td>
                            <td class="py-2.5 pr-3 text-right text-xs font-black text-amber-600">{{ fmt(t.fuel_used) }} L</td>
                            <td class="py-2.5 pr-3 text-right text-xs font-bold text-slate-500">{{ t.expected_consumption != null ? fmt(t.expected_consumption) + ' L' : '—' }}</td>
                            <td class="py-2.5 pr-3 text-right text-xs font-black" :class="(t.variance_liters || 0) > 0 ? 'text-rose-600' : 'text-emerald-600'">{{ t.variance_liters != null ? fmt(t.variance_liters) : '—' }}</td>
                            <td class="py-2.5 pr-3 text-right text-xs font-bold text-slate-500">{{ t.variance_percent != null ? t.variance_percent + '%' : '—' }}</td>
                            <td class="py-2.5 text-right">
                                <FlagBadge :flag="t.flag" />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Reconciliation -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="font-black text-xs text-slate-500 uppercase tracking-wider">Wialon Fill vs Recorded Dispense Reconciliation</h2>
                    <p class="text-[10px] font-bold text-slate-400 mt-0.5">Unaccounted fills are flagged; material variance can raise support tickets.</p>
                </div>
                <div class="flex items-center gap-2">
                    <button @click="runReconcile" :disabled="reconciling || ticketRunning" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl font-black text-[10px] uppercase flex items-center gap-1.5 shadow-sm disabled:opacity-50">
                        <RefreshCw :class="{ 'animate-spin': reconciling }" class="w-3.5 h-3.5" /> {{ reconciling ? 'Reconciling…' : 'Reconcile' }}
                    </button>
                    <button @click="runReconcile(true)" :disabled="reconciling || ticketRunning" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-slate-950 rounded-xl font-black text-[10px] uppercase flex items-center gap-1.5 shadow-sm disabled:opacity-50">
                        <FileWarning class="w-3.5 h-3.5" /> {{ ticketRunning ? 'Opening tickets…' : 'Reconcile + Tickets' }}
                    </button>
                </div>
            </div>

            <div v-if="ticketResult.length" class="mb-4 flex flex-wrap gap-2">
                <div v-for="r in ticketResult" :key="r.ticket_id" class="px-3 py-1.5 rounded-xl text-[10px] font-black uppercase flex items-center gap-1.5 border"
                     :class="r.new ? 'bg-amber-50 border-amber-300 text-amber-700' : 'bg-emerald-50 border-emerald-300 text-emerald-700'">
                    <TicketCheck class="w-3.5 h-3.5" />
                    {{ r.plate_number }} → {{ r.reference }} {{ r.new ? '(new)' : '(existing)' }}
                </div>
            </div>

            <div v-if="!reconcile?.vehicles?.length" class="text-center py-10">
                <p class="text-slate-400 font-bold text-xs uppercase">No telemetry fills or dispenses in period</p>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-slate-100 text-[9px] font-black uppercase tracking-widest text-slate-400">
                            <th class="pb-2">Vehicle</th>
                            <th class="pb-2">Driver</th>
                            <th class="pb-2 text-right">Wialon Refills</th>
                            <th class="pb-2 text-right">Recorded Dispenses</th>
                            <th class="pb-2 text-right">Variance</th>
                            <th class="pb-2 text-right">%</th>
                            <th class="pb-2 text-right">Overrides</th>
                            <th class="pb-2 text-right">Flag</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="v in reconcile.vehicles" :key="v.vehicle_id" class="border-b border-slate-50 last:border-0">
                            <td class="py-2.5 pr-3">
                                <p class="font-black text-xs text-slate-800">{{ v.plate_number }}</p>
                                <p class="text-[9px] font-bold text-slate-400">{{ v.vehicle_make }} {{ v.vehicle_model }}</p>
                            </td>
                            <td class="py-2.5 pr-3 text-xs font-bold text-slate-600">{{ v.driver_name || '—' }}</td>
                            <td class="py-2.5 pr-3 text-right text-xs font-black text-slate-700">{{ fmt(v.wialon_refills) }} L</td>
                            <td class="py-2.5 pr-3 text-right text-xs font-black text-amber-600">{{ fmt(v.recorded_dispenses) }} L</td>
                            <td class="py-2.5 pr-3 text-right text-xs font-black" :class="(v.variance_liters || 0) > 0 ? 'text-rose-600' : 'text-emerald-600'">{{ fmt(v.variance_liters) }} L</td>
                            <td class="py-2.5 pr-3 text-right text-xs font-bold text-slate-500">{{ v.variance_percent }}%</td>
                            <td class="py-2.5 pr-3 text-right text-xs font-bold text-slate-500">
                                {{ v.override_count || 0 }}<span v-if="v.override_count" class="text-slate-400"> ({{ fmt(v.override_liters) }} L)</span>
                            </td>
                            <td class="py-2.5 text-right"><FlagBadge :flag="v.flag" /></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="reconcile?.tanks?.length" class="mt-6 border-t border-slate-100 pt-4">
                <h3 class="font-black text-[10px] text-slate-500 uppercase tracking-wider mb-3">Tank Stock Reconciliation</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-slate-100 text-[9px] font-black uppercase tracking-widest text-slate-400">
                                <th class="pb-2">Tank</th>
                                <th class="pb-2 text-right">Throughput</th>
                                <th class="pb-2 text-right">Variance</th>
                                <th class="pb-2 text-right">%</th>
                                <th class="pb-2 text-right">Flag</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(t, i) in reconcile.tanks" :key="i" class="border-b border-slate-50 last:border-0">
                                <td class="py-2.5 pr-3 font-black text-xs text-slate-800">{{ t.name }}</td>
                                <td class="py-2.5 pr-3 text-right text-xs font-bold text-slate-500">{{ fmt(t.throughput_liters) }} L</td>
                                <td class="py-2.5 pr-3 text-right text-xs font-black" :class="(t.variance_liters || 0) > 0 ? 'text-rose-600' : 'text-emerald-600'">{{ fmt(t.variance_liters) }} L</td>
                                <td class="py-2.5 pr-3 text-right text-xs font-bold text-slate-500">{{ t.variance_percent }}%</td>
                                <td class="py-2.5 text-right"><FlagBadge :flag="t.flag" /></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { fuelLifecycleApi } from '../../../api/fuel/lifecycle';
import FlagBadge from '../../../components/FlagBadge.vue';
import { RefreshCw, FileWarning, TicketCheck } from 'lucide-vue-next';

const overviewLoading = ref(true);
const overview = ref({});
const days = ref(30);
const reconcile = ref({});
const reconciling = ref(false);
const ticketRunning = ref(false);
const ticketResult = ref([]);

function fmt(v) {
    if (v == null) return '0';
    return Number(v).toLocaleString('en-US', { maximumFractionDigits: 1 });
}

function fmtCurrency(v) {
    if (v == null) return '—';
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', maximumFractionDigits: 0 }).format(v);
}

function dateShort(v) {
    if (!v) return '—';
    return new Date(v).toLocaleDateString('en-US', { day: '2-digit', month: 'short', year: 'numeric' });
}

async function load() {
    overviewLoading.value = true;
    try {
        const [ov, rec] = await Promise.all([
            fuelLifecycleApi.overview({ days: days.value }),
            fuelLifecycleApi.reconcile({ date_from: '', date_to: '' }),
        ]);
        overview.value = ov.data;
        reconcile.value = rec.data || {};
    } catch (e) { console.error(e); }
    finally { overviewLoading.value = false; }
}

async function runReconcile(openTickets = false) {
    openTickets ? (ticketRunning.value = true) : (reconciling.value = true);
    ticketResult.value = [];
    try {
        const res = await fuelLifecycleApi.reconcile({ date_from: '', date_to: '', open_tickets: openTickets });
        reconcile.value = res.data || {};
        ticketResult.value = res.data?.tickets_opened || [];
    } catch (e) { console.error(e); }
    finally {
        reconciling.value = false;
        ticketRunning.value = false;
    }
}

onMounted(load);
</script>