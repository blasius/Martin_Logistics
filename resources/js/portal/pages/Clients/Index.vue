<template>
    <div class="p-6 max-w-5xl mx-auto">
        <header class="flex justify-between items-end mb-8">
            <div>
                <h1 class="text-3xl font-black text-slate-800 tracking-tight uppercase italic leading-none">Clients</h1>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-1">Customer Management</p>
            </div>
            <button @click="openCreateModal"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-2xl text-xs font-black shadow-lg shadow-blue-200 active:scale-95 transition-all uppercase">
                + Add Client
            </button>
        </header>

        <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-6 border-b border-slate-100 bg-slate-50/50">
                <h2 class="text-[10px] font-black text-slate-500 uppercase tracking-widest">All Clients</h2>
            </div>

            <div v-if="loading" class="flex justify-center p-12">
                <div class="w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
            </div>

            <div v-else-if="clients.length === 0" class="text-center p-12 text-xs text-slate-400 font-bold uppercase">
                No clients found.
            </div>

            <table v-else class="w-full">
                <thead>
                    <tr class="text-[9px] font-black text-slate-400 uppercase tracking-wider border-b border-slate-100">
                        <th class="text-left p-5 pl-8">Name</th>
                        <th class="text-left p-5">Contact Person</th>
                        <th class="text-left p-5">Phone</th>
                        <th class="text-center p-5">Type</th>
                        <th class="text-right p-5 pr-8">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="client in clients" :key="client.id"
                        class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors">
                        <td class="p-5 pl-8">
                            <div>
                                <span class="font-black text-sm text-slate-800">{{ client.name }}</span>
                                <p v-if="client.email" class="text-[10px] font-bold text-slate-400 mt-0.5">{{ client.email }}</p>
                            </div>
                        </td>
                        <td class="p-5">
                            <span class="font-bold text-sm text-slate-700">{{ client.contact_person || '—' }}</span>
                        </td>
                        <td class="p-5">
                            <span class="font-bold text-sm text-slate-700">{{ client.phone || '—' }}</span>
                        </td>
                        <td class="p-5 text-center">
                            <span :class="typeBadgeClass(client.type)"
                                  class="inline-flex items-center gap-1 text-[9px] font-black px-2.5 py-1 rounded-full uppercase">
                                {{ client.type }}
                            </span>
                        </td>
                        <td class="p-5 pr-8 text-right">
                            <button @click="openEditModal(client)"
                                    class="text-blue-500 hover:text-blue-700 hover:bg-blue-50 p-2 rounded-lg transition-colors active:scale-95"
                                    title="Edit">
                                <Pencil class="w-4 h-4" />
                            </button>
                            <button @click="confirmDelete(client)"
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
                 class="fixed inset-0 z-50 flex items-start justify-center bg-black/40 backdrop-blur-sm pt-10 pb-10"
                 @click.self="closeModal">
                <div class="bg-white rounded-[2.5rem] shadow-2xl border border-slate-200 w-full max-w-lg mx-4 flex flex-col max-h-[85vh] overflow-hidden">
                    <div class="p-6 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center flex-shrink-0">
                        <h2 class="text-[10px] font-black text-slate-500 uppercase tracking-widest">
                            {{ editingId ? 'Edit Client' : 'New Client' }}
                        </h2>
                        <button @click="closeModal" class="text-slate-400 hover:text-slate-600 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <div class="p-6 space-y-5 overflow-y-auto min-h-0 flex-1">
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1 block mb-2 tracking-wide">Name <span class="text-red-500">*</span></label>
                            <input v-model="form.name" type="text" placeholder="Company or individual name"
                                   class="border-slate-200 px-4 py-3.5 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-500 outline-none bg-slate-50 w-full transition-all" />
                            <p v-if="errors.name" class="text-[9px] font-bold text-red-500 mt-1.5 ml-1">{{ errors.name }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase ml-1 block mb-2 tracking-wide">Type <span class="text-red-500">*</span></label>
                                <select v-model="form.type"
                                        class="border-slate-200 px-4 py-3.5 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-500 outline-none bg-slate-50 w-full transition-all">
                                    <option value="company">Company</option>
                                    <option value="individual">Individual</option>
                                </select>
                                <p v-if="errors.type" class="text-[9px] font-bold text-red-500 mt-1.5 ml-1">{{ errors.type }}</p>
                            </div>
                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase ml-1 block mb-2 tracking-wide">Phone</label>
                                <input v-model="form.phone" type="tel" placeholder="+1234567890"
                                       class="border-slate-200 px-4 py-3.5 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-500 outline-none bg-slate-50 w-full transition-all" />
                                <p v-if="errors.phone" class="text-[9px] font-bold text-red-500 mt-1.5 ml-1">{{ errors.phone }}</p>
                            </div>
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1 block mb-2 tracking-wide">Email</label>
                            <input v-model="form.email" type="email" placeholder="client@example.com"
                                   class="border-slate-200 px-4 py-3.5 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-500 outline-none bg-slate-50 w-full transition-all" />
                            <p v-if="errors.email" class="text-[9px] font-bold text-red-500 mt-1.5 ml-1">{{ errors.email }}</p>
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1 block mb-2 tracking-wide">Contact Person</label>
                            <input v-model="form.contact_person" type="text" placeholder="Primary contact name"
                                   class="border-slate-200 px-4 py-3.5 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-500 outline-none bg-slate-50 w-full transition-all" />
                            <p v-if="errors.contact_person" class="text-[9px] font-bold text-red-500 mt-1.5 ml-1">{{ errors.contact_person }}</p>
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1 block mb-2 tracking-wide">Address</label>
                            <input v-model="form.address" type="text" placeholder="Street, city, postal code"
                                   class="border-slate-200 px-4 py-3.5 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-500 outline-none bg-slate-50 w-full transition-all" />
                            <p v-if="errors.address" class="text-[9px] font-bold text-red-500 mt-1.5 ml-1">{{ errors.address }}</p>
                        </div>

                        <div v-if="form.type === 'company'">
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1 block mb-2 tracking-wide">TIN Number <span class="text-red-500">*</span></label>
                            <input v-model="form.tin" type="text" placeholder="Tax identification number"
                                   class="border-slate-200 px-4 py-3.5 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-500 outline-none bg-slate-50 w-full transition-all" />
                            <p v-if="errors.tin" class="text-[9px] font-bold text-red-500 mt-1.5 ml-1">{{ errors.tin }}</p>
                        </div>
                    </div>

                    <div class="p-6 border-t border-slate-100 bg-slate-50 flex-shrink-0">
                        <button @click="saveClient"
                                :disabled="saving"
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white py-4 rounded-2xl text-xs font-black transition-all shadow-lg shadow-blue-200 active:scale-95 disabled:opacity-40 disabled:shadow-none uppercase tracking-widest">
                            {{ saving ? 'Saving...' : (editingId ? 'Update Client' : 'Save Client') }}
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
                        <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider mb-2">Delete Client</h3>
                        <p class="text-xs font-bold text-slate-500">Delete <span class="text-slate-700">{{ confirmDialog.client?.name }}</span>? This cannot be undone.</p>
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
import { clientsApi } from '../../api/clients';

const clients = ref([]);
const loading = ref(true);
const saving = ref(false);
const showModal = ref(false);
const editingId = ref(null);
const errors = ref({});

const confirmDialog = reactive({
    show: false,
    client: null,
    deleting: false,
});

const alertDialog = reactive({
    show: false,
    message: '',
});

const form = ref({
    name: '',
    contact_person: '',
    phone: '',
    email: '',
    address: '',
    type: 'company',
    tin: '',
});

const typeBadgeClass = (type) => {
    return type === 'company'
        ? 'bg-indigo-100 text-indigo-700'
        : 'bg-emerald-100 text-emerald-700';
};

const resetForm = () => {
    form.value = { name: '', contact_person: '', phone: '', email: '', address: '', type: 'company', tin: '' };
    errors.value = {};
    editingId.value = null;
};

const openCreateModal = () => {
    resetForm();
    showModal.value = true;
};

const openEditModal = (client) => {
    resetForm();
    editingId.value = client.id;
    form.value = {
        name: client.name,
        contact_person: client.contact_person || '',
        phone: client.phone || '',
        email: client.email || '',
        address: client.address || '',
        type: client.type,
        tin: client.tin || '',
    };
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    resetForm();
};

const fetchClients = async () => {
    loading.value = true;
    try {
        const response = await clientsApi.getAll();
        clients.value = response.data;
    } catch (e) {
        console.error('Failed to fetch clients', e);
    } finally {
        loading.value = false;
    }
};

const saveClient = async () => {
    saving.value = true;
    errors.value = {};
    try {
        if (editingId.value) {
            await clientsApi.update(editingId.value, form.value);
        } else {
            await clientsApi.create(form.value);
        }
        await fetchClients();
        closeModal();
    } catch (e) {
        if (e.response?.status === 422 && e.response.data?.errors) {
            const flat = {};
            for (const [key, msgs] of Object.entries(e.response.data.errors)) {
                flat[key] = msgs[0];
            }
            errors.value = flat;
        } else {
            alertDialog.message = 'Failed to save client.';
            alertDialog.show = true;
        }
    } finally {
        saving.value = false;
    }
};

const confirmDelete = (client) => {
    confirmDialog.client = client;
    confirmDialog.show = true;
};

const executeDelete = async () => {
    confirmDialog.deleting = true;
    try {
        await clientsApi.delete(confirmDialog.client.id);
        confirmDialog.show = false;
        confirmDialog.client = null;
        await fetchClients();
    } catch (e) {
        console.error('Failed to delete client', e);
        confirmDialog.show = false;
        confirmDialog.client = null;
        alertDialog.message = 'Failed to delete client.';
        alertDialog.show = true;
    } finally {
        confirmDialog.deleting = false;
    }
};

onMounted(fetchClients);
</script>
