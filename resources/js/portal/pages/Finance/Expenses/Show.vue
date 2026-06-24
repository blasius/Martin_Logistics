<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { expensesApi } from '../../../api/expenses'

const props = defineProps<{ id: string | number }>()
const router = useRouter()

const expense = ref<any>(null)
const loading = ref(true)
const payForm = ref({ paid_at: '', payment_method: '', payment_reference: '' })
const payProof = ref<File | null>(null)
const showPayModal = ref(false)
const paying = ref(false)

const approveComment = ref('')
const rejectReason = ref('')
const showRejectModal = ref(false)

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
    return cls === 'fixed' ? 'bg-blue-100 text-blue-600' : 'bg-purple-100 text-purple-600'
}

const amountDisplay = computed(() => {
    if (!expense.value) return ''
    const amt = Number(expense.value.amount).toLocaleString()
    const code = expense.value.currency?.code ?? ''
    return `${amt} ${code}`
})

const canApprove = computed(() => expense.value?.status === 'pending' && expense.value?.expense_class === 'variable')
const canPay = computed(() => expense.value?.status === 'approved')
const canEdit = computed(() => expense.value && !['paid', 'rejected'].includes(expense.value.status))

async function loadExpense() {
    loading.value = true
    try {
        const res = await expensesApi.show(props.id)
        expense.value = res.data
    } catch {} finally { loading.value = false }
}

async function approveExpense() {
    try {
        await expensesApi.approve(props.id, approveComment.value)
        await loadExpense()
        approveComment.value = ''
    } catch (e: any) {
        alert(e?.response?.data?.message || 'Failed to approve')
    }
}

async function rejectExpense() {
    if (!rejectReason.value) return alert('Please provide a reason')
    try {
        await expensesApi.reject(props.id, rejectReason.value)
        await loadExpense()
        showRejectModal.value = false
        rejectReason.value = ''
    } catch (e: any) {
        alert(e?.response?.data?.message || 'Failed to reject')
    }
}

async function payExpense() {
    paying.value = true
    try {
        const fd = new FormData()
        fd.append('paid_at', payForm.value.paid_at || new Date().toISOString().slice(0, 16))
        if (payForm.value.payment_method) fd.append('payment_method', payForm.value.payment_method)
        if (payForm.value.payment_reference) fd.append('payment_reference', payForm.value.payment_reference)
        if (payProof.value) fd.append('proof_of_payment', payProof.value)

        await expensesApi.pay(props.id, fd)
        await loadExpense()
        showPayModal.value = false
    } catch (e: any) {
        alert(e?.response?.data?.message || 'Failed to record payment')
    } finally { paying.value = false }
}

async function deleteExpense() {
    if (!confirm('Delete this expense?')) return
    try {
        await expensesApi.destroy(props.id)
        router.push('/finance/expenses')
    } catch (e: any) {
        alert(e?.response?.data?.message || 'Failed to delete')
    }
}

onMounted(loadExpense)
</script>

