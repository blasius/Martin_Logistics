<template>
    <div class="h-screen flex flex-col bg-slate-50 overflow-hidden relative">
        <iframe ref="pdfFrame" @load="onFrameLoad" class="hidden invisible w-0 h-0 absolute"></iframe>

        <Transition name="fade">
            <div v-if="confirm.show" class="fixed inset-0 z-[120] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm no-print">
                <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full p-6 border border-slate-200">
                    <div class="flex items-center gap-4 mb-4" :class="confirm.type === 'maintenance' ? 'text-rose-600' : 'text-amber-600'">
                        <div class="p-3 rounded-full" :class="confirm.type === 'maintenance' ? 'bg-rose-100' : 'bg-amber-100'">
                            <component :is="confirm.type === 'maintenance' ? Wrench : AlertTriangle" class="w-6 h-6" />
                        </div>
                        <h3 class="text-lg font-black uppercase tracking-tight text-slate-800">
                            {{ confirm.type === 'maintenance' ? 'Send to Shop?' : 'Confirm Unpair' }}
                        </h3>
                    </div>
                    <p class="text-sm text-slate-500 mb-6 font-medium leading-relaxed">
                        {{ confirm.type === 'maintenance'
                        ? 'This will instantly mark the unit as MAINTENANCE and unpair all active drivers and trailers.'
                        : 'Are you sure you want to remove this assignment?'
                        }}
                    </p>
                    <div class="flex gap-3">
                        <button @click="cancelUpdate" class="flex-1 px-4 py-2.5 bg-slate-100 text-slate-600 text-xs font-black rounded-lg">CANCEL</button>
                        <button @click="proceedWithUpdate"
                                class="flex-1 px-4 py-2.5 text-white text-xs font-black rounded-lg shadow-lg"
                                :class="confirm.type === 'maintenance' ? 'bg-rose-600' : 'bg-amber-500'">
                            YES, PROCEED
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <header class="bg-white border-b border-slate-200 px-8 py-4 flex flex-wrap items-center justify-between gap-4 shadow-sm z-10 no-print">
            <div class="flex items-center gap-4">
                <div class="p-2 bg-indigo-600 rounded-lg"><Truck class="w-6 h-6 text-white" /></div>
                <div>
                    <h1 class="text-xl font-black text-slate-800 tracking-tight uppercase leading-none">Fleet Control</h1>
                    <div class="flex items-center gap-2 mt-1">
                        <p class="text-[10px] font-bold text-slate-400 uppercase">Fleet Operations Hub</p>
                        <span v-if="!canEdit" class="text-[8px] bg-amber-50 text-amber-600 px-2 py-0.5 rounded-full font-black border border-amber-100 uppercase flex items-center gap-1">
                            <Lock class="w-2.5 h-2.5" /> Read Only Mode
                        </span>
                    </div>
                </div>
                <div class="relative ml-4">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                    <input v-model="searchQuery" type="text" placeholder="Search plate or driver..." class="pl-10 pr-4 py-2 w-80 bg-slate-100 border-none rounded-full text-sm focus:ring-2 focus:ring-indigo-500 transition-all" />
                </div>
            </div>
            <div class="flex items-center gap-3">
                <button @click="exportToExcel" class="flex items-center gap-2 px-4 py-2 bg-emerald-700 text-white text-xs font-black rounded-lg shadow-md hover:bg-emerald-800 transition-all">
                    <FileSpreadsheet class="w-4 h-4" /> EXCEL
                </button>
                <button @click="printBoard" :disabled="isPrinting" class="flex items-center gap-2 px-4 py-2 bg-slate-700 text-white text-xs font-black rounded-lg shadow-md hover:bg-slate-800 transition-all">
                    <Printer v-if="!isPrinting" class="w-4 h-4" />
                    <span v-else class="w-3 h-3 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                    {{ isPrinting ? 'GENERATING...' : 'PRINT' }}
                </button>
            </div>
        </header>

        <div class="px-8 py-3 bg-slate-800 text-white text-[10px] font-black uppercase tracking-widest grid grid-cols-12 gap-4 shadow-lg z-10">
            <div class="col-span-2">Unit ID</div>
            <div class="col-span-2">Specifications</div>
            <div class="col-span-3">Current Driver</div>
            <div class="col-span-3">Attached Trailer</div>
            <div class="col-span-2 text-right">Actions</div>
        </div>

        <div class="flex-1 overflow-y-auto px-8 py-4 space-y-2 custom-scrollbar">
            <div v-for="v in filteredVehicles" :key="v.id"
                 class="bg-white border border-slate-200 rounded-lg p-3 grid grid-cols-12 gap-4 items-center group transition-all"
                 :class="v.status === 'maintenance' ? 'opacity-75 bg-slate-50' : 'hover:border-indigo-300 shadow-sm'">

                <div class="col-span-2">
                    <span class="font-black text-slate-900 uppercase tracking-tighter">{{ v.plate_number }}</span>
                    <div class="text-[8px] font-black px-1.5 py-0.5 rounded inline-block ml-2 uppercase"
                         :class="v.status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'">{{ v.status }}</div>
                </div>

                <div class="col-span-2 text-xs text-slate-500 italic font-medium">{{ v.make }} {{ v.model }}</div>

                <div class="col-span-3 relative">
                    <select
                        :disabled="v.status === 'maintenance' || !canEdit"
                        :value="v.current_driver ? v.current_driver.id : null"
                        @change="handleSelectChange(v.id, $event.target.value, 'driver')"
                        class="w-full pl-3 pr-2 py-1.5 border-slate-200 rounded text-xs font-black text-slate-700 appearance-none focus:ring-1 focus:ring-indigo-500 transition-all"
                        :class="!canEdit ? 'bg-slate-100 text-slate-400 border-dashed cursor-not-allowed' : 'bg-slate-50'">
                        <option :value="null">-- STANDBY --</option>
                        <option v-if="v.current_driver" :value="v.current_driver.id" selected>{{ v.current_driver.name }}</option>
                        <option v-for="d in availableDrivers" :key="d.id" :value="d.id">{{ d.user.name }}</option>
                    </select>
                </div>

                <div class="col-span-3 relative">
                    <select
                        :disabled="v.status === 'maintenance' || !canEdit"
                        :value="v.current_assignment ? v.current_assignment.trailer_id : null"
                        @change="handleSelectChange(v.id, $event.target.value, 'trailer')"
                        class="w-full pl-3 pr-2 py-1.5 border-slate-200 rounded text-xs font-black text-slate-700 appearance-none focus:ring-1 focus:ring-emerald-500 transition-all"
                        :class="!canEdit ? 'bg-slate-100 text-slate-400 border-dashed cursor-not-allowed' : 'bg-slate-50'">
                        <option :value="null">-- BOBTAIL --</option>
                        <option v-if="v.current_assignment" :value="v.current_assignment.trailer_id" selected>{{ v.current_assignment.trailer.plate_number }}</option>
                        <option v-for="t in availableTrailers" :key="t.id" :value="t.id">{{ t.plate_number }}</option>
                    </select>
                </div>

                <div class="col-span-2 flex justify-end gap-1 no-print">
                    <template v-if="canEdit">
                        <button v-if="v.status === 'active'" @click="confirmMaintenance(v)" class="p-2 hover:bg-rose-50 text-slate-300 hover:text-rose-600 rounded-lg" title="Send to Maintenance">
                            <Wrench class="w-4 h-4" />
                        </button>
                        <button v-else @click="returnToService(v)" class="p-2 hover:bg-emerald-50 text-slate-300 hover:text-emerald-600 rounded-lg" title="Return to Service">
                            <CheckCircle class="w-4 h-4" />
                        </button>
                    </template>

                    <button @click="showHistory(v)" class="p-2 hover:bg-slate-100 text-slate-300 hover:text-indigo-600 rounded-lg" title="View History">
                        <History class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, reactive } from 'vue';
