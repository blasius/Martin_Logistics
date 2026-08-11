<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { invoicesApi } from '../../api/invoices'
import { paymentsApi } from '../../api/payments'

const route = useRoute()
const router = useRouter()
const invoice = ref<any>(null)
const loading = ref(true)
const showPaymentForm = ref(false)
const savingPayment = ref(false)
const paymentForm = ref({ amount: '', method: '', tx_reference: '', paid_at: '', notes: '' })

const totalPaid = computed(() => {
    return (invoice.value?.payments || []).reduce((s: number, p: any) => s + Number(p.amount), 0)
})

const balanceDue = computed(() => {
    return Math.max(0, Number(invoice.value?.total || 0) - totalPaid.value)
})

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

function formatDate(d: string) {
    if (!d) return '—'
    return new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })
}

async function fetchInvoice() {
    loading.value = true
    try {
        const res = await invoicesApi.show(route.params.id as string)
        invoice.value = res.data
    } catch {} finally { loading.value = false }
}

async function changeStatus(status: string) {
    if (!confirm(`Mark as ${status}?`)) return
    try {
        if (status === 'sent') await invoicesApi.markSent(invoice.value.id)
        else if (status === 'paid') await invoicesApi.markPaid(invoice.value.id)
        else if (status === 'overdue') await invoicesApi.markOverdue(invoice.value.id)
        else if (status === 'cancelled') await invoicesApi.markCancelled(invoice.value.id)
        await fetchInvoice()
    } catch {}
}

async function downloadPdf() {
    try {
        const res = await invoicesApi.downloadPdf(invoice.value.id)
        const url = window.URL.createObjectURL(new Blob([res.data]))
        const a = document.createElement('a')
        a.href = url
        a.download = `${invoice.value.reference}.pdf`
        a.click()
        window.URL.revokeObjectURL(url)
    } catch {}
}

async function remove() {
    if (!confirm('Delete this invoice?')) return
    try {
        await invoicesApi.destroy(invoice.value.id)
        router.push('/invoices')
    } catch {}
}

async function recordPayment() {
    savingPayment.value = true
    try {
        await paymentsApi.store({ invoice_id: invoice.value.id, ...paymentForm.value })
        showPaymentForm.value = false
        paymentForm.value = { amount: '', method: '', tx_reference: '', paid_at: '', notes: '' }
        await fetchInvoice()
    } catch (e: any) {
        alert(e.response?.data?.message || 'Failed to record payment')
    } finally { savingPayment.value = false }
}

onMounted(fetchInvoice)
</script>

