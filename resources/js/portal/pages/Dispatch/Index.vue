<template>
    <div class="h-screen flex flex-col bg-slate-50 overflow-hidden relative">
        <iframe ref="pdfFrame" @load="onFrameLoad" class="hidden invisible w-0 h-0 absolute"></iframe>

        <Transition name="fade">
            <div v-if="infoModal.show" class="fixed inset-0 z-[160] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
                <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full border border-slate-200 overflow-hidden">
                    <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                        <h3 class="font-black text-slate-800 uppercase text-sm tracking-widest">Manifest Details</h3>
                        <button @click="infoModal.show = false" class="text-slate-400 hover:text-slate-600"><X class="w-5 h-5" /></button>
                    </div>
                    <div class="p-6 space-y-4 text-xs">
                        <div class="flex justify-between border-b border-slate-50 pb-2">
                            <span class="text-slate-400 font-bold uppercase tracking-tighter">Unit</span>
                            <span class="font-black text-slate-800">{{ infoModal.data.vehicle }}/{{ infoModal.data.trailer }}</span>
                        </div>
                        <div class="flex justify-between border-b border-slate-50 pb-2">
                            <span class="text-slate-400 font-bold uppercase tracking-tighter">Driver</span>
                            <span class="font-black text-slate-800">{{ infoModal.data.driver }}</span>
                        </div>
                        <div class="flex justify-between border-b border-slate-50 pb-2">
                            <span class="text-slate-400 font-bold uppercase tracking-tighter">Passport</span>
                            <span class="font-black text-slate-800">{{ infoModal.data.passport }}</span>
                        </div>
                        <div class="flex justify-between border-b border-slate-50 pb-2">
                            <span class="text-slate-400 font-bold uppercase tracking-tighter">License</span>
                            <span class="font-black text-slate-800">{{ infoModal.data.license }}</span>
                        </div>
                        <div class="flex justify-between border-b border-slate-50 pb-2">
                            <span class="text-slate-400 font-bold uppercase tracking-tighter">Phone</span>
                            <span class="font-black text-slate-800">{{ infoModal.data.phone }}</span>
                        </div>
                    </div>
                    <div class="p-4 bg-slate-50">
                        <button @click="copyToClipboard" class="w-full bg-indigo-600 text-white py-3 rounded-xl font-black text-xs uppercase flex items-center justify-center gap-2 hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-200">
                            <Copy class="w-4 h-4" /> Copy for Dispatch
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <Transition name="fade">
            <div v-if="confirm.show" class="fixed inset-0 z-[150] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm no-print">
                <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-8 border border-slate-200">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="p-3 rounded-full" :class="confirm.severity === 'danger' ? 'bg-rose-100 text-rose-600' : 'bg-amber-100 text-amber-600'">
                            <component :is="confirm.icon" class="w-7 h-7" />
                        </div>
                        <h3 class="text-xl font-black uppercase tracking-tight text-slate-800">{{ confirm.title }}</h3>
                    </div>
                    <div class="bg-slate-50 border border-slate-100 rounded-xl p-4 mb-8">
                        <p class="text-sm text-slate-600 font-medium leading-relaxed" v-html="confirm.message"></p>
                    </div>
                    <div class="flex gap-3">
                        <button @click="confirm.show = false" class="flex-1 px-4 py-3 bg-slate-100 text-slate-600 text-xs font-black rounded-xl uppercase">Cancel</button>
                        <button @click="proceedWithUpdate" class="flex-1 px-4 py-3 text-white text-xs font-black rounded-xl shadow-lg uppercase transition-all" :class="confirm.severity === 'danger' ? 'bg-rose-600' : 'bg-amber-600'">Confirm</button>
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
                    <button @click="historyPanel.show = false" class="p-2 hover:bg-slate-200 rounded-full transition-colors"><X class="w-5 h-5 text-slate-400" /></button>
                </div>
                <div class="flex-1 overflow-y-auto p-6 space-y-8 custom-scrollbar">
                    <section v-for="(type, key) in { 'Recent Drivers': 'drivers', 'Recent Trailers': 'trailers' }" :key="key">
                        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2">{{ key }}</h3>
                        <div v-for="(h, i) in historyData[type]" :key="i" class="mb-4 pb-4 border-b border-slate-50 last:border-0">
                            <p class="text-xs font-black text-slate-800">{{ h.name || h.plate_number }}</p>
                            <p class="text-[10px] text-slate-400 mt-1 font-medium italic">{{ h.start_date || h.assigned_at }} → {{ h.end_date || h.unassigned_at || 'Present' }}</p>
                        </div>
                    </section>
                </div>
            </div>
        </Transition>

        <Transition name="slide-fade">
            <div v-if="notification.show" class="fixed bottom-8 right-8 z-[130] flex items-center gap-3 px-6 py-3 bg-slate-900 text-white rounded-xl shadow-2xl border border-slate-700 no-print">
                <Check class="w-4 h-4 text-emerald-400" />
                <span class="text-sm font-bold">{{ notification.message }}</span>
            </div>
        </Transition>

        <header class="bg-white border-b border-slate-200 px-8 py-4 flex flex-wrap items-center justify-between gap-4 shadow-sm z-10 no-print">
            <div class="flex items-center gap-4">
                <div class="p-2 bg-indigo-600 rounded-lg"><Truck class="w-6 h-6 text-white" /></div>
                <div>
                    <h1 class="text-xl font-black text-slate-800 uppercase leading-none">Fleet Control</h1>
                    <div class="flex items-center gap-3 mt-1.5">
                        <div class="flex bg-slate-100 p-0.5 rounded-lg">
                            <button v-for="s in ['active', 'maintenance', 'inactive']" :key="s" @click="statusFilter = s"
                                    class="px-3 py-1 text-[9px] font-black uppercase rounded-md transition-all"
                                    :class="statusFilter === s ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-400 hover:text-slate-600'">{{ s }}</button>
                        </div>
                        <span v-if="!canEdit" class="text-[8px] bg-amber-50 text-amber-600 px-2 py-0.5 rounded-full font-black border border-amber-100 uppercase flex items-center gap-1"><Lock class="w-2.5 h-2.5" /> View Only</span>
                    </div>
                </div>
                <div class="relative ml-4">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                    <input v-model="searchQuery" type="text" placeholder="Search driver or plate number..." class="pl-10 pr-4 py-2 w-64 bg-slate-100 border-none rounded-full text-sm focus:ring-2 focus:ring-indigo-500" />
                </div>
            </div>
            <div class="flex items-center gap-3">
                <button @click="exportToExcel" class="flex items-center gap-2 px-4 py-2 bg-emerald-700 text-white text-xs font-black rounded-lg hover:bg-emerald-800"><FileSpreadsheet class="w-4 h-4" /> EXCEL</button>
                <button @click="printBoard" :disabled="isPrinting" class="flex items-center gap-2 px-4 py-2 bg-slate-700 text-white text-xs font-black rounded-lg hover:bg-slate-800">
                    <Printer v-if="!isPrinting" class="w-4 h-4" /><span v-else class="w-3 h-3 border-2 border-white/30 border-t-white rounded-full animate-spin"></span> PRINT
                </button>
            </div>
        </header>

        <div class="px-8 py-3 bg-slate-800 text-white text-[10px] font-black uppercase tracking-widest grid grid-cols-12 gap-4 shadow-lg z-10">
            <div class="col-span-2">Unit ID</div>
            <div class="col-span-2">Specs</div>
            <div class="col-span-3">Driver Assignment</div>
            <div class="col-span-3">Trailer Assignment</div>
            <div class="col-span-2 text-right">Actions</div>
        </div>

        <div class="flex-1 overflow-y-auto px-8 py-4 space-y-2 custom-scrollbar">
            <div v-for="v in filteredVehicles" :key="v.id"
                 class="bg-white border border-slate-200 rounded-lg p-3 grid grid-cols-12 gap-4 items-center transition-all"
                 :class="v.status !== 'active' ? 'opacity-70 bg-slate-50' : 'hover:border-indigo-300'">

                <div class="col-span-2 flex items-center gap-2">
                    <span class="font-black text-slate-900 uppercase tracking-tighter">{{ v.plate_number }}</span>
                    <button @click="showInfo(v)" class="p-1 text-slate-300 hover:text-indigo-600 transition-colors">
                        <Info class="w-4 h-4" />
                    </button>
                    <div class="text-[8px] font-black px-1.5 py-0.5 rounded inline-block uppercase"
                         :class="{
                             'bg-emerald-100 text-emerald-700': v.status === 'active',
                             'bg-amber-100 text-amber-700': v.status === 'maintenance',
                             'bg-rose-100 text-rose-700': v.status === 'inactive'
                         }">{{ v.status }}</div>
                </div>

                <div class="col-span-2 text-xs text-slate-500 font-medium">{{ v.make }} {{ v.model }}</div>

                <div class="col-span-3 relative group/search">
                    <div class="flex items-center gap-2">
                        <input type="text" v-model="rowSearch.drivers[v.id]" :disabled="v.status !== 'active' || !canEdit"
                               :placeholder="v.current_driver ? v.current_driver.name : '-- SEARCH DRIVER --'"
                               class="flex-1 pl-3 pr-8 py-1.5 bg-slate-50 border border-slate-200 rounded text-xs font-black text-slate-700 focus:ring-2 focus:ring-indigo-500 uppercase outline-none" />
                        <span v-if="v.current_driver" class="text-[8px] bg-slate-100 text-slate-500 px-1.5 py-1 rounded font-bold whitespace-nowrap">
                            {{ formatDuration(v.current_driver.start_date) }}
                        </span>
                    </div>
                    <div v-if="hasStartedTyping(v.id, 'driver')" class="absolute z-[100] w-full mt-1 bg-white border border-slate-200 rounded shadow-2xl max-h-48 overflow-y-auto hidden group-focus-within/search:block">
                        <div @mousedown="initiateAction(v, null, 'driver')" class="p-2 text-[10px] font-black text-rose-500 hover:bg-rose-50 cursor-pointer border-b uppercase">-- SET STANDBY --</div>
                        <div v-for="d in getFilteredDrivers(v.id)" :key="d.id" @mousedown="initiateAction(v, d, 'driver')" class="p-2 border-b hover:bg-slate-50 cursor-pointer flex justify-between items-center">
                            <span class="text-xs font-black text-slate-700 uppercase">{{ d.user.name }}</span>
                            <span v-if="getAssetLocation(d.user_id, 'driver')" class="text-[8px] bg-amber-100 text-amber-700 px-1.5 rounded font-black">On {{ getAssetLocation(d.user_id, 'driver') }}</span>
                        </div>
                    </div>
                </div>

                <div class="col-span-3 relative group/search">
                    <div class="flex items-center gap-2">
                        <input type="text" v-model="rowSearch.trailers[v.id]" :disabled="v.status !== 'active' || !canEdit"
                               :placeholder="v.current_assignment ? v.current_assignment.trailer.plate_number : '-- SEARCH TRAILER --'"
                               class="flex-1 pl-3 pr-8 py-1.5 bg-slate-50 border border-slate-200 rounded text-xs font-black text-slate-700 focus:ring-2 focus:ring-emerald-500 uppercase outline-none" />
                        <span v-if="v.current_assignment" class="text-[8px] bg-slate-100 text-slate-500 px-1.5 py-1 rounded font-bold whitespace-nowrap">
                            {{ formatDuration(v.current_assignment.assigned_at) }}
                        </span>
                    </div>
                    <div v-if="hasStartedTyping(v.id, 'trailer')" class="absolute z-[100] w-full mt-1 bg-white border border-slate-200 rounded shadow-2xl max-h-48 overflow-y-auto hidden group-focus-within/search:block">
                        <div @mousedown="initiateAction(v, null, 'trailer')" class="p-2 text-[10px] font-black text-rose-500 hover:bg-rose-50 cursor-pointer border-b uppercase">-- UNCOUPLE --</div>
                        <div v-for="t in getFilteredTrailers(v.id)" :key="t.id" @mousedown="initiateAction(v, t, 'trailer')" class="p-2 border-b hover:bg-slate-50 cursor-pointer flex justify-between items-center">
                            <span class="text-xs font-black text-slate-700 uppercase">{{ t.plate_number }}</span>
                            <span v-if="getAssetLocation(t.id, 'trailer')" class="text-[8px] bg-amber-100 text-amber-700 px-1.5 rounded font-black">On {{ getAssetLocation(t.id, 'trailer') }}</span>
                        </div>
                    </div>
                </div>

                <div class="col-span-2 flex justify-end gap-1 no-print">
                    <template v-if="canEdit">
                        <template v-if="v.status === 'active'">
                            <button @click="confirmMaintenance(v)" class="p-2 hover:bg-amber-50 text-slate-300 hover:text-amber-600 rounded-lg" title="To Maintenance">
                                <Wrench class="w-4 h-4" />
                            </button>
                            <button @click="confirmDeactivation(v)" class="p-2 hover:bg-rose-50 text-slate-300 hover:text-rose-600 rounded-lg" title="Deactivate">
                                <XCircle class="w-4 h-4" />
                            </button>
                        </template>
                        <button v-else @click="returnToService(v)" class="p-2 hover:bg-emerald-50 text-slate-300 hover:text-emerald-600 rounded-lg" title="Return to Service">
                            <CheckCircle class="w-4 h-4" />
                        </button>
                    </template>
                    <button @click="showHistory(v)" class="p-2 hover:bg-slate-100 text-slate-300 hover:text-indigo-600 rounded-lg">
                        <History class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, reactive, markRaw } from 'vue';