import { api } from "../../../plugins/axios";
import { Search, FileSpreadsheet, Printer, Truck, Check, CheckCircle, AlertTriangle, History, X, Wrench, Lock } from 'lucide-vue-next';

// --- STATE ---
const vehicles = ref([]);
const availableDrivers = ref([]);
const availableTrailers = ref([]);
const searchQuery = ref('');
const canEdit = ref(false); // Changed to simple boolean from backend
const notification = reactive({ show: false, message: '' });
const confirm = reactive({ show: false, payload: null, type: 'unpair' });
const historyPanel = reactive({ show: false, vehiclePlate: '' });
const historyData = ref({ drivers: [], trailers: [] });
const pdfFrame = ref(null);
const isPrinting = ref(false);

const loadData = async () => {
    try {
        const { data } = await api.get('portal/dispatch');
        vehicles.value = data.vehicles;
        availableDrivers.value = data.available_drivers;
        availableTrailers.value = data.available_trailers;
        canEdit.value = data.can_edit; // Capture boolean from backend
    } catch (e) {
        console.error("Failed to load page data:", e.message);
    }
};

const printBoard = async () => {
    if (!pdfFrame.value) return;
    isPrinting.value = true;
    try {
        const { data } = await api.get('portal/dispatch/print-url');
        pdfFrame.value.src = data.url;
    } catch (e) {
        console.error("Print fetch failed", e);
        isPrinting.value = false;
    }
};

