<template>
    <div class="h-screen flex flex-col bg-slate-50 overflow-hidden relative">

        <Transition name="fade">
            <div v-if="confirm.show" class="fixed inset-0 z-[120] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm no-print">
                <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full p-6 border border-slate-200">
                    <div class="flex items-center gap-4 mb-4 text-amber-600">
                        <div class="p-3 bg-amber-100 rounded-full">
                            <AlertTriangle class="w-6 h-6" />
                        </div>
                        <h3 class="text-lg font-black uppercase tracking-tight text-slate-800">Confirm Unpair</h3>
                    </div>
                    <p class="text-sm text-slate-500 mb-6 font-medium leading-relaxed">
                        Are you sure you want to <strong>remove</strong> this assignment? This will end the active record for this asset.
                    </p>
                    <div class="flex gap-3">
                        <button @click="cancelUpdate" class="flex-1 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-black rounded-lg transition-all">CANCEL</button>
                        <button @click="proceedWithUpdate" class="flex-1 px-4 py-2.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-black rounded-lg shadow-lg transition-all">YES, UNPAIR</button>
                    </div>
                </div>
            </div>
        </Transition>

        <Transition name="slide">
            <div v-if="historyPanel.show" class="fixed inset-y-0 right-0 w-80 bg-white shadow-2xl z-[110] border-l border-slate-200 flex flex-col no-print">
                <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                    <div>
                        <h2 class="font-black text-slate-800 uppercase tracking-tight">Unit History</h2>
                        <p class="text-xs font-bold text-indigo-600">{{ historyPanel.vehiclePlate }}</p>
                    </div>
                    <button @click="historyPanel.show = false" class="p-2 hover:bg-slate-200 rounded-full transition-colors">
                        <X class="w-5 h-5 text-slate-400" />
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto p-6 space-y-8 custom-scrollbar">
                    <section>
                        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                            <User class="w-3 h-3" /> Recent Drivers
                        </h3>
                        <div v-for="(h, i) in historyData.drivers" :key="'d'+i" class="mb-4 pb-4 border-b border-slate-50 last:border-0">
                            <p class="text-xs font-black text-slate-800">{{ h.name }}</p>
                            <p class="text-[10px] text-slate-400 mt-1 font-medium">{{ h.start_date }} → {{ h.end_date || 'Present' }}</p>
                        </div>
                        <p v-if="!historyData.drivers.length" class="text-xs italic text-slate-400">No driver history found.</p>
                    </section>

                    <section>
                        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                            <Container class="w-3 h-3" /> Recent Trailers
                        </h3>
                        <div v-for="(t, i) in historyData.trailers" :key="'t'+i" class="mb-4 pb-4 border-b border-slate-50 last:border-0">
                            <p class="text-xs font-black text-slate-800">{{ t.plate_number }}</p>
                            <p class="text-[10px] text-slate-400 mt-1 font-medium">{{ t.assigned_at }} → {{ t.unassigned_at || 'Present' }}</p>
                        </div>
                        <p v-if="!historyData.trailers.length" class="text-xs italic text-slate-400">No trailer history found.</p>
                    </section>
                </div>
            </div>
        </Transition>

        <Transition name="slide-fade">
            <div v-if="notification.show" class="fixed bottom-8 right-8 z-[130] flex items-center gap-3 px-6 py-3 bg-slate-900 text-white rounded-xl shadow-2xl border border-slate-700 no-print">
                <div class="p-1 bg-emerald-500 rounded-full"><Check class="w-3 h-3 text-white" /></div>
                <span class="text-sm font-bold">{{ notification.message }}</span>
            </div>
        </Transition>

        <header class="bg-white border-b border-slate-200 px-8 py-4 flex flex-wrap items-center justify-between gap-4 shadow-sm z-10 no-print">
            <div class="flex items-center gap-4">
                <div class="p-2 bg-indigo-600 rounded-lg"><Truck class="w-6 h-6 text-white" /></div>
                <div>
                    <h1 class="text-xl font-black text-slate-800 tracking-tight uppercase leading-none">Fleet Control</h1>
                    <p class="text-[10px] font-bold text-slate-400 uppercase mt-1">Operational Dispatch</p>
                </div>
                <div class="relative ml-4">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                    <input v-model="searchQuery" type="text" placeholder="Search unit, driver, or trailer..." class="pl-10 pr-4 py-2 w-80 bg-slate-100 border-none rounded-full text-sm focus:ring-2 focus:ring-indigo-500 transition-all" />
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button @click="exportToExcel" class="flex items-center gap-2 px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-black rounded-lg shadow-md transition-all active:scale-95">
                    <FileSpreadsheet class="w-4 h-4" /> EXCEL
                </button>
                <button @click="printBoard" class="flex items-center gap-2 px-4 py-2 bg-slate-700 hover:bg-slate-800 text-white text-xs font-black rounded-lg shadow-md transition-all active:scale-95">
                    <Printer class="w-4 h-4" /> PRINT
                </button>
            </div>
        </header>

        <div class="px-8 py-3 bg-slate-800 text-white text-[10px] font-black uppercase tracking-widest grid grid-cols-12 gap-4 shadow-lg z-10">
            <div class="col-span-2">Power Unit</div>
            <div class="col-span-2">Info</div>
            <div class="col-span-3">Assigned Driver</div>
            <div class="col-span-4">Attached Trailer</div>
            <div class="col-span-1 text-right">Logs</div>
        </div>

        <div class="flex-1 overflow-y-auto px-8 py-4 space-y-2 custom-scrollbar">
            <div v-for="v in filteredVehicles" :key="v.id" class="bg-white border border-slate-200 rounded-lg p-3 grid grid-cols-12 gap-4 items-center hover:border-indigo-300 hover:shadow-sm transition-all group page-break-inside-avoid">
                <div class="col-span-2">
                    <span class="font-black text-slate-900 uppercase">{{ v.plate_number }}</span>
                    <div class="text-[8px] font-bold px-1.5 py-0.5 rounded inline-block ml-2 uppercase" :class="v.status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'">{{ v.status }}</div>
                </div>
                <div class="col-span-2 text-xs text-slate-500 italic">{{ v.make }} {{ v.model }}</div>

                <div class="col-span-3 relative">
                    <User class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3 h-3 text-slate-400 no-print" />
                    <select :value="v.current_driver ? v.current_driver.id : null" @change="handleSelectChange(v.id, $event.target.value, 'driver')" class="w-full pl-7 pr-2 py-1.5 bg-slate-50 border-slate-200 rounded text-xs font-bold text-slate-700 appearance-none focus:ring-1 focus:ring-indigo-500">
                        <option :value="null">-- STANDBY --</option>
                        <option v-if="v.current_driver" :value="v.current_driver.id" selected>{{ v.current_driver.name }}</option>
                        <option v-for="d in availableDrivers" :key="d.id" :value="d.id">{{ d.user.name }}</option>
                    </select>
                </div>

                <div class="col-span-4 relative">
                    <Container class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3 h-3 text-slate-400 no-print" />
                    <select :value="v.current_assignment ? v.current_assignment.trailer_id : null" @change="handleSelectChange(v.id, $event.target.value, 'trailer')" class="w-full pl-7 pr-2 py-1.5 bg-slate-50 border-slate-200 rounded text-xs font-bold text-slate-700 appearance-none focus:ring-1 focus:ring-emerald-500">
                        <option :value="null">-- BOBTAIL --</option>
                        <option v-if="v.current_assignment" :value="v.current_assignment.trailer_id" selected>{{ v.current_assignment.trailer.plate_number }}</option>
                        <option v-for="t in availableTrailers" :key="t.id" :value="t.id">{{ t.plate_number }} ({{ t.capacity_weight }}kg)</option>
                    </select>
                </div>

                <div class="col-span-1 flex justify-end no-print">
                    <button @click="showHistory(v)" class="p-2 hover:bg-slate-100 rounded-full transition-colors text-slate-400 hover:text-indigo-600">
                        <History class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, reactive } from 'vue';