import { api } from "../../../plugins/axios";
import { Search, FileSpreadsheet, Printer, Truck, Check, CheckCircle, Info, Copy, History, X, Wrench, Lock, Link2, UserPlus, XCircle } from 'lucide-vue-next';
import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';
dayjs.extend(relativeTime);

// --- STATE ---
const vehicles = ref([]);
const availableDrivers = ref([]);
const availableTrailers = ref([]);
const searchQuery = ref('');
const statusFilter = ref('active');
const canEdit = ref(false);
const rowSearch = reactive({ drivers: {}, trailers: {} });
const notification = reactive({ show: false, message: '' });
const confirm = reactive({ show: false, title: '', message: '', severity: 'warning', icon: null, payload: null });
const historyPanel = reactive({ show: false, vehiclePlate: '' });
const historyData = ref({ drivers: [], trailers: [] });
const infoModal = reactive({ show: false, data: {} });
const isPrinting = ref(false);
const pdfFrame = ref(null);

// --- METHODS ---
const loadData = async () => {
    const { data } = await api.get('portal/dispatch');
    vehicles.value = data.vehicles;
    availableDrivers.value = data.available_drivers;
    availableTrailers.value = data.available_trailers;
    canEdit.value = data.can_edit;
};

const formatDuration = (date) => date ? dayjs(date).fromNow(true) : '';