<template>
    <div>
        <div class="mb-6">
            <router-link to="/invoices" class="text-sm text-emerald-600 hover:text-emerald-700 mb-2 inline-block">&larr; Back</router-link>
            <div v-if="invoice" class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold text-slate-800">{{ invoice.reference }}</h1>
                    <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium"
                        :class="statusClass(invoice.status)">{{ invoice.status }}</span>
                    <span v-if="invoice.type !== 'invoice'"
                        class="inline-block px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-700">
                        {{ invoice.type.replace('_', ' ') }}
                    </span>
                </div>
                <div class="flex gap-2">
                    <button @click="downloadPdf"
                        class="px-3 py-1.5 text-sm font-medium text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50">PDF</button>
                    <button v-if="invoice.status === 'draft'" @click="changeStatus('sent')"
                        class="px-3 py-1.5 text-sm font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100">Mark Sent</button>
                    <button v-if="invoice.status === 'sent'" @click="changeStatus('paid')"
                        class="px-3 py-1.5 text-sm font-medium text-green-600 bg-green-50 rounded-lg hover:bg-green-100">Mark Paid</button>
                    <button v-if="invoice.status === 'sent' || invoice.status === 'draft'" @click="changeStatus('overdue')"
                        class="px-3 py-1.5 text-sm font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100">Mark Overdue</button>
                    <button v-if="invoice.status !== 'cancelled'" @click="changeStatus('cancelled')"
                        class="px-3 py-1.5 text-sm font-medium text-slate-600 bg-slate-100 rounded-lg hover:bg-slate-200">Cancel</button>
                    <button v-if="invoice.status === 'draft' || invoice.status === 'cancelled'" @click="remove"
                        class="px-3 py-1.5 text-sm font-medium text-red-600 bg-white border border-red-200 rounded-lg hover:bg-red-50">Delete</button>
                </div>
            </div>
        </div>

        <div v-if="loading" class="p-8 text-center text-slate-400">Loading...</div>

        <template v-else-if="invoice">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6">
                        <h2 class="text-lg font-semibold text-slate-800 mb-4">Line Items</h2>
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-slate-100">
                                    <th class="text-left py-2 text-xs font-semibold text-slate-500 uppercase">Description</th>
                                    <th class="text-right py-2 text-xs font-semibold text-slate-500 uppercase">Qty</th>
                                    <th class="text-right py-2 text-xs font-semibold text-slate-500 uppercase">Unit Price</th>
                                    <th class="text-right py-2 text-xs font-semibold text-slate-500 uppercase">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in invoice.items" :key="item.id" class="border-b border-slate-50">
                                    <td class="py-2 font-medium text-slate-700">{{ item.description }}</td>
                                    <td class="py-2 text-right text-slate-600">{{ Number(item.quantity).toLocaleString() }}</td>
                                    <td class="py-2 text-right text-slate-600">{{ Number(item.unit_price).toLocaleString() }}</td>
                                    <td class="py-2 text-right font-medium">{{ Number(item.total).toLocaleString() }} {{ invoice.currency?.code }}</td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="mt-4 border-t border-slate-100 pt-4">
                            <div class="flex justify-end">
                                <div class="w-64 space-y-1 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-slate-500">Subtotal</span>
                                        <span>{{ Number(invoice.subtotal).toLocaleString() }} {{ invoice.currency?.code }}</span>
                                    </div>
                                    <div v-if="Number(invoice.discount_total) > 0" class="flex justify-between">
                                        <span class="text-slate-500">Discount</span>
                                        <span class="text-red-600">-{{ Number(invoice.discount_total).toLocaleString() }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-slate-500">Tax</span>
                                        <span>{{ Number(invoice.tax_total).toLocaleString() }} {{ invoice.currency?.code }}</span>
                                    </div>
                                    <div class="flex justify-between text-lg font-bold text-emerald-600 border-t border-slate-200 pt-1">
                                        <span>Total</span>
                                        <span>{{ Number(invoice.total).toLocaleString() }} {{ invoice.currency?.code }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6">
                        <h2 class="text-lg font-semibold text-slate-800 mb-4">Payments</h2>
                        <div v-if="invoice.payments?.length">
                            <div v-for="p in invoice.payments" :key="p.id"
                                class="flex justify-between py-2 border-b border-slate-50 last:border-0 text-sm">
                                <span>{{ p.paid_at ? formatDate(p.paid_at) : formatDate(p.created_at) }}</span>
                                <span class="font-medium">{{ Number(p.amount).toLocaleString() }} {{ invoice.currency?.code }}</span>
                            </div>
                        </div>
                        <p v-else class="text-sm text-slate-400">No payments recorded</p>
                        <div class="mt-3 pt-3 border-t border-slate-100">
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-slate-500">Total Paid</span>
                                <span class="font-medium text-green-600">{{ totalPaid.toLocaleString() }} {{ invoice.currency?.code }}</span>
                            </div>
                            <div class="flex justify-between text-sm font-semibold">
                                <span class="text-slate-600">Balance Due</span>
                                <span :class="balanceDue > 0 ? 'text-red-600' : 'text-green-600'">{{ balanceDue.toLocaleString() }} {{ invoice.currency?.code }}</span>
                            </div>
                        </div>
                        <button v-if="['sent', 'overdue'].includes(invoice.status)" @click="showPaymentForm = true"
                            class="mt-3 w-full px-3 py-1.5 text-sm font-medium text-emerald-600 bg-emerald-50 rounded-lg hover:bg-emerald-100">
                            Record Payment
                        </button>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6">
                        <h2 class="text-lg font-semibold text-slate-800 mb-4">Details</h2>
                        <div class="space-y-3 text-sm">
                            <div><span class="text-slate-400 block text-xs">Client</span><span class="font-medium">{{ invoice.client?.name }}</span></div>
                            <div><span class="text-slate-400 block text-xs">Issue Date</span><span>{{ formatDate(invoice.issue_date) }}</span></div>
                            <div><span class="text-slate-400 block text-xs">Due Date</span><span>{{ formatDate(invoice.due_date) }}</span></div>
                            <div v-if="invoice.order"><span class="text-slate-400 block text-xs">Order</span>
                                <router-link :to="`/orders/${invoice.order.id}`" class="text-emerald-600 hover:text-emerald-700">{{ invoice.order.reference }}</router-link>
                            </div>
                            <div v-if="invoice.contract"><span class="text-slate-400 block text-xs">Contract</span><span>{{ invoice.contract.reference }}</span></div>
                        </div>
                    </div>

                    <div v-if="invoice.notes" class="bg-white rounded-xl border border-slate-100 shadow-sm p-6">
                        <h2 class="text-sm font-semibold text-slate-800 mb-2">Notes</h2>
                        <p class="text-sm text-slate-600 whitespace-pre-wrap">{{ invoice.notes }}</p>
                    </div>
                </div>
            </div>
        </template>

        <div v-if="showPaymentForm" class="modal-backdrop" @click.self="showPaymentForm = false">
            <div class="modal-content max-w-md">
                <h2 class="text-xl font-bold mb-4">Record Payment</h2>
                <form @submit.prevent="recordPayment" class="space-y-4">
                    <div>
                        <label class="label">Amount</label>
                        <input v-model="paymentForm.amount" type="number" step="0.01" min="0.01" class="input w-full" required />
                    </div>
                    <div>
                        <label class="label">Payment Method</label>
                        <select v-model="paymentForm.method" class="select w-full">
                            <option value="">Select</option>
                            <option value="cash">Cash</option>
                            <option value="mobile">Mobile Money</option>
                            <option value="bank">Bank Transfer</option>
                            <option value="cheque">Cheque</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Transaction Reference</label>
                        <input v-model="paymentForm.tx_reference" class="input w-full" />
                    </div>
                    <div>
                        <label class="label">Payment Date</label>
                        <input v-model="paymentForm.paid_at" type="date" class="input w-full" />
                    </div>
                    <div>
                        <label class="label">Notes</label>
                        <textarea v-model="paymentForm.notes" class="textarea w-full" rows="2"></textarea>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="showPaymentForm = false" class="btn">Cancel</button>
                        <button type="submit" class="btn btn-primary" :disabled="savingPayment">{{ savingPayment ? 'Saving...' : 'Record Payment' }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
