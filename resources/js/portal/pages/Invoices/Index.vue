<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { invoicesApi } from '../../api/invoices'

const invoices = ref<any[]>([])
const loading = ref(true)
const statusFilter = ref('')
const typeFilter = ref('')
const generateModal = ref(false)
const orders = ref<any[]>([])
const selectedOrderId = ref<number | null>(null)
const generating = ref(false)

async function fetchInvoices() {
    loading.value = true
    try {
        const params: any = {}
        if (statusFilter.value) params.status = statusFilter.value
        if (typeFilter.value) params.type = typeFilter.value
        const res = await invoicesApi.getAll(params)
        invoices.value = res.data.data ?? res.data
    } catch {} finally { loading.value = false }
}

function statusClass(status: string) {
    const map: Record<string, string> = {
        draft: 'bg-gray-100 text-gray-600',
        sent: 'bg-blue-100 text-blue-700',
        paid: 'bg-green-100 text-green-700',
        overdue: 'bg-red-100 text-red-700',
        cancelled: 'bg-slate-100 text-slate-600',
    }
    return map[status] ?? 'bg-gray-100 text-gray-600'
}

async function openGenerate() {
    generateModal.value = true
    selectedOrderId.value = null
    try {
        const res = await (await import('../../api/orders')).ordersApi.getAll({ per_page: 500, status: 'delivered' })
        orders.value = res.data.data ?? res.data
    } catch {}
}

async function doGenerate() {
    if (!selectedOrderId.value) return
    generating.value = true
    try {
        await invoicesApi.generateFromOrder(selectedOrderId.value)
        generateModal.value = false
        await fetchInvoices()
    } catch {} finally { generating.value = false }
}

function typeBadge(type: string) {
    if (type === 'credit_note') return 'bg-purple-100 text-purple-700'
    if (type === 'debit_note') return 'bg-orange-100 text-orange-700'
    return 'bg-slate-100 text-slate-600'
}

function formatDate(d: string) {
    if (!d) return '—'
    return new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })
}

onMounted(fetchInvoices)
</script>

<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-slate-800">Invoices</h1>
            <div class="flex gap-2">
                <button @click="openGenerate"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors text-sm font-medium">
                    Generate from Order
                </button>
                <router-link to="/invoices/create"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-white text-slate-700 border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors text-sm font-medium">
                    Create Manually
                </router-link>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-4 mb-6">
            <div class="flex flex-wrap gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Status</label>
                    <select v-model="statusFilter" @change="fetchInvoices"
                        class="text-sm border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500">
                        <option value="">All</option>
                        <option value="draft">Draft</option>
                        <option value="sent">Sent</option>
                        <option value="paid">Paid</option>
                        <option value="overdue">Overdue</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Type</label>
                    <select v-model="typeFilter" @change="fetchInvoices"
                        class="text-sm border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500">
                        <option value="">All</option>
                        <option value="invoice">Invoice</option>
                        <option value="credit_note">Credit Note</option>
                        <option value="debit_note">Debit Note</option>
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
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Dates</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Total</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="inv in invoices" :key="inv.id"
                        class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors cursor-pointer"
                        @click="$router.push(`/invoices/${inv.id}`)">
                        <td class="px-4 py-3 text-sm font-medium text-slate-800">{{ inv.reference }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ inv.client?.name }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium"
                                :class="typeBadge(inv.type)">{{ inv.type.replace('_', ' ') }}</span>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600">
                            {{ formatDate(inv.issue_date) }} → {{ formatDate(inv.due_date) }}
                        </td>
                        <td class="px-4 py-3 text-right text-sm font-medium text-slate-800">
                            {{ Number(inv.total).toLocaleString() }} {{ inv.currency?.code }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium"
                                :class="statusClass(inv.status)">{{ inv.status }}</span>
                        </td>
                    </tr>
                    <tr v-if="!invoices.length">
                        <td colspan="6" class="px-4 py-8 text-center text-slate-400">No invoices found</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Generate from Order Modal -->
        <div v-if="generateModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
            @click.self="generateModal = false">
            <div class="bg-white rounded-xl shadow-xl max-w-lg w-full mx-4">
                <div class="flex items-center justify-between p-6 border-b border-slate-100">
                    <h2 class="text-lg font-semibold text-slate-800">Generate Invoice from Order</h2>
                    <button @click="generateModal = false"
                        class="text-slate-400 hover:text-slate-600 text-xl leading-none">&times;</button>
                </div>
                <div class="p-6">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Select Delivered Order</label>
                    <select v-model="selectedOrderId"
                        class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500">
                        <option :value="null">Choose...</option>
                        <option v-for="o in orders" :key="o.id" :value="o.id">{{ o.reference }} — {{ o.client?.name }}</option>
                    </select>
                </div>
                <div class="flex justify-end gap-3 p-6 border-t border-slate-100">
                    <button @click="generateModal = false"
                        class="px-4 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-200 rounded-lg">
                        Cancel
                    </button>
                    <button @click="doGenerate" :disabled="!selectedOrderId || generating"
                        class="px-6 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 disabled:opacity-50">
                        {{ generating ? 'Generating...' : 'Generate' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
