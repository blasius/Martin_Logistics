<template>
    <div class="p-6 bg-slate-50 min-h-screen space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-black text-slate-800 tracking-tight uppercase">Parts Catalog</h1>
            <button @click="openCreateModal" class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl shadow-lg font-black text-xs flex items-center gap-2 uppercase">
                <Plus class="w-4 h-4" /> New Part
            </button>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex gap-4">
            <div class="relative flex-1">
                <Search class="absolute left-3 top-2.5 w-4 h-4 text-slate-400" />
                <input v-model="filters.search" @input="debounceSearch" type="text" placeholder="Search by name or SKU..." class="w-full pl-10 p-2 bg-slate-50 border-none rounded-xl text-xs font-bold outline-none">
            </div>
            <select v-model="filters.category" @change="fetch" class="bg-slate-50 border-none rounded-xl text-xs font-bold px-3 outline-none">
                <option value="">All Categories</option>
                <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
            </select>
        </div>

        <div v-if="loading" class="grid gap-4">
            <div v-for="i in 5" class="h-16 bg-white rounded-2xl animate-pulse border border-slate-100"></div>
        </div>

        <div v-else class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-slate-50/50 text-[10px] font-black text-slate-400 uppercase border-b">
                    <tr>
                        <th class="p-4">SKU</th>
                        <th class="p-4">Name</th>
                        <th class="p-4">Category</th>
                        <th class="p-4">Unit Price</th>
                        <th class="p-4">UOM</th>
                        <th class="p-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="part in parts" :key="part.id" class="border-b border-slate-50 hover:bg-slate-50/50">
                        <td class="p-4 font-bold text-xs text-indigo-600 font-mono">{{ part.sku }}</td>
                        <td class="p-4 font-black text-sm text-slate-800">{{ part.name }}</td>
                        <td class="p-4"><span class="text-[10px] bg-slate-100 text-slate-600 px-2 py-1 rounded-full font-bold">{{ part.category }}</span></td>
                        <td class="p-4 text-sm font-bold text-slate-700">{{ formatAmount(part.unit_price) }}</td>
                        <td class="p-4 text-xs text-slate-500 font-medium">{{ part.unit_of_measure }}</td>
                        <td class="p-4 text-right">
                            <button @click="openEditModal(part)" class="text-indigo-600 hover:text-indigo-800 mr-2"><Edit3 class="w-4 h-4 inline" /></button>
                            <button @click="confirmDelete(part)" class="text-rose-500 hover:text-rose-700"><Trash2 class="w-4 h-4 inline" /></button>
                        </td>
                    </tr>
                    <tr v-if="parts.length === 0">
                        <td colspan="6" class="p-10 text-center text-slate-400 font-bold text-xs uppercase">No parts found</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" @click.self="showModal = false">
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full border border-slate-200">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center">
                    <h3 class="font-black text-slate-800 uppercase text-sm">{{ editing ? 'Edit Part' : 'New Part' }}</h3>
                    <button @click="showModal = false" class="text-slate-400 hover:text-slate-600"><X class="w-5 h-5" /></button>
                </div>
                <form @submit.prevent="save" class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase">SKU <span class="text-slate-300 font-normal">(auto)</span></label>
                            <input v-model="form.sku" class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold" placeholder="Leave blank to auto-generate">
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase">Name</label>
                            <input v-model="form.name" required class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                        </div>
                    </div>
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase">Barcode</label>
                        <input v-model="form.barcode" class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                    </div>
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase">Description</label>
                        <textarea v-model="form.description" rows="2" class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold"></textarea>
                    </div>
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase">Category</label>
                            <input v-model="form.category" class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase">Unit of Measure</label>
                            <input v-model="form.unit_of_measure" class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase">Unit Price</label>
                            <input v-model.number="form.unit_price" type="number" step="0.01" min="0" class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                        </div>
                    </div>
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase">Compatible Vehicle Makes</label>
                        <input v-model="form.compatible_vehicle_makes" placeholder="e.g. Howo, Sinotruk, Shacman" class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="showModal = false" class="flex-1 px-4 py-3 bg-slate-100 text-slate-600 text-xs font-black rounded-xl uppercase">Cancel</button>
                        <button type="submit" class="flex-1 px-4 py-3 bg-indigo-600 text-white text-xs font-black rounded-xl shadow-lg uppercase" :disabled="saving">
                            {{ saving ? 'Saving...' : (editing ? 'Update' : 'Create') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { partsApi } from '../../../api/workshop/parts';
import { Plus, Search, Edit3, Trash2, X } from 'lucide-vue-next';

const loading = ref(true);
const saving = ref(false);
const showModal = ref(false);
const editing = ref(null);
const parts = ref([]);
const categories = ref([]);
const filters = ref({ search: '', category: '' });
const form = ref({ sku: '', barcode: '', name: '', description: '', category: '', unit_of_measure: '', unit_price: 0, compatible_vehicle_makes: '' });

let debounceTimer;

function debounceSearch() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(fetch, 300);
}

async function fetch() {
    loading.value = true;
    try {
        const { data } = await partsApi.index(filters.value);
        parts.value = data.parts?.data || [];
        categories.value = data.categories || [];
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
}

function openCreateModal() {
    editing.value = null;
    form.value = { sku: '', barcode: '', name: '', description: '', category: '', unit_of_measure: '', unit_price: 0, compatible_vehicle_makes: '' };
    showModal.value = true;
}

function openEditModal(part) {
    editing.value = part;
    form.value = { ...part };
    showModal.value = true;
}

async function save() {
    saving.value = true;
    try {
        if (editing.value) {
            await partsApi.update(editing.value.id, form.value);
        } else {
            await partsApi.store(form.value);
        }
        showModal.value = false;
        await fetch();
    } catch (e) {
        console.error(e);
    } finally {
        saving.value = false;
    }
}

async function confirmDelete(part) {
    if (!confirm(`Delete part "${part.name}"?`)) return;
    try {
        await partsApi.destroy(part.id);
        await fetch();
    } catch (e) {
        console.error(e);
    }
}

function formatAmount(amount) {
    if (!amount && amount !== 0) return '—';
    return new Intl.NumberFormat('en-US', { style: 'decimal', minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(amount);
}

onMounted(fetch);
</script>
