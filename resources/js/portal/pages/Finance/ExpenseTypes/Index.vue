<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { expenseTypesApi } from '../../../api/expense-types'

const types = ref<any[]>([])
const loading = ref(true)

const statusFilter = ref('')
const activeOnly = ref(false)

async function fetchTypes() {
    loading.value = true
    try {
        const params: any = {}
        if (statusFilter.value) params.status = statusFilter.value
        params.active_only = activeOnly.value ? '1' : '0'
        const res = await expenseTypesApi.getAll(params)
        types.value = res.data.data ?? res.data
    } catch {} finally { loading.value = false }
}

function statusClass(status: string) {
    const map: Record<string, string> = {
        active: 'bg-green-100 text-green-700',
        draft: 'bg-gray-100 text-gray-600',
        pending_approval: 'bg-yellow-100 text-yellow-700',
        rejected: 'bg-red-100 text-red-700',
    }
    return map[status] ?? 'bg-gray-100 text-gray-600'
}

function classBadge(expenseClass: string) {
    return expenseClass === 'fixed'
        ? 'bg-blue-100 text-blue-600'
        : 'bg-purple-100 text-purple-600'
}

async function submitForApproval(id: number) {
    if (!confirm('Submit this type for approval?')) return
    try {
        await expenseTypesApi.submit(id)
        await fetchTypes()
    } catch {}
}

async function approveType(id: number) {
    if (!confirm('Approve this expense type?')) return
    try {
        await expenseTypesApi.approveType(id)
        await fetchTypes()
    } catch {}
}

async function rejectType(id: number) {
    if (!confirm('Reject this expense type?')) return
    try {
        await expenseTypesApi.rejectType(id)
        await fetchTypes()
    } catch {}
}

onMounted(fetchTypes)
</script>

<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-slate-800">Expense Types</h1>
            <router-link to="/finance/expense-types/create"
                class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors text-sm font-medium">
                New Type
            </router-link>
        </div>

        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-4 mb-6">
            <div class="flex flex-wrap gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Status</label>
                    <select v-model="statusFilter" @change="fetchTypes"
                        class="text-sm border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500">
                        <option value="">All</option>
                        <option value="draft">Draft</option>
                        <option value="pending_approval">Pending Approval</option>
                        <option value="active">Active</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <label class="flex items-center gap-2 text-sm cursor-pointer">
                        <input type="checkbox" v-model="activeOnly" @change="fetchTypes"
                            class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                        Active only
                    </label>
                </div>
            </div>
        </div>

        <div v-if="loading" class="bg-white rounded-xl border border-slate-100 shadow-sm p-6">
            <div class="animate-pulse space-y-4">
                <div class="h-10 bg-slate-100 rounded w-full"></div>
                <div class="h-10 bg-slate-100 rounded w-full"></div>
                <div class="h-10 bg-slate-100 rounded w-full"></div>
            </div>
        </div>

        <div v-else-if="types.length === 0"
            class="bg-white rounded-xl border border-slate-100 shadow-sm p-12 text-center">
            <div class="text-slate-400 text-lg font-medium mb-1">No expense types</div>
            <p class="text-slate-500 text-sm">Create pre-approved expense items for the catalog.</p>
        </div>

        <div v-else class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/50">
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Name</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Class</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Category</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Default Amount</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Status</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="t in types" :key="t.id" class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors">
                        <td class="px-4 py-3">
                            <div class="font-medium text-slate-800">{{ t.name }}</div>
                            <div v-if="t.description" class="text-xs text-slate-500 truncate max-w-xs">{{ t.description }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-block px-2 py-0.5 rounded text-xs font-medium"
                                :class="classBadge(t.expense_class)">
                                {{ t.expense_class }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ t.category ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-right text-slate-800">
                            {{ t.default_amount != null ? Number(t.default_amount).toLocaleString() : '-' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-block px-2 py-0.5 rounded text-xs font-medium"
                                :class="statusClass(t.status)">
                                {{ t.status?.replace('_', ' ') }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button v-if="t.status === 'draft'" @click="submitForApproval(t.id)"
                                    class="text-xs px-2 py-1 bg-yellow-50 text-yellow-700 rounded hover:bg-yellow-100 transition-colors">
                                    Submit
                                </button>
                                <button v-if="t.status === 'pending_approval'" @click="approveType(t.id)"
                                    class="text-xs px-2 py-1 bg-green-50 text-green-700 rounded hover:bg-green-100 transition-colors">
                                    Approve
                                </button>
                                <button v-if="t.status === 'pending_approval'" @click="rejectType(t.id)"
                                    class="text-xs px-2 py-1 bg-red-50 text-red-700 rounded hover:bg-red-100 transition-colors">
                                    Reject
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
