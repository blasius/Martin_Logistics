<template>
    <div class="p-6 space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold">Fines Analytics</h1>
            <div class="flex items-center gap-3">
                <button @click="refresh" class="px-3 py-2 bg-sky-600 text-white rounded hover:bg-sky-500">Refresh</button>
            </div>
        </div>

        <!-- Summary cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white shadow rounded p-4">
                <div class="text-sm text-gray-500">Fines this month</div>
                <div class="mt-2 text-xl font-semibold">{{ summary.total_this_month }}</div>
                <div class="text-sm text-gray-600">{{ formatCurrency(summary.total_this_month_amount) }} RWF</div>
            </div>

            <div class="bg-white shadow rounded p-4">
                <div class="text-sm text-gray-500">Unpaid fines</div>
                <div class="mt-2 text-xl font-semibold">{{ summary.total_unpaid }}</div>
                <div class="text-sm text-gray-600">Outstanding</div>
            </div>

            <div class="bg-white shadow rounded p-4">
                <div class="text-sm text-gray-500">Paid fines</div>
                <div class="mt-2 text-xl font-semibold">{{ summary.total_paid }}</div>
                <div class="text-sm text-gray-600">All-time</div>
            </div>

            <div class="bg-white shadow rounded p-4">
                <div class="text-sm text-gray-500">This month amount</div>
                <div class="mt-2 text-xl font-semibold">{{ formatCurrency(summary.total_this_month_amount) }} RWF</div>
                <div class="text-sm text-gray-600">Revenue/Expense</div>
            </div>
        </div>

        <!-- Charts grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <!-- Timeseries: fines over last 30 days -->
            <div class="bg-white p-4 rounded shadow">
                <div class="flex items-center justify-between mb-2">
                    <h2 class="font-semibold">Fines - Last 30 days</h2>
                    <div class="text-sm text-gray-500">Counts & Amounts</div>
                </div>
                <canvas id="timeseriesChart" ref="timeseriesCanvas" style="max-height:320px"></canvas>
            </div>

            <!-- Monthly totals and pie -->
            <div class="bg-white p-4 rounded shadow space-y-4">
                <div>
                    <h2 class="font-semibold">Monthly totals (12 months)</h2>
                    <canvas id="monthlyChart" ref="monthlyCanvas" style="max-height:200px"></canvas>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <h3 class="text-sm font-medium mb-2">Top Violations</h3>
                        <ul class="space-y-2 max-h-40 overflow-auto">
                            <li v-for="v in topViolations" :key="v.violation_name" class="flex justify-between">
                                <div class="text-sm">{{ v.violation_name }}</div>
                                <div class="text-sm font-medium">{{ v.occurrences }}</div>
                            </li>
                        </ul>
                    </div>

                    <div>
                        <h3 class="text-sm font-medium mb-2">Vehicles vs Trailers</h3>
                        <canvas id="typePie" ref="typePieCanvas" style="max-height:160px"></canvas>
                        <div class="text-xs text-gray-500 mt-2">Top vehicles & trailers by count listed below.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top lists -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="bg-white p-4 rounded shadow">
                <h3 class="font-semibold mb-2">Top Vehicles (by fines)</h3>
                <ul class="divide-y">
                    <li v-for="v in topVehicles" :key="v.plate_number" class="py-2 flex justify-between">
                        <div class="font-medium">{{ v.plate_number }}</div>
                        <div class="text-sm text-gray-600">{{ v.count }} • {{ formatCurrency(v.total_amount) }} RWF</div>
                    </li>
                </ul>
            </div>

            <div class="bg-white p-4 rounded shadow">
                <h3 class="font-semibold mb-2">Top Trailers (by fines)</h3>
                <ul class="divide-y">
                    <li v-for="t in topTrailers" :key="t.plate_number" class="py-2 flex justify-between">
                        <div class="font-medium">{{ t.plate_number }}</div>
                        <div class="text-sm text-gray-600">{{ t.count }} • {{ formatCurrency(t.total_amount) }} RWF</div>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Recent fines table -->
        <div class="bg-white p-4 rounded shadow">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold">Recent fines</h3>
                <div class="text-sm text-gray-500">Latest 10</div>
            </div>
            <div class="overflow-auto">
                <table class="min-w-full divide-y">
                    <thead>
                    <tr class="text-left text-xs text-gray-500">
                        <th class="px-3 py-2">Plate</th>
                        <th class="px-3 py-2">Ticket #</th>
                        <th class="px-3 py-2">Amount</th>
                        <th class="px-3 py-2">Status</th>
                        <th class="px-3 py-2">Issued</th>
                        <th class="px-3 py-2">Violations</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y">
                    <tr v-for="r in recent" :key="r.id">
                        <td class="px-3 py-2">{{ r.plate_number }}</td>
                        <td class="px-3 py-2">{{ r.ticket_number }}</td>
                        <td class="px-3 py-2">{{ formatCurrency(r.ticket_amount) }} RWF</td>
                        <td class="px-3 py-2">{{ r.status }}</td>
                        <td class="px-3 py-2">{{ r.issued_at }}</td>
                        <td class="px-3 py-2">
                            <ul class="list-disc ml-4">
                                <li v-for="v in r.violations" :key="v.name" class="text-sm">{{ v.name }} — {{ formatCurrency(v.amount) }}</li>
                            </ul>
                        </td>
                    </tr>
                    <tr v-if="recent.length === 0">
                        <td colspan="6" class="px-3 py-4 text-gray-500">No recent fines.</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { api } from '@/plugins/axios.js';
