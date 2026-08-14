<template>
    <div class="p-6 bg-slate-50 min-h-screen space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-black text-slate-800 tracking-tight uppercase">Trip Status Board</h1>
                <p class="text-xs font-bold text-slate-400">Live trip lifecycle overview</p>
            </div>
            <div class="flex gap-2">
                <button @click="fetchTrips" class="p-2.5 bg-white rounded-xl border border-slate-200 hover:bg-slate-50 shadow-sm">
                    <RefreshCw class="w-4 h-4 text-slate-500" />
                </button>
                <RouterLink to="/trips/create" class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl shadow-lg font-black text-xs flex items-center gap-2 uppercase hover:bg-indigo-700 transition-all">
                    <Plus class="w-4 h-4" /> New Dispatch
                </RouterLink>
            </div>
        </div>

        <div v-if="loading" class="text-center py-20 bg-white rounded-2xl border border-slate-200 shadow-sm">
            <p class="text-slate-400 font-bold text-xs uppercase">Loading...</p>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4">
            <div v-for="col in columns" :key="col.status"
                class="bg-slate-100/60 rounded-2xl border border-slate-200 flex flex-col max-h-[calc(100vh-220px)]">
                <div class="p-4 flex items-center justify-between border-b border-slate-200/70">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full" :class="col.dotClass"></span>
                        <span class="text-[10px] font-black uppercase tracking-wider" :class="col.textClass">{{ col.label }}</span>
                    </div>
                    <span class="text-xs font-black text-slate-500 bg-white rounded-full px-2.5 py-0.5 border border-slate-200">{{ grouped(col.status).length }}</span>
                </div>

                <div class="flex-1 overflow-y-auto p-3 space-y-3 custom-scrollbar">
                    <div v-if="grouped(col.status).length === 0"
                        class="text-center py-8 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                        Empty
                    </div>

                    <button v-for="trip in grouped(col.status)" :key="trip.id"
                        @click="goToDetails(trip)"
                        class="w-full text-left bg-white rounded-2xl border border-slate-200 p-4 shadow-sm hover:shadow-md hover:border-indigo-200 active:scale-[0.98] transition-all">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-black text-sm text-slate-800">{{ trip.reference }}</span>
                            <span v-if="trip.is_deviated"
                                class="text-[8px] font-black uppercase px-1.5 py-0.5 rounded bg-red-100 text-red-600">
                                Deviated
                            </span>
                        </div>

                        <p class="text-xs font-bold text-slate-600 leading-snug">
                            {{ trip.origin || '—' }} → {{ trip.destination || '—' }}
                        </p>
                        <p v-if="trip.client_name" class="text-[10px] font-bold text-slate-400">{{ trip.client_name }}</p>

                        <div class="mt-3 pt-3 border-t border-slate-100 space-y-1">
                            <div class="flex justify-between text-[10px] font-bold text-slate-500">
                                <span>Vehicle</span>
                                <span class="font-black font-mono text-indigo-600">{{ trip.vehicle_plate_snapshot || '—' }}</span>
                            </div>
                            <div class="flex justify-between text-[10px] font-bold text-slate-500">
                                <span>Driver</span>
                                <span class="text-slate-800">{{ trip.driver_name_snapshot || '—' }}</span>
                            </div>
                            <div class="flex justify-between text-[10px] font-bold text-slate-500">
                                <span>Departed</span>
                                <span class="text-slate-800">{{ formatDateTime(trip.departure_time) }}</span>
                            </div>
                            <div v-if="trip.planned_distance_km" class="flex justify-between text-[10px] font-bold text-slate-500">
                                <span>Distance</span>
                                <span class="text-slate-800">{{ trip.planned_distance_km }} km</span>
                            </div>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { Plus, RefreshCw } from 'lucide-vue-next';
import { tripsApi } from '../../api/trips';

const router = useRouter();

const loading = ref(false);
const trips = ref([]);

const columns = [
    { status: 'pending', label: 'Pending', textClass: 'text-slate-600', dotClass: 'bg-slate-400' },
    { status: 'assigned', label: 'Assigned', textClass: 'text-blue-700', dotClass: 'bg-blue-500' },
    { status: 'on_route', label: 'On Route', textClass: 'text-amber-700', dotClass: 'bg-amber-500' },
    { status: 'delivered', label: 'Delivered', textClass: 'text-emerald-700', dotClass: 'bg-emerald-500' },
    { status: 'cancelled', label: 'Cancelled', textClass: 'text-red-700', dotClass: 'bg-red-500' },
];

const grouped = (status) => trips.value.filter(t => t.status === status);

const formatDateTime = (value) => {
    if (!value) return '—';
    return new Date(value).toLocaleString(undefined, {
        month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit',
    });
};

const goToDetails = (trip) => {
    router.push({ name: 'trips.show', params: { id: trip.id } });
};

const fetchTrips = async () => {
    loading.value = true;
    try {
        const res = await tripsApi.list({ per_page: 1000 });
        trips.value = res.data.data;
    } catch (e) {
        console.error('Failed to fetch trips', e);
    } finally {
        loading.value = false;
    }
};

onMounted(fetchTrips);
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
</style>