const showInfo = (v) => {
    infoModal.data = {
        vehicle: v.plate_number,
        trailer: v.current_assignment?.trailer?.plate_number || 'N/A',
        driver: v.current_driver?.name || 'N/A',
        passport: v.current_driver?.passport_number || 'N/A',
        license: v.current_driver?.license_number || 'N/A',
        phone: v.current_driver?.phone || 'N/A'
    };
    infoModal.show = true;
};

const copyToClipboard = () => {
    const d = infoModal.data;
    const text = `UNIT: ${d.vehicle}/${d.trailer}\nDRIVER: ${d.driver}\nPASSPORT: ${d.passport}\nLICENSE: ${d.license}\nPHONE: ${d.phone}`;
    navigator.clipboard.writeText(text).then(() => {
        triggerNotification("Details copied!");
        infoModal.show = false;
    });
};

const hasStartedTyping = (vid, type) => rowSearch[type + 's'][vid] && rowSearch[type + 's'][vid].trim().length > 0;

const getAssetLocation = (id, type) => {
    const found = vehicles.value.find(v => {
        if (type === 'driver') return v.current_driver?.id === id || v.current_driver?.user_id === id;
        return v.current_assignment?.trailer_id === id;
    });
    return found ? found.plate_number : null;
};

const getFilteredDrivers = (vid) => {
    const q = (rowSearch.drivers[vid] || '').toLowerCase();
    return availableDrivers.value.filter(d => d.user.name.toLowerCase().includes(q));
};

