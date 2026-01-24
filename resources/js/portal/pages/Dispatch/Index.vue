<template>
    <div class="h-screen flex flex-col bg-slate-50 overflow-hidden">
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
                <div class="flex gap-4 mr-4 text-right border-r pr-6 border-slate-200">
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase">Idle Drivers</p>
                        <p class="text-sm font-black text-indigo-600">{{ availableDrivers.length }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase">Idle Trailers</p>
                        <p class="text-sm font-black text-emerald-600">{{ availableTrailers.length }}</p>
                    </div>
                </div>

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
            <div v-if="filteredVehicles.length === 0" class="py-20 text-center">
                <div class="inline-block p-4 bg-slate-100 rounded-full mb-4">
                    <Search class="w-8 h-8 text-slate-300" />
                </div>
                <p class="text-slate-400 font-bold uppercase text-xs tracking-widest">No matching units found</p>
            </div>

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
                        class="w-full pl-8 pr-2 py-1.5 bg-slate-50 border-slate-200 rounded text-xs font-bold text-slate-700 appearance-none focus:ring-1 focus:ring-indigo-500 disabled:opacity-50">
                        <option :value="null">-- STANDBY / NO DRIVER --</option>
                        <option v-if="v.current_driver" :value="null" selected>{{ v.current_driver.name }} (Current)</option>
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
                            {{ v.current_assignment.trailer.plate_number }} ({{ v.current_assignment.trailer.type }})
                        </option>
                        <option v-for="t in availableTrailers" :key="t.id" :value="t.id">{{ t.plate_number }} - {{ t.capacity_weight }}kg</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { Search, User, Container, FileSpreadsheet, Printer, Truck } from 'lucide-vue-next';

// --- STATE ---
const vehicles = ref([]);
const availableDrivers = ref([]);
const availableTrailers = ref([]);
const searchQuery = ref('');

// --- DATA FETCHING ---
const loadData = async () => {
    try {
        const { data } = await axios.get('/api/portal/dispatch');
        vehicles.value = data.vehicles;
        availableDrivers.value = data.available_drivers;
        availableTrailers.value = data.available_trailers;
    } catch (e) {
        console.error("Failed to sync fleet board.");
    }
};

// --- COMPUTED SEARCH ---
const filteredVehicles = computed(() => {
    if (!searchQuery.value) return vehicles.value;
    const q = searchQuery.value.toLowerCase();
    return vehicles.value.filter(v =>
        v.plate_number.toLowerCase().includes(q) ||
        (v.current_driver && v.current_driver.name.toLowerCase().includes(q)) ||
        (v.current_assignment && v.current_assignment.trailer.plate_number.toLowerCase().includes(q))
    );
});

// --- ACTIONS ---
const exportToExcel = () => window.open('/api/portal/dispatch/export', '_blank');
const printBoard = () => window.print();

const update = async (vehicleId, targetId, type) => {
    try {
        const payload = { vehicle_id: vehicleId };
        type === 'driver' ? payload.driver_id = targetId : payload.trailer_id = targetId;

        await axios.post('/api/portal/dispatch/pair', payload);
        // Instant reload to update dropdown pools
        await loadData();
    } catch (e) {
        alert("Operation failed. The asset may have been modified by another user.");
        loadData();
    }
};

onMounted(loadData);
</script>

<style scoped>
/* CUSTOM SCROLLBAR FOR WEB */
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

/* PRINT ENGINE OPTIMIZATION */
@media print {
    header, .no-print, button, input, .absolute {
        display: none !important;
    }

    .bg-slate-50 { background-color: white !important; }

    .overflow-y-auto {
        overflow: visible !important;
        height: auto !important;
    }

    .h-screen { height: auto !important; }

    select {
        border: none !important;
        background: transparent !important;
        appearance: none !important;
        padding-left: 0 !important;
        font-size: 10pt !important;
    }

    .page-break-inside-avoid {
        page-break-inside: avoid;
    }

    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    /* Print Header Branding */
    .flex-1::before {
        content: "MARTIN LOGISTICS - DISPATCH STATUS REPORT (" attr(data-date) ")";
        display: block;
        text-align: center;
        font-size: 14pt;
        font-weight: bold;
        margin-bottom: 20px;
        border-bottom: 2px solid #334155;
        padding-bottom: 10px;
    }
}
</style>
