<template>
    <div class="min-h-screen bg-slate-50">
        <!-- Header -->
        <header class="bg-white border-b border-slate-200 px-8 py-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-black text-slate-800 uppercase tracking-tight">Analytics Dashboard</h1>
                    <p class="text-slate-500 mt-1">Detailed insights and performance metrics</p>
                </div>
                <div class="flex items-center gap-4">
                    <select v-model="selectedPeriod" @change="loadAnalytics" class="px-4 py-2 border border-slate-200 rounded-lg text-sm font-medium focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="day">Today</option>
                        <option value="week">This Week</option>
                        <option value="month">This Month</option>
                    </select>
                    <button @click="refreshData" :disabled="loading" class="p-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 transition-colors">
                        <RefreshCw v-if="!loading" class="w-5 h-5" />
                        <div v-else class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                    </button>
                </div>
            </div>
        </header>

        <div class="px-8 py-6">
            <!-- Date Range Info -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <p class="text-sm font-medium text-blue-800">
                    Showing data for {{ formatDate(analytics.date_range?.start) }} - {{ formatDate(analytics.date_range?.end) }}
                </p>
            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Trip Timeline Chart -->
                <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
                    <h3 class="text-lg font-black text-slate-800 uppercase tracking-tight mb-4">Trip Timeline</h3>
                    <div class="h-64 flex items-center justify-center">
                        <TripTimelineChart :data="analytics.trip_timeline" />
                    </div>
                </div>

                <!-- Status Distribution -->
                <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
                    <h3 class="text-lg font-black text-slate-800 uppercase tracking-tight mb-4">Order Status Distribution</h3>
                    <div class="h-64 flex items-center justify-center">
                        <StatusDistributionChart :data="analytics.order_status_distribution" />
                    </div>
                </div>
            </div>

            <!-- Fleet Utilization -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
                    <h3 class="text-lg font-black text-slate-800 uppercase tracking-tight mb-4">Fleet Utilization</h3>
                    <div class="space-y-6">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-slate-600">Vehicle Utilization</span>
                                <span class="text-sm font-black text-slate-800">{{ analytics.fleet_utilization?.vehicle_utilization || 0 }}%</span>
                            </div>
                            <div class="w-full bg-slate-200 rounded-full h-3">
                                <div class="bg-blue-600 h-3 rounded-full transition-all duration-500" :style="{ width: `${analytics.fleet_utilization?.vehicle_utilization || 0}%` }"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-slate-600">Driver Utilization</span>
                                <span class="text-sm font-black text-slate-800">{{ analytics.fleet_utilization?.driver_utilization || 0 }}%</span>
                            </div>
                            <div class="w-full bg-slate-200 rounded-full h-3">
                                <div class="bg-purple-600 h-3 rounded-full transition-all duration-500" :style="{ width: `${analytics.fleet_utilization?.driver_utilization || 0}%` }"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Performing Vehicles -->
                <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
                    <h3 class="text-lg font-black text-slate-800 uppercase tracking-tight mb-4">Top Performing Vehicles</h3>
                    <div class="space-y-3">
                        <div v-for="(vehicle, index) in analytics.top_vehicles" :key="vehicle.plate" class="flex items-center justify-between p-3 bg-slate-50 rounded-lg">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center text-xs font-black">
                                    {{ index + 1 }}
                                </div>
                                <div>
                                    <p class="font-black text-sm text-slate-800">{{ vehicle.plate }}</p>
                                    <p class="text-xs text-slate-500">{{ vehicle.trip_count }} trips</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-black text-blue-600">{{ vehicle.trip_count }}</p>
                                <p class="text-xs text-slate-500">trips</p>
                            </div>
                        </div>
                        <div v-if="!analytics.top_vehicles?.length" class="text-center py-8 text-slate-400 text-sm">
                            No vehicle data available
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fine Trends & Compliance -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Fine Trends -->
                <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
                    <h3 class="text-lg font-black text-slate-800 uppercase tracking-tight mb-4">Fine Trends</h3>
                    <div class="h-64 flex items-center justify-center">
                        <FineTrendsChart :data="analytics.fine_trends" />
                    </div>
                </div>

                <!-- Compliance Status -->
                <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
                    <h3 class="text-lg font-black text-slate-800 uppercase tracking-tight mb-4">Compliance Alerts</h3>
                    <div class="space-y-4">
                        <!-- Insurance Expiring -->
                        <div v-if="analytics.compliance_status?.insurance_expiring?.length" class="border-l-4 border-amber-400 pl-4">
                            <p class="font-black text-sm text-slate-800 mb-2">Insurance Expiring Soon</p>
                            <div class="space-y-2">
                                <div v-for="insurance in analytics.compliance_status.insurance_expiring" :key="insurance.plate_number" class="flex items-center justify-between text-xs">
                                    <span class="font-medium text-slate-600">{{ insurance.plate_number }}</span>
                                    <span class="text-amber-600 font-medium">{{ formatDate(insurance.expiry_date) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Inspection Overdue -->
                        <div v-if="analytics.compliance_status?.inspection_overdue?.length" class="border-l-4 border-rose-400 pl-4">
                            <p class="font-black text-sm text-slate-800 mb-2">Inspection Overdue</p>
                            <div class="space-y-2">
                                <div v-for="inspection in analytics.compliance_status.inspection_overdue" :key="inspection.plate_number" class="flex items-center justify-between text-xs">
                                    <span class="font-medium text-slate-600">{{ inspection.plate_number }}</span>
                                    <span class="text-rose-600 font-medium">{{ formatDate(inspection.next_inspection_date) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Driver Documents Expiring -->
                        <div v-if="hasExpiringDriverDocs" class="border-l-4 border-purple-400 pl-4">
                            <p class="font-black text-sm text-slate-800 mb-2">Driver Documents Expiring</p>
                            <div class="space-y-2">
                                <div v-if="analytics.compliance_status?.driver_docs_expiring?.licenses?.length" class="text-xs">
                                    <p class="font-medium text-purple-600 mb-1">Licenses:</p>
                                    <div v-for="license in analytics.compliance_status.driver_docs_expiring.licenses" :key="license.user_id" class="flex items-center justify-between">
                                        <span class="text-slate-600">{{ license.user?.name }}</span>
                                        <span class="text-purple-600 font-medium">{{ formatDate(license.driving_licence_expiry) }}</span>
                                    </div>
                                </div>
                                <div v-if="analytics.compliance_status?.driver_docs_expiring?.passports?.length" class="text-xs">
                                    <p class="font-medium text-purple-600 mb-1">Passports:</p>
                                    <div v-for="passport in analytics.compliance_status.driver_docs_expiring.passports" :key="passport.user_id" class="flex items-center justify-between">
                                        <span class="text-slate-600">{{ passport.user?.name }}</span>
                                        <span class="text-purple-600 font-medium">{{ formatDate(passport.passport_expiry) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="!hasComplianceIssues" class="text-center py-8 text-emerald-600 text-sm font-medium">
                            All compliance items are up to date
                        </div>
                    </div>
                </div>
            </div>

            <!-- Trip Status Distribution -->
            <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
                <h3 class="text-lg font-black text-slate-800 uppercase tracking-tight mb-4">Trip Status Distribution</h3>
                <div class="h-64 flex items-center justify-center">
                    <TripStatusChart :data="analytics.trip_status_distribution" />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { RefreshCw } from 'lucide-vue-next';
import { api } from '../../plugins/axios';
import dayjs from 'dayjs';
import TripTimelineChart from '../../components/charts/TripTimelineChart.vue';
import StatusDistributionChart from '../../components/charts/StatusDistributionChart.vue';
import FineTrendsChart from '../../components/charts/FineTrendsChart.vue';
import TripStatusChart from '../../components/charts/TripStatusChart.vue';

// State
const loading = ref(false);
const selectedPeriod = ref('week');
const analytics = ref({
    trip_timeline: [],
    order_status_distribution: [],
    trip_status_distribution: [],
    fleet_utilization: {},
    top_vehicles: [],
    fine_trends: [],
    compliance_status: {},
    date_range: {}
});

// Computed
const hasExpiringDriverDocs = computed(() => {
    const docs = analytics.value.compliance_status?.driver_docs_expiring;
    return docs?.licenses?.length > 0 || docs?.passports?.length > 0;
});

const hasComplianceIssues = computed(() => {
    const status = analytics.value.compliance_status;
    return status?.insurance_expiring?.length > 0 || 
           status?.inspection_overdue?.length > 0 || 
           hasExpiringDriverDocs.value;
});

// Methods
const loadAnalytics = async () => {
    loading.value = true;
    try {
        const response = await api.get('/portal/dashboard/analytics', {
            params: { period: selectedPeriod.value }
        });
        analytics.value = response.data;
    } catch (error) {
        console.error('Failed to load analytics:', error);
    } finally {
        loading.value = false;
    }
};

const refreshData = () => {
    loadAnalytics();
};

const formatDate = (date) => {
    return dayjs(date).format('MMM DD, YYYY');
};

// Lifecycle
onMounted(() => {
    loadAnalytics();
});
</script>

<style scoped>
/* Custom animations */
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.bg-white {
    animation: slideIn 0.3s ease-out;
}
</style>
