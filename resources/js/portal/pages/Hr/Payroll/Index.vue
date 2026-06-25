<script setup lang="ts">
import { ref, onMounted } from 'vue'
import hrApi from '../../../api/hr'

const tab = ref('periods')
const payPeriods = ref<any[]>([])
const payslips = ref<any[]>([])
const loading = ref(true)
const showCreatePeriod = ref(false)
const periodForm = ref({ name: '', start_date: '', end_date: '' })
const submitting = ref(false)

const payslipPeriodFilter = ref('')
const payslipStatusFilter = ref('')

async function fetchPeriods() {
    try {
        const res = await hrApi.payPeriods.index()
        payPeriods.value = res.data
    } catch {}
}

async function fetchPayslips() {
    try {
        const params: any = {}
        if (payslipPeriodFilter.value) params.pay_period_id = payslipPeriodFilter.value
        if (payslipStatusFilter.value) params.status = payslipStatusFilter.value
        const res = await hrApi.payslips.index(params)
        payslips.value = res.data.data ?? res.data
    } catch {}
}

async function load() {
    loading.value = true
    await Promise.all([fetchPeriods(), fetchPayslips()])
    loading.value = false
}

async function createPeriod() {
    submitting.value = true
    try {
        await hrApi.payPeriods.store(periodForm.value)
        showCreatePeriod.value = false
        periodForm.value = { name: '', start_date: '', end_date: '' }
        await fetchPeriods()
    } catch (e: any) { alert(e.response?.data?.message || 'Failed') } finally { submitting.value = false }
}

async function closePeriod(id: number) {
    if (!confirm('Close this pay period?')) return
    try { await hrApi.payPeriods.close(id); await fetchPeriods() } catch {}
}

async function generatePayslips(periodId: number) {
    try {
        const res = await hrApi.payslips.generate({ pay_period_id: periodId })
        alert(res.data.message)
        await fetchPayslips()
    } catch (e: any) { alert(e.response?.data?.message || 'Failed') }
}

async function approvePayslip(slip: any) {
    const allowances = prompt('Allowances (JSON array or 0):', '[]')
    if (allowances === null) return
    const deductions = prompt('Deductions (JSON array or 0):', '[]')
    if (deductions === null) return

    try {
        const allowArr = JSON.parse(allowances || '[]')
        const deductArr = JSON.parse(deductions || '[]')
        const totalAllow = allowArr.reduce((s: number, a: any) => s + (a.amount ?? 0), 0)
        const totalDeduct = deductArr.reduce((s: number, d: any) => s + (d.amount ?? 0), 0)
        const gross = slip.basic_pay + totalAllow
        const net = gross - totalDeduct
        await hrApi.payslips.approve(slip.id, {
            allowances: allowArr, deductions: deductArr,
            total_allowances: totalAllow, total_deductions: totalDeduct,
            gross_pay: gross, net_pay: net,
        })
        await fetchPayslips()
    } catch (e: any) { alert('Error: ' + (e.response?.data?.message || e.message)) }
}

async function markPaid(id: number) {
    if (!confirm('Mark this payslip as paid?')) return
    try { await hrApi.payslips.markPaid(id); await fetchPayslips() } catch {}
}

function statusClass(s: string) {
    const map: Record<string, string> = { draft: 'bg-gray-100 text-gray-600', approved: 'bg-blue-100 text-blue-700', paid: 'bg-emerald-100 text-emerald-700' }
    return map[s] ?? 'bg-gray-100 text-gray-600'
}

onMounted(load)
</script>

