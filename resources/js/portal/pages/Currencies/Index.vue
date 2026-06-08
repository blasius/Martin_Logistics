<template>
    <div class="p-6 max-w-5xl mx-auto">
        <header class="flex justify-between items-end mb-8">
            <div>
                <h1 class="text-3xl font-black text-slate-800 tracking-tight uppercase italic leading-none">Currencies</h1>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-1">ISO 4217 Currency Codes</p>
            </div>
            <button @click="openCreateModal"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-2xl text-xs font-black shadow-lg shadow-blue-200 active:scale-95 transition-all uppercase">
                + Add Currency
            </button>
        </header>

        <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-6 border-b border-slate-100 bg-slate-50/50">
                <h2 class="text-[10px] font-black text-slate-500 uppercase tracking-widest">All Currencies</h2>
            </div>

            <div v-if="loading" class="flex justify-center p-12">
                <div class="w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
            </div>

            <div v-else-if="currencies.length === 0" class="text-center p-12 text-xs text-slate-400 font-bold uppercase">
                No currencies defined yet.
            </div>

            <table v-else class="w-full">
                <thead>
                    <tr class="text-[9px] font-black text-slate-400 uppercase tracking-wider border-b border-slate-100">
                        <th class="text-left p-5 pl-8">Code</th>
                        <th class="text-left p-5">Name</th>
                        <th class="text-left p-5">Symbol</th>
                        <th class="text-center p-5">Default</th>
                        <th class="text-right p-5 pr-8">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="currency in currencies" :key="currency.id"
                        class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors">
                        <td class="p-5 pl-8">
                            <span class="font-black text-sm text-slate-800">{{ currency.code }}</span>
                        </td>
                        <td class="p-5">
                            <span class="font-bold text-sm text-slate-700">{{ currency.name }}</span>
                        </td>
                        <td class="p-5">
                            <span class="font-bold text-slate-500">{{ currency.symbol || '—' }}</span>
                        </td>
                        <td class="p-5 text-center">
                            <span v-if="currency.is_default"
                                  class="inline-flex items-center gap-1 text-[9px] font-black px-2.5 py-1 rounded-full bg-green-100 text-green-700 uppercase">
                                Default
                            </span>
                            <span v-else class="text-[9px] text-slate-300 font-bold">—</span>
                        </td>
                        <td class="p-5 pr-8 text-right">
                            <button @click="openEditModal(currency)"
                                    class="text-blue-500 hover:text-blue-700 hover:bg-blue-50 p-2 rounded-lg transition-colors active:scale-95"
                                    title="Edit">
                                <Pencil class="w-4 h-4" />
                            </button>
                            <button @click="confirmDelete(currency)"
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
                            {{ editingId ? 'Edit Currency' : 'New Currency' }}
                        </h2>
                        <button @click="closeModal" class="text-slate-400 hover:text-slate-600 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <div class="p-6 space-y-5">
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1 block mb-2 tracking-wide">ISO Code</label>
                            <input v-model="form.code" type="text" maxlength="3" placeholder="USD"
                                   class="border-slate-200 px-4 py-3.5 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-500 outline-none bg-slate-50 w-full transition-all uppercase" />
                            <p v-if="errors.code" class="text-[9px] font-bold text-red-500 mt-1.5 ml-1">{{ errors.code }}</p>
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1 block mb-2 tracking-wide">Name</label>
                            <input v-model="form.name" type="text" placeholder="US Dollar"
                                   class="border-slate-200 px-4 py-3.5 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-500 outline-none bg-slate-50 w-full transition-all" />
                            <p v-if="errors.name" class="text-[9px] font-bold text-red-500 mt-1.5 ml-1">{{ errors.name }}</p>
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1 block mb-2 tracking-wide">Symbol</label>
                            <input v-model="form.symbol" type="text" maxlength="5" placeholder="$"
                                   class="border-slate-200 px-4 py-3.5 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-500 outline-none bg-slate-50 w-full transition-all" />
                            <p v-if="errors.symbol" class="text-[9px] font-bold text-red-500 mt-1.5 ml-1">{{ errors.symbol }}</p>
                        </div>

                        <div class="flex items-center gap-3 bg-slate-50 p-4 rounded-xl">
                            <input id="is_default" v-model="form.is_default" type="checkbox"
                                   class="w-5 h-5 rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
                            <label for="is_default" class="text-[10px] font-black text-slate-500 uppercase tracking-wide cursor-pointer">
                                Default Currency
                            </label>
                        </div>
                    </div>

                    <div class="p-6 border-t border-slate-100 bg-slate-50">
                        <button @click="saveCurrency"
                                :disabled="saving"
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white py-4 rounded-2xl text-xs font-black transition-all shadow-lg shadow-blue-200 active:scale-95 disabled:opacity-40 disabled:shadow-none uppercase tracking-widest">
                            {{ saving ? 'Saving...' : (editingId ? 'Update Currency' : 'Save Currency') }}
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
                        <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider mb-2">Delete Currency</h3>
                        <p class="text-xs font-bold text-slate-500">Delete <span class="text-slate-700">{{ confirmDialog.currency?.code }}</span> — <span class="text-slate-700">{{ confirmDialog.currency?.name }}</span>? This cannot be undone.</p>
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
import { currenciesApi } from '../../api/currencies';

