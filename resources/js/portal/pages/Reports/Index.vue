<template>
    <div class="max-w-7xl mx-auto space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-black text-slate-800 tracking-tight uppercase italic leading-none">Fleet Reports</h1>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-1">Real-time fleet intelligence</p>
            </div>
            <button @click="refresh" :disabled="loading"
                    class="text-[10px] font-black uppercase px-4 py-2 rounded-xl border border-slate-200 text-slate-500 hover:bg-slate-50 transition active:scale-95 disabled:opacity-40 flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" :class="{ 'animate-spin': loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Refresh
            </button>
        </div>

        <!-- Fleet Overview -->
        <section class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h2 class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Fleet Overview</h2>
            </div>
            <div class="p-6 grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <StatCard label="Total Vehicles" :value="data?.fleet?.total_vehicles" color="slate" />
                <StatCard label="Active" :value="data?.fleet?.active" color="emerald" />
                <StatCard label="Moving Now" :value="data?.fleet?.moving_now" color="blue" />
                <StatCard label="Maintenance" :value="data?.fleet?.maintenance" color="amber" />
                <StatCard label="Inactive" :value="data?.fleet?.inactive" color="red" />
                <StatCard label="Utilization" :value="data?.fleet?.utilization_rate + '%'" color="purple" />
            </div>
            <div class="px-6 pb-6 flex gap-4 text-[10px] font-bold text-slate-400">
                <span>Trailers: <strong class="text-slate-700">{{ data?.fleet?.active_trailers }}/{{ data?.fleet?.total_trailers }}</strong> active</span>
                <span>No driver: <strong class="text-red-500">{{ data?.fleet?.vehicles_without_drivers }}</strong></span>
                <span>Idle: <strong class="text-amber-500">{{ data?.fleet?.idle }}</strong></span>
            </div>
        </section>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Business Metrics / Top Clients -->
            <section class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h2 class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Top Profitable Clients</h2>
                </div>
                <div class="p-4" style="height: 280px">
                    <canvas ref="clientChart"></canvas>
                </div>
                <div class="px-6 pb-4 flex gap-4 text-[10px] font-bold text-slate-400">
                    <span>Total: <strong class="text-slate-700">{{ formatCurrency(data?.business?.total_revenue) }}</strong></span>
                    <span>Month: <strong class="text-emerald-600">{{ formatCurrency(data?.business?.month_revenue) }}</strong></span>
                    <span>Delivered: <strong class="text-slate-700">{{ data?.business?.delivered_orders }}</strong></span>
                </div>
            </section>

            <!-- Compliance -->
            <section class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                    <h2 class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Compliance</h2>
                    <span class="text-[9px] font-black px-2 py-0.5 rounded-md"
                          :class="healthColor">
                        {{ data?.compliance?.health_percentage }}% Healthy
                    </span>
                </div>
                <div class="p-6 space-y-4">
                    <MetricRow label="Fleet Health" :value="data?.compliance?.health_percentage + '%'" />
                    <MetricRow label="Grounded" :value="data?.compliance?.grounded" warning />
                    <MetricRow label="Insurance Expiring (30d)" :value="data?.compliance?.insurance_expiring_30d" />
                    <MetricRow label="Inspections Overdue" :value="data?.compliance?.inspections_overdue" warning />
                    <MetricRow label="Fines Pending" :value="data?.compliance?.fines_pending" />
                    <MetricRow label="Licences Expiring (30d)" :value="data?.compliance?.licences_expiring_30d" />
                    <div v-if="data?.compliance?.grounded_list?.length" class="border-t border-slate-100 pt-3 mt-3">
                        <p class="text-[9px] font-black text-red-400 uppercase tracking-wider mb-2">Grounded Units</p>
                        <div v-for="u in data.compliance.grounded_list" :key="u.plate"
                             class="flex items-center gap-2 text-[10px] font-bold text-slate-600 mb-1">
                            <span class="w-2 h-2 rounded-full bg-red-400 shrink-0"></span>
                            <span>{{ u.plate }}</span>
                            <span class="text-slate-400">({{ u.type }})</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Fuel -->
            <section class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h2 class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Fuel Overview</h2>
                </div>
                <div class="p-6 space-y-4">
                    <MetricRow label="Avg Fuel Level" :value="data?.fuel?.avg_fuel_level + '%'" />
                    <MetricRow label="Critical Fuel" :value="data?.fuel?.critical_count" warning />
                    <MetricRow label="Today Refilled" :value="data?.fuel?.today_filled + 'L'" />
                    <MetricRow label="Today Theft/Drain" :value="data?.fuel?.today_stolen + 'L'" warning />
                    <MetricRow label="Month Refilled" :value="data?.fuel?.month_filled + 'L'" />
                    <MetricRow label="Month Theft/Drain" :value="data?.fuel?.month_stolen + 'L'" warning />
                </div>
            </section>
        </div>

        <!-- Financial: Cost vs Revenue -->
        <section class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                <h2 class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Cost vs Revenue</h2>
                <span v-if="data?.financial?.profit_margin !== undefined"
                      class="text-[9px] font-black px-2 py-0.5 rounded-md"
                      :class="data.financial.profit_margin >= 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'">
                    {{ data.financial.profit_margin >= 0 ? '+' : '' }}{{ data.financial.profit_margin }}% Margin
                </span>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
                    <StatCard label="Total Revenue" :value="formatCurrency(data?.financial?.total_revenue)" color="emerald" />
                    <StatCard label="Total Expenses" :value="formatCurrency(data?.financial?.total_expenses)" color="red" />
                    <StatCard label="Fine Costs" :value="formatCurrency(data?.financial?.total_fine_cost)" color="amber" />
                    <StatCard label="Net Profit" :value="formatCurrency(data?.financial?.net_profit)" :color="profitColor" />
                    <StatCard label="Profit Margin" :value="data?.financial?.profit_margin + '%'" :color="profitColor" />
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Monthly Revenue vs Expenses Chart -->
                    <div>
                        <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3">Revenue vs Expenses (12 months)</p>
                        <div class="bg-slate-50 rounded-xl p-4 border border-slate-200" style="height: 220px">
                            <canvas ref="financialChart"></canvas>
                        </div>
                    </div>

                    <!-- Cost Breakdown -->
                    <div>
                        <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3">Cost Breakdown by Type</p>
                        <div class="bg-slate-50 rounded-xl p-4 border border-slate-200 space-y-2">
                            <div v-for="item in costBreakdown" :key="item.name"
                                 class="flex items-center gap-3">
                                <span class="text-[10px] font-bold text-slate-600 w-24 shrink-0 truncate">{{ item.name }}</span>
                                <div class="flex-1 bg-slate-200 rounded-full h-3 overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-500"
                                         :style="{ width: costBarWidth(item.amount), background: costColor(item.name) }"></div>
                                </div>
                                <span class="text-[10px] font-bold text-slate-500 w-20 text-right">{{ formatCurrency(item.amount) }}</span>
                            </div>
                            <div v-if="!costBreakdown.length" class="text-[10px] text-slate-400 text-center py-4">No expense data</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Trip Timeline -->
            <section class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h2 class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Trips This Week</h2>
                </div>
                <div class="p-4" style="height: 200px">
                    <canvas ref="tripChart"></canvas>
                </div>
            </section>

            <!-- Order Status Distribution -->
            <section class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h2 class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Order Status</h2>
                </div>
                <div class="p-4" style="height: 200px">
                    <canvas ref="orderChart"></canvas>
                </div>
            </section>

            <!-- Fine Trends -->
            <section class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h2 class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Fine Trends This Week</h2>
                </div>
                <div class="p-4" style="height: 200px">
                    <canvas ref="fineChart"></canvas>
                </div>
            </section>

            <!-- Fuel Consumption -->
            <section class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h2 class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Fuel Refilled (12 months)</h2>
                </div>
                <div class="p-4" style="height: 200px">
                    <canvas ref="fuelChart"></canvas>
                </div>
            </section>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { api } from '../../../plugins/axios'
