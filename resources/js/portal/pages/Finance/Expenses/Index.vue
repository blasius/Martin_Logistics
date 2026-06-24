<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { expensesApi } from '../../../api/expenses'

const expenses = ref<any[]>([])
const loading = ref(true)
const showFilters = ref(false)

const filters = ref({
    status: '',
    category: '',
    expense_class: '',
    from: '',
    to: '',
})

const sortBy = ref('created_at')
const sortDir = ref('desc')

const page = ref(1)
const lastPage = ref(1)

async function fetchExpenses() {
    loading.value = true
    try {
        const params: any = { page: page.value, sort_by: sortBy.value, sort_dir: sortDir.value }
        if (filters.value.status) params.status = filters.value.status
        if (filters.value.category) params.category = filters.value.category
        if (filters.value.expense_class) params.expense_class = filters.value.expense_class
        if (filters.value.from) params.from = filters.value.from
        if (filters.value.to) params.to = filters.value.to

        const res = await expensesApi.getAll(params)
        const d = res.data
        if (d.data) {
            expenses.value = d.data
            lastPage.value = d.last_page ?? 1
        } else {
            expenses.value = d
        }
    } catch {} finally { loading.value = false }
}

function statusClass(status: string) {
    const map: Record<string, string> = {
        pending: 'bg-yellow-100 text-yellow-700',
        approved: 'bg-blue-100 text-blue-700',
        paid: 'bg-green-100 text-green-700',
        rejected: 'bg-red-100 text-red-700',
        cancelled: 'bg-gray-100 text-gray-600',
    }
    return map[status] ?? 'bg-gray-100 text-gray-600'
}

function classBadge(cls: string) {
    return cls === 'fixed'
        ? 'bg-blue-100 text-blue-600'
        : 'bg-purple-100 text-purple-600'
}

function amountDisplay(exp: any) {
    const amt = Number(exp.amount).toLocaleString()
    const code = exp.currency?.code ?? ''
    return `${amt} ${code}`
}

function applyFilter() { page.value = 1; fetchExpenses() }
function resetFilter() {
    filters.value = { status: '', category: '', expense_class: '', from: '', to: '' }
    page.value = 1
    fetchExpenses()
}

function sort(field: string) {
    if (sortBy.value === field) {
        sortDir.value = sortDir.value === 'desc' ? 'asc' : 'desc'
    } else {
        sortBy.value = field
        sortDir.value = 'desc'
    }
    fetchExpenses()
}

onMounted(fetchExpenses)
</script>

<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-slate-800">Expenses</h1>
            <div class="flex items-center gap-3">
                <button @click="showFilters = !showFilters"
                    class="px-3 py-2 border border-slate-200 rounded-lg text-sm text-slate-600 hover:bg-slate-50 transition-colors">
                    {{ showFilters ? 'Hide Filters' : 'Filters' }}
                </button>
                <router-link to="/finance/expenses/create"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors text-sm font-medium">
                    New Expense
                </router-link>
            </div>
        </div>

        <div v-if="showFilters" class="bg-white rounded-xl border border-slate-100 shadow-sm p-4 mb-6">
            <div class="flex flex-wrap gap-4 items-end">
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Status</label>
                    <select v-model="filters.status"
                        class="text-sm border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500">
                        <option value="">All</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="paid">Paid</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Class</label>
                    <select v-model="filters.expense_class"
                        class="text-sm border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500">
                        <option value="">All</option>
                        <option value="fixed">Fixed</option>
                        <option value="variable">Variable</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">From</label>
                    <input type="date" v-model="filters.from"
                        class="text-sm border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">To</label>
                    <input type="date" v-model="filters.to"
                        class="text-sm border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500">
                </div>
                <div class="flex gap-2">
                    <button @click="applyFilter"
                        class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm hover:bg-emerald-700 transition-colors">
                        Apply
                    </button>
                    <button @click="resetFilter"
                        class="px-4 py-2 border border-slate-200 rounded-lg text-sm text-slate-600 hover:bg-slate-50 transition-colors">
                        Reset
                    </button>
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

        <div v-else-if="expenses.length === 0"
            class="bg-white rounded-xl border border-slate-100 shadow-sm p-12 text-center">
            <div class="text-slate-400 text-lg font-medium mb-1">No expenses found</div>
            <p class="text-slate-500 text-sm">Create a new expense to get started.</p>
        </div>

        <div v-else class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/50">
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase cursor-pointer"
                            @click="sort('reference')">Reference</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Name</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Vehicle</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-slate-500 uppercase cursor-pointer"
                            @click="sort('amount')">Amount</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Class</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Status</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Created</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="e in expenses" :key="e.id" class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors">
                        <td class="px-4 py-3">
                            <router-link :to="`/finance/expenses/${e.id}`"
                                class="text-sm font-mono text-emerald-600 hover:text-emerald-700">
                                {{ e.reference }}
                            </router-link>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-sm font-medium text-slate-800">{{ e.name }}</div>
                            <div v-if="e.category" class="text-xs text-slate-500">{{ e.category }}</div>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600">
                            {{ e.vehicle?.plate_number ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-right font-medium text-slate-800">
                            {{ amountDisplay(e) }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-block px-2 py-0.5 rounded text-xs font-medium"
                                :class="classBadge(e.expense_class)">
                                {{ e.expense_class }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-block px-2 py-0.5 rounded text-xs font-medium"
                                :class="statusClass(e.status)">
                                {{ e.status }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-500 text-right">
                            {{ new Date(e.created_at).toLocaleDateString() }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <router-link :to="`/finance/expenses/${e.id}`"
                                class="text-xs text-emerald-600 hover:text-emerald-700">
                                View
                            </router-link>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div v-if="lastPage > 1" class="flex items-center justify-between px-4 py-3 border-t border-slate-100">
                <button :disabled="page <= 1" @click="page--; fetchExpenses()"
                    class="px-3 py-1 text-sm border border-slate-200 rounded hover:bg-slate-50 disabled:opacity-50">
                    Previous
                </button>
                <span class="text-sm text-slate-500">Page {{ page }} of {{ lastPage }}</span>
                <button :disabled="page >= lastPage" @click="page++; fetchExpenses()"
                    class="px-3 py-1 text-sm border border-slate-200 rounded hover:bg-slate-50 disabled:opacity-50">
                    Next
                </button>
            </div>
        </div>
    </div>
</template>