const currencies = ref([]);
const loading = ref(true);
const saving = ref(false);
const showModal = ref(false);
const editingId = ref(null);
const errors = ref({});

const confirmDialog = reactive({
    show: false,
    currency: null,
    deleting: false,
});

const alertDialog = reactive({
    show: false,
    message: '',
});

const form = ref({
    code: '',
    name: '',
    symbol: '',
    is_default: false,
});

const resetForm = () => {
    form.value = { code: '', name: '', symbol: '', is_default: false };
    errors.value = {};
    editingId.value = null;
};

const openCreateModal = () => {
    resetForm();
    showModal.value = true;
};

const openEditModal = (currency) => {
    resetForm();
    editingId.value = currency.id;
    form.value = {
        code: currency.code,
        name: currency.name,
        symbol: currency.symbol || '',
        is_default: Boolean(currency.is_default),
    };
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    resetForm();
};

const fetchCurrencies = async () => {
    loading.value = true;
    try {
        const response = await currenciesApi.getAll();
        currencies.value = response.data;
    } catch (e) {
        console.error('Failed to fetch currencies', e);
    } finally {
        loading.value = false;
    }
};

const saveCurrency = async () => {
    saving.value = true;
    errors.value = {};
    try {
        if (editingId.value) {
            await currenciesApi.update(editingId.value, form.value);
        } else {
            await currenciesApi.create(form.value);
        }
        await fetchCurrencies();
        closeModal();
    } catch (e) {
        if (e.response?.status === 422 && e.response.data?.errors) {
            const flat = {};
            for (const [key, msgs] of Object.entries(e.response.data.errors)) {
                flat[key] = msgs[0];
            }
            errors.value = flat;
        } else {
            alertDialog.message = 'Failed to save currency.';
            alertDialog.show = true;
        }
    } finally {
        saving.value = false;
    }
};

const confirmDelete = (currency) => {
    confirmDialog.currency = currency;
    confirmDialog.show = true;
};

const executeDelete = async () => {
    confirmDialog.deleting = true;
    try {
        await currenciesApi.delete(confirmDialog.currency.id);
        confirmDialog.show = false;
        confirmDialog.currency = null;
        await fetchCurrencies();
    } catch (e) {
        console.error('Failed to delete currency', e);
        confirmDialog.show = false;
        confirmDialog.currency = null;
        alertDialog.message = 'Failed to delete currency.';
        alertDialog.show = true;
    } finally {
        confirmDialog.deleting = false;
    }
};

onMounted(fetchCurrencies);
</script>