import Chart from 'chart.js/auto'

const loading = ref(false)
const data = ref(null)
const clientChart = ref(null)
const tripChart = ref(null)
const orderChart = ref(null)
const fineChart = ref(null)
const fuelChart = ref(null)
const financialChart = ref(null)

let charts = []

const formatCurrency = (val) => {
    if (val == null) return '—'
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(val)
}

const healthColor = computed(() => {
    const h = data.value?.compliance?.health_percentage ?? 100
    if (h >= 80) return 'bg-emerald-100 text-emerald-700'
    if (h >= 50) return 'bg-amber-100 text-amber-700'
    return 'bg-red-100 text-red-700'
})

const profitColor = computed(() => {
    const m = data.value?.financial?.profit_margin ?? 0
    return m >= 0 ? 'emerald' : 'red'
})

const costBreakdown = computed(() => data.value?.financial?.cost_breakdown ?? [])

const maxCost = computed(() => {
    const items = costBreakdown.value
    return items.length ? Math.max(...items.map(i => Number(i.amount))) : 1
})

const maxCostLiters = computed(() => {
    const items = costBreakdown.value
    return items.length ? Math.max(...items.map(i => Number(i.amount))) : 1
})

const costBarWidth = (amount) => {
    return maxCost.value > 0 ? (amount / maxCost.value) * 100 + '%' : '0%'
}

const costColor = (name) => {
    const colors = {
        'Fuel': '#f59e0b',
        'Repairs': '#ef4444',
        'Fines': '#8b5cf6',
        'Salaries': '#3b82f6',
        'Insurance': '#10b981',
        'Maintenance': '#f97316',
    }
    return colors[name] || '#94a3b8'
}

const destroyCharts = () => {
    charts.forEach(c => c.destroy())
    charts = []
}

