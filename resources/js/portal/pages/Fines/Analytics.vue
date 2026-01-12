<template>
    <div class="p-6 space-y-6 bg-slate-50 min-h-screen font-sans">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-slate-800 tracking-tight flex items-center gap-2">
                    <i class="lucide lucide-bar-chart-3 text-indigo-600"></i>
                    Fines Analytics
                </h1>
                <p class="text-sm text-slate-500 font-medium">Fleet-wide financial risk & compliance monitoring</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <select v-model="range" @change="onRangeChange" class="border-slate-200 px-3 py-2 rounded-lg bg-white shadow-sm outline-none text-sm font-bold text-slate-700 focus:ring-2 ring-indigo-500">
                    <option value="7">Last 7 days</option>
                    <option value="30">Last 30 days</option>
                    <option value="90">Last 90 days</option>
                    <option value="custom">Custom Range</option>
                </select>

                <div v-if="range === 'custom'" class="flex items-center gap-2">
                    <input type="date" v-model="from" class="border-slate-200 px-2 py-2 rounded-lg shadow-sm text-sm" />
                    <input type="date" v-model="to" class="border-slate-200 px-2 py-2 rounded-lg shadow-sm text-sm" />
                </div>

                <button @click="fetchData" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-bold text-sm flex items-center gap-2">
                    <i class="lucide lucide-refresh-cw" :class="{'animate-spin': loading}"></i>
                    Refresh
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-100">
                <div class="text-[10px] font-black uppercase text-slate-400 tracking-wider">Total Exposure</div>
                <div class="mt-1 text-2xl font-black text-slate-900">{{ formatCurrency(summary.total_amount) }} RWF</div>
                <div class="text-[10px] text-slate-400 mt-1">Sum of all tickets + late fees</div>
            </div>

            <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-100 border-l-4 border-l-red-500">
                <div class="text-[10px] font-black uppercase text-red-500 tracking-wider">Already Penalized</div>
                <div class="mt-1 text-2xl font-black text-red-600">{{ summary.already_penalized || 0 }} Fines</div>
                <div class="text-[10px] text-slate-400 mt-1">Late fees currently active</div>
            </div>

            <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-100 border-l-4 border-l-orange-400">
                <div class="text-[10px] font-black uppercase text-orange-500 tracking-wider">Urgent Risk</div>
                <div class="mt-1 text-2xl font-black text-orange-600">{{ summary.at_risk_count || 0 }} Fines</div>
                <div class="text-[10px] text-slate-400 mt-1">Deadlines in &lt; 72 hours</div>
            </div>

            <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-100">
                <div class="text-[10px] font-black uppercase text-indigo-500 tracking-wider">Settlement Rate</div>
                <div class="mt-1 text-2xl font-black text-slate-900">{{ calculateRatio() }}%</div>
                <div class="w-full bg-slate-100 h-1.5 mt-2 rounded-full overflow-hidden">
                    <div class="bg-indigo-500 h-full" :style="{width: calculateRatio() + '%'}"></div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-sm border border-slate-100">
                <h2 class="font-bold text-slate-800 mb-4 flex items-center gap-2">
                    <i class="lucide lucide-trending-up text-indigo-500"></i>
                    Fines Issuance Trends
                </h2>
                <canvas ref="timeseriesCanvas" style="max-height: 350px;"></canvas>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-100">
                <h2 class="font-bold text-slate-800 mb-4">Top Violations</h2>
                <div class="space-y-2">
                    <div v-for="v in topViolations" :key="v.violation_name"
                         @click="openDrillDown('violation', v.violation_name)"
                         class="flex justify-between items-center p-3 rounded-lg hover:bg-indigo-50 cursor-pointer transition border border-transparent hover:border-indigo-100 group">
                        <span class="text-sm text-slate-700 font-bold group-hover:text-indigo-700 underline underline-offset-4 decoration-slate-200">{{ v.violation_name }}</span>
                        <span class="text-xs font-black bg-slate-100 px-2 py-1 rounded text-slate-600 group-hover:bg-indigo-100">{{ v.occurrences }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-6xl max-h-[90vh] flex flex-col border border-slate-200">

                <div class="p-6 border-b bg-slate-50 rounded-t-2xl flex flex-col lg:flex-row justify-between gap-4">
                    <div>
                        <h3 class="text-xl font-black text-slate-800 uppercase tracking-tight">{{ modalTitle }}</h3>
                        <p class="text-xs text-slate-500 font-bold">Showing filtered results for fleet compliance</p>
                    </div>

                    <div class="flex flex-1 gap-2 max-w-xl">
                        <div class="relative flex-1">
                            <i class="lucide lucide-search absolute left-3 top-2.5 text-slate-400 w-4 h-4"></i>
                            <input v-model="modalSearch" @input="handleModalSearch" type="text" placeholder="Search plate..." class="w-full pl-10 pr-4 py-2 border rounded-lg text-sm outline-none focus:ring-2 ring-indigo-500 shadow-sm" />
                        </div>
                        <select v-model="modalStatus" @change="fetchModalPage(1)" class="border px-3 py-2 rounded-lg text-sm font-bold bg-white outline-none shadow-sm">
                            <option value="">All ({{ modalCounts.all }})</option>
                            <option value="PAID">Paid ({{ modalCounts.paid }})</option>
                            <option value="unpaid">Unpaid ({{ modalCounts.unpaid }})</option>
                            <option value="at_risk">⚠️ Risk / Overdue ({{ modalCounts.at_risk }})</option>
                        </select>
                    </div>

                    <div class="flex gap-2">
                        <button v-if="modalStatus === 'at_risk'" class="px-4 py-2 bg-orange-500 text-white rounded-lg text-xs font-black hover:bg-orange-600 transition shadow-sm uppercase">Notify Drivers</button>
                        <button @click="closeModal" class="p-2 hover:bg-slate-200 rounded-full transition">✕</button>
                    </div>
                </div>

                <div class="flex-1 overflow-auto p-6">
                    <div v-if="modalLoading" class="flex flex-col items-center justify-center py-20 gap-4">
                        <div class="animate-spin h-10 w-10 border-4 border-indigo-500 border-t-transparent rounded-full"></div>
                        <p class="text-sm font-bold text-slate-400 uppercase tracking-widest">Loading Records...</p>
                    </div>

                    <table v-else class="w-full">
                        <thead class="text-[10px] font-black text-slate-400 uppercase border-b tracking-wider">
                        <tr>
                            <th class="pb-4 text-left px-2">Plate / Ticket</th>
                            <th class="pb-4 text-left px-2">Urgency & Timeline</th>
                            <th class="pb-4 text-right px-2">Financials</th>
                            <th class="pb-4 text-center px-2">Actions</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                        <tr v-for="f in modalFines.data" :key="f.id" :class="getUrgencyClass(f)" class="transition-colors group border-b border-slate-100">
                            <td class="py-4 px-2">
                                <div class="font-black text-slate-800 uppercase tracking-tight">{{ f.plate_number }}</div>
                                <div class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">{{ f.ticket_number }}</div>
                            </td>

                            <td class="py-4 px-2">
                                <div class="flex items-center gap-2">
                                        <span :class="getUrgencyBadgeClass(f)" class="px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-tighter">
                                            {{ getRelativeLabel(f.pay_by, f.status, f.late_fee) }}
                                        </span>
                                </div>
                                <div class="text-[11px] font-bold text-slate-500 mt-1 flex items-center gap-1">
                                    <i class="lucide lucide-calendar-check w-3 h-3"></i>
                                    Issued: {{ formatDate(f.issued_at) }}
                                </div>
                            </td>

                            <td class="py-4 px-2 text-right">
                                <div class="font-black text-slate-900">{{ formatCurrency(Number(f.ticket_amount) + Number(f.late_fee)) }} RWF</div>
                                <div v-if="f.late_fee > 0" class="text-[9px] text-red-600 font-black uppercase italic">
                                    + {{ formatCurrency(f.late_fee) }} penalty applied
                                </div>
                            </td>

                            <td class="py-4 px-2 text-center">
                                <button v-if="f.status !== 'PAID'" class="px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-[10px] font-black text-indigo-600 hover:bg-indigo-600 hover:text-white transition shadow-sm uppercase tracking-tighter">
                                    Clear Fine
                                </button>
                                <div v-else class="text-green-500 font-black text-[10px] flex items-center justify-center gap-1 uppercase tracking-widest">
                                    <i class="lucide lucide-check-circle-2 w-3 h-3"></i>
                                    Settled
                                </div>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t flex justify-between items-center bg-slate-50 rounded-b-2xl">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">
                        Page {{ modalFines.current_page }} of {{ modalFines.last_page }}
                    </span>
                    <div class="flex gap-2">
                        <button :disabled="!modalFines.prev_page_url" @click="fetchModalPage(modalFines.current_page - 1)" class="px-4 py-2 border bg-white rounded-lg disabled:opacity-30 text-xs font-bold shadow-sm">Previous</button>
                        <button :disabled="!modalFines.next_page_url" @click="fetchModalPage(modalFines.current_page + 1)" class="px-4 py-2 border bg-white rounded-lg disabled:opacity-30 text-xs font-bold shadow-sm">Next</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { api } from '@/plugins/axios.js';
import Chart from 'chart.js/auto';

// --- Dashboard State ---
const range = ref('30');
const from = ref('');
const to = ref('');
const summary = ref({});
const timeseries = ref([]);
const topViolations = ref([]);
const timeseriesCanvas = ref(null);
const loading = ref(false);
let chartInstance = null;

// --- Modal State ---
const showModal = ref(false);
const modalType = ref('');
const modalValue = ref('');
const modalFines = ref({ data: [], current_page: 1, last_page: 1 });
const modalSearch = ref('');
const modalStatus = ref('');
const modalCounts = ref({ all: 0, paid: 0, unpaid: 0, at_risk: 0 });
const modalLoading = ref(false);
let searchDebounce = null;

const modalTitle = computed(() => modalType.value === 'date' ? `Fines on ${modalValue.value}` : `${modalValue.value} Violations`);

// --- Date Helpers (Frontend) ---

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    // Remove Time portion for clean parsing
    const datePart = dateString.split('T')[0];
    return new Intl.DateTimeFormat('en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    }).format(new Date(datePart));
};

const getRelativeDays = (dateString) => {
    if (!dateString) return null;
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const deadline = new Date(dateString.split('T')[0]);
    deadline.setHours(0, 0, 0, 0);

    const diffTime = deadline - today;
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
};

const getRelativeLabel = (dateString, status, lateFee) => {
    if (status === 'PAID') return 'Settled';
    const days = getRelativeDays(dateString);
    if (lateFee > 0 || days < 0) return `${Math.abs(days)}d Overdue`;
    if (days === 0) return 'Expires Today';
    if (days === 1) return 'Expires Tomorrow';
    return `${days} days left`;
};

// --- Styling Logic ---

const getUrgencyBadgeClass = (f) => {
    if (f.status === 'PAID') return 'bg-green-100 text-green-700';
    const days = getRelativeDays(f.pay_by);
    if (f.late_fee > 0 || days < 0) return 'bg-red-600 text-white animate-pulse';
    if (days <= 3) return 'bg-orange-500 text-white';
    if (days <= 7) return 'bg-amber-400 text-amber-900';
    return 'bg-slate-200 text-slate-700';
};

const getUrgencyClass = (f) => {
    if (f.status === 'PAID') return 'opacity-60 grayscale-[0.5]';
    const days = getRelativeDays(f.pay_by);
    if (f.late_fee > 0 || days < 0) return 'bg-red-50/50 hover:bg-red-50';
    if (days <= 3) return 'bg-orange-50/50 hover:bg-orange-50';
    return 'hover:bg-slate-50';
};

// --- Actions & Data ---

onMounted(() => fetchData());

async function fetchData() {
    loading.value = true;
    const params = range.value === 'custom' ? { range: 'custom', from: from.value, to: to.value } : { range: range.value };
    try {
        const { data } = await api.get('/api/portal/fines/analytics', { params });
        summary.value = data.summary;
        timeseries.value = data.timeseries;
        topViolations.value = data.top_violations;
        renderChart();
    } catch (e) { console.error(e); }
    finally { loading.value = false; }
}

function renderChart() {
    if (chartInstance) chartInstance.destroy();
    chartInstance = new Chart(timeseriesCanvas.value, {
        type: 'line',
        data: {
            labels: timeseries.value.map(d => d.date),
            datasets: [{
                label: 'Fines Count',
                data: timeseries.value.map(d => d.count),
                borderColor: '#4f46e5',
                backgroundColor: 'rgba(79, 70, 229, 0.05)',
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            onClick: (e, el) => { if(el.length) openDrillDown('date', chartInstance.data.labels[el[0].index]); },
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, grid: { color: '#f1f5f9' } }, x: { grid: { display: false } } }
        }
    });
}

function openDrillDown(type, value) {
    modalType.value = type;
    modalValue.value = value;
    modalSearch.value = '';
    modalStatus.value = '';
    showModal.value = true;
    fetchModalPage(1);
}

async function fetchModalPage(page) {
    modalLoading.value = true;
    const endpoint = modalType.value === 'date' ? '/api/portal/fines/by-day' : '/api/portal/fines/by-violation';
    const params = { page, from: summary.value.from, to: summary.value.to, search: modalSearch.value, status: modalStatus.value };
    if (modalType.value === 'date') params.date = modalValue.value;
    else params.violation_name = modalValue.value;

    try {
        const { data } = await api.get(endpoint, { params });
        modalFines.value = data.results;
        modalCounts.value = data.meta_counts;
    } catch (e) { console.error(e); }
    finally { modalLoading.value = false; }
}

function handleModalSearch() {
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(() => fetchModalPage(1), 400);
}

const calculateRatio = () => summary.value.total_count ? Math.round((summary.value.total_paid / summary.value.total_count) * 100) : 0;
const formatCurrency = (n) => Number(n || 0).toLocaleString();
const onRangeChange = () => { if(range.value !== 'custom') fetchData(); };
const closeModal = () => showModal.value = false;
</script>

<style scoped>
/* Scoped Lucide Icon sizes */
.lucide { width: 1.25rem; height: 1.25rem; }
</style>
