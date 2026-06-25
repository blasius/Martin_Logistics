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
        const res = await accountingReportsApi.balanceSheet(params)
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
        <h1 class="text-2xl font-bold text-slate-800 mb-6">Balance Sheet</h1>

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

        <div v-else-if="report" class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Assets -->
            <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="px-4 py-3 bg-blue-50 border-b border-blue-100">
                    <h3 class="text-sm font-semibold text-blue-700 uppercase">Assets</h3>
                </div>
                <table class="w-full">
                    <tr v-for="a in report.assets.accounts" :key="a.account_id" class="border-b border-slate-50">
                        <td class="px-4 py-2 text-sm text-slate-800">{{ a.code }} — {{ a.name }}</td>
                        <td class="px-4 py-2 text-right text-sm font-mono text-slate-800">
                            {{ Number(a.balance).toLocaleString() }}</td>
                    </tr>
                    <tfoot class="bg-blue-50/50">
                        <tr>
                            <td class="px-4 py-2 text-sm font-semibold text-slate-700">Total Assets</td>
                            <td class="px-4 py-2 text-right text-sm font-semibold text-blue-700">
                                {{ Number(report.assets.total).toLocaleString() }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="space-y-6">
                <!-- Liabilities -->
                <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
                    <div class="px-4 py-3 bg-orange-50 border-b border-orange-100">
                        <h3 class="text-sm font-semibold text-orange-700 uppercase">Liabilities</h3>
                    </div>
                    <table class="w-full">
                        <tr v-for="a in report.liabilities.accounts" :key="a.account_id" class="border-b border-slate-50">
                            <td class="px-4 py-2 text-sm text-slate-800">{{ a.code }} — {{ a.name }}</td>
                            <td class="px-4 py-2 text-right text-sm font-mono text-slate-800">
                                {{ Number(a.balance).toLocaleString() }}</td>
                        </tr>
                        <tfoot class="bg-orange-50/50">
                            <tr>
                                <td class="px-4 py-2 text-sm font-semibold text-slate-700">Total Liabilities</td>
                                <td class="px-4 py-2 text-right text-sm font-semibold text-orange-700">
                                    {{ Number(report.liabilities.total).toLocaleString() }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Equity -->
                <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
                    <div class="px-4 py-3 bg-purple-50 border-b border-purple-100">
                        <h3 class="text-sm font-semibold text-purple-700 uppercase">Equity</h3>
                    </div>
                    <table class="w-full">
                        <tr v-for="a in report.equity.accounts" :key="a.account_id" class="border-b border-slate-50">
                            <td class="px-4 py-2 text-sm text-slate-800">{{ a.code }} — {{ a.name }}</td>
                            <td class="px-4 py-2 text-right text-sm font-mono text-slate-800">
                                {{ Number(a.balance).toLocaleString() }}</td>
                        </tr>
                        <tr class="border-b border-slate-50 bg-purple-50/50">
                            <td class="px-4 py-2 text-sm text-purple-700 font-medium">Retained Earnings</td>
                            <td class="px-4 py-2 text-right text-sm font-mono text-purple-700">
                                {{ Number(report.retained_earnings).toLocaleString() }}</td>
                        </tr>
                        <tfoot class="bg-purple-50/50">
                            <tr>
                                <td class="px-4 py-2 text-sm font-semibold text-slate-700">Total Liabilities & Equity</td>
                                <td class="px-4 py-2 text-right text-sm font-semibold text-purple-700">
                                    {{ Number(report.total_liabilities_equity).toLocaleString() }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <p class="text-xs text-slate-400 text-right md:col-span-2">Generated: {{ report.generated_at }}</p>
        </div>

        <div v-else class="bg-white rounded-xl border border-slate-100 shadow-sm p-8 text-center text-slate-400">
            Set filters and click Generate to view the Balance Sheet
        </div>
    </div>
</template>
