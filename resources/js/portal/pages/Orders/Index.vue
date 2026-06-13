<template>
    <div class="p-6 max-w-6xl mx-auto">
        <header class="flex justify-between items-end mb-8">
            <div>
                <h1 class="text-3xl font-black text-slate-800 tracking-tight uppercase italic leading-none">Orders</h1>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-1">Customer Orders Management</p>
            </div>
            <button @click="openCreateModal"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-2xl text-xs font-black shadow-lg shadow-blue-200 active:scale-95 transition-all uppercase">
                + New Order
            </button>
        </header>

        <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-6 border-b border-slate-100 bg-slate-50/50">
                <h2 class="text-[10px] font-black text-slate-500 uppercase tracking-widest">All Orders</h2>
            </div>

            <div v-if="loading" class="flex justify-center p-12">
                <div class="w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
            </div>

            <div v-else-if="orders.length === 0" class="text-center p-12 text-xs text-slate-400 font-bold uppercase">
                No orders found.
            </div>

            <table v-else class="w-full">
                <thead>
                    <tr class="text-[9px] font-black text-slate-400 uppercase tracking-wider border-b border-slate-100">
                        <th class="text-left p-5 pl-8">Reference</th>
                        <th class="text-left p-5">Client</th>
                        <th class="text-left p-5">Origin</th>
                        <th class="text-left p-5">Destination</th>
                        <th class="text-center p-5">Pickup Date</th>
                        <th class="text-center p-5">Status</th>
                        <th class="text-right p-5">Price</th>
                        <th class="text-right p-5 pr-8">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="order in orders" :key="order.id"
                        class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors">
                        <td class="p-5 pl-8">
                            <span class="font-black text-sm text-slate-800">{{ order.reference }}</span>
                        </td>
                        <td class="p-5">
                            <span class="font-bold text-sm text-slate-700">{{ order.client_name || '—' }}</span>
                        </td>
                        <td class="p-5">
                            <span class="font-bold text-sm text-slate-700">{{ order.origin }}</span>
                        </td>
                        <td class="p-5">
                            <span class="font-bold text-sm text-slate-700">{{ order.destination }}</span>
                        </td>
                        <td class="p-5 text-center">
                            <span class="font-bold text-sm text-slate-600">{{ order.pickup_date || '—' }}</span>
                        </td>
                        <td class="p-5 text-center">
                            <span :class="statusBadgeClass(order.status)"
                                  class="inline-flex items-center gap-1 text-[9px] font-black px-2.5 py-1 rounded-full uppercase">
                                {{ order.status }}
                            </span>
                        </td>
                        <td class="p-5 text-right">
                            <span class="font-black text-sm text-slate-800">{{ formatPrice(order.price) }}</span>
                        </td>
                        <td class="p-5 pr-8 text-right">
                            <button @click="openEditModal(order)"
                                    class="text-blue-500 hover:text-blue-700 hover:bg-blue-50 p-2 rounded-lg transition-colors active:scale-95"
                                    title="Edit">
                                <Pencil class="w-4 h-4" />
                            </button>
                            <button @click="confirmDelete(order)"
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
                <div class="bg-white rounded-[2.5rem] shadow-2xl border border-slate-200 w-full max-w-2xl mx-4 flex flex-col max-h-[85vh] overflow-hidden">
                    <div class="p-6 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center flex-shrink-0">
                        <h2 class="text-[10px] font-black text-slate-500 uppercase tracking-widest">
                            {{ editingId ? 'Edit Order' : 'New Order' }}
                        </h2>
                        <button @click="closeModal" class="text-slate-400 hover:text-slate-600 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <div class="p-6 space-y-5 overflow-y-auto min-h-0 flex-1">
                        <div v-if="editingId">
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1 block mb-2 tracking-wide">Reference</label>
                            <input :value="form.reference" type="text" readonly
                                   class="border-slate-200 px-4 py-3.5 rounded-xl text-sm font-bold bg-slate-100 text-slate-500 w-full cursor-not-allowed" />
                        </div>

                        <div ref="clientDropdownRef" class="relative">
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1 block mb-2 tracking-wide">Client <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input v-model="clientSearch"
                                       @focus="clientDropdownOpen = true"
                                       autocomplete="off"
                                       :placeholder="form.client_id ? selectedClientName : 'Search clients...'"
                                       class="border-slate-200 px-4 py-3.5 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-500 outline-none bg-slate-50 w-full transition-all pr-10" />
                                <button v-if="form.client_id" @click="clearClient" type="button"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                            <div v-if="clientDropdownOpen"
                                 class="absolute z-20 mt-1 w-full bg-white border border-slate-200 rounded-xl shadow-lg max-h-56 overflow-y-auto">
                                <div v-for="c in filteredClients" :key="c.id"
                                     @click="selectClient(c)"
                                     class="px-4 py-3 cursor-pointer hover:bg-blue-50 transition-colors border-b border-slate-50 last:border-b-0">
                                    <div class="text-sm font-bold text-slate-800">{{ c.name }}</div>
                                    <div v-if="c.email || c.phone" class="text-[10px] font-bold text-slate-400 mt-0.5">
                                        {{ [c.email, c.phone].filter(Boolean).join(' · ') }}
                                    </div>
                                </div>
                                <div v-if="filteredClients.length === 0"
                                     class="px-4 py-4 text-xs font-bold text-slate-400 text-center">
                                    No clients found
                                </div>
                            </div>
                            <p v-if="errors.client_id" class="text-[9px] font-bold text-red-500 mt-1.5 ml-1">{{ errors.client_id }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase ml-1 block mb-2 tracking-wide">Origin <span class="text-red-500">*</span></label>
                                <input v-model="form.origin" type="text" placeholder="Pickup location"
                                       class="border-slate-200 px-4 py-3.5 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-500 outline-none bg-slate-50 w-full transition-all" />
                                <p v-if="errors.origin" class="text-[9px] font-bold text-red-500 mt-1.5 ml-1">{{ errors.origin }}</p>
                            </div>
                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase ml-1 block mb-2 tracking-wide">Destination <span class="text-red-500">*</span></label>
                                <input v-model="form.destination" type="text" placeholder="Dropoff location"
                                       class="border-slate-200 px-4 py-3.5 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-500 outline-none bg-slate-50 w-full transition-all" />
                                <p v-if="errors.destination" class="text-[9px] font-bold text-red-500 mt-1.5 ml-1">{{ errors.destination }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase ml-1 block mb-2 tracking-wide">Pickup Date</label>
                                <input v-model="form.pickup_date" type="date"
                                       class="border-slate-200 px-4 py-3.5 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-500 outline-none bg-slate-50 w-full transition-all" />
                                <p v-if="errors.pickup_date" class="text-[9px] font-bold text-red-500 mt-1.5 ml-1">{{ errors.pickup_date }}</p>
                            </div>
                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase ml-1 block mb-2 tracking-wide">Status <span class="text-red-500">*</span></label>
                                <select v-model="form.status"
                                        class="border-slate-200 px-4 py-3.5 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-500 outline-none bg-slate-50 w-full transition-all">
                                    <option value="draft">Draft</option>
                                    <option value="confirmed">Confirmed</option>
                                    <option value="in_transit">In Transit</option>
                                    <option value="delivered">Delivered</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                                <p v-if="errors.status" class="text-[9px] font-bold text-red-500 mt-1.5 ml-1">{{ errors.status }}</p>
                            </div>
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1 block mb-2 tracking-wide">Price</label>
                            <input v-model="form.price" type="number" step="0.01" min="0" placeholder="0.00"
                                   class="border-slate-200 px-4 py-3.5 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-500 outline-none bg-slate-50 w-full transition-all" />
                            <p v-if="errors.price" class="text-[9px] font-bold text-red-500 mt-1.5 ml-1">{{ errors.price }}</p>
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1 block mb-2 tracking-wide">Notes</label>
                            <textarea v-model="form.notes" rows="3" placeholder="Additional notes..."
                                      class="border-slate-200 px-4 py-3.5 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-500 outline-none bg-slate-50 w-full transition-all resize-none"></textarea>
                            <p v-if="errors.notes" class="text-[9px] font-bold text-red-500 mt-1.5 ml-1">{{ errors.notes }}</p>
                        </div>
                    </div>

                    <div class="p-6 border-t border-slate-100 bg-slate-50 flex-shrink-0">
                        <button @click="saveOrder"
                                :disabled="saving"
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white py-4 rounded-2xl text-xs font-black transition-all shadow-lg shadow-blue-200 active:scale-95 disabled:opacity-40 disabled:shadow-none uppercase tracking-widest">
                            {{ saving ? 'Saving...' : (editingId ? 'Update Order' : 'Create Order') }}
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
                        <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider mb-2">Delete Order</h3>
                        <p class="text-xs font-bold text-slate-500">Delete order <span class="text-slate-700">{{ confirmDialog.order?.reference }}</span>? This cannot be undone.</p>
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
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue';
import { Pencil, Trash2 } from 'lucide-vue-next';
import { ordersApi } from '../../api/orders';
import { clientsApi } from '../../api/clients';

const orders = ref([]);
const clients = ref([]);
const loading = ref(true);
const saving = ref(false);
const showModal = ref(false);
const editingId = ref(null);
const errors = ref({});

const clientSearch = ref('');
const clientDropdownOpen = ref(false);
const clientDropdownRef = ref(null);

const selectedClientName = computed(() => {
    const c = clients.value.find(c => c.id === form.value.client_id);
    return c ? c.name : '';
});

const filteredClients = computed(() => {
    const q = clientSearch.value.toLowerCase().trim();
    if (!q) return clients.value;
    return clients.value.filter(c =>
        c.name.toLowerCase().includes(q) ||
        (c.email && c.email.toLowerCase().includes(q)) ||
        (c.phone && c.phone.toLowerCase().includes(q))
    );
});

const selectClient = (client) => {
    form.value.client_id = client.id;
    clientSearch.value = '';
    clientDropdownOpen.value = false;
};

const clearClient = () => {
    form.value.client_id = '';
    clientSearch.value = '';
    clientDropdownOpen.value = false;
};

const handleClickOutside = (e) => {
    if (clientDropdownRef.value && !clientDropdownRef.value.contains(e.target)) {
        clientDropdownOpen.value = false;
    }
};

const confirmDialog = reactive({
    show: false,
    order: null,
    deleting: false,
});

const alertDialog = reactive({
    show: false,
    message: '',
});

const form = ref({
    client_id: '',
    origin: '',
    destination: '',
    pickup_date: '',
    status: 'draft',
    price: '',
    notes: '',
});

const statusBadgeClass = (status) => {
    const map = {
        draft: 'bg-slate-100 text-slate-600',
        confirmed: 'bg-blue-100 text-blue-700',
        in_transit: 'bg-amber-100 text-amber-700',
        delivered: 'bg-emerald-100 text-emerald-700',
        cancelled: 'bg-red-100 text-red-700',
    };
    return map[status] || 'bg-slate-100 text-slate-600';
};

const formatPrice = (price) => {
    if (!price && price !== 0) return '—';
    return '$' + Number(price).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const resetForm = () => {
    form.value = { client_id: '', origin: '', destination: '', pickup_date: '', status: 'draft', price: '', notes: '', reference: '' };
    errors.value = {};
    editingId.value = null;
    clientSearch.value = '';
    clientDropdownOpen.value = false;
};

const openCreateModal = () => {
    resetForm();
    showModal.value = true;
};

const openEditModal = (order) => {
    resetForm();
    editingId.value = order.id;
    form.value = {
        client_id: order.client_id,
        origin: order.origin,
        destination: order.destination,
        pickup_date: order.pickup_date || '',
        status: order.status,
        price: order.price ?? '',
        notes: order.notes || '',
        reference: order.reference,
    };
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    resetForm();
};

const fetchOrders = async () => {
    loading.value = true;
    try {
        const response = await ordersApi.getAll();
        orders.value = response.data;
    } catch (e) {
        console.error('Failed to fetch orders', e);
    } finally {
        loading.value = false;
    }
};

const fetchClients = async () => {
    try {
        const response = await clientsApi.getAll();
        clients.value = response.data;
    } catch (e) {
        console.error('Failed to fetch clients', e);
    }
};

const saveOrder = async () => {
    saving.value = true;
    errors.value = {};
    try {
        if (editingId.value) {
            await ordersApi.update(editingId.value, form.value);
        } else {
            await ordersApi.create(form.value);
        }
        await fetchOrders();
        closeModal();
    } catch (e) {
        if (e.response?.status === 422 && e.response.data?.errors) {
            const flat = {};
            for (const [key, msgs] of Object.entries(e.response.data.errors)) {
                flat[key] = msgs[0];
            }
            errors.value = flat;
        } else {
            alertDialog.message = 'Failed to save order.';
            alertDialog.show = true;
        }
    } finally {
        saving.value = false;
    }
};

const confirmDelete = (order) => {
    confirmDialog.order = order;
    confirmDialog.show = true;
};

const executeDelete = async () => {
    confirmDialog.deleting = true;
    try {
        await ordersApi.delete(confirmDialog.order.id);
        confirmDialog.show = false;
        confirmDialog.order = null;
        await fetchOrders();
    } catch (e) {
        console.error('Failed to delete order', e);
        confirmDialog.show = false;
        confirmDialog.order = null;
        alertDialog.message = 'Failed to delete order.';
        alertDialog.show = true;
    } finally {
        confirmDialog.deleting = false;
    }
};

onMounted(() => {
    fetchOrders();
    fetchClients();
    document.addEventListener('mousedown', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('mousedown', handleClickOutside);
});
</script>
