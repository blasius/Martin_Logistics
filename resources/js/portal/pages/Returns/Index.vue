<script setup lang="ts">
import { ref, onMounted } from 'vue'
import returnsApi from '../../api/returns'

const returns = ref<any[]>([])
const loading = ref(true)
const statusFilter = ref('')

async function fetch() {
    loading.value = true
    try {
        const params: any = { per_page: 50 }
        if (statusFilter.value) params.status = statusFilter.value
        const res = await returnsApi.index(params)
        returns.value = res.data.data ?? res.data
    } catch {} finally { loading.value = false }
}

function statusClass(status: string) {
    const map: Record<string, string> = {
        pending: 'bg-amber-100 text-amber-700',
        approved: 'bg-blue-100 text-blue-700',
        rejected: 'bg-red-100 text-red-700',
        pickup_scheduled: 'bg-purple-100 text-purple-700',
        received: 'bg-cyan-100 text-cyan-700',
        completed: 'bg-emerald-100 text-emerald-700',
        cancelled: 'bg-gray-100 text-gray-500',
    }
    return map[status] ?? 'bg-gray-100 text-gray-600'
}

onMounted(fetch)
</script>

<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-slate-800">Return Requests</h1>
            <router-link to="/returns/create"
                class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors text-sm font-medium">
                New Return
            </router-link>
        </div>

        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-4 mb-6">
            <div class="flex flex-wrap gap-4 items-end">
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Status</label>
                    <select v-model="statusFilter" @change="fetch"
                        class="text-sm border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500">
                        <option value="">All</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                        <option value="pickup_scheduled">Pickup Scheduled</option>
                        <option value="received">Received</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
            <div v-if="loading" class="p-8 text-center text-slate-400">Loading...</div>
            <table v-else class="w-full">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/50">
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Reference</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Client</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Order</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Items</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Reason</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Created</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="r in returns" :key="r.id" class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors">
                        <td class="px-4 py-3">
                            <router-link :to="`/returns/${r.id}`" class="text-sm font-medium text-emerald-600 hover:text-emerald-700">
                                {{ r.reference }}
                            </router-link>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ r.client?.user?.name ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ r.order?.reference ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ r.items?.length ?? 0 }} item(s)</td>
                        <td class="px-4 py-3 text-sm text-slate-600 max-w-[200px] truncate">{{ r.reason }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-medium" :class="statusClass(r.status)">{{ r.status.replace('_', ' ') }}</span>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-500">{{ r.created_at?.slice(0, 10) }}</td>
                        <td class="px-4 py-3 text-right">
                            <router-link :to="`/returns/${r.id}`" class="text-xs text-emerald-600 hover:text-emerald-700 font-medium">View</router-link>
                        </td>
                    </tr>
                    <tr v-if="!returns.length && !loading">
                        <td colspan="8" class="px-4 py-8 text-center text-slate-400">No return requests found.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
