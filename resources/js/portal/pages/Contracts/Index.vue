<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { contractsApi } from '../../api/contracts'

const contracts = ref<any[]>([])
const loading = ref(true)

const statusFilter = ref('')
const typeFilter = ref('')

async function fetchContracts() {
    loading.value = true
    try {
        const params: any = {}
        if (statusFilter.value) params.status = statusFilter.value
        if (typeFilter.value) params.type = typeFilter.value
        const res = await contractsApi.getAll(params)
        contracts.value = res.data.data ?? res.data
    } catch {} finally { loading.value = false }
}

function statusClass(status: string) {
    const map: Record<string, string> = {
        active: 'bg-green-100 text-green-700',
        draft: 'bg-gray-100 text-gray-600',
        expired: 'bg-red-100 text-red-700',
        cancelled: 'bg-slate-100 text-slate-600',
    }
    return map[status] ?? 'bg-gray-100 text-gray-600'
}

function daysLeft(endDate: string) {
    const end = new Date(endDate)
    const now = new Date()
    const diff = Math.ceil((end.getTime() - now.getTime()) / (1000 * 60 * 60 * 24))
    return diff
}

onMounted(fetchContracts)
</script>

<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-slate-800">Contracts</h1>
            <router-link to="/contracts/create"
                class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors text-sm font-medium">
                New Contract
            </router-link>
        </div>

        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-4 mb-6">
            <div class="flex flex-wrap gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Status</label>
                    <select v-model="statusFilter" @change="fetchContracts"
                        class="text-sm border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500">
                        <option value="">All</option>
                        <option value="draft">Draft</option>
                        <option value="active">Active</option>
                        <option value="expired">Expired</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Type</label>
                    <select v-model="typeFilter" @change="fetchContracts"
                        class="text-sm border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500">
                        <option value="">All</option>
                        <option value="monthly">Monthly</option>
                        <option value="yearly">Yearly</option>
                        <option value="spot">Spot</option>
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
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Type</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Period</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="c in contracts" :key="c.id"
                        class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors cursor-pointer"
                        @click="$router.push(`/contracts/${c.id}`)">
                        <td class="px-4 py-3 text-sm font-medium text-slate-800">{{ c.reference }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ c.client?.name }}</td>
                        <td class="px-4 py-3 text-sm text-slate-500 capitalize">{{ c.type }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">
                            {{ c.start_date }} → {{ c.end_date }}
                            <span v-if="c.status === 'active'" class="ml-2 text-xs"
                                :class="daysLeft(c.end_date) <= 7 ? 'text-red-500 font-medium' : 'text-slate-400'">
                                ({{ daysLeft(c.end_date) }}d left)
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium"
                                :class="statusClass(c.status)">{{ c.status }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <router-link :to="`/contracts/${c.id}`" @click.stop
                                class="text-xs text-emerald-600 hover:text-emerald-700">View</router-link>
                        </td>
                    </tr>
                    <tr v-if="!contracts.length">
                        <td colspan="6" class="px-4 py-8 text-center text-slate-400">No contracts found</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
