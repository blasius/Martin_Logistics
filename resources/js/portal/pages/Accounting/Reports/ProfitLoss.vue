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
        const res = await accountingReportsApi.profitLoss(params)
        report.value = res.data
    } catch {} finally { loading.value = false }
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
        <h1 class="text-2xl font-bold text-slate-800 mb-6">Profit & Loss Statement</h1>

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

        <div v-else-if="report" class="space-y-6">
            <!-- Revenue -->
            <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="px-4 py-3 bg-green-50 border-b border-green-100">
                    <h3 class="text-sm font-semibold text-green-700 uppercase">Revenue</h3>
                </div>
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-slate-100 text-xs text-slate-500 uppercase">
                            <th class="text-left px-4 py-2">Account</th>
                            <th class="text-right px-4 py-2">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="a in report.revenue.accounts" :key="a.account_id" class="border-b border-slate-50">
                            <td class="px-4 py-2 text-sm text-slate-800">{{ a.code }} — {{ a.name }}</td>
                            <td class="px-4 py-2 text-right text-sm font-mono text-slate-800">
                                {{ Number(a.balance).toLocaleString() }}</td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-green-50/50">
                        <tr>
                            <td class="px-4 py-2 text-sm font-semibold text-slate-700">Total Revenue</td>
                            <td class="px-4 py-2 text-right text-sm font-semibold text-green-700">
                                {{ Number(report.revenue.total).toLocaleString() }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Expenses -->
            <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="px-4 py-3 bg-red-50 border-b border-red-100">
                    <h3 class="text-sm font-semibold text-red-700 uppercase">Expenses</h3>
                </div>
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-slate-100 text-xs text-slate-500 uppercase">
                            <th class="text-left px-4 py-2">Account</th>
                            <th class="text-right px-4 py-2">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="a in report.expenses.accounts" :key="a.account_id" class="border-b border-slate-50">
                            <td class="px-4 py-2 text-sm text-slate-800">{{ a.code }} — {{ a.name }}</td>
                            <td class="px-4 py-2 text-right text-sm font-mono text-slate-800">
                                {{ Number(a.balance).toLocaleString() }}</td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-red-50/50">
                        <tr>
                            <td class="px-4 py-2 text-sm font-semibold text-slate-700">Total Expenses</td>
                            <td class="px-4 py-2 text-right text-sm font-semibold text-red-700">
                                {{ Number(report.expenses.total).toLocaleString() }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Net Income -->
            <div class="bg-white rounded-xl border-2 shadow-sm overflow-hidden"
                :class="report.net_income >= 0 ? 'border-green-300' : 'border-red-300'">
                <div class="px-4 py-4 flex justify-between items-center">
                    <span class="text-base font-bold" :class="report.net_income >= 0 ? 'text-green-700' : 'text-red-700'">
                        {{ report.net_income >= 0 ? 'Net Income' : 'Net Loss' }}
                    </span>
                    <span class="text-lg font-bold font-mono"
                        :class="report.net_income >= 0 ? 'text-green-700' : 'text-red-700'">
                        {{ Number(report.net_income).toLocaleString() }}
                    </span>
                </div>
            </div>

            <p class="text-xs text-slate-400 text-right">Generated: {{ report.generated_at }}</p>
        </div>

        <div v-else class="bg-white rounded-xl border border-slate-100 shadow-sm p-8 text-center text-slate-400">
            Set filters and click Generate to view the P&L Statement
        </div>
    </div>
</template>
