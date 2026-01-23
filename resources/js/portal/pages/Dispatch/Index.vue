<template>
    <div class="p-8 bg-slate-100 min-h-screen">
        <div class="max-w-7xl mx-auto">
            <h1 class="text-3xl font-black text-slate-800 mb-8 italic">DISPATCH COMMAND</h1>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="v in vehicles" :key="v.id" class="bg-white rounded-2xl shadow-md overflow-hidden border-t-4 border-indigo-500">
                    <div class="p-5">
                        <div class="flex justify-between items-center mb-4">
                            <span class="text-2xl font-black text-slate-900 tracking-tighter">{{ v.plate_number }}</span>
                            <span class="text-[10px] font-bold px-2 py-1 bg-emerald-100 text-emerald-700 rounded-full uppercase">{{ v.status }}</span>
                        </div>

                        <div class="space-y-4">
                            <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                                <label class="block text-[10px] font-black text-slate-400 uppercase mb-2">Assigned Driver</label>
                                <select
                                    @change="update(v.id, $event.target.value, 'driver')"
                                    class="w-full bg-transparent border-none p-0 text-sm font-bold focus:ring-0">
                                    <option :value="null">-- NO DRIVER --</option>
                                    <option v-if="v.current_driver" :value="null" selected>
                                        {{ v.current_driver.name }} (CURRENT)
                                    </option>
                                    <option v-for="d in availableDrivers" :key="d.id" :value="d.id">
                                        {{ d.user.name }}
                                    </option>
                                </select>
                            </div>

                            <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                                <label class="block text-[10px] font-black text-slate-400 uppercase mb-2">Attached Trailer</label>
                                <select
                                    @change="update(v.id, $event.target.value, 'trailer')"
                                    class="w-full bg-transparent border-none p-0 text-sm font-bold focus:ring-0">
                                    <option :value="null">-- NO TRAILER --</option>
                                    <option v-if="v.current_assignment" :value="v.current_assignment.trailer_id" selected>
                                        {{ v.current_assignment.trailer.plate_number }} (CURRENT)
                                    </option>
                                    <option v-for="t in availableTrailers" :key="t.id" :value="t.id">
                                        {{ t.plate_number }} - {{ t.type }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const vehicles = ref([]);
const availableDrivers = ref([]);
const availableTrailers = ref([]);

const refresh = async () => {
    try {
        const { data } = await axios.get('/api/portal/dispatch');
        vehicles.value = data.vehicles;
        availableDrivers.value = data.available_drivers;
        availableTrailers.value = data.available_trailers;
    } catch (e) {
        console.error("Board failed to load");
    }
};

const update = async (vehicleId, targetId, type) => {
    try {
        const payload = { vehicle_id: vehicleId };
        type === 'driver' ? payload.driver_id = targetId : payload.trailer_id = targetId;

        await axios.post('/api/portal/dispatch/pair', payload);
        refresh();
    } catch (e) {
        alert("Action failed. The asset may have been reassigned.");
        refresh();
    }
};

onMounted(refresh);
</script>
