<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { journalEntriesApi, fiscalYearsApi } from '../../../api/accounting'

const entries = ref<any[]>([])
const fiscalYears = ref<any[]>([])
const loading = ref(true)
const statusFilter = ref('')
const typeFilter = ref('')
const fiscalYearFilter = ref('')
const dateFrom = ref('')
const dateTo = ref('')

function statusClass(s: string) {
    const map: Record<string, string> = {
        draft: 'bg-gray-100 text-gray-600',
        posted: 'bg-green-100 text-green-700',
        reversed: 'bg-red-100 text-red-700',
    }
    return map[s] ?? 'bg-gray-100 text-gray-600'
}

function typeBadge(t: string) {
    if (t === 'manual') return 'bg-slate-100 text-slate-600'
    if (t.startsWith('auto_')) return 'bg-blue-100 text-blue-700'
    return 'bg-slate-100 text-slate-600'
}

async function fetchEntries() {
    loading.value = true
    try {
        const params: any = {}
        if (statusFilter.value) params.status = statusFilter.value
        if (typeFilter.value) params.type = typeFilter.value
        if (fiscalYearFilter.value) params.fiscal_year_id = fiscalYearFilter.value
        if (dateFrom.value) params.date_from = dateFrom.value
        if (dateTo.value) params.date_to = dateTo.value
        const res = await journalEntriesApi.getAll(params)
        entries.value = res.data.data ?? res.data
    } catch {} finally { loading.value = false }
}

async function fetchFiscalYears() {
    try {
        const res = await fiscalYearsApi.getAll({ per_page: 100 })
        fiscalYears.value = res.data.data ?? res.data
    } catch {}
}

onMounted(async () => {
    await fetchFiscalYears()
    await fetchEntries()
})
</script>

<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-slate-800">Journal Entries</h1>
            <router-link to="/accounting/journal-entries/create"
                class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 text-sm font-medium">
                New Entry
            </router-link>
        </div>

        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-4 mb-6">
            <div class="flex flex-wrap gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Status</label>
                    <select v-model="statusFilter" @change="fetchEntries"
                        class="text-sm border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500">
                        <option value="">All</option>
                        <option value="draft">Draft</option>
                        <option value="posted">Posted</option>
                        <option value="reversed">Reversed</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Type</label>
                    <select v-model="typeFilter" @change="fetchEntries"
                        class="text-sm border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500">
                        <option value="">All</option>
                        <option value="manual">Manual</option>
                        <option value="auto_invoice">Auto — Invoice</option>
                        <option value="auto_payment">Auto — Payment</option>
                        <option value="auto_expense">Auto — Expense</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Fiscal Year</label>
                    <select v-model="fiscalYearFilter" @change="fetchEntries"
                        class="text-sm border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500">
                        <option value="">All</option>
                        <option v-for="fy in fiscalYears" :key="fy.id" :value="fy.id">{{ fy.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">From</label>
                    <input type="date" v-model="dateFrom" @change="fetchEntries"
                        class="text-sm border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">To</label>
                    <input type="date" v-model="dateTo" @change="fetchEntries"
                        class="text-sm border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500" />
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
            <div v-if="loading" class="p-8 text-center text-slate-400">Loading...</div>
            <table v-else class="w-full">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/50">
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Reference</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Date</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Description</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Type</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="e in entries" :key="e.id"
                        class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors cursor-pointer"
                        @click="$router.push(`/accounting/journal-entries/${e.id}`)">
                        <td class="px-4 py-3 text-sm font-medium text-slate-800 font-mono">{{ e.reference }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ e.date }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600 max-w-xs truncate">{{ e.description }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium"
                                :class="typeBadge(e.type)">{{ e.type.replace('auto_', 'Auto — ') }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium"
                                :class="statusClass(e.status)">{{ e.status }}</span>
                        </td>
                    </tr>
                    <tr v-if="!entries.length">
                        <td colspan="5" class="px-4 py-8 text-center text-slate-400">No journal entries found</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
