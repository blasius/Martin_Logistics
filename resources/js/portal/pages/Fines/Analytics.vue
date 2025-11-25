<template>
    <div class="p-6 space-y-6">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold">Fines Analytics</h1>
                <div class="text-sm text-gray-500">Overview & insights</div>
            </div>

            <div class="flex items-center gap-2">
                <select v-model="range" @change="onRangeChange" class="border px-3 py-2 rounded">
                    <option value="7">Last 7 days</option>
                    <option value="30">Last 30 days</option>
                    <option value="90">Last 90 days</option>
                    <option value="custom">Custom</option>
                </select>

                <input v-if="range==='custom'" type="date" v-model="from" class="border px-2 py-2 rounded" />
                <input v-if="range==='custom'" type="date" v-model="to" class="border px-2 py-2 rounded" />

                <button @click="refresh" class="px-3 py-2 bg-sky-600 text-white rounded">Refresh</button>

                <button @click="exportCsvRange" title="Export CSV for current range" class="px-3 py-2 border rounded">Export CSV</button>
            </div>
        </div>

        <!-- summary cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white shadow rounded p-4">
                <div class="text-sm text-gray-500">Fines (count)</div>
                <div class="mt-2 text-xl font-semibold">{{ summary.total_count }}</div>
                <div class="text-sm text-gray-600">From {{ summary.from }} → {{ summary.to }}</div>
            </div>

            <div class="bg-white shadow rounded p-4">
                <div class="text-sm text-gray-500">Total amount</div>
                <div class="mt-2 text-xl font-semibold">{{ formatCurrency(summary.total_amount) }} RWF</div>
            </div>

            <div class="bg-white shadow rounded p-4">
                <div class="text-sm text-gray-500">Unpaid</div>
                <div class="mt-2 text-xl font-semibold">{{ summary.total_unpaid }}</div>
            </div>

            <div class="bg-white shadow rounded p-4">
                <div class="text-sm text-gray-500">Paid</div>
                <div class="mt-2 text-xl font-semibold">{{ summary.total_paid }}</div>
            </div>
        </div>

        <!-- charts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="bg-white p-4 rounded shadow">
                <div class="flex items-center justify-between">
                    <h2 class="font-semibold">Fines over time</h2>
                    <div class="text-sm text-gray-500">Click a bar to drill down</div>
                </div>
                <canvas ref="timeseriesCanvas" style="max-height:340px"></canvas>
            </div>

            <div class="bg-white p-4 rounded shadow">
                <h2 class="font-semibold mb-2">Top Violations</h2>
                <ul class="space-y-2 max-h-60 overflow-auto">
                    <li v-for="v in topViolations" :key="v.violation_name" class="flex justify-between">
                        <div class="text-sm">{{ v.violation_name }}</div>
                        <div class="text-sm font-medium">{{ v.occurrences }}</div>
                    </li>
                </ul>

                <div class="mt-4">
                    <h3 class="font-semibold mb-2">Top Vehicles</h3>
                    <ul class="space-y-2 max-h-40 overflow-auto">
                        <li v-for="v in topVehicles" :key="v.plate_number" class="flex justify-between">
                            <div class="font-medium">{{ v.plate_number }}</div>
                            <div class="text-sm text-gray-600">{{ v.count }} • {{ formatCurrency(v.total_amount) }}</div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Recent fines -->
        <div class="bg-white p-4 rounded shadow">
            <div class="flex items-center justify-between mb-2">
                <h3 class="font-semibold">Recent fines</h3>
                <div class="text-sm text-gray-500">Latest entries (in range)</div>
            </div>

            <div class="overflow-auto">
                <table class="min-w-full divide-y">
                    <thead><tr class="text-left text-xs text-gray-500">
                        <th class="px-3 py-2">Plate</th><th class="px-3 py-2">Ticket</th><th class="px-3 py-2">Amount</th><th class="px-3 py-2">Status</th><th class="px-3 py-2">Issued</th>
                    </tr></thead>
                    <tbody class="divide-y">
                    <tr v-for="r in recent" :key="r.id" class="hover:bg-gray-50">
                        <td class="px-3 py-2">{{ r.plate_number }}</td>
                        <td class="px-3 py-2">{{ r.ticket_number }}</td>
                        <td class="px-3 py-2">{{ formatCurrency(r.ticket_amount) }}</td>
                        <td class="px-3 py-2">{{ r.status }}</td>
                        <td class="px-3 py-2">{{ r.issued_at }}</td>
                    </tr>
                    <tr v-if="recent.length===0"><td colspan="5" class="px-3 py-4 text-gray-500">No recent fines.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Drill-down modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-start justify-center p-6">
            <div class="absolute inset-0 bg-black opacity-40" @click="closeModal"></div>
            <div class="bg-white rounded-lg shadow-lg w-full max-w-4xl z-60 p-4 overflow-auto">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold">Fines for {{ modalDate }}</h3>
                    <div class="flex items-center gap-2">
                        <button @click="exportDayCsv(modalDate)" class="px-3 py-1 border rounded text-sm">Export CSV</button>
                        <button @click="closeModal" class="px-3 py-1 bg-gray-200 rounded">Close</button>
                    </div>
                </div>

                <div v-if="modalLoading" class="text-gray-500">Loading...</div>

                <div v-else>
                    <div class="overflow-auto max-h-96">
                        <table class="min-w-full divide-y">
                            <thead><tr class="text-left text-xs text-gray-500">
                                <th class="px-3 py-2">Plate</th><th class="px-3 py-2">Ticket</th><th class="px-3 py-2">Amount</th><th class="px-3 py-2">Status</th><th class="px-3 py-2">Violations</th>
                            </tr></thead>
                            <tbody class="divide-y">
                            <tr v-for="f in modalFines.data" :key="f.id">
                                <td class="px-3 py-2">{{ f.plate_number }}</td>
                                <td class="px-3 py-2">{{ f.ticket_number }}</td>
                                <td class="px-3 py-2">{{ formatCurrency(f.ticket_amount) }}</td>
                                <td class="px-3 py-2">{{ f.status }}</td>
                                <td class="px-3 py-2">
                                    <ul class="list-disc ml-4">
                                        <li v-for="v in f.violations" :key="v.name">{{ v.name }} — {{ formatCurrency(v.amount) }}</li>
                                    </ul>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3 flex items-center gap-2">
                        <button :disabled="!modalFines.prev_page_url" @click="fetchModalPage(modalFines.current_page - 1)" class="px-3 py-1 border rounded">Prev</button>
                        <div>Page {{ modalFines.current_page }} / {{ modalFines.last_page }}</div>
                        <button :disabled="!modalFines.next_page_url" @click="fetchModalPage(modalFines.current_page + 1)" class="px-3 py-1 border rounded">Next</button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import { api } from '@/plugins/axios.js';