import axios from 'axios';
import { Search, User, Container, FileSpreadsheet, Printer, Truck, Check, AlertTriangle, History, X } from 'lucide-vue-next';

// --- STATE ---
const vehicles = ref([]);
const availableDrivers = ref([]);
const availableTrailers = ref([]);
const searchQuery = ref('');
const notification = reactive({ show: false, message: '' });
const confirm = reactive({ show: false, payload: null });
const historyPanel = reactive({ show: false, vehiclePlate: '' });
const historyData = ref({ drivers: [], trailers: [] });

// --- METHODS ---
const triggerNotification = (msg) => {
    notification.message = msg;
    notification.show = true;
    setTimeout(() => { notification.show = false; }, 3000);
};

const loadData = async () => {
    const { data } = await axios.get('/api/portal/dispatch');
    vehicles.value = data.vehicles;
    availableDrivers.value = data.available_drivers;
    availableTrailers.value = data.available_trailers;
};

const handleSelectChange = (vehicleId, targetId, type) => {
    const isUnpairing = !targetId || targetId === 'null';
    if (isUnpairing) {
        confirm.payload = { vehicleId, targetId: null, type };
        confirm.show = true;
    } else {
        executeRecordUpdate(vehicleId, targetId, type);
    }
};

const proceedWithUpdate = () => {
    if (confirm.payload) {
        const { vehicleId, targetId, type } = confirm.payload;
        executeRecordUpdate(vehicleId, targetId, type);
    }
    confirm.show = false;
};

