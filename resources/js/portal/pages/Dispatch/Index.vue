<template>
    <div class="h-screen flex flex-col bg-slate-50 overflow-hidden relative">
        <Transition name="slide-fade">
            <div v-if="notification.show"
                 class="fixed bottom-8 right-8 z-50 flex items-center gap-3 px-6 py-3 bg-slate-900 text-white rounded-xl shadow-2xl border border-slate-700 no-print">
                <div class="p-1 bg-emerald-500 rounded-full">
                    <Check class="w-4 h-4 text-white" />
                </div>
                <span class="text-sm font-bold tracking-wide">{{ notification.message }}</span>
            </div>
        </Transition>

        <header class="bg-white border-b border-slate-200 px-8 py-4 flex flex-wrap items-center justify-between gap-4 shadow-sm z-10 no-print">
            <div class="flex items-center gap-4">
                <div class="p-2 bg-indigo-600 rounded-lg">
                    <Truck class="w-6 h-6 text-white" />
                </div>
                <div>
                    <h1 class="text-xl font-black text-slate-800 tracking-tight uppercase leading-none">Fleet Control</h1>
                    <p class="text-[10px] font-bold text-slate-400 uppercase mt-1">Real-time Dispatch Command</p>
                </div>

                <div class="relative ml-4">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search plate, driver, or trailer..."
                        class="pl-10 pr-4 py-2 w-80 bg-slate-100 border-none rounded-full text-sm focus:ring-2 focus:ring-indigo-500 transition-all"
                    />
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button @click="exportToExcel" class="flex items-center gap-2 px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-black rounded-lg shadow-md transition-all active:scale-95">
                    <FileSpreadsheet class="w-4 h-4" />
                    EXCEL
                </button>

                <button @click="printBoard" class="flex items-center gap-2 px-4 py-2 bg-slate-700 hover:bg-slate-800 text-white text-xs font-black rounded-lg shadow-md transition-all active:scale-95">
                    <Printer class="w-4 h-4" />
                    PRINT
                </button>
            </div>
        </header>

        <div class="px-8 py-3 bg-slate-800 text-white text-[10px] font-black uppercase tracking-widest grid grid-cols-12 gap-4 shadow-lg z-10">
            <div class="col-span-2">Power Unit (Plate)</div>
            <div class="col-span-2">Vehicle Info</div>
            <div class="col-span-4">Assigned Driver</div>
            <div class="col-span-4">Attached Trailer</div>
        </div>

        <div class="flex-1 overflow-y-auto px-8 py-4 space-y-2 custom-scrollbar">
            <div v-for="v in filteredVehicles" :key="v.id"
                 class="bg-white border border-slate-200 rounded-lg p-3 grid grid-cols-12 gap-4 items-center hover:border-indigo-300 hover:shadow-sm transition-all group page-break-inside-avoid">

                <div class="col-span-2">
                    <span class="font-black text-slate-900 group-hover:text-indigo-600 transition-colors">{{ v.plate_number }}</span>
                    <div class="text-[9px] font-bold px-1.5 py-0.5 rounded inline-block ml-2 uppercase"
                         :class="v.status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'">
                        {{ v.status }}
                    </div>
                </div>

                <div class="col-span-2">
                    <span class="text-xs text-slate-500 font-medium italic">{{ v.make }} {{ v.model }}</span>
                </div>

                <div class="col-span-4 relative">
                    <User class="absolute left-3 top-1/2 -translate-y-1/2 w-3 h-3 text-slate-400 no-print" />
                    <select
                        @change="update(v.id, $event.target.value, 'driver')"
                        class="w-full pl-8 pr-2 py-1.5 bg-slate-50 border-slate-200 rounded text-xs font-bold text-slate-700 appearance-none focus:ring-1 focus:ring-indigo-500">
                        <option :value="null">-- STANDBY / NO DRIVER --</option>
                        <option v-if="v.current_driver" :value="null" selected>{{ v.current_driver.name }}</option>
                        <option v-for="d in availableDrivers" :key="d.id" :value="d.id">{{ d.user.name }}</option>
                    </select>
                </div>

                <div class="col-span-4 relative">
                    <Container class="absolute left-3 top-1/2 -translate-y-1/2 w-3 h-3 text-slate-400 no-print" />
                    <select
                        @change="update(v.id, $event.target.value, 'trailer')"
                        class="w-full pl-8 pr-2 py-1.5 bg-slate-50 border-slate-200 rounded text-xs font-bold text-slate-700 appearance-none focus:ring-1 focus:ring-emerald-500">
                        <option :value="null">-- BOBTAIL / NO TRAILER --</option>
                        <option v-if="v.current_assignment" :value="v.current_assignment.trailer_id" selected>
                            {{ v.current_assignment.trailer.plate_number }}
                        </option>
                        <option v-for="t in availableTrailers" :key="t.id" :value="t.id">{{ t.plate_number }}</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, reactive } from 'vue';
import axios from 'axios';
import { Search, User, Container, FileSpreadsheet, Printer, Truck, Check } from 'lucide-vue-next';

const vehicles = ref([]);
const availableDrivers = ref([]);
const availableTrailers = ref([]);
const searchQuery = ref('');

// NOTIFICATION LOGIC
const notification = reactive({
    show: false,
    message: ''
});

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

const update = async (vehicleId, targetId, type) => {
    try {
        const payload = { vehicle_id: vehicleId };
        type === 'driver' ? payload.driver_id = targetId : payload.trailer_id = targetId;
        await axios.post('/api/portal/dispatch/pair', payload);
        await loadData();
        triggerNotification(`Vehicle ${type} updated successfully`);
    } catch (e) {
        console.error(e);
    }
};

const filteredVehicles = computed(() => {
    if (!searchQuery.value) return vehicles.value;
    const q = searchQuery.value.toLowerCase();
    return vehicles.value.filter(v =>
        v.plate_number.toLowerCase().includes(q) ||
        (v.current_driver && v.current_driver.name.toLowerCase().includes(q))
    );
});

const exportToExcel = () => window.open('/api/portal/dispatch/export', '_blank');
const printBoard = () => window.print();

onMounted(loadData);
</script>

<style scoped>
.slide-fade-enter-active { transition: all 0.3s ease-out; }
.slide-fade-leave-active { transition: all 0.4s cubic-bezier(1, 0.5, 0.8, 1); }
.slide-fade-enter-from, .slide-fade-leave-to {
    transform: translateY(20px);
    opacity: 0;
}

.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

@media print {
    header, .no-print, button, input { display: none !important; }
    .bg-slate-50 { background-color: white !important; }
    .overflow-y-auto { overflow: visible !important; height: auto !important; }
    * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
}
</style>
