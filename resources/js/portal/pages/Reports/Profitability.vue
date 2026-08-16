<template>
    <div class="h-screen flex flex-col bg-slate-50 overflow-hidden relative">
        <header class="bg-white border-b border-slate-200 px-8 py-4 flex flex-wrap items-center justify-between gap-4 shadow-sm z-10 no-print">
            <div class="flex items-center gap-4">
                <div class="p-2 bg-indigo-600 rounded-lg"><TrendingUp class="w-6 h-6 text-white" /></div>
                <div>
                    <h1 class="text-xl font-black text-slate-800 uppercase leading-none">Profitability Report</h1>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1.5">Income, costs and profit per trip</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <button @click="exportCsv" :disabled="!report?.breakdown.length" class="flex items-center gap-2 px-4 py-2 bg-emerald-700 text-white text-xs font-black rounded-lg hover:bg-emerald-800 transition-all disabled:opacity-40 disabled:cursor-not-allowed">
                    <Download class="w-4 h-4" /> CSV
                </button>
                <button @click="generate" class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-xs font-black rounded-lg hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-200">
                    <RefreshCw v-if="!loading" class="w-4 h-4" />
                    <span v-else class="w-3 h-3 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                    {{ loading ? 'Generating...' : 'Generate' }}
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
                    <label class="block text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">Group By</label>
                    <select v-model="filters.group_by" class="w-40 pl-3 pr-3 py-2 bg-slate-100 border-none rounded-lg text-xs font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500 outline-none">
                        <option v-for="g in groupByOptions" :key="g.value" :value="g.value">{{ g.label }}</option>
                    </select>
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
                    <label class="block text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">Route</label>
                    <select v-model="filters.route_id" class="w-40 pl-3 pr-3 py-2 bg-slate-100 border-none rounded-lg text-xs font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500 outline-none">
                        <option value="">All Routes</option>
                        <option v-for="r in options.routes" :key="r.id" :value="r.id">{{ r.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">Client</label>
                    <select v-model="filters.client_id" class="w-40 pl-3 pr-3 py-2 bg-slate-100 border-none rounded-lg text-xs font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500 outline-none">
                        <option value="">All Clients</option>
                        <option v-for="c in options.clients" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                </div>
                <button @click="reset" class="px-4 py-2 bg-slate-100 text-slate-600 text-xs font-black rounded-lg hover:bg-slate-200 transition-all">Reset</button>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto px-8 py-6 custom-scrollbar">
            <div v-if="loading" class="flex flex-col items-center justify-center py-24 gap-3">
                <span class="w-8 h-8 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin"></span>
                <p class="text-xs font-black text-slate-400 uppercase tracking-widest">Generating report...</p>
            </div>

            <template v-else-if="report">
                <div v-if="report.summary.total_trips">
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
                        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Total Trips</p>
                                <span class="p-2 rounded-lg bg-indigo-100 text-indigo-600"><Truck class="w-4 h-4" /></span>
                            </div>
                            <p class="text-xl font-black text-slate-900">{{ report.summary.total_trips }}</p>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mt-1">Delivered</p>
                        </div>
                        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Revenue</p>
                                <span class="p-2 rounded-lg bg-indigo-50 text-indigo-500"><TrendingUp class="w-4 h-4" /></span>
                            </div>
                            <p class="text-xl font-black text-slate-900">{{ fmtMoney(report.summary.total_revenue) }}</p>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mt-1">{{ fmtMoney(report.summary.avg_revenue_per_trip) }} / trip</p>
                        </div>
                        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Expenses</p>
                                <span class="p-2 rounded-lg bg-amber-50 text-amber-600"><Receipt class="w-4 h-4" /></span>
                            </div>
                            <p class="text-xl font-black text-slate-900">{{ fmtMoney(report.summary.total_expenses) }}</p>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mt-1">{{ fmtMoney(report.summary.avg_expense_per_trip) }} / trip</p>
                        </div>
                        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Net Profit</p>
                                <span class="p-2 rounded-lg" :class="report.summary.total_profit >= 0 ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600'"><Wallet class="w-4 h-4" /></span>
                            </div>
                            <p class="text-xl font-black" :class="report.summary.total_profit >= 0 ? 'text-emerald-600' : 'text-rose-600'">{{ fmtMoney(report.summary.total_profit) }}</p>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mt-1">{{ fmtMoney(report.summary.avg_profit_per_trip) }} / trip</p>
                        </div>
                        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Profit Margin</p>
                                <span class="p-2 rounded-lg" :class="report.summary.profit_margin >= 0 ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600'"><Percent class="w-4 h-4" /></span>
                            </div>
                            <p class="text-xl font-black" :class="report.summary.profit_margin >= 0 ? 'text-emerald-600' : 'text-rose-600'">{{ report.summary.profit_margin }}%</p>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mt-1">Net margin</p>
                        </div>
                        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Loss Making</p>
                                <span class="p-2 rounded-lg bg-rose-100 text-rose-600"><TrendingDown class="w-4 h-4" /></span>
                            </div>
                            <p class="text-xl font-black text-rose-600">{{ report.summary.loss_groups }}</p>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mt-1">{{ report.summary.profitable_groups }} profitable</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
                        <div class="lg:col-span-2 bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                            <h3 class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-4">Monthly Revenue vs Expenses vs Profit</h3>
                            <div class="h-64">
                                <canvas ref="trendCanvas"></canvas>
                            </div>
                        </div>
                        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                            <h3 class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-4">Cost Breakdown</h3>
                            <div v-if="report.cost_breakdown.length" class="h-64">
                                <canvas ref="costCanvas"></canvas>
                            </div>
                            <div v-else class="h-64 flex items-center justify-center">
                                <p class="text-xs font-black text-slate-400 uppercase tracking-widest">No expenses recorded</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
                        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                            <h3 class="text-[10px] font-black uppercase tracking-widest text-emerald-600 mb-4">Top Profitable — {{ groupLabel }}</h3>
                            <div v-if="report.top_profitable.length">
                                <div v-for="t in report.top_profitable" :key="t.label" class="flex items-center justify-between py-2 border-b border-slate-50 last:border-0">
                                    <span class="text-xs font-black text-slate-800">{{ t.label }}</span>
                                    <span class="text-xs font-black text-emerald-600">{{ fmtMoney(t.profit) }} <span class="text-[9px] text-slate-400">({{ t.margin }}%)</span></span>
                                </div>
                            </div>
                            <p v-else class="text-xs font-black text-slate-400 uppercase tracking-widest py-4 text-center">No profitable entries</p>
                        </div>
                        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                            <h3 class="text-[10px] font-black uppercase tracking-widest text-rose-600 mb-4">Top Losses — {{ groupLabel }}</h3>
                            <div v-if="report.top_losses.length">
                                <div v-for="t in report.top_losses" :key="t.label" class="flex items-center justify-between py-2 border-b border-slate-50 last:border-0">
                                    <span class="text-xs font-black text-slate-800">{{ t.label }}</span>
                                    <span class="text-xs font-black text-rose-600">{{ fmtMoney(t.profit) }} <span class="text-[9px] text-slate-400">({{ t.margin }}%)</span></span>
                                </div>
                            </div>
                            <p v-else class="text-xs font-black text-slate-400 uppercase tracking-widest py-4 text-center">No loss-making entries</p>
                        </div>
                    </div>

                    <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
                        <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                            <h3 class="text-[10px] font-black uppercase tracking-widest text-slate-500">Breakdown — {{ groupLabel }}</h3>
                            <span class="text-xs font-black text-slate-500">{{ report.breakdown.length }} entries</span>
                        </div>
                        <table class="w-full">
                            <thead class="bg-slate-800 text-white">
                                <tr>
                                    <th class="px-4 py-3 text-[10px] font-black uppercase tracking-widest text-left">#</th>
                                    <th class="px-4 py-3 text-[10px] font-black uppercase tracking-widest text-left">{{ groupLabel }}</th>
                                    <th class="px-4 py-3 text-[10px] font-black uppercase tracking-widest text-right">Trips</th>
                                    <th class="px-4 py-3 text-[10px] font-black uppercase tracking-widest text-right">Revenue</th>
                                    <th class="px-4 py-3 text-[10px] font-black uppercase tracking-widest text-right">Expenses</th>
                                    <th class="px-4 py-3 text-[10px] font-black uppercase tracking-widest text-right">Profit</th>
                                    <th class="px-4 py-3 text-[10px] font-black uppercase tracking-widest text-right">Margin</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(row, i) in report.breakdown" :key="row.label" class="border-t border-slate-100 hover:bg-indigo-50/60 transition-colors" :class="row.profit < 0 ? 'bg-rose-50/50' : ''">
                                    <td class="px-4 py-3 text-xs font-bold text-slate-400">{{ i + 1 }}</td>
                                    <td class="px-4 py-3 text-xs font-black text-slate-800">{{ row.label }}</td>
                                    <td class="px-4 py-3 text-xs text-slate-600 text-right">{{ row.trips }}</td>
                                    <td class="px-4 py-3 text-xs text-slate-600 text-right whitespace-nowrap">{{ fmtMoney(row.revenue) }}</td>
                                    <td class="px-4 py-3 text-xs text-slate-600 text-right whitespace-nowrap">{{ fmtMoney(row.expenses) }}</td>
                                    <td class="px-4 py-3 text-xs font-black text-right whitespace-nowrap" :class="row.profit >= 0 ? 'text-emerald-600' : 'text-rose-600'">{{ fmtMoney(row.profit) }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="text-[9px] font-black px-2 py-1 rounded-full uppercase whitespace-nowrap" :class="marginBadge(row.margin)">{{ row.margin }}%</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div v-if="!report.breakdown.length" class="px-4 py-12 text-center">
                            <p class="text-xs font-black text-slate-400 uppercase tracking-widest">No entries found</p>
                        </div>
                    </div>
                </div>

                <div v-else class="flex flex-col items-center justify-center py-24 gap-3">
                    <Truck class="w-10 h-10 text-slate-300" />
                    <p class="text-sm font-black text-slate-400 uppercase tracking-widest">No delivered trips found for the selected filters</p>
                </div>
            </template>

            <div v-else class="flex flex-col items-center justify-center py-24 gap-3">
                <TrendingUp class="w-10 h-10 text-slate-300" />
                <p class="text-sm font-black text-slate-400 uppercase tracking-widest">Set filters and click Generate to build the report</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, watch } from 'vue';
import { TrendingUp, TrendingDown, Download, RefreshCw, Truck, Wallet, Receipt, Percent } from 'lucide-vue-next';
import Chart from 'chart.js/auto';
import ChartDataLabels from 'chartjs-plugin-datalabels';
import dayjs from 'dayjs';
import { reportsApi } from '../../api/reports';

Chart.register(ChartDataLabels);

const options = reactive({ vehicles: [], drivers: [], dispatchers: [], routes: [], clients: [] });
const filters = reactive({
    from: dayjs().startOf('month').format('YYYY-MM-DD'),
    to: dayjs().format('YYYY-MM-DD'),
    group_by: 'truck',
    vehicle_id: '',
    driver_id: '',
    dispatcher_id: '',
    route_id: '',
    client_id: '',
});
const groupByOptions = [
    { value: 'truck', label: 'Per Truck' },
    { value: 'trip', label: 'Per Trip' },
    { value: 'driver', label: 'Per Driver' },
    { value: 'dispatcher', label: 'Per Dispatcher' },
    { value: 'route', label: 'Per Route' },
    { value: 'client', label: 'Per Client' },
    { value: 'month', label: 'Per Month' },
];
const loading = ref(false);
const report = ref(null);
const trendCanvas = ref(null);
const costCanvas = ref(null);
let trendChart = null;
let costChart = null;

const groupLabel = computed(() => groupByOptions.find((g) => g.value === filters.group_by)?.label || 'Group');

const fetchOptions = async () => {
    const { data } = await reportsApi.options();
    Object.assign(options, data);
};

const generate = async () => {
    loading.value = true;
    try {
        const params = {};
        Object.keys(filters).forEach((k) => {
            if (filters[k] !== '' && filters[k] !== null) params[k] = filters[k];
        });
        const { data } = await reportsApi.profitability(params);
        report.value = data;
    } catch (e) {
        alert('Failed to load report.');
    } finally {
        loading.value = false;
    }
};

const reset = () => {
    filters.vehicle_id = '';
    filters.driver_id = '';
    filters.dispatcher_id = '';
    filters.route_id = '';
    filters.client_id = '';
    generate();
};

const fmtMoney = (n) => Number(n || 0).toLocaleString(undefined, { maximumFractionDigits: 2 });
const compactMoney = (n) => {
    const abs = Math.abs(n);
    if (abs >= 1e6) return (n / 1e6).toFixed(1) + 'M';
    if (abs >= 1e3) return (n / 1e3).toFixed(1) + 'K';
    return String(Math.round(n));
};
const marginBadge = (m) => (m >= 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700');

const destroyCharts = () => {
    if (trendChart) { trendChart.destroy(); trendChart = null; }
    if (costChart) { costChart.destroy(); costChart = null; }
};

const renderCharts = () => {
    destroyCharts();
    if (!report.value?.summary?.total_trips) return;

    if (trendCanvas.value && report.value.trend.length) {
        const trend = report.value.trend;
        trendChart = new Chart(trendCanvas.value, {
            type: 'bar',
            data: {
                labels: trend.map((t) => t.period),
                datasets: [
                    { label: 'Revenue', data: trend.map((t) => t.revenue), backgroundColor: 'rgba(99, 102, 241, 0.85)', borderRadius: 4 },
                    { label: 'Expenses', data: trend.map((t) => t.expenses), backgroundColor: 'rgba(245, 158, 11, 0.85)', borderRadius: 4 },
                    { label: 'Profit', data: trend.map((t) => t.profit), backgroundColor: 'rgba(16, 185, 129, 0.85)', borderRadius: 4 },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top', labels: { boxWidth: 12, font: { size: 10 }, usePointStyle: true } },
                },
                scales: {
                    x: { grid: { display: false } },
                    y: { ticks: { callback: (v) => compactMoney(v) } },
                },
            },
        });
    }

    if (costCanvas.value && report.value.cost_breakdown.length) {
        const cb = report.value.cost_breakdown;
        const palette = ['#6366f1', '#f59e0b', '#ef4444', '#10b981', '#8b5cf6', '#0ea5e9', '#f97316', '#14b8a6', '#ec4899', '#64748b'];
        costChart = new Chart(costCanvas.value, {
            type: 'doughnut',
            data: {
                labels: cb.map((c) => c.name),
                datasets: [{
                    data: cb.map((c) => c.total),
                    backgroundColor: cb.map((_, i) => palette[i % palette.length]),
                    borderWidth: 2,
                    borderColor: '#fff',
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: {
                    legend: { position: 'right', labels: { boxWidth: 12, font: { size: 10 } } },
                    datalabels: {
                        color: '#fff',
                        font: { weight: 'bold', size: 10 },
                        formatter: (v) => compactMoney(v),
                        display: (ctx) => ctx.dataset.data[ctx.dataIndex] > 0,
                    },
                },
            },
        });
    }
};

const exportCsv = () => {
    const rows = report.value?.breakdown || [];
    if (!rows.length) return;
    const headers = ['Label', 'Trips', 'Revenue', 'Expenses', 'Profit', 'Margin %'];
    const csv = [
        headers.join(','),
        ...rows.map((r) => [r.label, r.trips, r.revenue, r.expenses, r.profit, r.margin]
            .map((c) => `"${String(c).replace(/"/g, '""')}"`).join(',')),
    ].join('\n');
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `profitability_${filters.group_by}_${filters.from}_${filters.to}.csv`;
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
};

watch(report, renderCharts, { flush: 'post' });

onMounted(() => {
    fetchOptions();
    generate();
});

onUnmounted(destroyCharts);
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
</style>
