<template>
    <div class="p-6 bg-slate-50 min-h-screen space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-black text-slate-800 tracking-tight uppercase">Warehouses</h1>
            <button @click="openCreateModal" class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl shadow-lg font-black text-xs flex items-center gap-2 uppercase">
                <Plus class="w-4 h-4" /> New Warehouse
            </button>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
            <div class="relative w-72">
                <Search class="absolute left-3 top-2.5 w-4 h-4 text-slate-400" />
                <input v-model="filters.search" @input="debounceSearch" type="text" placeholder="Search warehouses..." class="w-full pl-10 p-2 bg-slate-50 border-none rounded-xl text-xs font-bold outline-none">
            </div>
        </div>

        <div v-if="loading" class="grid gap-4">
            <div v-for="i in 3" class="h-16 bg-white rounded-2xl animate-pulse border border-slate-100"></div>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="wh in warehouses" :key="wh.id" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="font-black text-slate-800">{{ wh.name }}</h3>
                        <p class="text-xs font-mono font-bold text-indigo-600">{{ wh.code }}</p>
                    </div>
                    <span class="text-[10px] font-black px-2 py-1 rounded-full" :class="wh.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-400'">
                        {{ wh.is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
                <p v-if="wh.location" class="text-xs text-slate-400 mt-2">{{ wh.location }}</p>
                <div class="flex gap-2 mt-4">
                    <button @click="openEditModal(wh)" class="text-indigo-600 hover:text-indigo-800 text-xs font-bold"><Edit3 class="w-3.5 h-3.5 inline" /> Edit</button>
                    <button @click="confirmDelete(wh)" class="text-rose-500 hover:text-rose-700 text-xs font-bold"><Trash2 class="w-3.5 h-3.5 inline" /> Delete</button>
                </div>
            </div>
            <div v-if="warehouses.length === 0" class="col-span-full text-center py-20">
                <p class="text-slate-400 font-bold text-xs uppercase">No warehouses found</p>
            </div>
        </div>

        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" @click.self="showModal = false">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full border border-slate-200">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center">
                    <h3 class="font-black text-slate-800 uppercase text-sm">{{ editing ? 'Edit Warehouse' : 'New Warehouse' }}</h3>
                    <button @click="showModal = false" class="text-slate-400 hover:text-slate-600"><X class="w-5 h-5" /></button>
                </div>
                <form @submit.prevent="save" class="p-6 space-y-4">
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase">Name</label>
                        <input v-model="form.name" required class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase">Code</label>
                            <input v-model="form.code" required class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase">Active</label>
                            <select v-model="form.is_active" class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                                <option :value="true">Active</option>
                                <option :value="false">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase">Location</label>
                        <input v-model="form.location" class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
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
import { warehousesApi } from '../../../api/workshop/warehouses';
import { Plus, Search, Edit3, Trash2, X } from 'lucide-vue-next';

const loading = ref(true);
const saving = ref(false);
const showModal = ref(false);
const editing = ref(null);
const warehouses = ref([]);
const filters = ref({ search: '' });
const form = ref({ name: '', code: '', location: '', is_active: true });

let debounceTimer;

function debounceSearch() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(fetch, 300);
}

async function fetch() {
    loading.value = true;
    try {
        const { data } = await warehousesApi.index(filters.value);
        warehouses.value = data.warehouses?.data || [];
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
}

function openCreateModal() {
    editing.value = null;
    form.value = { name: '', code: '', location: '', is_active: true };
    showModal.value = true;
}

function openEditModal(wh) {
    editing.value = wh;
    form.value = { ...wh };
    showModal.value = true;
}

async function save() {
    saving.value = true;
    try {
        if (editing.value) {
            await warehousesApi.update(editing.value.id, form.value);
        } else {
            await warehousesApi.store(form.value);
        }
        showModal.value = false;
        await fetch();
    } catch (e) {
        console.error(e);
    } finally {
        saving.value = false;
    }
}

async function confirmDelete(wh) {
    if (!confirm(`Delete warehouse "${wh.name}"?`)) return;
    try {
        await warehousesApi.destroy(wh.id);
        await fetch();
    } catch (e) {
        console.error(e);
    }
}

onMounted(fetch);
</script>
