<template>
    <div v-if="loading" class="flex items-center justify-center min-h-screen bg-slate-50">
        <Loader2 class="w-10 h-10 text-indigo-600 animate-spin" />
    </div>

    <div v-else-if="data" class="p-6 space-y-6 bg-slate-50 min-h-screen">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-2xl bg-indigo-600 text-white flex items-center justify-center font-black text-2xl shadow-2xl ring-4 ring-white">
                    {{ data.driver.user?.name.substring(0,2).toUpperCase() }}
                </div>
                <div>
                    <nav class="flex mb-1 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                        <router-link to="/drivers" class="hover:text-indigo-600 transition-colors">Personnel</router-link>
                        <span class="mx-2">/</span>
                        <span class="text-indigo-600">Profile: {{ data.driver.id }}</span>
                    </nav>
                    <h1 class="text-3xl font-black text-slate-900 tracking-tighter uppercase leading-none">
                        {{ data.driver.user?.name }}
                    </h1>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button class="px-6 py-3 bg-white border border-slate-200 text-slate-700 rounded-2xl shadow-sm font-black text-xs uppercase tracking-widest hover:bg-slate-50 transition-all flex items-center gap-2">
                    <FileText class="w-4 h-4" /> Export Log
                </button>
                <button class="px-6 py-3 bg-indigo-600 text-white rounded-2xl shadow-xl font-black text-xs uppercase tracking-widest hover:scale-105 transition-all flex items-center gap-2">
                    <UserPlus class="w-4 h-4" /> Edit Driver
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm relative overflow-hidden">
                <div class="absolute right-[-10px] top-[-10px] opacity-[0.03]">
                    <TrendingUp class="w-24 h-24" />
                </div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Safety Performance</p>
                <p class="text-3xl font-black text-indigo-600">{{ data.stats.safety_score }}%</p>
                <div class="w-full bg-slate-100 h-2 mt-4 rounded-full overflow-hidden">
                    <div class="bg-indigo-600 h-full transition-all duration-1000" :style="`width: ${data.stats.safety_score}%`"></div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Total Operations</p>
                <p class="text-3xl font-black text-slate-800">{{ data.stats.total_trips }}</p>
                <p class="text-[9px] font-bold text-slate-400 mt-2 uppercase">Lifetime Trips Completed</p>
            </div>

            <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Current Asset</p>
                <p :class="data.stats.assigned_vehicle ? 'text-orange-500' : 'text-slate-400'" class="text-xl font-black uppercase">
                    {{ data.stats.assigned_vehicle || 'No Vehicle' }}
                </p>
                <p class="text-[9px] font-bold text-slate-400 mt-2 uppercase">Current Assignment</p>
            </div>

            <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Tenure</p>
                <p class="text-xl font-black text-slate-800 uppercase">{{ data.stats.active_since }}</p>
                <p class="text-[9px] font-bold text-indigo-600 mt-2 uppercase tracking-tighter">Verified Member</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-1 space-y-6">
                <div class="bg-white p-8 rounded-3xl border border-slate-200 shadow-sm">
                    <h3 class="text-xs font-black text-slate-900 uppercase tracking-widest mb-8 flex items-center gap-2">
                        <ShieldCheck class="w-4 h-4 text-indigo-600" /> Documents & Identity
                    </h3>
                    <div class="space-y-6">
                        <div class="group">
                            <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest block mb-1">Driving Licence</label>
                            <p class="text-sm font-black text-slate-800 tracking-tight">{{ data.driver.driving_licence }}</p>
                        </div>
                        <div class="group">
                            <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest block mb-1">Passport / ID Number</label>
                            <p class="text-sm font-black text-slate-800 tracking-tight">{{ data.driver.passport_number || 'NOT PROVIDED' }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest block mb-1">Nationality</label>
                                <p class="text-sm font-black text-slate-800 uppercase">{{ data.driver.nationality }}</p>
                            </div>
                            <div>
                                <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest block mb-1">Sex</label>
                                <p class="text-sm font-black text-slate-800 uppercase">{{ data.driver.sex }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-slate-900 p-8 rounded-3xl shadow-2xl relative overflow-hidden group">
                    <div class="absolute right-[-20px] bottom-[-20px] opacity-10 group-hover:scale-110 transition-transform duration-700">
                        <Phone class="w-32 h-32 text-white" />
                    </div>
                    <h3 class="text-[10px] font-black text-indigo-400 uppercase tracking-widest mb-4">Direct Contact</h3>
                    <p class="text-2xl font-black text-white tracking-tighter">{{ data.driver.phone }}</p>
                    <p class="text-xs font-bold text-slate-400 mt-1">{{ data.driver.user.email }}</p>
                </div>
            </div>

            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-slate-50 flex justify-between items-center">
                        <h3 class="text-xs font-black text-slate-900 uppercase tracking-widest flex items-center gap-2">
                            <MapPin class="w-4 h-4 text-indigo-600" /> Recent Trip Logs
                        </h3>
                        <button class="text-[10px] font-black text-indigo-600 uppercase hover:underline">View Ledger</button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-slate-50/50 text-[10px] font-black text-slate-400 uppercase border-b">
                            <tr>
                                <th class="p-5 tracking-widest">Route Details</th>
                                <th class="p-5 tracking-widest">Assigned Asset</th>
                                <th class="p-5 tracking-widest">Execution</th>
                                <th class="p-5 tracking-widest text-center">Date</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                            <tr v-for="trip in data.stats.trip_history" :key="trip.id" class="hover:bg-indigo-50/30 transition-colors">
                                <td class="p-5">
                                    <p class="text-xs font-black text-slate-800 uppercase tracking-tighter">{{ trip.origin }} <span class="text-indigo-400 mx-1">→</span> {{ trip.destination }}</p>
                                    <p class="text-[9px] font-bold text-slate-400 mt-0.5 uppercase tracking-widest">REF: {{ trip.id }}</p>
                                </td>
                                <td class="p-5">
                                    <div class="flex items-center gap-2">
                                        <Truck class="w-3 h-3 text-slate-300" />
                                        <span class="text-[10px] font-black text-slate-700 uppercase">{{ trip.vehicle_plate_snapshot }}</span>
                                    </div>
                                </td>
                                <td class="p-5">
                                        <span :class="getStatusClass(trip.status)" class="text-[9px] font-black uppercase px-2.5 py-1 rounded-lg tracking-wider border">
                                            {{ trip.status }}
                                        </span>
                                </td>
                                <td class="p-5 text-center">
                                    <p class="text-[10px] font-black text-slate-500 uppercase">{{ formatDate(trip.created_at) }}</p>
                                </td>
                            </tr>
                            <tr v-if="!data.stats.trip_history.length">
                                <td colspan="4" class="p-20 text-center">
                                    <div class="flex flex-col items-center gap-2">
                                        <ClipboardList class="w-10 h-10 text-slate-200" />
                                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">No operations recorded for this unit</p>
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { api } from "../../../plugins/axios";
import {
    Loader2, Users, FileText, UserPlus, Phone,
    ChevronRight, ShieldCheck, MapPin, Truck,
    TrendingUp, ClipboardList
} from 'lucide-vue-next';

const props = defineProps(['id']);
const data = ref(null);
const loading = ref(true);

const getStatusClass = (status) => {
    const map = {
        'delivered': 'bg-green-50 text-green-700 border-green-100',
        'on_route': 'bg-blue-50 text-blue-700 border-blue-100',
        'pending': 'bg-orange-50 text-orange-700 border-orange-100',
        'cancelled': 'bg-red-50 text-red-700 border-red-100'
    };
    return map[status] || 'bg-slate-50 text-slate-700 border-slate-100';
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
};

async function fetchDriverDetails() {
    loading.value = true;
    try {
        const res = await api.get(`/portal/drivers/${props.id}`);
        data.value = res.data;
    } catch (e) {
        console.error("Fetch Error:", e);
    } finally {
        loading.value = false;
    }
}

onMounted(fetchDriverDetails);
</script>