import Chart from 'chart.js/auto';

const summary = ref({
    total_this_month: 0,
    total_this_month_amount: 0,
    total_unpaid: 0,
    total_paid: 0,
});
const timeseries = ref([]); // {date,count,amount}
const topViolations = ref([]);
const topVehicles = ref([]);
const topTrailers = ref([]);
const monthly = ref([]); // {month,count,amount}
const recent = ref([]);

const timeseriesCanvas = ref(null);
const monthlyCanvas = ref(null);
const typePieCanvas = ref(null);

let timeseriesChart = null;
let monthlyChart = null;
let typePieChart = null;

const fetchData = async () => {
    try {
        const res = await api.get('/api/portal/fines/analytics');
        const data = res.data;

        summary.value = data.summary ?? summary.value;
        timeseries.value = data.timeseries ?? [];
        topViolations.value = data.top_violations ?? [];
        topVehicles.value = data.top_vehicles ?? [];
        topTrailers.value = data.top_trailers ?? [];
        monthly.value = data.monthly ?? [];
        recent.value = data.recent ?? [];

        renderCharts();
    } catch (err) {
        console.error('Failed to load analytics', err);
    }
};

const refresh = () => fetchData();

const formatCurrency = (n) => {
    if (n == null) return '0';
    return Number(n).toLocaleString();
};

const prepareTimeseries = () => {
    const labels = timeseries.value.map(x => x.date);
    const counts = timeseries.value.map(x => x.count);
    const amounts = timeseries.value.map(x => x.amount);
    return { labels, counts, amounts };
};

const prepareMonthly = () => {
    const labels = monthly.value.map(x => x.month);
    const amounts = monthly.value.map(x => x.amount);
    return { labels, amounts };
};

const renderCharts = () => {
    // Timeseries chart
    const ts = prepareTimeseries();
    if (timeseriesChart) timeseriesChart.destroy();
    timeseriesChart = new Chart(timeseriesCanvas.value, {
        type: 'line',
        data: {
            labels: ts.labels,
            datasets: [
                { label: 'Fines (count)', data: ts.counts, tension: 0.3, yAxisID: 'y1' },
                { label: 'Amount (RWF)', data: ts.amounts, tension: 0.3, yAxisID: 'y2' },
            ],
        },
        options: {
            interaction: { mode: 'index', intersect: false },
            scales: {
                y1: { position: 'left', title: { display: true, text: 'Count' } },
                y2: { position: 'right', title: { display: true, text: 'Amount (RWF)' }, grid: { drawOnChartArea: false } },
            },
            plugins: { legend: { position: 'bottom' } }
        }
    });

    // Monthly chart
    const m = prepareMonthly();
    if (monthlyChart) monthlyChart.destroy();
    monthlyChart = new Chart(monthlyCanvas.value, {
        type: 'bar',
        data: {
            labels: m.labels,
            datasets: [{ label: 'Amount (RWF)', data: m.amounts }]
        },
        options: { plugins: { legend: { display: false } } }
    });

    // Type pie chart (vehicles vs trailers)
    const vehiclesCount = topVehicles.value.reduce((s, i) => s + i.count, 0);
    const trailersCount = topTrailers.value.reduce((s, i) => s + i.count, 0);
    if (typePieChart) typePieChart.destroy();
    typePieChart = new Chart(typePieCanvas.value, {
        type: 'pie',
        data: {
            labels: ['Vehicles', 'Trailers'],
            datasets: [{ data: [vehiclesCount, trailersCount], backgroundColor: ['#60A5FA', '#F97316'] }]
        },
        options: {}
    });
};

onMounted(fetchData);
</script>

<style scoped>
/* small tweaks, Tailwind handles most styles */
</style>
