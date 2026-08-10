<template>
    <div class="p-6 bg-slate-50 min-h-screen space-y-6">
        <!-- Header Controls -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <button @click="$router.push('/fuel')" class="p-2.5 bg-white rounded-xl border border-slate-200 hover:bg-slate-100 shadow-sm">
                    <ArrowLeft class="w-4 h-4 text-slate-600" />
                </button>
                <div>
                    <h1 class="text-2xl font-black text-slate-800 tracking-tight uppercase">{{ tank?.code }}</h1>
                    <p class="text-xs font-bold text-slate-400">{{ tank?.name }} &middot; {{ tank?.fuel_type }}</p>
                </div>
            </div>
            <div class="flex gap-2">
                <button @click="openLevelModal" class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-black text-xs uppercase shadow-sm">Update Level</button>
                <button @click="openDeliveryModal" class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-black text-xs uppercase shadow-sm">Record Delivery</button>
                <button @click="load" class="p-2.5 bg-white rounded-xl border border-slate-200 hover:bg-slate-100 shadow-sm">
                    <RefreshCw class="w-4 h-4 text-slate-500" />
                </button>
            </div>
        </div>

        <div v-if="!tank" class="text-center py-20"><p class="text-slate-400 font-bold text-xs uppercase">Loading Tank Data...</p></div>

        <template v-else>
            <!-- Hero 3D Tank Showcase Viewport -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-6">
                <div class="w-full">
                    <VirtualTankViewer
                        :capacity="Number(tank.capacity || 39000)"
                        :current-level="Number(tank.current_level || 0)"
                        :reorder-threshold="tank.reorder_threshold != null ? Number(tank.reorder_threshold) : null"
                        :fuel-type="tank.fuel_type"
                    />
                </div>

                <!-- Telemetry Stats Panel -->
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Capacity</p>
                        <p class="text-xl font-black text-slate-800 mt-1">{{ capacityFormatted }} L</p>
                    </div>
                    <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Current Stock</p>
                        <p class="text-xl font-black mt-1" :class="isLow ? 'text-rose-600' : 'text-amber-500'">{{ currentLevelFormatted }} L</p>
                    </div>
                    <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Space Available</p>
                        <p class="text-xl font-black text-emerald-600 mt-1">{{ spaceAvailable.toLocaleString() }} L</p>
                    </div>
                    <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Reorder Limit</p>
                        <p class="text-xl font-black text-slate-800 mt-1">{{ thresholdFormatted }} L</p>
                    </div>
                    <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Total Delivered</p>
                        <p class="text-xl font-black text-slate-800 mt-1">{{ deliveredTotal.toLocaleString() }} L</p>
                    </div>
                    <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Total Dispensed</p>
                        <p class="text-xl font-black text-slate-800 mt-1">{{ dispensedTotal.toLocaleString() }} L</p>
                    </div>
                </div>
            </div>

            <!-- History Table -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h2 class="font-black text-xs text-slate-500 uppercase tracking-wider mb-4">Delivery History</h2>
                <div v-if="!tank.deliveries?.length" class="text-center py-10">
                    <p class="text-slate-400 font-bold text-xs uppercase">No delivery records found</p>
                </div>
                <table v-else class="w-full text-left border-collapse">
                    <thead class="bg-slate-50 text-[10px] font-black text-slate-400 uppercase border-b border-slate-200">
                    <tr>
                        <th class="p-3">Date</th>
                        <th class="p-3">Supplier</th>
                        <th class="p-3">Qty (L)</th>
                        <th class="p-3">Unit Price</th>
                        <th class="p-3">Total</th>
                        <th class="p-3">Invoice</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="d in tank.deliveries" :key="d.id" class="border-b border-slate-100 text-xs">
                        <td class="p-3 font-bold text-slate-600">{{ d.delivered_at }}</td>
                        <td class="p-3 text-slate-600">{{ d.supplier?.name }}</td>
                        <td class="p-3 font-bold text-amber-600">+{{ d.quantity?.toLocaleString() }} L</td>
                        <td class="p-3">${{ d.unit_price }}</td>
                        <td class="p-3 font-bold">${{ d.total_amount }}</td>
                        <td class="p-3 text-slate-400">{{ d.invoice_reference || '—' }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </template>

        <!-- Update Level Modal -->
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
                    <button type="submit" class="w-full py-3 bg-blue-600 text-white rounded-xl font-black text-xs uppercase">Save Level</button>
                </form>
            </div>
        </div>

        <!-- Record Delivery Modal -->
        <div v-if="showDeliveryModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" @click.self="showDeliveryModal = false">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full border border-slate-200">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center">
                    <h3 class="font-black text-slate-800 uppercase text-sm">Record Delivery</h3>
                    <button type="button" @click="showDeliveryModal = false" class="text-slate-400 hover:text-slate-600"><X class="w-5 h-5" /></button>
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
import { ref, computed, onMounted, defineAsyncComponent } from 'vue';
import { useRoute } from 'vue-router';
import { fuelTankApi } from '../../../api/fuel/tanks';
import { fuelDeliveryApi } from '../../../api/fuel/deliveries';
import { ArrowLeft, RefreshCw, X } from 'lucide-vue-next';

const VirtualTankViewer = defineAsyncComponent(() => import('../../../components/VirtualTankViewer.vue'));

const route = useRoute();
const tank = ref(null);
const suppliers = ref([]);
const showLevelModal = ref(false);
const showDeliveryModal = ref(false);
const levelForm = ref({ current_level: 0 });
const deliveryForm = ref({
    supplier_id: '', fuel_type: 'diesel',
    quantity: 0, unit_price: 0, total_amount: 0,
    invoice_reference: '', delivered_at: new Date().toISOString().slice(0, 16),
});

const capacityFormatted = computed(() => Number(tank.value?.capacity || 0).toLocaleString());
const currentLevelFormatted = computed(() => Number(tank.value?.current_level || 0).toLocaleString());
const thresholdFormatted = computed(() => tank.value?.reorder_threshold != null ? Number(tank.value.reorder_threshold).toLocaleString() : '—');
const spaceAvailable = computed(() => Math.max(0, Number(tank.value?.capacity || 0) - Number(tank.value?.current_level || 0)));
const deliveredTotal = computed(() => Number(tank.value?.deliveries_sum_quantity || 0));
const dispensedTotal = computed(() => Number(tank.value?.dispenses_sum_quantity || 0));
const isLow = computed(() => tank.value && tank.value.reorder_threshold != null && tank.value.current_level <= tank.value.reorder_threshold);

async function load() {
    try {
        const res = await fuelTankApi.show(route.params.id);
        tank.value = res.data;
    } catch (e) { console.error(e); }
}

async function loadSuppliers() {
    try {
        const { vendorsApi } = await import('../../../api/workshop/vendors');
        const res = await vendorsApi.list();
        suppliers.value = res.data;
    } catch (e) { console.error(e); }
}

function openLevelModal() {
    levelForm.value = { current_level: Number(tank.value?.current_level || 0) };
    showLevelModal.value = true;
}

function openDeliveryModal() {
    deliveryForm.value = {
        supplier_id: '',
        fuel_type: tank.value?.fuel_type || 'diesel',
        quantity: 0,
        unit_price: 0,
        total_amount: 0,
        invoice_reference: '',
        delivered_at: new Date().toISOString().slice(0, 16),
    };
    showDeliveryModal.value = true;
}

async function updateLevel() {
    try {
        await fuelTankApi.updateLevel(route.params.id, { current_level: levelForm.value.current_level });
        showLevelModal.value = false;
        await load();
    } catch (e) { console.error(e); }
}

async function recordDelivery() {
    try {
        await fuelDeliveryApi.store({ ...deliveryForm.value, tank_id: route.params.id });
        showDeliveryModal.value = false;
        await load();
    } catch (e) { console.error(e); }
}

onMounted(() => { load(); loadSuppliers(); });
</script>