<template>
    <div>
        <router-link to="/finance/expenses" class="text-sm text-emerald-600 hover:text-emerald-700 mb-4 inline-block">
            &larr; Back to Expenses
        </router-link>

        <div v-if="loading" class="bg-white rounded-xl border border-slate-100 shadow-sm p-6">
            <div class="animate-pulse space-y-4">
                <div class="h-8 bg-slate-100 rounded w-64"></div>
                <div class="h-4 bg-slate-100 rounded w-48"></div>
                <div class="h-20 bg-slate-100 rounded w-full"></div>
            </div>
        </div>

        <template v-else-if="expense">
            <div class="flex items-start justify-between mb-6">
                <div>
                    <div class="flex items-center gap-3 mb-1">
                        <h1 class="text-2xl font-bold text-slate-800">{{ expense.name }}</h1>
                        <span class="inline-block px-2 py-0.5 rounded text-xs font-medium"
                            :class="statusClass(expense.status)">{{ expense.status }}</span>
                        <span class="inline-block px-2 py-0.5 rounded text-xs font-medium"
                            :class="classBadge(expense.expense_class)">{{ expense.expense_class }}</span>
                    </div>
                    <p class="text-sm font-mono text-slate-500">{{ expense.reference }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <button v-if="canApprove" @click="approveExpense"
                        class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm hover:bg-emerald-700 transition-colors">
                        Approve
                    </button>
                    <button v-if="canApprove" @click="showRejectModal = true"
                        class="px-4 py-2 border border-red-200 text-red-600 rounded-lg text-sm hover:bg-red-50 transition-colors">
                        Reject
                    </button>
                    <button v-if="canPay" @click="showPayModal = true"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition-colors">
                        Record Payment
                    </button>
                    <button v-if="canEdit" @click="deleteExpense"
                        class="px-4 py-2 border border-slate-200 text-slate-600 rounded-lg text-sm hover:bg-slate-50 transition-colors">
                        Delete
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6">
                        <h2 class="text-lg font-semibold text-slate-800 mb-4">Details</h2>
                        <dl class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <dt class="text-slate-500">Amount</dt>
                                <dd class="font-semibold text-slate-800">{{ amountDisplay }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Category</dt>
                                <dd class="text-slate-800">{{ expense.category ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Vehicle</dt>
                                <dd class="text-slate-800">{{ expense.vehicle?.plate_number ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Driver</dt>
                                <dd class="text-slate-800">{{ expense.driver?.name ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Trip</dt>
                                <dd class="text-slate-800">{{ expense.trip?.reference ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Location</dt>
                                <dd class="text-slate-800">{{ expense.location ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Odometer</dt>
                                <dd class="text-slate-800">{{ expense.odometer != null ? expense.odometer.toLocaleString() + ' km' : '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Created By</dt>
                                <dd class="text-slate-800">{{ expense.creator?.name ?? '-' }}</dd>
                            </div>
                        </dl>
                        <div v-if="expense.description" class="mt-4 pt-4 border-t border-slate-100">
                            <h3 class="text-sm font-medium text-slate-700 mb-1">Description</h3>
                            <p class="text-sm text-slate-600">{{ expense.description }}</p>
                        </div>
                    </div>

                    <div v-if="expense.approvals?.length" class="bg-white rounded-xl border border-slate-100 shadow-sm p-6">
                        <h2 class="text-lg font-semibold text-slate-800 mb-4">Approval Chain</h2>
                        <div class="space-y-3">
                            <div v-for="a in expense.approvals" :key="a.id"
                                class="flex items-center justify-between p-3 bg-slate-50 rounded-lg">
                                <div>
                                    <div class="text-sm font-medium text-slate-800">{{ a.approver_role }}</div>
                                    <div v-if="a.approver" class="text-xs text-slate-500">{{ a.approver.name }}</div>
                                </div>
                                <div class="text-right">
                                    <span class="inline-block px-2 py-0.5 rounded text-xs font-medium"
                                        :class="a.status === 'approved' ? 'bg-green-100 text-green-700' : a.status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700'">
                                        {{ a.status }}
                                    </span>
                                    <div v-if="a.comment" class="text-xs text-slate-500 mt-1">{{ a.comment }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="expense.support_ticket" class="bg-white rounded-xl border border-slate-100 shadow-sm p-6">
                        <h2 class="text-lg font-semibold text-slate-800 mb-2">Linked Support Ticket</h2>
                        <p class="text-sm text-slate-600">
                            Ticket: {{ expense.support_ticket.reference }} - {{ expense.support_ticket.title }}
                        </p>
                    </div>
                </div>

                <div class="space-y-6">
                    <div v-if="expense.status === 'paid'" class="bg-white rounded-xl border border-slate-100 shadow-sm p-6">
                        <h2 class="text-lg font-semibold text-slate-800 mb-4">Payment</h2>
                        <dl class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-slate-500">Paid at</span>
                                <span class="text-slate-800">{{ new Date(expense.paid_at).toLocaleString() }}</span>
                            </div>
                            <div v-if="expense.payment_method" class="flex justify-between">
                                <span class="text-slate-500">Method</span>
                                <span class="text-slate-800">{{ expense.payment_method }}</span>
                            </div>
                            <div v-if="expense.payment_reference" class="flex justify-between">
                                <span class="text-slate-500">Reference</span>
                                <span class="text-slate-800 font-mono">{{ expense.payment_reference }}</span>
                            </div>
                            <div v-if="expense.proof_of_payment_path" class="mt-3">
                                <a :href="'/storage/' + expense.proof_of_payment_path" target="_blank"
                                    class="text-sm text-emerald-600 hover:text-emerald-700">
                                    View Proof of Payment
                                </a>
                            </div>
                        </dl>
                    </div>

                    <div v-if="expense.expense_type" class="bg-white rounded-xl border border-slate-100 shadow-sm p-6">
                        <h2 class="text-lg font-semibold text-slate-800 mb-2">Expense Type</h2>
                        <p class="text-sm text-slate-800 font-medium">{{ expense.expense_type.name }}</p>
                        <p v-if="expense.expense_type.default_amount != null" class="text-xs text-slate-500">
                            Default: {{ Number(expense.expense_type.default_amount).toLocaleString() }}
                        </p>
                    </div>

                    <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6">
                        <h2 class="text-lg font-semibold text-slate-800 mb-2">Timeline</h2>
                        <dl class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-slate-500">Created</span>
                                <span class="text-slate-800">{{ new Date(expense.created_at).toLocaleString() }}</span>
                            </div>
                            <div v-if="expense.updated_at !== expense.created_at" class="flex justify-between">
                                <span class="text-slate-500">Updated</span>
                                <span class="text-slate-800">{{ new Date(expense.updated_at).toLocaleString() }}</span>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <!-- Pay Modal -->
            <div v-if="showPayModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
                @click.self="showPayModal = false">
                <div class="bg-white rounded-xl p-6 w-full max-w-md shadow-xl">
                    <h3 class="text-lg font-semibold text-slate-800 mb-4">Record Payment</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Paid at</label>
                            <input type="datetime-local" v-model="payForm.paid_at"
                                class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Method</label>
                            <input v-model="payForm.payment_method" placeholder="e.g. Bank Transfer, Cash, Mobile Money"
                                class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Reference</label>
                            <input v-model="payForm.payment_reference" placeholder="Transaction reference"
                                class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Proof of Payment</label>
                            <input type="file" accept="image/*,application/pdf" @change="(e: any) => payProof = e.target.files?.[0] ?? null"
                                class="w-full text-sm">
                        </div>
                    </div>
                    <div class="flex items-center gap-3 mt-6 pt-4 border-t border-slate-100">
                        <button @click="payExpense" :disabled="paying"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition-colors disabled:opacity-50">
                            {{ paying ? 'Recording...' : 'Confirm Payment' }}
                        </button>
                        <button @click="showPayModal = false"
                            class="text-sm text-slate-500 hover:text-slate-700">Cancel</button>
                    </div>
                </div>
            </div>

            <!-- Reject Modal -->
            <div v-if="showRejectModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
                @click.self="showRejectModal = false">
                <div class="bg-white rounded-xl p-6 w-full max-w-md shadow-xl">
                    <h3 class="text-lg font-semibold text-slate-800 mb-4">Reject Expense</h3>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Reason <span class="text-red-500">*</span></label>
                        <textarea v-model="rejectReason" rows="3" required
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm"></textarea>
                    </div>
                    <div class="flex items-center gap-3 mt-6 pt-4 border-t border-slate-100">
                        <button @click="rejectExpense"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 transition-colors">
                            Reject
                        </button>
                        <button @click="showRejectModal = false"
                            class="text-sm text-slate-500 hover:text-slate-700">Cancel</button>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>
