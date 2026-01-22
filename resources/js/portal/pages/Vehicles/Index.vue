<template>
    <div class="p-6 space-y-6 bg-slate-50 min-h-screen">

        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-slate-800 flex items-center gap-3">
                    <Truck class="text-indigo-600 w-8 h-8" />
                    Fleet Inventory
                </h1>
                <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mt-1">Managed Vehicles & Trailer Assignments</p>
            </div>
            <div class="flex gap-2">
                <button class="px-4 py-2 bg-white border border-slate-200 text-slate-700 rounded-xl shadow-sm hover:bg-slate-50 transition font-bold text-sm flex items-center gap-2">
                    <Container class="w-4 h-4" />
                    Add Trailer
                </button>
                <button class="px-4 py-2 bg-indigo-600 text-white rounded-xl shadow-lg shadow-indigo-200 hover:bg-indigo-700 transition font-bold text-sm flex items-center gap-2">
                    <Plus class="w-4 h-4" />
                    Add Vehicle
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div v-for="(val, label) in statsMap" :key="label" class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-tighter">{{ label.replace('_', ' ') }}</p>
                <p class="text-2xl font-black text-slate-800">{{ val }}</p>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex flex-col md:flex-row gap-4 justify-between items-center">
            <div class="relative w-full md:w-96">
                <Search class="absolute left-3 top-2.5 text-slate-400 w-4 h-4" />
                <input v-model="search" @input="debounceSearch" type="text" placeholder="Search plate or make..." class="w-full pl-10 pr-4 py-2 bg-slate-50 border-transparent focus:bg-white focus:ring-2 ring-indigo-500 rounded-xl text-sm outline-none transition-all">
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-slate-50 text-[10px] font-black text-slate-400 uppercase border-b">
                <tr>
                    <th class="p-4">Main Vehicle (Power Unit)</th>
                    <th class="p-4">Attached Trailer</th>
                    <th class="p-4">Status</th>
                    <th class="p-4 text-center">Actions</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                <tr v-for="v in vehicles.data" :key="v.id" class="hover:bg-slate-50/50 transition-colors">
                    <td class="p-4">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-slate-100 rounded-lg text-slate-600">
                                <Truck class="w-5 h-5" />
                            </div>
                            <div>
                                <div class="font-black text-slate-800 uppercase text-sm">{{ v.plate_number }}</div>
                                <div class="text-[10px] font-bold text-slate-400 uppercase">{{ v.make }} {{ v.model }}</div>
                            </div>
                        </div>
                    </td>

                    <td class="p-4">
                        <div v-if="v.current_assignment" class="flex items-center gap-2">
                            <div class="px-2 py-1 bg-indigo-50 text-indigo-700 rounded border border-indigo-100 flex items-center gap-2">
                                <Link class="w-3 h-3" />
                                <span class="text-xs font-black">{{ v.current_assignment.trailer.plate_number }}</span>
                            </div>
                            <span class="text-[9px] font-bold text-slate-400 uppercase">{{ v.current_assignment.trailer.type }}</span>
                        </div>
                        <div v-else class="text-[10px] font-bold text-slate-300 italic flex items-center gap-2">
                            <Link2Off class="w-3 h-3" />
                            No Trailer Assigned
                        </div>
                    </td>

                    <td class="p-4">
                            <span :class="getStatusClass(v.status)" class="px-2 py-1 rounded-md text-[9px] font-black uppercase tracking-widest border">
                                {{ v.status }}
                            </span>
                    </td>

                    <td class="p-4 text-center">
                        <div class="flex justify-center gap-2">
                            <button class="p-2 hover:bg-slate-100 rounded-lg text-slate-400 hover:text-indigo-600 transition">
                                <History class="w-4 h-4" />
                            </button>
                            <button class="p-2 hover:bg-slate-100 rounded-lg text-slate-400 hover:text-indigo-600 transition">
                                <Settings2 class="w-4 h-4" />
                            </button>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { api } from '@/plugins/axios';
import {
    Truck, Container, Plus, Search,
    Link, Link2Off, Settings2, History
} from 'lucide-vue-next';

const vehicles = ref({ data: [] });
const statsMap = ref({});
const search = ref('');
let timer = null;

async function fetchVehicles(page = 1) {
    try {
        const { data } = await api.get('/api/portal/vehicles', {
            params: { page, search: search.value }
        });
        vehicles.value = data.vehicles;
        statsMap.value = data.stats;
    } catch (e) {
        console.error("Failed to load vehicles", e);
    }
}

const getStatusClass = (status) => {
    const map = {
        'active': 'bg-green-50 text-green-700 border-green-100',
        'maintenance': 'bg-orange-50 text-orange-700 border-orange-100',
        'inactive': 'bg-slate-50 text-slate-400 border-slate-100'
    };
    return map[status] || map.inactive;
};

const debounceSearch = () => {
    clearTimeout(timer);
    timer = setTimeout(() => fetchVehicles(1), 500);
};

onMounted(() => fetchVehicles());
</script>