const onFrameLoad = () => {
    if (!pdfFrame.value || !isPrinting.value) return;
    const frame = pdfFrame.value;
    if (frame.src && frame.src.includes('secure-print')) {
        setTimeout(() => {
            frame.contentWindow.focus();
            frame.contentWindow.print();
            isPrinting.value = false;
        }, 500);
    }
};

const handleSelectChange = (vehicleId, targetId, type) => {
    if (!canEdit.value) return;
    const isUnpairing = !targetId || targetId === 'null';
    if (isUnpairing) {
        confirm.payload = { vehicleId, targetId: null, type };
        confirm.type = 'unpair';
        confirm.show = true;
    } else {
        executeRecordUpdate(vehicleId, targetId, type);
    }
};

const confirmMaintenance = (vehicle) => {
    if (!canEdit.value) return;
    confirm.payload = { vehicleId: vehicle.id, action: 'maintenance' };
    confirm.type = 'maintenance';
    confirm.show = true;
};

const returnToService = async (vehicle) => {
    if (!canEdit.value) return;
    await api.post('/portal/dispatch/activate', { vehicle_id: vehicle.id });
    triggerNotification("Unit returned to Active Service");
    loadData();
};

const proceedWithUpdate = async () => {
    const p = confirm.payload;
    if (p.action === 'maintenance') {
        await api.post('/portal/dispatch/maintenance', { vehicle_id: p.vehicleId });
        triggerNotification("Unit moved to Maintenance");
    } else {
        await executeRecordUpdate(p.vehicleId, p.targetId, p.type);
    }
    confirm.show = false;
    loadData();
};

const executeRecordUpdate = async (vehicleId, targetId, type) => {
    await api.post('/portal/dispatch/pair', { vehicle_id: vehicleId, [type + '_id']: targetId });
    triggerNotification(`${type} updated`);
    loadData();
};

const showHistory = async (vehicle) => {
    historyPanel.vehiclePlate = vehicle.plate_number;
    const { data } = await api.get(`/portal/dispatch/history/${vehicle.id}`);
    historyData.value = data;
    historyPanel.show = true;
};

const triggerNotification = (msg) => {
    notification.message = msg;
    notification.show = true;
    setTimeout(() => notification.show = false, 3000);
};

const cancelUpdate = () => { confirm.show = false; loadData(); };
const exportToExcel = () => window.open('/portal/dispatch/export', '_blank');

const filteredVehicles = computed(() => {
    const q = searchQuery.value.toLowerCase();
    return vehicles.value.filter(v =>
        v.plate_number.toLowerCase().includes(q) ||
        (v.current_driver && v.current_driver.name.toLowerCase().includes(q))
    );
});

onMounted(loadData);
</script>
