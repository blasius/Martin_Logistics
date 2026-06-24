<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { truckRequestsApi } from '../../api/truck-requests'

const requests = ref<any[]>([])
const loading = ref(true)
const statusFilter = ref('')
const myOnly = ref(true)

async function fetchRequests() {
    loading.value = true
    try {
        const params: any = {}
        if (statusFilter.value) params.status = statusFilter.value
        params.my_only = myOnly.value ? '1' : '0'
        const res = await truckRequestsApi.getAll(params)
        requests.value = res.data.data ?? res.data
    } catch {} finally { loading.value = false }
}

function statusClass(status: string) {
    const map: Record<string, string> = {
        draft: 'bg-gray-100 text-gray-600',
        submitted: 'bg-blue-100 text-blue-700',
        truck_assigned: 'bg-yellow-100 text-yellow-700',
        in_progress: 'bg-green-100 text-green-700',
        completed: 'bg-slate-100 text-slate-600',
        cancelled: 'bg-red-100 text-red-700',
    }
    return map[status] ?? 'bg-gray-100 text-gray-600'
}

onMounted(fetchRequests)
</script>

<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-slate-800">Truck Requests</h1>
            <router-link to="/truck-requests/create"
                class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors text-sm font-medium">
                New Request
            </router-link>
        </div>

        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-4 mb-6">
            <div class="flex flex-wrap gap-4 items-end">
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Status</label>
                    <select v-model="statusFilter" @change="fetchRequests"
                        class="text-sm border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500">
                        <option value="">All</option>
                        <option value="draft">Draft</option>
                        <option value="submitted">Submitted</option>
                        <option value="truck_assigned">Assigned</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <label class="flex items-center gap-2 pb-1">
                    <input type="checkbox" v-model="myOnly" @change="fetchRequests"
                        class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" />
                    <span class="text-sm text-slate-600">My requests only</span>
                </label>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
            <div v-if="loading" class="p-8 text-center text-slate-400">Loading...</div>
            <table v-else class="w-full">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/50">
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Reference</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Cargo</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Tonnage</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Pickup → Dropoff</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Dates</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Vehicle</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="r in requests" :key="r.id"
                        class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors cursor-pointer"
                        @click="$router.push(`/truck-requests/${r.id}`)">
                        <td class="px-4 py-3 text-sm font-medium text-slate-800">{{ r.reference }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ r.cargo_type }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ Number(r.tonnage).toLocaleString() }}T</td>
                        <td class="px-4 py-3 text-sm text-slate-600 max-w-[200px] truncate">{{ r.pickup_location }} → {{ r.dropoff_location }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ r.expected_pickup_date }} → {{ r.expected_delivery_date }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium"
                                :class="statusClass(r.status)">{{ r.status.replace('_', ' ') }}</span>
                        </td>
                        <td class="px-4 py-3 text-right text-sm text-slate-500">{{ r.assigned_vehicle?.plate_number ?? '—' }}</td>
                    </tr>
                    <tr v-if="!requests.length">
                        <td colspan="7" class="px-4 py-8 text-center text-slate-400">No truck requests found</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