<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-slate-800">Payroll</h1>
        </div>

        <div class="flex gap-2 mb-6 border-b border-slate-200">
            <button @click="tab = 'periods'" :class="['px-4 py-2 text-sm font-medium border-b-2', tab === 'periods' ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-slate-500']">Pay Periods</button>
            <button @click="tab = 'payslips'" :class="['px-4 py-2 text-sm font-medium border-b-2', tab === 'payslips' ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-slate-500']">Payslips</button>
        </div>

        <div v-if="loading" class="text-center py-12 text-slate-400">Loading...</div>

        <!-- Pay Periods -->
        <div v-if="tab === 'periods' && !loading">
            <div class="mb-4">
                <button @click="showCreatePeriod = true" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 text-sm font-medium">New Pay Period</button>
            </div>
            <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead><tr class="border-b border-slate-100 bg-slate-50/50"><th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Name</th><th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Start</th><th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">End</th><th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Payslips</th><th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Status</th><th class="text-right px-4 py-3"></th></tr></thead>
                    <tbody>
                        <tr v-for="p in payPeriods" :key="p.id" class="border-b border-slate-50">
                            <td class="px-4 py-3 font-medium text-slate-700">{{ p.name }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ p.start_date }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ p.end_date }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ p.payslips_count ?? 0 }}</td>
                            <td class="px-4 py-3"><span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-medium" :class="p.status === 'open' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600'">{{ p.status }}</span></td>
                            <td class="px-4 py-3 text-right">
                                <button v-if="p.status === 'open'" @click="generatePayslips(p.id)" class="px-2 py-1 text-xs bg-blue-100 text-blue-700 rounded hover:bg-blue-200 mr-1">Generate</button>
                                <button v-if="p.status === 'open'" @click="closePeriod(p.id)" class="px-2 py-1 text-xs bg-gray-100 text-gray-600 rounded hover:bg-gray-200">Close</button>
                            </td>
                        </tr>
                        <tr v-if="!payPeriods.length"><td colspan="6" class="px-4 py-8 text-center text-slate-400">No pay periods.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Payslips -->
        <div v-if="tab === 'payslips' && !loading">
            <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-4 mb-6">
                <div class="flex flex-wrap gap-4">
                    <div><label class="block text-xs font-medium text-slate-500 mb-1">Pay Period</label><select v-model="payslipPeriodFilter" @change="fetchPayslips" class="text-sm border border-slate-200 rounded-lg px-3 py-2"><option value="">All</option><option v-for="p in payPeriods" :key="p.id" :value="p.id">{{ p.name }}</option></select></div>
                    <div><label class="block text-xs font-medium text-slate-500 mb-1">Status</label><select v-model="payslipStatusFilter" @change="fetchPayslips" class="text-sm border border-slate-200 rounded-lg px-3 py-2"><option value="">All</option><option value="draft">Draft</option><option value="approved">Approved</option><option value="paid">Paid</option></select></div>
                </div>
            </div>
            <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead><tr class="border-b border-slate-100 bg-slate-50/50"><th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Employee</th><th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Period</th><th class="text-right px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Basic</th><th class="text-right px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Allowances</th><th class="text-right px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Deductions</th><th class="text-right px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Net</th><th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Status</th><th class="text-right px-4 py-3"></th></tr></thead>
                    <tbody>
                        <tr v-for="s in payslips" :key="s.id" class="border-b border-slate-50">
                            <td class="px-4 py-3 text-slate-700">{{ s.employee?.first_name }} {{ s.employee?.last_name }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ s.pay_period?.name ?? '—' }}</td>
                            <td class="px-4 py-3 text-right text-slate-700">{{ s.currency?.code ?? '' }} {{ Number(s.basic_pay).toFixed(2) }}</td>
                            <td class="px-4 py-3 text-right text-slate-600">{{ Number(s.total_allowances).toFixed(2) }}</td>
                            <td class="px-4 py-3 text-right text-slate-600">({{ Number(s.total_deductions).toFixed(2) }})</td>
                            <td class="px-4 py-3 text-right font-semibold text-slate-800">{{ Number(s.net_pay).toFixed(2) }}</td>
                            <td class="px-4 py-3"><span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-medium" :class="statusClass(s.status)">{{ s.status }}</span></td>
                            <td class="px-4 py-3 text-right">
                                <button v-if="s.status === 'draft'" @click="approvePayslip(s)" class="px-2 py-1 text-xs bg-blue-100 text-blue-700 rounded hover:bg-blue-200 mr-1">Approve</button>
                                <button v-if="s.status === 'approved'" @click="markPaid(s.id)" class="px-2 py-1 text-xs bg-emerald-100 text-emerald-700 rounded hover:bg-emerald-200">Pay</button>
                            </td>
                        </tr>
                        <tr v-if="!payslips.length"><td colspan="8" class="px-4 py-8 text-center text-slate-400">No payslips.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Create Period Modal -->
        <div v-if="showCreatePeriod" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" @click.self="showCreatePeriod = false">
            <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-md">
                <h3 class="text-lg font-semibold text-slate-700 mb-4">New Pay Period</h3>
                <form @submit.prevent="createPeriod" class="space-y-3">
                    <div><label class="block text-xs font-medium text-slate-500 mb-1">Name</label><input v-model="periodForm.name" required placeholder="e.g. June 2026" class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500" /></div>
                    <div class="grid grid-cols-2 gap-3">
                        <div><label class="block text-xs font-medium text-slate-500 mb-1">Start</label><input v-model="periodForm.start_date" type="date" required class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500" /></div>
                        <div><label class="block text-xs font-medium text-slate-500 mb-1">End</label><input v-model="periodForm.end_date" type="date" required class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500" /></div>
                    </div>
                    <div class="flex justify-end gap-3 mt-4">
                        <button type="button" @click="showCreatePeriod = false" class="px-4 py-2 text-sm text-slate-600 border border-slate-200 rounded-lg">Cancel</button>
                        <button type="submit" :disabled="submitting" class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-medium disabled:opacity-50">{{ submitting ? 'Creating...' : 'Create' }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
