<template>
    <div class="p-6 max-w-7xl mx-auto">
        <header class="mb-6">
            <h1 class="text-2xl font-black text-slate-800 uppercase tracking-tight">Wallets</h1>
            <p class="text-sm text-slate-500">User wallet balances across currencies</p>
        </header>

        <div class="flex gap-4 mb-6">
            <input v-model="filters.user_id" type="number" placeholder="Filter by User ID"
                   class="border border-slate-200 rounded-lg px-4 py-2 text-sm w-48 focus:ring-2 focus:ring-blue-500 outline-none" />
            <select v-model="filters.currency_id" class="border border-slate-200 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                <option value="">All Currencies</option>
                <option v-for="c in currencies" :key="c.id" :value="c.id">{{ c.code }}</option>
            </select>
            <input v-model.number="filters.balance_min" type="number" placeholder="Max negative balance"
                   class="border border-slate-200 rounded-lg px-4 py-2 text-sm w-48 focus:ring-2 focus:ring-blue-500 outline-none" />
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <table class="w-full">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="text-left px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">User</th>
                        <th class="text-left px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Currency</th>
                        <th class="text-right px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Balance</th>
                        <th class="text-center px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="text-right px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="w in wallets" :key="w.id" class="border-b border-slate-100 hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <router-link :to="`/wallets/${w.id}`" class="text-sm font-medium text-blue-600 hover:underline">
                                {{ w.user?.name || 'User #' + w.user_id }}
                            </router-link>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ w.currency?.code }}</td>
                        <td class="px-4 py-3 text-right text-sm font-bold"
                            :class="parseFloat(w.current_balance) >= 0 ? 'text-emerald-600' : 'text-red-600'">
                            {{ formatCurrency(w.current_balance, w.currency) }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span v-if="parseFloat(w.current_balance) > 0" class="px-2 py-0.5 text-xs font-medium bg-emerald-100 text-emerald-700 rounded-full">Positive</span>
                            <span v-else-if="parseFloat(w.current_balance) < 0" class="px-2 py-0.5 text-xs font-medium bg-red-100 text-red-700 rounded-full">Negative</span>
                            <span v-else class="px-2 py-0.5 text-xs font-medium bg-slate-100 text-slate-500 rounded-full">Zero</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <router-link :to="`/wallets/${w.id}`" class="text-xs font-medium text-blue-600 hover:underline">View</router-link>
                        </td>
                    </tr>
                    <tr v-if="!wallets.length">
                        <td colspan="5" class="text-center py-8 text-sm text-slate-400">No wallets found</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="meta" class="flex items-center justify-between mt-4 text-sm text-slate-500">
            <span>Page {{ meta.current_page }} of {{ meta.last_page }} ({{ meta.total }} wallets)</span>
            <div class="flex gap-2">
                <button @click="loadPage(meta.current_page - 1)" :disabled="!meta.prev_page_url"
                    class="px-3 py-1 bg-white border border-slate-200 rounded-lg text-xs disabled:opacity-30 hover:bg-slate-50">Prev</button>
                <button @click="loadPage(meta.current_page + 1)" :disabled="!meta.next_page_url"
                    class="px-3 py-1 bg-white border border-slate-200 rounded-lg text-xs disabled:opacity-30 hover:bg-slate-50">Next</button>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mt-8">
            <h2 class="text-lg font-bold text-slate-800 mb-4">Quick Actions</h2>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <h3 class="text-sm font-bold text-slate-700 mb-3">Create Transaction</h3>
                <form @submit.prevent="createTransaction" class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <input v-model.number="txForm.user_id" type="number" min="1" required placeholder="User ID"
                           class="border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none" />
                    <select v-model="txForm.type" required class="border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                        <option value="credit">Credit (+)</option>
                        <option value="debit">Debit (-)</option>
                    </select>
                    <input v-model.number="txForm.amount" type="number" step="0.01" min="0.01" required placeholder="Amount"
                           class="border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none" />
                    <input v-model="txForm.category" required placeholder="Category (e.g. fine, allowance)"
                           class="border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none" />
                    <input v-model="txForm.description" placeholder="Description (optional)"
                           class="border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none col-span-2" />
                    <button type="submit" :disabled="saving"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 disabled:opacity-50">
                        {{ saving ? 'Processing...' : 'Submit' }}
                    </button>
                </form>
                <p v-if="txSuccess" class="mt-2 text-sm text-emerald-600">{{ txSuccess }}</p>
                <p v-if="txError" class="mt-2 text-sm text-red-600">{{ txError }}</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import { walletsApi } from '../../api/wallets';
import { currenciesApi } from '../../api/currencies';

const wallets = ref([]);
const currencies = ref([]);
const users = ref([]);
const meta = ref(null);
const saving = ref(false);
const txSuccess = ref('');
const txError = ref('');

const filters = ref({ user_id: '', currency_id: '', balance_min: '' });
const txForm = ref({ user_id: '', type: 'credit', amount: 0, category: '', description: '' });

function formatCurrency(amount, currency) {
    const num = parseFloat(amount || 0);
    const sym = currency?.symbol || '';
    return `${sym} ${num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

async function loadWallets() {
    const params = {};
    if (filters.value.user_id) params.user_id = filters.value.user_id;
    if (filters.value.currency_id) params.currency_id = filters.value.currency_id;
    if (filters.value.balance_min) params.balance_min = filters.value.balance_min;
    try {
        const res = await walletsApi.index(params);
        wallets.value = res.data.data;
        meta.value = {
            current_page: res.data.current_page,
            last_page: res.data.last_page,
            total: res.data.total,
            prev_page_url: res.data.prev_page_url,
            next_page_url: res.data.next_page_url,
        };
    } catch (e) { console.error(e); }
}

async function loadPage(page) {
    if (!page) return;
    const params = { page };
    if (filters.value.user_id) params.user_id = filters.value.user_id;
    if (filters.value.currency_id) params.currency_id = filters.value.currency_id;
    if (filters.value.balance_min) params.balance_min = filters.value.balance_min;
    const res = await walletsApi.index(params);
    wallets.value = res.data.data;
    meta.value.current_page = res.data.current_page;
    meta.value.last_page = res.data.last_page;
    meta.value.total = res.data.total;
    meta.value.prev_page_url = res.data.prev_page_url;
    meta.value.next_page_url = res.data.next_page_url;
}

async function createTransaction() {
    saving.value = true;
    txSuccess.value = '';
    txError.value = '';
    try {
        await walletsApi.storeTransaction(txForm.value);
        txSuccess.value = 'Transaction created successfully';
        txForm.value = { user_id: '', type: 'credit', amount: 0, category: '', description: '' };
        await loadWallets();
    } catch (e) {
        txError.value = e.response?.data?.message || 'Failed to create transaction';
    } finally { saving.value = false; }
}

watch(filters, loadWallets, { deep: true });

onMounted(async () => {
    await loadWallets();
    try {
            const curRes = await currenciesApi.getAll();
            currencies.value = curRes.data.data || curRes.data;
        } catch (e) { console.error(e); }
});
</script>
