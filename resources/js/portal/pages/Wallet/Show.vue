<template>
    <div class="p-6 max-w-7xl mx-auto">
        <div v-if="loading" class="text-center py-12 text-slate-400">Loading wallet...</div>
        <template v-else-if="wallet">
            <header class="mb-6 flex items-center justify-between">
                <div>
                    <router-link to="/wallets" class="text-sm text-blue-600 hover:underline mb-1 block">&larr; Back to Wallets</router-link>
                    <h1 class="text-2xl font-black text-slate-800 uppercase tracking-tight">{{ wallet.user?.name || 'User #' + wallet.user_id }}</h1>
                    <p class="text-sm text-slate-500">{{ wallet.user?.email }}</p>
                </div>
                <div class="text-right">
                    <div class="text-3xl font-black" :class="balance >= 0 ? 'text-emerald-600' : 'text-red-600'">
                        {{ formatCurrency(wallet.current_balance, wallet.currency) }}
                    </div>
                    <span :class="balance > 0 ? 'text-emerald-600' : balance < 0 ? 'text-red-600' : 'text-slate-400'" class="text-sm font-medium">
                        {{ balance > 0 ? 'Positive Balance' : balance < 0 ? 'Negative Balance' : 'Zero Balance' }}
                    </span>
                </div>
            </header>

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-8">
                <div class="lg:col-span-3">
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-bold text-slate-800">Transaction History</h2>
                            <div class="flex gap-2">
                                <select v-model="txFilters.type" class="border border-slate-200 rounded-lg px-3 py-1.5 text-xs focus:ring-2 focus:ring-blue-500 outline-none">
                                    <option value="">All Types</option>
                                    <option value="credit">Credits</option>
                                    <option value="debit">Debits</option>
                                </select>
                                <select v-model="txFilters.category" class="border border-slate-200 rounded-lg px-3 py-1.5 text-xs focus:ring-2 focus:ring-blue-500 outline-none">
                                    <option value="">All Categories</option>
                                    <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
                                </select>
                            </div>
                        </div>
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-slate-100">
                                    <th class="text-left pb-2 text-xs font-bold text-slate-500 uppercase tracking-wider">Date</th>
                                    <th class="text-left pb-2 text-xs font-bold text-slate-500 uppercase tracking-wider">Type</th>
                                    <th class="text-left pb-2 text-xs font-bold text-slate-500 uppercase tracking-wider">Category</th>
                                    <th class="text-right pb-2 text-xs font-bold text-slate-500 uppercase tracking-wider">Amount</th>
                                    <th class="text-right pb-2 text-xs font-bold text-slate-500 uppercase tracking-wider">Balance</th>
                                    <th class="text-left pb-2 text-xs font-bold text-slate-500 uppercase tracking-wider">Description</th>
                                    <th class="text-center pb-2 text-xs font-bold text-slate-500 uppercase tracking-wider">Acknowledged</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="tx in transactions" :key="tx.id" class="border-b border-slate-50 hover:bg-slate-50">
                                    <td class="py-2.5 text-xs text-slate-500">{{ formatDate(tx.created_at) }}</td>
                                    <td class="py-2.5">
                                        <span :class="tx.type === 'credit' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'"
                                            class="px-2 py-0.5 text-xs font-medium rounded-full">
                                            {{ tx.type === 'credit' ? 'CR' : 'DR' }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 text-sm text-slate-700">{{ tx.category }}</td>
                                    <td class="py-2.5 text-right text-sm font-bold"
                                        :class="tx.type === 'credit' ? 'text-emerald-600' : 'text-red-600'">
                                        {{ tx.type === 'credit' ? '+' : '-' }}{{ formatCurrency(tx.amount, wallet.currency) }}
                                    </td>
                                    <td class="py-2.5 text-right text-sm font-medium text-slate-600">{{ formatCurrency(tx.balance_after, wallet.currency) }}</td>
                                    <td class="py-2.5 text-sm text-slate-500 max-w-[200px] truncate" :title="tx.description">{{ tx.description || '-' }}</td>
                                    <td class="py-2.5 text-center">
                                        <span v-if="tx.user_acknowledged_at" class="text-xs text-emerald-600">Yes</span>
                                        <span v-else class="text-xs text-slate-400">No</span>
                                    </td>
                                </tr>
                                <tr v-if="!transactions.length">
                                    <td colspan="7" class="text-center py-6 text-sm text-slate-400">No transactions yet</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
                        <h3 class="text-sm font-bold text-slate-700 mb-3">Manual Transaction</h3>
                        <form @submit.prevent="addTransaction" class="space-y-3">
                            <select v-model="manualTx.type" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                                <option value="credit">Credit (+)</option>
                                <option value="debit">Debit (-)</option>
                            </select>
                            <input v-model.number="manualTx.amount" type="number" step="0.01" min="0.01" required placeholder="Amount"
                                   class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none" />
                            <input v-model="manualTx.category" required placeholder="Category"
                                   class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none" />
                            <input v-model="manualTx.description" placeholder="Description"
                                   class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none" />
                            <button type="submit" :disabled="savingTx"
                                class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 disabled:opacity-50">
                                {{ savingTx ? 'Saving...' : 'Submit' }}
                            </button>
                            <p v-if="manualSuccess" class="text-xs text-emerald-600">{{ manualSuccess }}</p>
                            <p v-if="manualError" class="text-xs text-red-600">{{ manualError }}</p>
                        </form>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
                        <h3 class="text-sm font-bold text-slate-700 mb-3">Settle Wallet</h3>
                        <p class="text-xs text-slate-500 mb-2">Balance: {{ formatCurrency(wallet.current_balance, wallet.currency) }}</p>
                        <form @submit.prevent="settleWallet" class="space-y-3">
                            <select v-model="settleForm.method" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                                <option value="cash">Cash</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="mobile_money">Mobile Money</option>
                                <option value="salary_deduction">Salary Deduction</option>
                            </select>
                            <input v-model="settleForm.notes" placeholder="Notes"
                                   class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none" />
                            <button type="submit" :disabled="savingSettle || parseFloat(wallet.current_balance) === 0"
                                class="w-full px-4 py-2 bg-amber-600 text-white rounded-lg text-sm font-medium hover:bg-amber-700 disabled:opacity-50">
                                {{ savingSettle ? 'Processing...' : 'Settle to Zero' }}
                            </button>
                            <p v-if="settleSuccess" class="text-xs text-emerald-600">{{ settleSuccess }}</p>
                            <p v-if="settleError" class="text-xs text-red-600">{{ settleError }}</p>
                        </form>
                    </div>

                    <div v-if="settlements.length" class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
                        <h3 class="text-sm font-bold text-slate-700 mb-3">Settlement History</h3>
                        <div v-for="s in settlements" :key="s.id" class="text-xs text-slate-600 border-b border-slate-100 py-2 last:border-0">
                            <div class="flex justify-between">
                                <span class="font-medium">{{ s.method }}</span>
                                <span class="font-bold">{{ formatCurrency(s.amount_settled, wallet.currency) }}</span>
                            </div>
                            <div class="text-slate-400">{{ formatDate(s.settled_at) }} {{ s.settler?.name ? 'by ' + s.settler.name : '' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
        <div v-else class="text-center py-12 text-slate-400">Wallet not found</div>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import { walletsApi } from '../../api/wallets';

const route = useRoute();
const wallet = ref(null);
const transactions = ref([]);
const settlements = ref([]);
const categories = ref([]);
const loading = ref(true);
const savingTx = ref(false);
const savingSettle = ref(false);
const manualSuccess = ref('');
const manualError = ref('');
const settleSuccess = ref('');
const settleError = ref('');

const txFilters = ref({ type: '', category: '' });
const manualTx = ref({ type: 'credit', amount: 0, category: '', description: '' });
const settleForm = ref({ method: 'cash', notes: '' });

const balance = ref(0);

function formatCurrency(amount, currency) {
    const num = parseFloat(amount || 0);
    const sym = currency?.symbol || '';
    return `${sym} ${num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

async function loadWallet() {
    loading.value = true;
    try {
        const res = await walletsApi.show(route.params.id);
        wallet.value = res.data;
        balance.value = parseFloat(res.data.current_balance || 0);
    } catch (e) {
        wallet.value = null;
    } finally { loading.value = false; }
}

async function loadTransactions() {
    const params = {};
    if (txFilters.value.type) params.type = txFilters.value.type;
    if (txFilters.value.category) params.category = txFilters.value.category;
    try {
        const res = await walletsApi.transactions(route.params.id, params);
        transactions.value = res.data.data || res.data;
    } catch (e) { console.error(e); }
}

async function loadSettlements() {
    try {
        const res = await walletsApi.settlements(route.params.id);
        settlements.value = res.data;
    } catch (e) { console.error(e); }
}

async function addTransaction() {
    savingTx.value = true;
    manualSuccess.value = '';
    manualError.value = '';
    try {
        await walletsApi.storeTransaction({
            user_id: wallet.value.user_id,
            type: manualTx.value.type,
            amount: manualTx.value.amount,
            category: manualTx.value.category,
            description: manualTx.value.description,
            currency_id: wallet.value.currency_id,
        });
        manualSuccess.value = 'Transaction recorded';
        manualTx.value = { type: 'credit', amount: 0, category: '', description: '' };
        await loadWallet();
        await loadTransactions();
    } catch (e) {
        manualError.value = e.response?.data?.message || 'Failed';
    } finally { savingTx.value = false; }
}

async function settleWallet() {
    savingSettle.value = true;
    settleSuccess.value = '';
    settleError.value = '';
    try {
        await walletsApi.settle(route.params.id, settleForm.value);
        settleSuccess.value = 'Wallet settled';
        await loadWallet();
        await loadSettlements();
    } catch (e) {
        settleError.value = e.response?.data?.message || 'Failed';
    } finally { savingSettle.value = false; }
}

watch(txFilters, loadTransactions, { deep: true });

onMounted(async () => {
    await loadWallet();
    await loadTransactions();
    await loadSettlements();
});
</script>
