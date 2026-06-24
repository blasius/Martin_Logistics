<template>
    <div class="p-6 bg-slate-50 min-h-screen space-y-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <button @click="$router.push('/fuel')" class="p-2 bg-white rounded-xl border border-slate-200 hover:bg-slate-50">
                    <ArrowLeft class="w-4 h-4 text-slate-500" />
                </button>
                <div>
                    <h1 class="text-2xl font-black text-slate-800 tracking-tight uppercase">{{ tank?.code }}</h1>
                    <p class="text-xs font-bold text-slate-400">{{ tank?.name }} &middot; {{ tank?.fuel_type }}</p>
                </div>
            </div>
            <div class="flex gap-2">
                <button @click="showLevelModal = true" class="px-4 py-2 bg-blue-600 text-white rounded-xl font-black text-[10px] uppercase">Update Level</button>
                <button @click="showDeliveryModal = true" class="px-4 py-2 bg-emerald-600 text-white rounded-xl font-black text-[10px] uppercase">Record Delivery</button>
                <button @click="load" class="p-2.5 bg-white rounded-xl border border-slate-200 hover:bg-slate-50">
                    <RefreshCw class="w-4 h-4 text-slate-500" />
                </button>
            </div>
        </div>

        <div v-if="!tank" class="text-center py-20"><p class="text-slate-400 font-bold text-xs uppercase">Loading...</p></div>

        <template v-else>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                    <p class="text-[10px] font-black text-slate-400 uppercase">Capacity</p>
                    <p class="text-2xl font-black text-slate-800 mt-1">{{ tank.capacity?.toLocaleString() }} L</p>
                </div>
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                    <p class="text-[10px] font-black text-slate-400 uppercase">Current Level</p>
                    <p class="text-2xl font-black text-blue-600 mt-1">{{ tank.current_level?.toLocaleString() }} L</p>
                    <div class="mt-2 bg-slate-100 rounded-full h-2.5 overflow-hidden">
                        <div class="h-full rounded-full bg-blue-500 transition-all" :style="{ width: tankPercent + '%' }"></div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                    <p class="text-[10px] font-black text-slate-400 uppercase">Status</p>
                    <p class="mt-1">
                        <span v-if="tank.current_level <= tank.reorder_threshold" class="text-[10px] font-black px-3 py-1.5 bg-rose-100 text-rose-700 rounded-full">LOW STOCK</span>
                        <span v-else class="text-[10px] font-black px-3 py-1.5 bg-emerald-100 text-emerald-700 rounded-full">OK</span>
                    </p>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h2 class="font-black text-xs text-slate-500 uppercase mb-4">Delivery History</h2>
                <div v-if="!tank.deliveries?.length" class="text-center py-10">
                    <p class="text-slate-400 font-bold text-xs uppercase">No deliveries recorded</p>
                </div>
                <table v-else class="w-full text-left">
                    <thead class="bg-slate-50/50 text-[10px] font-black text-slate-400 uppercase border-b">
                        <tr>
                            <th class="p-3">Date</th>
                            <th class="p-3">Supplier</th>
                            <th class="p-3">Qty (L)</th>
                            <th class="p-3">Unit Price</th>
                            <th class="p-3">Total</th>
                            <th class="p-3">Invoice</th>
                            <th class="p-3">Received By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="d in tank.deliveries" :key="d.id" class="border-b border-slate-50 text-xs">
                            <td class="p-3 font-bold text-slate-600">{{ formatDate(d.delivered_at) }}</td>
                            <td class="p-3 text-slate-600">{{ d.supplier?.name }}</td>
                            <td class="p-3 font-bold">{{ d.quantity?.toLocaleString() }}</td>
                            <td class="p-3">{{ formatCurrency(d.unit_price) }}</td>
                            <td class="p-3 font-bold">{{ formatCurrency(d.total_amount) }}</td>
                            <td class="p-3 text-slate-400">{{ d.invoice_reference || '—' }}</td>
                            <td class="p-3 text-slate-600">{{ d.receiver?.name }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </template>

        <div v-if="showLevelModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" @click.self="showLevelModal = false">
            <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full border border-slate-200">
                <div class="p-6 border-b border-slate-100">
                    <h3 class="font-black text-slate-800 uppercase text-sm">Update Tank Level</h3>
                </div>
                <form @submit.prevent="updateLevel" class="p-6 space-y-4">
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase">Current Level (L)</label>
                        <input v-model.number="levelForm.current_level" type="number" min="0" step="0.01" required class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                    </div>
                    <button type="submit" class="w-full py-3 bg-blue-600 text-white rounded-xl font-black text-xs uppercase">Save</button>
                </form>
            </div>
        </div>

        <div v-if="showDeliveryModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" @click.self="showDeliveryModal = false">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full border border-slate-200">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center">
                    <h3 class="font-black text-slate-800 uppercase text-sm">Record Delivery</h3>
                    <button @click="showDeliveryModal = false" class="text-slate-400 hover:text-slate-600"><X class="w-5 h-5" /></button>
                </div>
                <form @submit.prevent="recordDelivery" class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase">Quantity (L)</label>
                            <input v-model.number="deliveryForm.quantity" type="number" min="0" step="0.01" required class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase">Fuel Type</label>
                            <select v-model="deliveryForm.fuel_type" required class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                                <option value="diesel">Diesel</option>
                                <option value="petrol">Petrol</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase">Supplier</label>
                            <select v-model="deliveryForm.supplier_id" required class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                                <option value="">Select</option>
                                <option v-for="v in suppliers" :key="v.id" :value="v.id">{{ v.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase">Unit Price</label>
                            <input v-model.number="deliveryForm.unit_price" type="number" min="0" step="0.01" required class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                        </div>
                    </div>
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase">Total Amount</label>
                        <input v-model.number="deliveryForm.total_amount" type="number" min="0" step="0.01" required class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase">Invoice Ref</label>
                            <input v-model="deliveryForm.invoice_reference" class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase">Delivery Date</label>
                            <input v-model="deliveryForm.delivered_at" type="datetime-local" required class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                        </div>
                    </div>
                    <button type="submit" class="w-full py-3 bg-emerald-600 text-white rounded-xl font-black text-xs uppercase">Record Delivery</button>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { fuelTankApi } from '../../../api/fuel/tanks';
import { fuelDeliveryApi } from '../../../api/fuel/deliveries';
import { ArrowLeft, RefreshCw, X } from 'lucide-vue-next';

const route = useRoute();
const tank = ref(null);
const suppliers = ref([]);
const showLevelModal = ref(false);
const showDeliveryModal = ref(false);
const levelForm = ref({ current_level: 0 });
const deliveryForm = ref({
    tank_id: route.params.id, supplier_id: '', fuel_type: 'diesel',
    quantity: 0, unit_price: 0, total_amount: 0,
    invoice_reference: '', delivered_at: new Date().toISOString().slice(0, 16),
});

const tankPercent = computed(() => {
    if (!tank.value?.capacity) return 0;
    return Math.min((tank.value.current_level / tank.value.capacity) * 100, 100);
});

async function load() {
    try {
        const [tankRes, suppliersRes] = await Promise.all([
            fuelTankApi.show(route.params.id),
            import('../../../api/workshop/repair-requests').then(m => m.repairRequestsApi.vehicles()),
        ]);
        tank.value = tankRes.data;
    } catch (e) { console.error(e); }
}

async function loadSuppliers() {
    try {
        const { vendorsApi } = await import('../../../api/workshop/vendors');
        const res = await vendorsApi.list();
        suppliers.value = res.data;
    } catch (e) { console.error(e); }
}

onMounted(() => { load(); loadSuppliers(); });
</script>