const renderCharts = () => {
    destroyCharts()
    if (!data.value) return

    const d = data.value

    // Top Clients
    if (clientChart.value && d.business?.top_clients?.length) {
        const reversed = [...d.business.top_clients].reverse()
        const labels = reversed.map(c => c.name)
        const revenues = reversed.map(c => c.total_revenue)
        const maxRev = Math.max(...revenues, 1)
        const barColors = [
            '#10b981', '#34d399', '#6ee7b7', '#059669', '#047857',
            '#3b82f6', '#60a5fa', '#93c5fd', '#2563eb', '#1d4ed8'
        ].slice(0, revenues.length)
        charts.push(new Chart(clientChart.value, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Revenue', data: revenues,
                    backgroundColor: barColors,
                    borderRadius: 3,
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { beginAtZero: true, ticks: { font: { size: 9 }, callback: (v) => '$' + (v / 1000).toFixed(0) + 'k' } },
                    y: { ticks: { font: { size: 8 } } }
                }
            }
        }))
    }

    // Trip Timeline
    if (tripChart.value && d.charts?.trip_timeline?.length) {
        const labels = d.charts.trip_timeline.map(t => t.date?.slice(5))
        const counts = d.charts.trip_timeline.map(t => t.count)
        charts.push(new Chart(tripChart.value, {
            type: 'bar',
            data: { labels, datasets: [{ label: 'Trips', data: counts, backgroundColor: '#3b82f6', borderRadius: 4 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
        }))
    }

    // Order Status
    if (orderChart.value && d.charts?.order_status_distribution?.length) {
        const statusColors = { draft: '#94a3b8', confirmed: '#3b82f6', in_transit: '#f59e0b', delivered: '#10b981', cancelled: '#ef4444' }
        const labels = d.charts.order_status_distribution.map(s => s.status)
        const counts = d.charts.order_status_distribution.map(s => s.count)
        const colors = d.charts.order_status_distribution.map(s => statusColors[s.status] || '#94a3b8')
        charts.push(new Chart(orderChart.value, {
            type: 'doughnut',
            data: { labels, datasets: [{ data: counts, backgroundColor: colors, borderWidth: 0 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right', labels: { font: { size: 9 }, boxWidth: 10 } } } }
        }))
    }

    // Fine Trends
    if (fineChart.value && d.charts?.fine_trends?.length) {
        const labels = d.charts.fine_trends.map(t => t.date?.slice(5))
        const amounts = d.charts.fine_trends.map(t => Number(t.total_amount))
        charts.push(new Chart(fineChart.value, {
            type: 'line',
            data: { labels, datasets: [{ label: 'Fine Amount', data: amounts, borderColor: '#ef4444', backgroundColor: 'rgba(239, 68, 68, 0.1)', fill: true, tension: 0.3, pointRadius: 3 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
        }))
    }

    // Fuel Consumption
    if (fuelChart.value && d.fuel?.monthly_consumption?.length) {
        const labels = d.financial?.monthly_labels || []
        const values = d.fuel.monthly_consumption
        charts.push(new Chart(fuelChart.value, {
            type: 'bar',
            data: { labels, datasets: [{ label: 'Liters', data: values, backgroundColor: '#f59e0b', borderRadius: 4 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
        }))
    }

    // Financial Chart
    if (financialChart.value && d.financial?.monthly_labels?.length) {
        charts.push(new Chart(financialChart.value, {
            type: 'bar',
            data: {
                labels: d.financial.monthly_labels,
                datasets: [
                    { label: 'Revenue', data: d.financial.monthly_revenue, backgroundColor: '#10b981', borderRadius: 3 },
                    { label: 'Expenses', data: d.financial.monthly_expenses, backgroundColor: '#ef4444', borderRadius: 3 },
                    { label: 'Fines', data: d.financial.monthly_fines, backgroundColor: '#8b5cf6', borderRadius: 3 },
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'top', labels: { font: { size: 9 }, boxWidth: 10 } } },
                scales: { y: { beginAtZero: true } }
            }
        }))
    }
}

const refresh = async () => {
    loading.value = true
    try {
        const { data: result } = await api.get('portal/reports')
        data.value = result
        await nextTick()
        renderCharts()
    } catch (e) {
        console.error('Failed to load reports', e)
    } finally {
        loading.value = false
    }
}

let interval
onMounted(() => {
    refresh()
    interval = setInterval(refresh, 60000)
})

onUnmounted(() => {
    destroyCharts()
    clearInterval(interval)
})
</script>

<script>
export default {
    components: {
        StatCard: {
            template: `
                <div class="bg-slate-50 rounded-xl p-3 border border-slate-100">
                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-wider mb-1">{{ label }}</p>
                    <p class="text-lg font-black" :class="'text-' + color + '-600'">{{ value ?? '—' }}</p>
                </div>
            `,
            props: ['label', 'value', 'color']
        },
        MetricRow: {
            template: `
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-slate-500">{{ label }}</span>
                    <span class="text-xs font-black" :class="cls">{{ value ?? '—' }}</span>
                </div>
            `,
            props: ['label', 'value', 'highlight', 'warning'],
            computed: {
                cls() {
                    if (this.highlight) return 'text-emerald-600'
                    if (this.warning) return 'text-red-500'
                    return 'text-slate-700'
                }
            }
        }
    }
}
</script>

<style scoped>
.animate-spin {
    animation: spin 1s linear infinite;
}
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>