const getFilteredTrailers = (vid) => {
    const q = (rowSearch.trailers[vid] || '').toLowerCase();
    return availableTrailers.value.filter(t => t.plate_number.toLowerCase().includes(q));
};

const initiateAction = (vehicle, asset, type) => {
    const assetId = asset?.id;
    const assetName = type === 'driver' ? asset?.user.name : asset?.plate_number;
    const assetUserId = type === 'driver' ? asset?.user_id : null;
    const currentLocation = assetId ? getAssetLocation(type === 'driver' ? assetUserId : assetId, type) : null;

    if (!assetId) {
        confirm.title = "Unpair Asset";
        confirm.message = `Remove current ${type} from unit <strong>${vehicle.plate_number}</strong>?`;
        confirm.severity = 'warning'; confirm.icon = markRaw(XCircle);
    } else if (currentLocation && currentLocation !== vehicle.plate_number) {
        confirm.title = "Transfer Alert";
        confirm.message = `<strong>${assetName}</strong> is currently on <strong>${currentLocation}</strong>. Detach from there and move to <strong>${vehicle.plate_number}</strong>?`;
        confirm.severity = 'danger'; confirm.icon = markRaw(Link2);
    } else {
        confirm.title = "Confirm Assignment";
        confirm.message = `Assign <strong>${assetName}</strong> to unit <strong>${vehicle.plate_number}</strong>?`;
        confirm.severity = 'warning'; confirm.icon = markRaw(UserPlus);
    }

    confirm.payload = { vehicleId: vehicle.id, assetId, type };
    confirm.show = true;
    rowSearch[type + 's'][vehicle.id] = '';
};

