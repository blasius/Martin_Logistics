<template>
    <div class="h-screen flex flex-col bg-slate-50 overflow-hidden relative">
        <Transition name="fade">
            <div v-if="showCreateModal" class="fixed inset-0 z-[160] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
                <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full border border-slate-200 overflow-hidden">
                    <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                        <h3 class="font-black text-slate-800 uppercase text-sm tracking-widest">Record Payment</h3>
                        <button @click="showCreateModal = false" class="text-slate-400 hover:text-slate-600"><X class="w-5 h-5" /></button>
                    </div>
                    <form @submit.prevent="recordPayment" class="p-6 space-y-4">
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1.5">Invoice</label>
                            <select v-model="form.invoice_id" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500 outline-none">
                                <option value="">Select Invoice</option>
                                <option v-for="inv in invoices" :key="inv.id" :value="inv.id">
                                    {{ inv.reference }} — {{ inv.client?.name }} — {{ Number(inv.total).toLocaleString() }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1.5">Amount</label>
                            <input v-model="form.amount" type="number" step="0.01" min="0.01" required placeholder="0.00" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm font-bold text-slate-700 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 outline-none" />
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1.5">Payment Method</label>
                            <select v-model="form.method" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500 outline-none">
                                <option value="">Select</option>
                                <option value="cash">Cash</option>
                                <option value="mobile">Mobile Money</option>
                                <option value="bank">Bank Transfer</option>
                                <option value="cheque">Cheque</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1.5">Transaction Reference</label>
                            <input v-model="form.tx_reference" placeholder="Optional" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm font-bold text-slate-700 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 outline-none" />
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1.5">Payment Date</label>
                            <input v-model="form.paid_at" type="date" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500 outline-none" />
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1.5">Notes</label>
                            <textarea v-model="form.notes" rows="2" placeholder="Optional" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm font-bold text-slate-700 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 outline-none resize-none"></textarea>
                        </div>
                        <div class="flex gap-3 pt-2">
                            <button type="button" @click="showCreateModal = false" class="flex-1 px-4 py-3 bg-slate-100 text-slate-600 text-xs font-black rounded-xl uppercase hover:bg-slate-200 transition-all">Cancel</button>
                            <button type="submit" :disabled="saving" class="flex-1 px-4 py-3 bg-indigo-600 text-white text-xs font-black rounded-xl shadow-lg uppercase transition-all hover:bg-indigo-700 disabled:opacity-50">
                                {{ saving ? 'Saving...' : 'Record Payment' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Transition>

        <Transition name="fade">
            <div v-if="showViewModal" class="fixed inset-0 z-[160] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
                <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full border border-slate-200 overflow-hidden">
                    <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                        <h3 class="font-black text-slate-800 uppercase text-sm tracking-widest">Payment Details</h3>
                        <button @click="showViewModal = false" class="text-slate-400 hover:text-slate-600"><X class="w-5 h-5" /></button>
                    </div>
                    <div class="p-6 space-y-4 text-xs">
                        <div class="flex justify-between border-b border-slate-50 pb-2">
                            <span class="text-slate-400 font-bold uppercase tracking-tighter">Invoice</span>
                            <span class="font-black text-slate-800">{{ selectedPayment?.invoice?.reference || '-' }}</span>
                        </div>
                        <div class="flex justify-between border-b border-slate-50 pb-2">
                            <span class="text-slate-400 font-bold uppercase tracking-tighter">Client</span>
                            <span class="font-black text-slate-800">{{ selectedPayment?.invoice?.client?.name || '-' }}</span>
                        </div>
                        <div class="flex justify-between border-b border-slate-50 pb-2">
                            <span class="text-slate-400 font-bold uppercase tracking-tighter">Amount</span>
                            <span class="font-black text-slate-800">{{ fmtAmount(selectedPayment?.amount) }}</span>
                        </div>
                        <div class="flex justify-between border-b border-slate-50 pb-2">
                            <span class="text-slate-400 font-bold uppercase tracking-tighter">Method</span>
                            <span class="font-black text-slate-800">{{ selectedPayment?.method || '-' }}</span>
                        </div>
                        <div class="flex justify-between border-b border-slate-50 pb-2">
                            <span class="text-slate-400 font-bold uppercase tracking-tighter">Reference</span>
                            <span class="font-black text-slate-800">{{ selectedPayment?.tx_reference || '-' }}</span>
                        </div>
                        <div class="flex justify-between border-b border-slate-50 pb-2">
                            <span class="text-slate-400 font-bold uppercase tracking-tighter">Paid At</span>
                            <span class="font-black text-slate-800">{{ fmtDateTime(selectedPayment?.paid_at) }}</span>
                        </div>
                        <div class="flex justify-between border-b border-slate-50 pb-2">
                            <span class="text-slate-400 font-bold uppercase tracking-tighter">Recorded By</span>
                            <span class="font-black text-slate-800">{{ selectedPayment?.cashier?.name || '-' }}</span>
                        </div>
                        <div v-if="selectedPayment?.notes" class="flex justify-between border-b border-slate-50 pb-2">
                            <span class="text-slate-400 font-bold uppercase tracking-tighter">Notes</span>
                            <span class="font-black text-slate-800 text-right">{{ selectedPayment.notes }}</span>
                        </div>
                    </div>
                    <div class="p-4 bg-slate-50">
                        <button @click="showViewModal = false" class="w-full bg-slate-100 text-slate-600 py-3 rounded-xl font-black text-xs uppercase hover:bg-slate-200 transition-all">Close</button>
                    </div>
                </div>
            </div>
        </Transition>

        <Transition name="slide-fade">
            <div v-if="notification.show" class="fixed bottom-8 right-8 z-[130] flex items-center gap-3 px-6 py-3 bg-slate-900 text-white rounded-xl shadow-2xl border border-slate-700 no-print">
                <Check class="w-4 h-4 text-emerald-400" />
                <span class="text-sm font-bold">{{ notification.message }}</span>
            </div>
        </Transition>

        <header class="bg-white border-b border-slate-200 px-8 py-4 flex flex-wrap items-center justify-between gap-4 shadow-sm z-10 no-print">
            <div class="flex items-center gap-4">
                <div class="p-2 bg-indigo-600 rounded-lg"><Banknote class="w-6 h-6 text-white" /></div>
                <div>
                    <h1 class="text-xl font-black text-slate-800 uppercase leading-none">Payments</h1>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1.5">Recorded incoming payments</p>
                </div>
            </div>
            <button @click="openCreateModal" class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-xs font-black rounded-lg hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-200">
                <Plus class="w-4 h-4" /> Record Payment
            </button>
        </header>

        <div class="px-8 py-3 bg-white border-b border-slate-200 z-10 no-print">
            <div class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="block text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">Invoice Ref</label>
                    <input v-model="filters.invoice_id" type="text" placeholder="e.g. INV-001" class="w-40 pl-3 pr-3 py-2 bg-slate-100 border-none rounded-lg text-xs font-bold text-slate-700 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 outline-none" />
                </div>
                <div>
                    <label class="block text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">Client ID</label>
                    <input v-model="filters.client_id" type="text" placeholder="Client #" class="w-32 pl-3 pr-3 py-2 bg-slate-100 border-none rounded-lg text-xs font-bold text-slate-700 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 outline-none" />
                </div>
                <div>
                    <label class="block text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">Method</label>
                    <select v-model="filters.method" class="w-40 pl-3 pr-3 py-2 bg-slate-100 border-none rounded-lg text-xs font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500 outline-none">
                        <option value="">All Methods</option>
                        <option value="cash">Cash</option>
                        <option value="mobile">Mobile</option>
                        <option value="bank">Bank Transfer</option>
                        <option value="cheque">Cheque</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">From</label>
                    <input v-model="filters.date_from" type="date" class="pl-3 pr-3 py-2 bg-slate-100 border-none rounded-lg text-xs font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500 outline-none" />
                </div>
                <div>
                    <label class="block text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">To</label>
                    <input v-model="filters.date_to" type="date" class="pl-3 pr-3 py-2 bg-slate-100 border-none rounded-lg text-xs font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500 outline-none" />
                </div>
                <button @click="loadPayments" class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-xs font-black rounded-lg hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-200">
                    <Filter class="w-3.5 h-3.5" /> Filter
                </button>
                <button @click="resetFilters" class="px-4 py-2 bg-slate-100 text-slate-600 text-xs font-black rounded-lg hover:bg-slate-200 transition-all">Reset</button>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto px-8 py-4 custom-scrollbar">
            <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
                <table class="w-full">
                    <thead class="bg-slate-800 text-white">
                        <tr>
                            <th class="px-4 py-3 text-[10px] font-black uppercase tracking-widest text-left">ID</th>
                            <th class="px-4 py-3 text-[10px] font-black uppercase tracking-widest text-left">Invoice</th>
                            <th class="px-4 py-3 text-[10px] font-black uppercase tracking-widest text-left">Client</th>
                            <th class="px-4 py-3 text-[10px] font-black uppercase tracking-widest text-left">Amount</th>
                            <th class="px-4 py-3 text-[10px] font-black uppercase tracking-widest text-left">Method</th>
                            <th class="px-4 py-3 text-[10px] font-black uppercase tracking-widest text-left">Reference</th>
                            <th class="px-4 py-3 text-[10px] font-black uppercase tracking-widest text-left">Paid At</th>
                            <th class="px-4 py-3 text-[10px] font-black uppercase tracking-widest text-left">Recorded By</th>
                            <th class="px-4 py-3 text-[10px] font-black uppercase tracking-widest text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="p in payments.data" :key="p.id" class="border-t border-slate-100 hover:bg-indigo-50/60 transition-colors">
                            <td class="px-4 py-3 font-black text-slate-900 uppercase tracking-tighter whitespace-nowrap">{{ p.id }}</td>
                            <td class="px-4 py-3 text-xs font-bold text-slate-800 whitespace-nowrap">{{ p.invoice?.reference || '-' }}</td>
                            <td class="px-4 py-3 text-xs text-slate-600 whitespace-nowrap">{{ p.invoice?.client?.name || (p.requisition_id ? 'Requisition' : '-') }}</td>
                            <td class="px-4 py-3 text-xs font-black text-slate-900 whitespace-nowrap">{{ fmtAmount(p.amount) }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="text-[9px] font-black px-2 py-1 rounded-full uppercase" :class="methodBadge(p.method)">{{ p.method || '-' }}</span>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-500 whitespace-nowrap">{{ p.tx_reference || '-' }}</td>
                            <td class="px-4 py-3 text-xs text-slate-500 whitespace-nowrap">{{ fmtDate(p.paid_at) }}</td>
                            <td class="px-4 py-3 text-xs text-slate-600 whitespace-nowrap">{{ p.cashier?.name || '-' }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <button @click="viewPayment(p)" class="text-[10px] font-black text-indigo-600 bg-indigo-50 hover:bg-indigo-100 px-3 py-1.5 rounded-lg uppercase transition-colors">View</button>
                            </td>
                        </tr>
                        <tr v-if="!payments.data?.length">
                            <td colspan="9" class="px-4 py-12 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <Receipt class="w-8 h-8 text-slate-300" />
                                    <p class="text-xs font-black text-slate-400 uppercase tracking-widest">No payments found</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-if="payments.last_page > 1" class="flex items-center justify-center gap-3 mt-4 no-print">
                <button @click="loadPayments(payments.current_page - 1)" :disabled="!payments.prev_page_url" class="px-3 py-1.5 bg-white border border-slate-200 text-xs font-black rounded-lg disabled:opacity-40 disabled:cursor-not-allowed hover:border-indigo-300 transition-colors">Prev</button>
                <span class="text-xs font-bold text-slate-500">Page {{ payments.current_page }} of {{ payments.last_page }}</span>
                <button @click="loadPayments(payments.current_page + 1)" :disabled="!payments.next_page_url" class="px-3 py-1.5 bg-white border border-slate-200 text-xs font-black rounded-lg disabled:opacity-40 disabled:cursor-not-allowed hover:border-indigo-300 transition-colors">Next</button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { Banknote, Plus, X, Check, Receipt, Filter } from 'lucide-vue-next';
import dayjs from 'dayjs';
import { paymentsApi } from "../../api/payments";
import { invoicesApi } from "../../api/invoices";

const payments = ref({});
const invoices = ref([]);
const filters = reactive({ invoice_id: "", client_id: "", method: "", date_from: "", date_to: "" });
const showCreateModal = ref(false);
const showViewModal = ref(false);
const selectedPayment = ref(null);
const saving = ref(false);
const notification = reactive({ show: false, message: '' });
const form = reactive({ invoice_id: "", amount: "", method: "", tx_reference: "", paid_at: "", notes: "" });

const loadPayments = async (page = 1) => {
    const params = { page, ...filters };
    Object.keys(params).forEach((k) => { if (!params[k]) delete params[k]; });
    const { data } = await paymentsApi.index(params);
    payments.value = data;
};

const resetFilters = () => {
    Object.keys(filters).forEach((k) => filters[k] = "");
    loadPayments();
};

const openCreateModal = async () => {
    Object.keys(form).forEach((k) => form[k] = "");
    const { data } = await invoicesApi.getAll({ per_page: 200, status: "sent,overdue" });
    invoices.value = data.data || data;
    showCreateModal.value = true;
};

const recordPayment = async () => {
    saving.value = true;
    try {
        await paymentsApi.store({ ...form });
        showCreateModal.value = false;
        loadPayments();
        triggerNotification("Payment recorded");
    } catch (e) {
        alert(e.response?.data?.message || "Failed to record payment");
    } finally {
        saving.value = false;
    }
};

const viewPayment = async (p) => {
    const { data } = await paymentsApi.show(p.id);
    selectedPayment.value = data;
    showViewModal.value = true;
};

const triggerNotification = (msg) => {
    notification.message = msg;
    notification.show = true;
    setTimeout(() => notification.show = false, 3000);
};

const fmtAmount = (a) => Number(a || 0).toLocaleString();
const fmtDate = (d) => d ? dayjs(d).format('YYYY-MM-DD') : '-';
const fmtDateTime = (d) => d ? dayjs(d).format('YYYY-MM-DD HH:mm') : '-';

const methodBadge = (m) => ({
    cash: 'bg-emerald-100 text-emerald-700',
    mobile: 'bg-indigo-100 text-indigo-700',
    bank: 'bg-sky-100 text-sky-700',
    cheque: 'bg-amber-100 text-amber-700',
}[m] || 'bg-slate-100 text-slate-600');

onMounted(loadPayments);
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.slide-fade-enter-active, .slide-fade-leave-active { transition: all 0.3s; }
.slide-fade-enter-from, .slide-fade-leave-to { transform: translateY(20px); opacity: 0; }
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
</style>
