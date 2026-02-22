<template>
    <div v-if="loading" class="flex items-center justify-center min-h-screen bg-slate-50">
        <Loader2 class="w-10 h-10 text-indigo-600 animate-spin" />
    </div>

    <div v-else-if="vehicle" class="p-6 space-y-6 bg-slate-50 min-h-screen">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <nav class="flex mb-2 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                    <router-link to="/vehicles" class="hover:text-indigo-600">Fleet</router-link>
                    <span class="mx-2">/</span>
                    <span class="text-indigo-600">Vehicle Unit</span>
                </nav>
                <h1 class="text-2xl font-black text-slate-800 flex items-center gap-3 tracking-tighter uppercase">
                    <div class="w-12 h-12 rounded-2xl bg-slate-800 text-white flex items-center justify-center font-black text-lg shadow-xl">
                        <Truck class="w-6 h-6" />
                    </div>
                    {{ vehicle.plate_number }}
                </h1>
            </div>
            <div class="flex gap-3">
                <button class="px-6 py-3 bg-white text-slate-700 border border-slate-200 rounded-2xl shadow-sm font-black text-xs uppercase flex items-center gap-2">
                    <Wrench class="w-4 h-4" /> Service Log
                </button>
                <button :class="vehicle.status === 'active' ? 'bg-green-600' : 'bg-orange-500'" class="px-6 py-3 text-white rounded-2xl shadow-xl font-black text-xs uppercase flex items-center gap-2">
                    <Activity class="w-4 h-4" /> {{ vehicle.status }}
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Make / Model</p>
                <p class="text-lg font-black text-slate-800 uppercase">{{ vehicle.make }} {{ vehicle.model }}</p>
                <p class="text-[9px] font-bold text-slate-400 mt-1 uppercase">Year: {{ vehicle.year || 'N/A' }}</p>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Current Driver</p>
                <p class="text-lg font-black text-indigo-600 uppercase">{{ stats.current_driver || 'Unassigned' }}</p>
                <p class="text-[9px] font-bold text-slate-400 mt-1 uppercase">Active Assignment</p>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Trips</p>
                <p class="text-2xl font-black text-slate-800">{{ stats.total_trips || 0 }}</p>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Fuel Consumed</p>
                <p class="text-2xl font-black text-slate-800">0 <span class="text-xs text-slate-400 uppercase">Ltrs</span></p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-1 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                <h3 class="text-xs font-black text-slate-900 uppercase tracking-tighter mb-6 border-b pb-4">Specifications</h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-[10px] font-black text-slate-400 uppercase">Color</span>
                        <span class="font-black text-slate-700 uppercase">{{ vehicle.color }}</span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-[10px] font-black text-slate-400 uppercase">Status</span>
                        <span class="font-black text-indigo-600 uppercase">{{ vehicle.status }}</span>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-50">
                    <h3 class="text-xs font-black text-slate-900 uppercase tracking-tighter">Recent Trip Activity</h3>
                </div>
                <div class="p-20 text-center">
                    <MapPin class="w-12 h-12 text-slate-200 mx-auto mb-4" />
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">No recent trip data for this unit</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { api } from "../../../plugins/axios";
import { Loader2, Truck, Activity, Wrench, MapPin } from 'lucide-vue-next';

const props = defineProps(['id']);
const vehicle = ref(null);
const stats = ref({});
const loading = ref(true);

async function fetchVehicle() {
    loading.value = true;
    try {
        const { data } = await api.get(`/portal/vehicles/${props.id}`);
        vehicle.value = data.vehicle;
        stats.value = data.stats;
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
}

onMounted(fetchVehicle);
</script>