import Chart from 'chart.js/auto';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Echo init — adjust with your env config
window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    // Remove the invalid fallback
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_CLUSTER || 'mt1',
    wsHost: import.meta.env.VITE_WS_HOST || window.location.hostname,
    wsPort: import.meta.env.VITE_WS_PORT || 6001,
    forceTLS: false,
    disableStats: true,
    encrypted: false,
});

const summary = ref({ total_count:0, total_amount:0, total_unpaid:0, total_paid:0, from:'', to:'' });
const timeseries = ref([]);
const topViolations = ref([]);
const topVehicles = ref([]);
const topTrailers = ref([]);
const monthly = ref([]);
const recent = ref([]);

const range = ref('30');
const from = ref('');
const to = ref('');

const timeseriesCanvas = ref(null);
let timeseriesChart = null;

const showModal = ref(false);
const modalDate = ref('');
const modalLoading = ref(false);
const modalFines = ref({ data: [], current_page:1, last_page:1, prev_page_url:null, next_page_url:null });

onMounted(() => {
    // default dates when page loads, computed in fetch
    fetchData();

    // Real-time: listen for new fines and refresh summary + charts
    window.Echo.channel('traffic-fines').listen('.NewTrafficFine', (e) => {
        console.log('NewTrafficFine', e);
        // keep the UI responsive: fetchData could be rate limited if many events occur
        fetchDataDebounced();
    });
});

let debounceTimer = null;
const fetchDataDebounced = () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(fetchData, 800);
};

function parseRangeToParams() {
    if (range.value === 'custom') {
        return { range: 'custom', from: from.value, to: to.value };
    }
    return { range: range.value };
}

async function fetchData() {
    try {
        const params = parseRangeToParams();
        const res = await api.get('/api/portal/fines/analytics', { params });
        const data = res.data;
        summary.value = data.summary ?? summary.value;
        timeseries.value = data.timeseries ?? [];
        topViolations.value = data.top_violations ?? [];
        topVehicles.value = data.top_vehicles ?? [];
        topTrailers.value = data.top_trailers ?? [];
        monthly.value = data.monthly ?? [];
        recent.value = data.recent ?? [];
        renderTimeseries();
    } catch (err) {
        console.error('Failed to load analytics', err);
    }
}

const refresh = () => fetchData();

const formatCurrency = (n) => (n==null? '0' : Number(n).toLocaleString());

function renderTimeseries() {
    const labels = timeseries.value.map(x => x.date);
    const counts = timeseries.value.map(x => x.count);
    const amounts = timeseries.value.map(x => x.amount);

    if (timeseriesChart) timeseriesChart.destroy();

    const ctx = timeseriesCanvas.value.getContext('2d');
    timeseriesChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                { label: 'Count', data: counts, yAxisID: 'y1', backgroundColor: '#60A5FA' },
                { label: 'Amount (RWF)', data: amounts, yAxisID: 'y2', type: 'line', borderColor: '#10B981', tension: 0.3 },
            ]
        },
        options: {
            onClick(evt, elements) {
                if (!elements.length) return;
                const idx = elements[0].index;
                const day = labels[idx];
                openDrillDown(day);
            },
            scales: {
                y1: { position: 'left', beginAtZero:true },
                y2: { position: 'right', beginAtZero:true, grid: { drawOnChartArea: false } }
            },
            plugins: { legend: { position: 'bottom' } }
        }
    });
}

function openDrillDown(date) {
    modalDate.value = date;
    showModal.value = true;
    fetchModalPage(1);
}

async function fetchModalPage(page = 1) {
    modalLoading.value = true;
    try {
        const res = await api.get('/api/portal/fines/by-day', { params: { date: modalDate.value, page } });
        modalFines.value = res.data;
    } catch (e) {
        console.error('drill down error', e);
    } finally {
        modalLoading.value = false;
    }
}

function closeModal() {
    showModal.value = false;
    modalFines.value = { data: [], current_page:1, last_page:1, prev_page_url:null, next_page_url:null };
}

function onRangeChange() {
    if (range.value !== 'custom') {
        from.value = '';
        to.value = '';
        fetchData();
    }
}

// CSV export for current range
async function exportCsvRange() {
    const params = parseRangeToParams();
    params.export = 'csv';
    // direct link to endpoint to start download: create a full URL with querystring
    const query = new URLSearchParams(params).toString();
    window.location.href = `/api/portal/fines/analytics?${query}`;
}

// export CSV for modal day
function exportDayCsv(date) {
    window.location.href = `/api/portal/fines/export-day?date=${date}`;
}
</script>

<style scoped>
/* rely on Tailwind; small custom tweaks if needed */
</style>