// --- NEW STATUS TOGGLE LOGIC ---
const confirmMaintenance = (v) => {
    confirm.title = "Maintenance Mode";
    confirm.message = `Mark <strong>${v.plate_number}</strong> as Maintenance? This will unpair all current assignments.`;
    confirm.severity = 'danger'; confirm.icon = markRaw(Wrench);
    confirm.payload = { vehicleId: v.id, action: 'maintenance' };
    confirm.show = true;
};

const confirmDeactivation = (v) => {
    confirm.title = "Deactivate Unit";
    confirm.message = `Mark <strong>${v.plate_number}</strong> as Inactive? This will unpair all current assignments.`;
    confirm.severity = 'danger'; confirm.icon = markRaw(XCircle);
    confirm.payload = { vehicleId: v.id, action: 'inactive' };
    confirm.show = true;
};

const proceedWithUpdate = async () => {
    const p = confirm.payload;
    if (p.action === 'maintenance') {
        await api.post('/portal/dispatch/maintenance', { vehicle_id: p.vehicleId });
        triggerNotification("Unit moved to Shop");
    } else if (p.action === 'inactive') {
        await api.post('/portal/dispatch/toggle-status', { vehicle_id: p.vehicleId, status: 'inactive' });
        triggerNotification("Unit Deactivated");
    } else {
        await api.post('/portal/dispatch/pair', { vehicle_id: p.vehicleId, [p.type + '_id']: p.assetId });
        triggerNotification(`${p.type} Updated`);
    }
    confirm.show = false;
    loadData();
};

const returnToService = async (v) => {
    // We use the new toggle-status route to force it back to active
    await api.post('/portal/dispatch/toggle-status', { vehicle_id: v.id, status: 'active' });
    triggerNotification("Unit Reactivated");
    loadData();
};

const showHistory = async (v) => {
    historyPanel.vehiclePlate = v.plate_number;
    const { data } = await api.get(`/portal/dispatch/history/${v.id}`);
    historyData.value = data;
    historyPanel.show = true;
};

const triggerNotification = (msg) => {
    notification.message = msg; notification.show = true;
    setTimeout(() => notification.show = false, 3000);
};

const exportToExcel = async () => {
    try {
        triggerNotification("Generating Excel file...");
        const response = await api.get('portal/dispatch/export', { responseType: 'blob' });
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `fleet_dispatch_${dayjs().format('YYYY-MM-DD')}.xlsx`);
        document.body.appendChild(link);
        link.click();
        link.remove();
        triggerNotification("Export Complete");
    } catch (error) {
        triggerNotification("Export failed.");
    }
};

const printBoard = async () => {
    isPrinting.value = true;
    const { data } = await api.get('portal/dispatch/print-url');
    pdfFrame.value.src = data.url;
};

const onFrameLoad = () => {
    if (!isPrinting.value) return;
    setTimeout(() => {
        pdfFrame.value.contentWindow.print();
        isPrinting.value = false;
    }, 500);
};

const filteredVehicles = computed(() => {
    let list = vehicles.value;
    list = list.filter(v => v.status === statusFilter.value);
    const q = searchQuery.value.toLowerCase();
    if (q) {
        list = list.filter(v =>
            v.plate_number.toLowerCase().includes(q) ||
            (v.current_driver && v.current_driver.name.toLowerCase().includes(q))
        );
    }
    return list;
});

onMounted(loadData);
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.slide-enter-active, .slide-leave-active { transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
.slide-enter-from, .slide-leave-to { transform: translateX(100%); }
.slide-fade-enter-active, .slide-fade-leave-active { transition: all 0.3s; }
.slide-fade-enter-from, .slide-fade-leave-to { transform: translateY(20px); opacity: 0; }
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
</style>