const cancelUpdate = () => {
    confirm.show = false;
    confirm.payload = null;
    loadData();
};

const executeRecordUpdate = async (vehicleId, targetId, type) => {
    try {
        const payload = { vehicle_id: vehicleId };
        type === 'driver' ? payload.driver_id = targetId : payload.trailer_id = targetId;
        await axios.post('/api/portal/dispatch/pair', payload);
        await loadData();
        triggerNotification(`${type.charAt(0).toUpperCase() + type.slice(1)} record updated.`);
    } catch (e) { alert("Sync failed."); loadData(); }
};

const showHistory = async (vehicle) => {
    historyPanel.vehiclePlate = vehicle.plate_number;
    const { data } = await axios.get(`/api/portal/dispatch/history/${vehicle.id}`);
    historyData.value = data;
    historyPanel.show = true;
};

const exportToExcel = () => window.open('/api/portal/dispatch/export', '_blank');
const printBoard = () => window.print();

const filteredVehicles = computed(() => {
    if (!searchQuery.value) return vehicles.value;
    const q = searchQuery.value.toLowerCase();
    return vehicles.value.filter(v => v.plate_number.toLowerCase().includes(q) || (v.current_driver && v.current_driver.name.toLowerCase().includes(q)) || (v.current_assignment && v.current_assignment.trailer.plate_number.toLowerCase().includes(q)));
});

onMounted(loadData);
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.slide-fade-enter-active { transition: all 0.3s ease-out; }
.slide-fade-leave-active { transition: all 0.4s cubic-bezier(1, 0.5, 0.8, 1); }
.slide-fade-enter-from, .slide-fade-leave-to { transform: translateY(20px); opacity: 0; }
.slide-enter-active, .slide-leave-active { transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
.slide-enter-from, .slide-leave-to { transform: translateX(100%); }

.custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

@media print {
    header, .no-print, button, input { display: none !important; }
    .bg-slate-50 { background-color: white !important; }
    .overflow-y-auto { overflow: visible !important; height: auto !important; }
    select { border: none !important; appearance: none !important; background: transparent !important; }
    .page-break-inside-avoid { page-break-inside: avoid; }
    * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
}
</style>
