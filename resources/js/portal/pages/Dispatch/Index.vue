<template>
    <div class="h-screen flex flex-col bg-slate-50 overflow-hidden">
        <header class="bg-white border-b border-slate-200 px-8 py-4 flex flex-wrap items-center justify-between gap-4 shadow-sm z-10">
            <div class="flex items-center gap-4">
                <h1 class="text-xl font-black text-slate-800 tracking-tight uppercase">Fleet Control</h1>
                <div class="relative">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search plate, driver, or trailer..."
                        class="pl-10 pr-4 py-2 w-80 bg-slate-100 border-none rounded-full text-sm focus:ring-2 focus:ring-indigo-500"
                    />
                </div>
            </div>

            <div class="flex gap-6">
                <button
                    @click="exportToExcel"
                    class="flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-lg transition-colors shadow-sm"
                >
                    <Download class="w-4 h-4" />
                    EXPORT EXCEL
                </button>
                <div class="text-center">
                    <p class="text-[10px] font-bold text-slate-400 uppercase">Idle Drivers</p>
                    <p class="text-lg font-black text-indigo-600">{{ availableDrivers.length }}</p>
                </div>
                <div class="text-center border-l border-slate-200 pl-6">
                    <p class="text-[10px] font-bold text-slate-400 uppercase">Idle Trailers</p>
                    <p class="text-lg font-black text-emerald-600">{{ availableTrailers.length }}</p>
                </div>
            </div>
        </header>

        <div class="px-8 py-3 bg-slate-800 text-white text-[10px] font-black uppercase tracking-widest grid grid-cols-12 gap-4">
            <div class="col-span-2">Power Unit (Plate)</div>
            <div class="col-span-2">Make / Model</div>
            <div class="col-span-4">Assigned Driver</div>
            <div class="col-span-4">Attached Trailer</div>
        </div>

        <div class="flex-1 overflow-y-auto px-8 py-4 space-y-2 custom-scrollbar">
            <div v-if="filteredVehicles.length === 0" class="py-20 text-center text-slate-400 font-medium">
                No vehicles found matching "{{ searchQuery }}"
            </div>

            <div v-for="v in filteredVehicles" :key="v.id"
                 class="bg-white border border-slate-200 rounded-lg p-3 grid grid-cols-12 gap-4 items-center hover:border-indigo-300 hover:shadow-sm transition-all group">

                <div class="col-span-2">
                    <span class="font-black text-slate-900 group-hover:text-indigo-600 transition-colors">{{ v.plate_number }}</span>
                </div>

                <div class="col-span-2">
                    <span class="text-xs text-slate-500 font-medium">{{ v.make }} {{ v.model }}</span>
                </div>

                <div class="col-span-4 flex items-center gap-2">
                    <div class="flex-1 relative">
                        <User class="absolute left-3 top-1/2 -translate-y-1/2 w-3 h-3 text-slate-400" />
                        <select
                            @change="update(v.id, $event.target.value, 'driver')"
                            class="w-full pl-8 pr-2 py-1.5 bg-slate-50 border-slate-200 rounded text-xs font-bold text-slate-700 appearance-none focus:ring-1 focus:ring-indigo-500">
                            <option :value="null">-- STANDBY --</option>
                            <option v-if="v.current_driver" :value="null" selected>{{ v.current_driver.name }}</option>
                            <option v-for="d in availableDrivers" :key="d.id" :value="d.id">{{ d.user.name }}</option>
                        </select>
                    </div>
                </div>

                <div class="col-span-4 flex items-center gap-2">
                    <div class="flex-1 relative">
                        <Container class="absolute left-3 top-1/2 -translate-y-1/2 w-3 h-3 text-slate-400" />
                        <select
                            @change="update(v.id, $event.target.value, 'trailer')"
                            class="w-full pl-8 pr-2 py-1.5 bg-slate-50 border-slate-200 rounded text-xs font-bold text-slate-700 appearance-none focus:ring-1 focus:ring-emerald-500">
                            <option :value="null">-- BOBTAIL --</option>
                            <option v-if="v.current_assignment" :value="v.current_assignment.trailer_id" selected>
                                {{ v.current_assignment.trailer.plate_number }} ({{ v.current_assignment.trailer.type }})
                            </option>
                            <option v-for="t in availableTrailers" :key="t.id" :value="t.id">{{ t.plate_number }}</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { Search, User, Container } from 'lucide-vue-next';
import { Download } from 'lucide-vue-next';

const vehicles = ref([]);
const availableDrivers = ref([]);
const availableTrailers = ref([]);
const searchQuery = ref('');

const loadData = async () => {
    const { data } = await axios.get('/api/portal/dispatch');
    vehicles.value = data.vehicles;
    availableDrivers.value = data.available_drivers;
    availableTrailers.value = data.available_trailers;
};

const exportToExcel = () => {
    // Open the export URL in a new tab to trigger the download
    window.open('/api/portal/dispatch/export', '_blank');
};

const filteredVehicles = computed(() => {
    if (!searchQuery.value) return vehicles.value;
    const q = searchQuery.value.toLowerCase();
    return vehicles.value.filter(v =>
        v.plate_number.toLowerCase().includes(q) ||
        (v.current_driver && v.current_driver.name.toLowerCase().includes(q)) ||
        (v.current_assignment && v.current_assignment.trailer.plate_number.toLowerCase().includes(q))
    );
});

const update = async (vehicleId, targetId, type) => {
    try {
        const payload = { vehicle_id: vehicleId };
        type === 'driver' ? payload.driver_id = targetId : payload.trailer_id = targetId;
        await axios.post('/api/portal/dispatch/pair', payload);
        loadData();
    } catch (e) {
        console.error("Assignment failed");
    }
};

onMounted(loadData);
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
</style>
