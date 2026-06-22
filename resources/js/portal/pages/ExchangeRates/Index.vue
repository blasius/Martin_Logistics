<template>
    <div class="p-6 max-w-5xl mx-auto">
        <header class="flex justify-between items-end mb-8">
            <div>
                <h1 class="text-3xl font-black text-slate-800 tracking-tight uppercase italic leading-none">Exchange Rates</h1>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-1">Currency Conversion Rates</p>
            </div>
            <button @click="openCreateModal"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-2xl text-xs font-black shadow-lg shadow-blue-200 active:scale-95 transition-all uppercase">
                + Add Rate
            </button>
        </header>

        <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-6 border-b border-slate-100 bg-slate-50/50">
                <h2 class="text-[10px] font-black text-slate-500 uppercase tracking-widest">All Exchange Rates</h2>
            </div>

            <div v-if="loading" class="flex justify-center p-12">
                <div class="w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
            </div>

            <div v-else-if="rates.length === 0" class="text-center p-12 text-xs text-slate-400 font-bold uppercase">
                No exchange rates defined yet.
            </div>

            <table v-else class="w-full">
                <thead>
                    <tr class="text-[9px] font-black text-slate-400 uppercase tracking-wider border-b border-slate-100">
                        <th class="text-left p-5 pl-8">From</th>
                        <th class="text-left p-5">To</th>
                        <th class="text-right p-5">Rate</th>
                        <th class="text-left p-5">Valid From</th>
                        <th class="text-left p-5">Valid To</th>
                        <th class="text-left p-5">Created By</th>
                        <th class="text-right p-5 pr-8">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="rate in rates" :key="rate.id"
                        class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors">
                        <td class="p-5 pl-8">
                            <span class="font-black text-sm text-slate-800">{{ rate.base_currency?.code || rate.base_currency }}</span>
                        </td>
                        <td class="p-5">
                            <span class="font-black text-sm text-slate-800">{{ rate.target_currency?.code || rate.target_currency }}</span>
                        </td>
                        <td class="p-5 text-right font-bold text-sm text-slate-700 tabular-nums">
                            {{ Number(rate.rate).toLocaleString(undefined, { minimumFractionDigits: 4, maximumFractionDigits: 6 }) }}
                        </td>
                        <td class="p-5">
                            <span class="text-xs font-semibold text-slate-600">{{ formatDate(rate.valid_from) }}</span>
                        </td>
                        <td class="p-5">
                            <span v-if="rate.valid_to" class="text-xs font-semibold text-slate-600">{{ formatDate(rate.valid_to) }}</span>
                            <span v-else class="text-[10px] font-bold text-green-600 uppercase">Active</span>
                        </td>
                        <td class="p-5">
                            <span class="text-xs font-semibold text-slate-600">{{ rate.creator?.name || '—' }}</span>
                        </td>
                        <td class="p-5 pr-8 text-right">
                            <button @click="openEditModal(rate)"
                                    class="text-blue-500 hover:text-blue-700 hover:bg-blue-50 p-2 rounded-lg transition-colors active:scale-95"
                                    title="Edit">
                                <Pencil class="w-4 h-4" />
                            </button>
                            <button @click="confirmDelete(rate)"
                                    class="text-red-400 hover:text-red-600 hover:bg-red-50 p-2 rounded-lg transition-colors active:scale-95 ml-1"
                                    title="Delete">
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Teleport to="body">
            <div v-if="showModal"
                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
                 @click.self="closeModal">
                <div class="bg-white rounded-[2.5rem] shadow-2xl border border-slate-200 w-full max-w-lg mx-4 overflow-hidden">
                    <div class="p-6 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                        <h2 class="text-[10px] font-black text-slate-500 uppercase tracking-widest">
                            {{ editingId ? 'Edit Exchange Rate' : 'New Exchange Rate' }}
                        </h2>
                        <button @click="closeModal" class="text-slate-400 hover:text-slate-600 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <div class="p-6 space-y-5">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase ml-1 block mb-2 tracking-wide">From Currency</label>
                                <select v-model="form.base_currency_id"
                                        :disabled="!!editingId"
                                        class="border-slate-200 px-4 py-3.5 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-500 outline-none bg-slate-50 w-full transition-all">
                                    <option value="" disabled>Select</option>
                                    <option v-for="c in currencies" :key="c.id" :value="c.id">{{ c.code }} — {{ c.name }}</option>
                                </select>
                                <p v-if="errors.base_currency_id" class="text-[9px] font-bold text-red-500 mt-1.5 ml-1">{{ errors.base_currency_id }}</p>
                            </div>
                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase ml-1 block mb-2 tracking-wide">To Currency</label>
                                <select v-model="form.target_currency_id"
                                        :disabled="!!editingId"
                                        class="border-slate-200 px-4 py-3.5 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-500 outline-none bg-slate-50 w-full transition-all">
                                    <option value="" disabled>Select</option>
                                    <option v-for="c in currencies" :key="c.id" :value="c.id">{{ c.code }} — {{ c.name }}</option>
                                </select>
                                <p v-if="errors.target_currency_id" class="text-[9px] font-bold text-red-500 mt-1.5 ml-1">{{ errors.target_currency_id }}</p>
                            </div>
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1 block mb-2 tracking-wide">Rate</label>
                            <input v-model="form.rate" type="number" step="0.000001" min="0" placeholder="1.000000"
                                   class="border-slate-200 px-4 py-3.5 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-500 outline-none bg-slate-50 w-full transition-all" />
                            <p v-if="errors.rate" class="text-[9px] font-bold text-red-500 mt-1.5 ml-1">{{ errors.rate }}</p>
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1 block mb-2 tracking-wide">Valid From</label>
                            <input v-model="form.valid_from" type="datetime-local"
                                   class="border-slate-200 px-4 py-3.5 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-500 outline-none bg-slate-50 w-full transition-all" />
                            <p v-if="errors.valid_from" class="text-[9px] font-bold text-red-500 mt-1.5 ml-1">{{ errors.valid_from }}</p>
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1 block mb-2 tracking-wide">
                                Valid To
                                <span class="text-[9px] font-bold text-slate-400 normal-case">(leave empty for current rate)</span>
                            </label>
                            <input v-model="form.valid_to" type="datetime-local"
                                   class="border-slate-200 px-4 py-3.5 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-500 outline-none bg-slate-50 w-full transition-all" />
                            <p v-if="errors.valid_to" class="text-[9px] font-bold text-red-500 mt-1.5 ml-1">{{ errors.valid_to }}</p>
                        </div>
                    </div>

                    <div class="p-6 border-t border-slate-100 bg-slate-50">
                        <button @click="saveRate"
                                :disabled="saving"
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white py-4 rounded-2xl text-xs font-black transition-all shadow-lg shadow-blue-200 active:scale-95 disabled:opacity-40 disabled:shadow-none uppercase tracking-widest">
                            {{ saving ? 'Saving...' : (editingId ? 'Update Rate' : 'Save Rate') }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <Teleport to="body">
            <div v-if="confirmDialog.show"
                 class="fixed inset-0 z-[60] flex items-center justify-center bg-black/40 backdrop-blur-sm">
                <div class="bg-white rounded-[2.5rem] shadow-2xl border border-slate-200 w-full max-w-sm mx-4 overflow-hidden">
                    <div class="p-6 text-center">
                        <div class="mx-auto w-12 h-12 rounded-full bg-red-100 flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                            </svg>
                        </div>
                        <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider mb-2">Delete Rate</h3>
                        <p class="text-xs font-bold text-slate-500">
                            Delete rate {{ confirmDialog.rate?.base_currency?.code || '' }} → {{ confirmDialog.rate?.target_currency?.code || '' }}?
                            This cannot be undone.
                        </p>
                    </div>
                    <div class="px-6 pb-6 flex gap-3">
                        <button @click="confirmDialog.show = false"
                                class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-700 py-3.5 rounded-2xl text-xs font-black transition-all active:scale-95 uppercase tracking-wider">
                            Cancel
                        </button>
                        <button @click="executeDelete"
                                :disabled="confirmDialog.deleting"
                                class="flex-1 bg-red-600 hover:bg-red-700 text-white py-3.5 rounded-2xl text-xs font-black transition-all shadow-lg shadow-red-200 active:scale-95 disabled:opacity-40 disabled:shadow-none uppercase tracking-wider">
                            {{ confirmDialog.deleting ? 'Deleting...' : 'Delete' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <Teleport to="body">
            <div v-if="alertDialog.show"
                 class="fixed inset-0 z-[60] flex items-center justify-center bg-black/40 backdrop-blur-sm"
                 @click.self="alertDialog.show = false">
                <div class="bg-white rounded-[2.5rem] shadow-2xl border border-slate-200 w-full max-w-sm mx-4 overflow-hidden">
                    <div class="p-6 text-center">
                        <div class="mx-auto w-12 h-12 rounded-full bg-amber-100 flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider mb-2">Error</h3>
                        <p class="text-xs font-bold text-slate-500">{{ alertDialog.message }}</p>
                    </div>
                    <div class="px-6 pb-6">
                        <button @click="alertDialog.show = false"
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3.5 rounded-2xl text-xs font-black transition-all shadow-lg shadow-blue-200 active:scale-95 uppercase tracking-wider">
                            OK
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { Pencil, Trash2 } from 'lucide-vue-next';
import { exchangeRatesApi } from '../../api/exchange-rates';
import { currenciesApi } from '../../api/currencies';

const rates = ref([]);
const currencies = ref([]);
const loading = ref(true);
const saving = ref(false);
const showModal = ref(false);
const editingId = ref(null);
const errors = ref({});

const confirmDialog = reactive({
    show: false,
    rate: null,
    deleting: false,
});

const alertDialog = reactive({
    show: false,
    message: '',
});

const form = ref({
    base_currency_id: '',
    target_currency_id: '',
    rate: '',
    valid_from: '',
    valid_to: '',
});

const resetForm = () => {
    form.value = { base_currency_id: '', target_currency_id: '', rate: '', valid_from: '', valid_to: '' };
    errors.value = {};
    editingId.value = null;
};

const openCreateModal = () => {
    resetForm();
    form.value.valid_from = new Date().toISOString().slice(0, 16);
    showModal.value = true;
};

const openEditModal = (rate) => {
    resetForm();
    editingId.value = rate.id;
    form.value = {
        base_currency_id: rate.base_currency_id,
        target_currency_id: rate.target_currency_id,
        rate: rate.rate,
        valid_from: rate.valid_from?.slice(0, 16) || '',
        valid_to: rate.valid_to?.slice(0, 16) || '',
    };
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    resetForm();
};

const formatDate = (dt) => {
    if (!dt) return '—';
    const d = new Date(dt);
    return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const fetchRates = async () => {
    loading.value = true;
    try {
        const response = await exchangeRatesApi.getAll();
        rates.value = response.data;
    } catch (e) {
        console.error('Failed to fetch exchange rates', e);
    } finally {
        loading.value = false;
    }
};

const fetchCurrencies = async () => {
    try {
        const response = await currenciesApi.getAll();
        currencies.value = response.data;
    } catch (e) {
        console.error('Failed to fetch currencies', e);
    }
};

const saveRate = async () => {
    saving.value = true;
    errors.value = {};
    try {
        if (editingId.value) {
            await exchangeRatesApi.update(editingId.value, form.value);
        } else {
            await exchangeRatesApi.create(form.value);
        }
        await fetchRates();
        closeModal();
    } catch (e) {
        if (e.response?.status === 422 && e.response.data?.errors) {
            const flat = {};
            for (const [key, msgs] of Object.entries(e.response.data.errors)) {
                flat[key] = msgs[0];
            }
            errors.value = flat;
        } else {
            alertDialog.message = 'Failed to save exchange rate.';
            alertDialog.show = true;
        }
    } finally {
        saving.value = false;
    }
};

const confirmDelete = (rate) => {
    confirmDialog.rate = rate;
    confirmDialog.show = true;
};

const executeDelete = async () => {
    confirmDialog.deleting = true;
    try {
        await exchangeRatesApi.delete(confirmDialog.rate.id);
        confirmDialog.show = false;
        confirmDialog.rate = null;
        await fetchRates();
    } catch (e) {
        console.error('Failed to delete exchange rate', e);
        confirmDialog.show = false;
        confirmDialog.rate = null;
        alertDialog.message = 'Failed to delete exchange rate.';
        alertDialog.show = true;
    } finally {
        confirmDialog.deleting = false;
    }
};

onMounted(() => {
    fetchCurrencies();
    fetchRates();
});
</script>
