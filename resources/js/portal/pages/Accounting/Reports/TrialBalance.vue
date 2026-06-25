<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { accountingReportsApi, fiscalYearsApi } from '../../../api/accounting'

const report = ref<any>(null)
const fiscalYears = ref<any[]>([])
const loading = ref(false)
const fiscalYearFilter = ref('')
const endDate = ref('')

async function generate() {
    loading.value = true
    try {
        const params: any = {}
        if (fiscalYearFilter.value) params.fiscal_year_id = fiscalYearFilter.value
        if (endDate.value) params.end_date = endDate.value
        const res = await accountingReportsApi.trialBalance(params)
        report.value = res.data
    } catch {} finally { loading.value = false }
}

function typeClass(type: string) {
    const map: Record<string, string> = {
        asset: 'text-blue-600',
        liability: 'text-orange-600',
        equity: 'text-purple-600',
        revenue: 'text-green-600',
        expense: 'text-red-600',
    }
    return map[type] ?? ''
}

onMounted(async () => {
    try {
        const res = await fiscalYearsApi.getAll({ per_page: 100 })
        fiscalYears.value = res.data.data ?? res.data
    } catch {}
})
</script>

<template>
    <div>
        <h1 class="text-2xl font-bold text-slate-800 mb-6">Trial Balance</h1>

        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-4 mb-6">
            <div class="flex flex-wrap gap-4 items-end">
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Fiscal Year</label>
                    <select v-model="fiscalYearFilter"
                        class="text-sm border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500">
                        <option value="">All</option>
                        <option v-for="fy in fiscalYears" :key="fy.id" :value="fy.id">{{ fy.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">As of Date</label>
                    <input type="date" v-model="endDate"
                        class="text-sm border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500" />
                </div>
                <button @click="generate"
                    class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700">
                    Generate
                </button>
            </div>
        </div>

        <div v-if="loading" class="p-8 text-center text-slate-400">Generating...</div>

        <div v-else-if="report" class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="px-4 py-2 bg-slate-50 border-b border-slate-100 flex justify-between text-xs text-slate-500">
                <span>{{ report.rows.length }} accounts</span>
                <span>Generated: {{ report.generated_at }}</span>
            </div>
            <table class="w-full">
                <thead>
                    <tr class="border-b border-slate-100 text-xs text-slate-500 uppercase tracking-wider">
                        <th class="text-left px-4 py-2">Code</th>
                        <th class="text-left px-4 py-2">Account</th>
                        <th class="text-left px-4 py-2">Type</th>
                        <th class="text-right px-4 py-2">Debit</th>
                        <th class="text-right px-4 py-2">Credit</th>
                        <th class="text-right px-4 py-2">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in report.rows" :key="row.account_id" class="border-b border-slate-50">
                        <td class="px-4 py-2 text-sm font-mono text-slate-600">{{ row.code }}</td>
                        <td class="px-4 py-2 text-sm text-slate-800">{{ row.name }}</td>
                        <td class="px-4 py-2">
                            <span class="text-xs capitalize" :class="typeClass(row.type)">{{ row.type }}</span>
                        </td>
                        <td class="px-4 py-2 text-right text-sm font-mono text-slate-800">
                            {{ Number(row.total_debit).toLocaleString() }}</td>
                        <td class="px-4 py-2 text-right text-sm font-mono text-slate-800">
                            {{ Number(row.total_credit).toLocaleString() }}</td>
                        <td class="px-4 py-2 text-right text-sm font-mono font-semibold"
                            :class="row.balance >= 0 ? 'text-slate-800' : 'text-red-600'">
                            {{ Number(row.balance).toLocaleString() }}</td>
                    </tr>
                </tbody>
                <tfoot class="bg-slate-50">
                    <tr>
                        <td colspan="3" class="px-4 py-2 text-sm font-semibold text-slate-700">Totals</td>
                        <td class="px-4 py-2 text-right text-sm font-semibold text-slate-800">
                            {{ Number(report.totals.debit).toLocaleString() }}</td>
                        <td class="px-4 py-2 text-right text-sm font-semibold text-slate-800">
                            {{ Number(report.totals.credit).toLocaleString() }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div v-else class="bg-white rounded-xl border border-slate-100 shadow-sm p-8 text-center text-slate-400">
            Set filters and click Generate to view the Trial Balance
        </div>
    </div>
</template>
