<template>
    <div class="p-6 space-y-6 bg-slate-50 min-h-screen font-sans">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-slate-800 flex items-center gap-2 uppercase tracking-tight">
                    <i class="lucide lucide-alert-octagon text-red-600"></i>
                    Risk & Penalties
                </h1>
                <p class="text-sm text-slate-500 font-medium italic">Active financial exposure from unsettled fines</p>
            </div>

            <div class="flex items-center gap-2">
                <select v-model="range" @change="fetchData" class="border-slate-200 px-3 py-2 rounded-lg bg-white shadow-sm text-sm font-bold text-slate-700 outline-none border focus:ring-2 ring-indigo-500">
                    <option value="7">Last 7 days</option>
                    <option value="30">Last 30 days</option>
                    <option value="90">Last 90 days</option>
                </select>
                <button @click="downloadExport" class="p-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition shadow-sm" title="Export CSV">
                    <i class="lucide lucide-download w-5 h-5"></i>
                </button>
                <button @click="fetchData" class="px-4 py-2 bg-slate-900 text-white rounded-lg hover:bg-black transition font-bold text-sm flex items-center gap-2 shadow-lg">
                    <i class="lucide lucide-refresh-cw w-4 h-4" :class="{'animate-spin': loading}"></i> Refresh
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white p-5 rounded-2xl shadow-sm border-t-4 border-t-red-600">
                <div class="text-[10px] font-black uppercase text-red-600 tracking-widest">Penalized Fines</div>
                <div class="mt-1 flex items-baseline gap-2">
                    <span class="text-3xl font-black text-slate-900">{{ summary.penalized_count || 0 }}</span>
                    <span class="text-[10px] font-bold text-slate-400">Cases</span>
                </div>
                <div class="text-[10px] text-slate-400 mt-2 font-bold uppercase italic">Already carrying late fees</div>
            </div>

            <div class="bg-white p-5 rounded-2xl shadow-sm border-t-4 border-t-orange-500">
                <div class="text-[10px] font-black uppercase text-orange-500 tracking-widest">Expiring Soon</div>
                <div class="mt-1 flex items-baseline gap-2">
                    <span class="text-3xl font-black text-slate-900">{{ summary.expiring_soon_count || 0 }}</span>
                    <span class="text-[10px] font-bold text-slate-400">Cases</span>
                </div>
                <div class="text-[10px] text-slate-400 mt-2 font-bold uppercase italic">Due within 72 hours</div>
            </div>

            <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100 flex flex-col justify-between">
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-[9px] font-black uppercase text-red-500">Total Penalties</span>
                        <span class="text-sm font-black text-red-600">{{ formatCurrency(summary.total_penalties_amount) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-[9px] font-black uppercase text-orange-500">Expiring Principal</span>
                        <span class="text-sm font-black text-orange-600">{{ formatCurrency(summary.total_expiring_tickets_amount) }}</span>
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
                <h2 class="font-black text-slate-800 mb-4 text-sm uppercase tracking-widest flex items-center gap-2">
                    <i class="lucide lucide-trending-up w-4 h-4 text-indigo-500"></i>
                    Issuance History
                </h2>
                <canvas ref="timeseriesCanvas" style="max-height: 320px;"></canvas>
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
                            <option value="at_risk">⚠️ Priority Risk ({{ modalCounts.at_risk }})</option>
                            <option value="paid">Settled Records ({{ modalCounts.paid }})</option>
                            <option value="">Show All Fines ({{ modalCounts.all }})</option>
                        </select>
                        <input v-model="modalSearch" @input="handleModalSearch" type="text" placeholder="Search plate..." class="flex-1 px-4 py-2 border rounded-xl text-sm outline-none focus:ring-2 ring-indigo-500" />
                    </div>
                    <button @click="closeModal" class="text-slate-400 hover:text-red-600 font-black px-2 transition">✕</button>
                </div>

                <div class="flex-1 overflow-auto p-6">
                    <table class="w-full text-left">
                        <thead class="text-[10px] font-black text-slate-400 uppercase border-b">
                        <tr><th class="pb-4">Plate / Ticket</th><th class="pb-4">Financial Status</th><th class="pb-4 text-right">Total Owed</th><th class="pb-4 text-center">Action</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                        <tr v-for="f in modalFines.data" :key="f.id" class="hover:bg-slate-50/80 transition group">
                            <td class="py-4">
                                <div class="font-black text-slate-800 uppercase text-sm tracking-tight">{{ f.plate_number }}</div>
                                <div class="text-[10px] text-slate-400 font-bold">{{ f.ticket_number }}</div>
                            </td>
                            <td class="py-4">
                                    <span :class="getBadgeClass(f)" class="px-2 py-1 rounded-lg text-[9px] font-black uppercase">
                                        {{ getStatusLabel(f) }}
                                    </span>
                            </td>
                            <td class="py-4 text-right">
                                <div class="font-black text-slate-900">{{ formatCurrency(Number(f.ticket_amount) + Number(f.late_fee)) }}</div>
                                <div v-if="f.late_fee > 0" class="text-[9px] text-red-500 font-bold">+{{ formatCurrency(f.late_fee) }} penalty</div>
                            </td>
                            <td class="py-4 text-center">
                                <button class="bg-slate-100 text-[10px] font-black text-slate-600 px-4 py-2 rounded-xl hover:bg-indigo-600 hover:text-white transition uppercase tracking-tighter">Details</button>
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
import { api } from '@/plugins/axios.js';
import Chart from 'chart.js/auto';

const range = ref('30');
const summary = ref({});
const timeseries = ref([]);
const topViolations = ref([]);
const timeseriesCanvas = ref(null);
const loading = ref(false);
let chartInstance = null;

const showModal = ref(false);
const modalType = ref('');
const modalValue = ref('');
const modalFines = ref({ data: [], current_page: 1, last_page: 1 });
const modalSearch = ref('');
const modalStatus = ref('at_risk');
const modalCounts = ref({});
let searchDebounce = null;

const modalTitle = computed(() => modalType.value === 'date' ? `Dated: ${modalValue.value}` : `Type: ${modalValue.value}`);

onMounted(() => fetchData());

async function fetchData() {
    loading.value = true;
    try {
        const { data } = await api.get('/api/portal/fines/analytics', { params: { range: range.value } });
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
            datasets: [{ label: 'Tickets', data: timeseries.value.map(d => d.count), borderColor: '#4f46e5', borderWidth: 3, tension: 0.4, fill: true, backgroundColor: 'rgba(79, 70, 229, 0.05)', pointRadius: 0, pointHoverRadius: 6 }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            onClick: (e, el) => { if(el.length) openDrillDown('date', chartInstance.data.labels[el[0].index]); },
            plugins: { legend: { display: false } },
            scales: { y: { grid: { color: '#f1f5f9' }, border: { display: false } }, x: { grid: { display: false } } }
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
    const endpoint = modalType.value === 'date' ? '/api/portal/fines/by-day' : '/api/portal/fines/by-violation';
    const params = { page, from: summary.value.from, to: summary.value.to, search: modalSearch.value, status: modalStatus.value };
    if (modalType.value === 'date') params.date = modalValue.value;
    else params.violation_name = modalValue.value;

    try {
        const { data } = await api.get(endpoint, { params });
        modalFines.value = data.results;
        modalCounts.value = data.meta_counts;
    } catch (e) { console.error(e); }
}

const formatCurrency = (n) => Number(n || 0).toLocaleString() + ' RWF';
const calculateRatio = () => summary.value.total_count ? Math.round((summary.value.total_paid / summary.value.total_count) * 100) : 0;
const closeModal = () => showModal.value = false;
const handleModalSearch = () => { clearTimeout(searchDebounce); searchDebounce = setTimeout(() => fetchModalPage(1), 400); };
const downloadExport = () => window.open(`/api/portal/fines/analytics?export=csv&range=${range.value}`, '_blank');

const getStatusLabel = (f) => {
    if (f.status === 'PAID') return 'Settled';
    if (f.late_fee > 0) return 'Overdue';
    return 'Expiring Soon';
};

const getBadgeClass = (f) => {
    if (f.status === 'PAID') return 'bg-emerald-50 text-emerald-600';
    if (f.late_fee > 0) return 'bg-red-600 text-white';
    return 'bg-orange-500 text-white';
};
</script>
