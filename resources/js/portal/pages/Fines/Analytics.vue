<template>
    <div class="p-6 space-y-6 bg-slate-50 min-h-screen font-sans">
        <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-slate-800 flex items-center gap-2 uppercase tracking-tight">
                    <i class="lucide lucide-alert-octagon text-red-600"></i>
                    TRAFFIC FINES ANALYTICS
                </h1>
                <p class="text-sm text-slate-500 font-medium italic"></p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <select v-model="range" @change="onRangeChange" class="border-slate-200 px-3 py-2 rounded-lg bg-white shadow-sm text-sm font-bold text-slate-700 outline-none border focus:ring-2 ring-indigo-500">
                    <option value="7">Last 7 days</option>
                    <option value="30">Last 30 days</option>
                    <option value="90">Last 90 days</option>
                    <option value="12m">Last 12 months</option>
                    <option value="custom">Custom range</option>
                </select>

                <template v-if="range === 'custom'">
                    <div class="flex items-center gap-1 bg-white border border-slate-200 rounded-lg px-2 py-1 shadow-sm">
                        <label class="text-[9px] font-black uppercase text-slate-400">From</label>
                        <input v-model="customFrom" type="date" :max="customTo || today"
                               @change="onCustomChange"
                               class="px-1 py-1 bg-transparent text-xs font-bold text-slate-700 outline-none" />
                    </div>
                    <div class="flex items-center gap-1 bg-white border border-slate-200 rounded-lg px-2 py-1 shadow-sm">
                        <label class="text-[9px] font-black uppercase text-slate-400">To</label>
                        <input v-model="customTo" type="date" :min="customFrom || undefined" :max="today"
                               @change="onCustomChange"
                               class="px-1 py-1 bg-transparent text-xs font-bold text-slate-700 outline-none" />
                    </div>
                    <button v-if="customFrom && customTo" @click="fetchData"
                            class="px-3 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-bold text-xs shadow-sm">
                        Apply
                    </button>
                </template>

                <button @click="downloadExport" class="p-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition shadow-sm" title="Export CSV">
                    <i class="lucide lucide-download w-5 h-5"></i>
                </button>
                <button @click="fetchData" class="px-4 py-2 bg-slate-900 text-white rounded-lg hover:bg-black transition font-bold text-sm flex items-center gap-2 shadow-lg">
                    <i class="lucide lucide-refresh-cw w-4 h-4" :class="{'animate-spin': loading}"></i> Refresh
                </button>
            </div>
        </div>

        <div v-if="rangeError" class="bg-red-50 border border-red-200 text-red-700 text-xs font-bold px-4 py-3 rounded-xl">
            {{ rangeError }}
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white p-5 rounded-2xl shadow-sm border-t-4 border-t-red-600">
                <div class="text-[10px] font-black uppercase text-red-600 tracking-widest">Overdue</div>
                <div class="mt-1 flex items-baseline gap-2">
                    <span class="text-3xl font-black text-slate-900">{{ summary.overdue_count || 0 }}</span>
                    <span class="text-[10px] font-bold text-slate-400">Cases</span>
                </div>
                <div class="text-[10px] text-slate-400 mt-2 font-bold uppercase italic">Past the police Pay By date</div>
            </div>

            <div class="bg-white p-5 rounded-2xl shadow-sm border-t-4 border-t-orange-500">
                <div class="text-[10px] font-black uppercase text-orange-500 tracking-widest">Due Soon</div>
                <div class="mt-1 flex items-baseline gap-2">
                    <span class="text-3xl font-black text-slate-900">{{ summary.due_soon_count || 0 }}</span>
                    <span class="text-[10px] font-bold text-slate-400">Cases</span>
                </div>
                <div class="text-[10px] text-slate-400 mt-2 font-bold uppercase italic">Not yet late, no penalty applied</div>
            </div>

            <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100 flex flex-col justify-between">
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-[9px] font-black uppercase text-slate-500">Outstanding Principal</span>
                        <span class="text-sm font-black text-slate-800">{{ formatCurrency(summary.total_principal) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-[9px] font-black uppercase text-red-500">Accrued Late Fees</span>
                        <span class="text-sm font-black text-red-600">{{ formatCurrency(summary.total_late_fees) }}</span>
                    </div>
                    <div class="flex justify-between items-center pt-2 border-t border-slate-100">
                        <span class="text-[9px] font-black uppercase text-slate-800">Total Owed</span>
                        <span class="text-sm font-black text-slate-900">{{ formatCurrency(outstandingTotal) }}</span>
                    </div>
                </div>
                <div class="mt-2 pt-2 border-t border-slate-50 text-[9px] font-bold text-slate-400 uppercase text-center">Current Collection Risk</div>
            </div>

            <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100">
                <div class="text-[10px] font-black uppercase text-indigo-500 tracking-widest">Settlement Rate</div>
                <div class="mt-1 text-3xl font-black text-slate-900">{{ calculateRatio() }}%</div>
                <div class="w-full bg-slate-100 h-1.5 mt-2 rounded-full overflow-hidden">
                    <div class="bg-indigo-500 h-full" :style="{width: calculateRatio() + '%'}"></div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                <div class="flex items-start justify-between gap-4 mb-4">
                    <div>
                        <h2 class="font-black text-slate-800 text-sm uppercase tracking-widest flex items-center gap-2">
                            <i class="lucide lucide-trending-up w-4 h-4 text-indigo-500"></i>
                            Issuance History
                        </h2>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mt-1">
                            Tickets by the date the offence occurred, as reported by police
                        </p>
                    </div>
                    <div class="text-right">
                        <div class="text-2xl font-black text-slate-900 leading-none">{{ issuedInRange }}</div>
                        <div class="text-[9px] font-black text-slate-400 uppercase tracking-widest mt-1">Issued in range</div>
                    </div>
                </div>
                <canvas ref="timeseriesCanvas" style="max-height: 320px;"></canvas>
                <p v-if="undatedCount" class="text-[10px] font-bold text-amber-600 uppercase tracking-wider mt-3">
                    {{ undatedCount }} ticket(s) have no police issue date and are excluded from this chart
                </p>
            </div>

            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                <h2 class="font-black text-slate-800 mb-4 text-sm uppercase tracking-widest">Unsettled Violations</h2>
                <div v-if="topViolations.length" class="space-y-2">
                    <div v-for="v in topViolations" :key="v.violation_name"
                         @click="openDrillDown('violation', v.violation_name)"
                         class="flex justify-between items-center p-3 rounded-xl hover:bg-slate-50 cursor-pointer transition border border-transparent hover:border-slate-200 group">
                        <span class="text-xs text-slate-700 font-bold group-hover:text-red-600">{{ v.violation_name }}</span>
                        <span class="text-[10px] font-black bg-red-50 px-2 py-1 rounded text-red-600">{{ v.occurrences }}</span>
                    </div>
                </div>
                <div v-else class="text-center py-10 text-slate-300 italic text-xs">No active violations found.</div>
            </div>
        </div>

        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-md">
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-6xl max-h-[90vh] flex flex-col border border-white/20">
                <div class="p-6 border-b bg-slate-50/50 flex flex-col lg:flex-row justify-between gap-4 rounded-t-3xl">
                    <div>
                        <h3 class="text-xl font-black text-slate-800 uppercase tracking-tight">{{ modalTitle }}</h3>
                        <p class="text-[10px] font-black text-red-600 uppercase tracking-widest flex items-center gap-1">
                            <i class="lucide lucide-alert-triangle w-3 h-3"></i> Filtered for Collection Risk
                        </p>
                    </div>

                    <div class="flex flex-1 gap-2 max-w-xl">
                        <select v-model="modalStatus" @change="fetchModalPage(1)" class="border px-3 py-2 rounded-xl text-xs font-black bg-white shadow-sm ring-2 ring-red-50 outline-none">
                            <option value="at_risk">⚠️ Priority Risk ({{ modalCounts.at_risk || 0 }})</option>
                            <option value="overdue">Past Pay By ({{ modalCounts.overdue || 0 }})</option>
                            <option value="outstanding">Outstanding ({{ modalCounts.outstanding || 0 }})</option>
                            <option value="paid">Settled Records ({{ modalCounts.paid || 0 }})</option>
                            <option value="">Show All Fines ({{ modalCounts.all || 0 }})</option>
                        </select>
                        <input v-model="modalSearch" @input="handleModalSearch" type="text" placeholder="Search plate..." class="flex-1 px-4 py-2 border rounded-xl text-sm outline-none focus:ring-2 ring-indigo-500" />
                    </div>
                    <button @click="closeModal" class="text-slate-400 hover:text-red-600 font-black px-2 transition">✕</button>
                </div>

                <div class="flex-1 overflow-auto p-6">
                    <table class="w-full text-left">
                        <thead class="text-[10px] font-black text-slate-400 uppercase border-b">
                        <tr><th class="pb-4">Plate / Ticket</th><th class="pb-4">Pay By</th><th class="pb-4">Financial Status</th><th class="pb-4 text-right">Total Owed</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                        <tr v-for="f in modalFines.data" :key="f.id" class="hover:bg-slate-50/80 transition group">
                            <td class="py-4">
                                <div class="font-black text-slate-800 uppercase text-sm tracking-tight">{{ f.plate_number }}</div>
                                <div class="text-[10px] text-slate-400 font-bold">{{ f.ticket_number }}</div>
                            </td>
                            <td class="py-4">
                                <div class="text-xs font-bold text-slate-600">{{ formatDate(f.pay_by) }}</div>
                                <div class="text-[9px] font-bold" :class="f.due_state === 'overdue' ? 'text-red-500' : 'text-slate-400'">
                                    {{ payByLabel(f) }}
                                </div>
                            </td>
                            <td class="py-4">
                                    <span :class="getBadgeClass(f)" class="px-2 py-1 rounded-lg text-[9px] font-black uppercase">
                                        {{ getStatusLabel(f) }}
                                    </span>
                            </td>
                            <td class="py-4 text-right">
                                <div class="font-black text-slate-900">{{ formatCurrency(f.total_due) }}</div>
                                <div class="text-[9px] text-slate-400 font-bold">{{ formatCurrency(f.ticket_amount) }} principal</div>
                                <div v-if="Number(f.late_fee) > 0" class="text-[9px] text-red-500 font-bold">+{{ formatCurrency(f.late_fee) }} penalty</div>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t flex justify-between items-center bg-slate-50 rounded-b-3xl">
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Page {{ modalFines.current_page }} / {{ modalFines.last_page }}</span>
                    <div class="flex gap-2">
                        <button :disabled="!modalFines.prev_page_url" @click="fetchModalPage(modalFines.current_page - 1)" class="px-4 py-2 border bg-white rounded-xl text-[10px] font-black disabled:opacity-30 uppercase">Prev</button>
                        <button :disabled="!modalFines.next_page_url" @click="fetchModalPage(modalFines.current_page + 1)" class="px-4 py-2 border bg-white rounded-xl text-[10px] font-black disabled:opacity-30 uppercase">Next</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { api } from "../../../plugins/axios";
import Chart from 'chart.js/auto';

const range = ref('30');
const customFrom = ref('');
const customTo = ref('');
const rangeError = ref('');
const summary = ref({});
const timeseries = ref([]);
const topViolations = ref([]);
const undatedCount = ref(0);
const timeseriesCanvas = ref(null);
const loading = ref(false);
let chartInstance = null;

const today = new Date().toISOString().slice(0, 10);
const bucket = computed(() => summary.value.bucket || 'day');

const issuedInRange = computed(() => timeseries.value.reduce((sum, d) => sum + (d.count || 0), 0));

// Params sent to the analytics endpoint, including the custom window when set.
function rangeParams() {
    const params = { range: range.value };
    if (range.value === 'custom') {
        if (customFrom.value) params.from = customFrom.value;
        if (customTo.value) params.to = customTo.value;
    }
    return params;
}

function onRangeChange() {
    rangeError.value = '';
    fetchData();
}

function onCustomChange() {
    rangeError.value = '';
    if (customFrom.value && customTo.value && customFrom.value > customTo.value) {
        rangeError.value = 'The start date must be on or before the end date.';
        return;
    }
    if (customFrom.value || customTo.value) fetchData();
}

const showModal = ref(false);
const modalType = ref('');
const modalValue = ref('');
const modalFines = ref({ data: [], current_page: 1, last_page: 1 });
const modalSearch = ref('');
const modalStatus = ref('at_risk');
const modalCounts = ref({});
let searchDebounce = null;

const modalTitle = computed(() => {
    if (modalType.value === 'date') return `Issued: ${modalValue.value}`;
    if (modalType.value === 'month') return `Issued: ${modalValue.value}`;
    return `Type: ${modalValue.value}`;
});

onMounted(() => fetchData());

async function fetchData() {
    loading.value = true;
    rangeError.value = '';
    try {
        const { data } = await api.get('/portal/fines/analytics', { params: rangeParams() });
        summary.value = data.summary;
        timeseries.value = data.timeseries;
        topViolations.value = data.top_violations;
        undatedCount.value = data.undated_count || 0;
        renderChart();
    } catch (e) {
        console.error(e);
        rangeError.value = e?.response?.data?.message || 'Could not load fines analytics for that range.';
    }
    finally { loading.value = false; }
}

function renderChart() {
    if (chartInstance) chartInstance.destroy();

    const isMonthly = bucket.value === 'month';

    chartInstance = new Chart(timeseriesCanvas.value, {
        type: 'line',
        data: {
            labels: timeseries.value.map(d => d.label || d.date),
            datasets: [{
                label: 'Tickets',
                data: timeseries.value.map(d => d.count),
                borderColor: '#4f46e5',
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                backgroundColor: 'rgba(79, 70, 229, 0.05)',
                pointRadius: 0,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            onClick: (e, el) => {
                if (!el.length) return;
                // Drill down on the raw bucket key, not the display label —
                // month labels are formatted for the axis and are not a date.
                const point = timeseries.value[el[0].index];
                if (!point) return;
                openDrillDown(isMonthly ? 'month' : 'date', point.date);
            },
            plugins: { legend: { display: false } },
            scales: {
                y: { grid: { color: '#f1f5f9' }, border: { display: false }, beginAtZero: true },
                x: { grid: { display: false }, ticks: { maxTicksLimit: 12, autoSkip: true, maxRotation: 0 } }
            }
        }
    });
}

function openDrillDown(type, value) {
    modalType.value = type;
    modalValue.value = value;
    modalStatus.value = 'at_risk';
    showModal.value = true;
    fetchModalPage(1);
}

async function fetchModalPage(page) {
    const isBucket = modalType.value === 'date' || modalType.value === 'month';
    const endpoint = isBucket ? '/portal/fines/by-day' : '/portal/fines/by-violation';
    const params = { page, from: summary.value.from, to: summary.value.to, search: modalSearch.value, status: modalStatus.value };

    if (modalType.value === 'month') params.month = modalValue.value;
    else if (modalType.value === 'date') params.date = modalValue.value;
    else params.violation_name = modalValue.value;

    try {
        const { data } = await api.get(endpoint, { params });
        modalFines.value = data.results;
        modalCounts.value = data.meta_counts;
    } catch (e) { console.error(e); }
}

const formatCurrency = (n) => Number(n || 0).toLocaleString('en-US', { maximumFractionDigits: 2 }) + ' RWF';
const calculateRatio = () => summary.value.total_count ? Math.round((summary.value.total_settled / summary.value.total_count) * 100) : 0;
const closeModal = () => showModal.value = false;
const handleModalSearch = () => { clearTimeout(searchDebounce); searchDebounce = setTimeout(() => fetchModalPage(1), 400); };
const downloadExport = () => {
    const params = new URLSearchParams(rangeParams()).toString();
    window.open(`/api/portal/fines/analytics?${params}&export=csv`, '_blank');
};

const outstandingTotal = computed(() =>
    Number(summary.value.total_principal || 0) + Number(summary.value.total_late_fees || 0)
);

const formatDate = (value) => {
    if (!value) return '—';
    return new Date(value).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
};

// Countdown text driven entirely by the portal's Pay By date.
const payByLabel = (f) => {
    if (!f.pay_by) return 'No pay-by date';
    if (f.due_state === 'settled') return 'Settled';

    const days = f.days_until_pay_by;
    if (days === null || days === undefined) return 'Due soon';
    if (days < 0) return `${Math.abs(days)}d overdue`;
    if (days === 0) return 'Due today';
    return `Due in ${days}d`;
};

// Badges follow the server-computed due state, which is derived from pay_by.
const getStatusLabel = (f) => {
    return {
        settled: 'Settled',
        overdue: 'Overdue',
        due_soon: 'Due Soon',
        due: 'Outstanding',
        unknown: 'No Pay By Date',
    }[f.due_state] || 'Outstanding';
};

const getBadgeClass = (f) => {
    return {
        settled: 'bg-emerald-50 text-emerald-600',
        overdue: 'bg-red-600 text-white',
        due_soon: 'bg-orange-500 text-white',
    }[f.due_state] || 'bg-slate-100 text-slate-600';
};
</script>
