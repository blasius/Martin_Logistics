<template>
    <div class="p-6 space-y-6 bg-gray-50 min-h-screen">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Fines Analytics</h1>
                <p class="text-sm text-gray-500">Comprehensive traffic violation management</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <select v-model="range" @change="onRangeChange" class="border px-3 py-2 rounded-lg bg-white shadow-sm outline-none">
                    <option value="7">Last 7 days</option>
                    <option value="30">Last 30 days</option>
                    <option value="90">Last 90 days</option>
                    <option value="custom">Custom Range</option>
                </select>

                <div v-if="range === 'custom'" class="flex items-center gap-2">
                    <input type="date" v-model="from" class="border px-2 py-2 rounded-lg shadow-sm" />
                    <input type="date" v-model="to" class="border px-2 py-2 rounded-lg shadow-sm" />
                </div>

                <button @click="fetchData" class="px-4 py-2 bg-sky-600 text-white rounded-lg hover:bg-sky-700 transition">Refresh</button>
                <button @click="exportMainCsv" class="px-4 py-2 border bg-white rounded-lg hover:bg-gray-50 transition">Export Range</button>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div v-for="(val, label) in summaryCards" :key="label" class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                <div class="text-xs font-bold uppercase text-gray-400 tracking-wider">{{ label }}</div>
                <div class="mt-2 text-2xl font-bold text-gray-800">{{ val }}</div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-sm border">
                <h2 class="font-bold text-gray-700 mb-4">Fines Over Time</h2>
                <canvas ref="timeseriesCanvas" class="w-full" style="max-height: 350px;"></canvas>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm border">
                <h2 class="font-bold text-gray-700 mb-2">Top Violations</h2>
                <p class="text-xs text-gray-400 mb-4 italic text-center">Click a violation to see details</p>
                <div class="space-y-1">
                    <div v-for="v in topViolations" :key="v.violation_name"
                         @click="openDrillDown('violation', v.violation_name)"
                         class="flex justify-between items-center p-3 rounded-lg hover:bg-sky-50 cursor-pointer transition border border-transparent hover:border-sky-100">
                        <span class="text-sm text-sky-700 font-medium underline">{{ v.violation_name }}</span>
                        <span class="text-xs font-bold bg-gray-100 px-2 py-1 rounded text-gray-600">{{ v.occurrences }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-6xl max-h-[90vh] flex flex-col">

                <div class="p-6 border-b bg-gray-50 rounded-t-2xl">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                        <h3 class="text-xl font-bold text-gray-800">{{ modalTitle }}</h3>

                        <div class="flex flex-1 flex-col sm:flex-row gap-2 max-w-2xl">
                            <div class="relative flex-1">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">🔍</span>
                                <input v-model="modalSearch" @input="handleModalSearch" type="text" placeholder="Search Plate Number..."
                                       class="w-full pl-10 pr-4 py-2 border rounded-lg focus:ring-2 focus:ring-sky-500 outline-none text-sm" />
                            </div>

                            <select v-model="modalStatus" @change="fetchModalPage(1)" class="border px-3 py-2 rounded-lg bg-white shadow-sm outline-none text-sm font-medium">
                                <option value="">All Fines ({{ modalCounts.all }})</option>
                                <option value="PAID">Paid ({{ modalCounts.paid }})</option>
                                <option value="unpaid">Unpaid ({{ modalCounts.unpaid }})</option>
                            </select>
                        </div>

                        <div class="flex gap-2">
                            <button @click="exportModalCsv" class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-medium">Export</button>
                            <button @click="closeModal" class="p-2 hover:bg-gray-200 rounded-full text-gray-500">✕</button>
                        </div>
                    </div>
                </div>

                <div class="flex-1 overflow-auto p-6">
                    <div v-if="modalLoading" class="flex justify-center py-20">
                        <div class="animate-spin h-10 w-10 border-4 border-sky-500 border-t-transparent rounded-full"></div>
                    </div>

                    <table v-else class="w-full text-left">
                        <thead>
                        <tr class="text-xs font-bold text-gray-400 uppercase border-b">
                            <th class="pb-3 px-2">Plate</th><th class="pb-3 px-2">Ticket #</th><th class="pb-3 px-2 text-right">Amount</th><th class="pb-3 px-2 text-center">Status</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y">
                        <tr v-for="f in modalFines.data" :key="f.id" class="hover:bg-gray-50 transition">
                            <td class="py-4 px-2 font-mono text-sm font-bold text-gray-700 uppercase">{{ f.plate_number }}</td>
                            <td class="py-4 px-2 text-sm text-gray-600">{{ f.ticket_number }}</td>
                            <td class="py-4 px-2 font-bold text-gray-800 text-right">{{ formatCurrency(f.ticket_amount) }}</td>
                            <td class="py-4 px-2 text-center">
                  <span class="px-3 py-1 rounded-full text-xs font-bold"
                        :class="f.status === 'PAID' ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700'">
                    {{ f.status }}
                  </span>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t flex justify-between items-center bg-gray-50 rounded-b-2xl">
                    <span class="text-sm text-gray-500">Page {{ modalFines.current_page }} of {{ modalFines.last_page }}</span>
                    <div class="flex gap-2">
                        <button :disabled="!modalFines.prev_page_url" @click="fetchModalPage(modalFines.current_page - 1)" class="px-4 py-2 border bg-white rounded-lg disabled:opacity-30">Previous</button>
                        <button :disabled="!modalFines.next_page_url" @click="fetchModalPage(modalFines.current_page + 1)" class="px-4 py-2 border bg-white rounded-lg disabled:opacity-30">Next</button>
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

// Main Dashboard Refs
const range = ref('30');
const from = ref('');
const to = ref('');
const summary = ref({});
const timeseries = ref([]);
const topViolations = ref([]);
const timeseriesCanvas = ref(null);
let chartInstance = null;

// Modal Refs
const showModal = ref(false);
const modalType = ref('');
const modalValue = ref('');
const modalLoading = ref(false);
const modalFines = ref({ data: [], current_page: 1, last_page: 1 });
const modalSearch = ref('');
const modalStatus = ref('');
const modalCounts = ref({ all: 0, paid: 0, unpaid: 0 }); // NEW
let searchDebounce = null;

const summaryCards = computed(() => ({
    'Total Fines': summary.value.total_count || 0,
    'Total Revenue': formatCurrency(summary.value.total_amount) + ' RWF',
    'Pending Unpaid': summary.value.total_unpaid || 0,
    'Settled Paid': summary.value.total_paid || 0,
}));

const modalTitle = computed(() => modalType.value === 'date' ? `Fines on ${modalValue.value}` : `Violation: ${modalValue.value}`);

onMounted(() => fetchData());

async function fetchData() {
    const params = range.value === 'custom' ? { range: 'custom', from: from.value, to: to.value } : { range: range.value };
    try {
        const { data } = await api.get('/api/portal/fines/analytics', { params });
        summary.value = data.summary;
        timeseries.value = data.timeseries;
        topViolations.value = data.top_violations;
        renderChart();
    } catch (e) { console.error(e); }
}

function renderChart() {
    if (chartInstance) chartInstance.destroy();
    chartInstance = new Chart(timeseriesCanvas.value, {
        type: 'bar',
        data: {
            labels: timeseries.value.map(d => d.date),
            datasets: [{ label: 'Fines', data: timeseries.value.map(d => d.count), backgroundColor: '#0ea5e9', borderRadius: 4 }]
        },
        options: {
            onClick: (e, el) => { if(el.length) openDrillDown('date', chartInstance.data.labels[el[0].index]); },
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
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
        // Handle the new response structure
        modalFines.value = data.results;
        modalCounts.value = data.meta_counts;
    } catch (e) { console.error(e); }
    finally { modalLoading.value = false; }
}

function handleModalSearch() {
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(() => fetchModalPage(1), 400);
}

function closeModal() {
    showModal.value = false;
    modalSearch.value = '';
    modalStatus.value = '';
}

const formatCurrency = (n) => Number(n || 0).toLocaleString();
const onRangeChange = () => { if(range.value !== 'custom') fetchData(); };

const exportMainCsv = () => {
    const p = range.value === 'custom' ? `range=custom&from=${from.value}&to=${to.value}` : `range=${range.value}`;
    window.location.href = `/api/portal/fines/analytics?${p}&export=csv`;
};

const exportModalCsv = () => {
    const base = modalType.value === 'date'
        ? `/api/portal/fines/export-day?date=${modalValue.value}`
        : `/api/portal/fines/export-violation?violation_name=${modalValue.value}&from=${summary.value.from}&to=${summary.value.to}`;
    window.location.href = `${base}&search=${modalSearch.value}&status=${modalStatus.value}`;
};
</script>
