<template>
    <div class="p-6 bg-slate-50 min-h-screen space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-black text-slate-800 tracking-tight uppercase">All Trips</h1>
                <p class="text-xs font-bold text-slate-400">Fleet dispatch history</p>
            </div>
            <div class="flex gap-2">
                <button @click="load" class="p-2.5 bg-white rounded-xl border border-slate-200 hover:bg-slate-50 shadow-sm">
                    <RefreshCw class="w-4 h-4 text-slate-500" />
                </button>
                <RouterLink to="/trips/create" class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl shadow-lg font-black text-xs flex items-center gap-2 uppercase hover:bg-indigo-700 transition-all">
                    <Plus class="w-4 h-4" /> New Dispatch
                </RouterLink>
            </div>
        </div>

        <!-- Status Summary Cards -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
            <button v-for="pill in countPills" :key="pill.status"
                @click="setStatusFilter(pill.status)"
                class="bg-white rounded-2xl border p-5 text-left transition-all hover:shadow-md active:scale-[0.98]"
                :class="filters.status === pill.status ? 'ring-2 ring-indigo-500 border-indigo-300' : 'border-slate-200'">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider">{{ pill.label }}</span>
                    <span class="w-2 h-2 rounded-full" :class="pill.dotClass"></span>
                </div>
                <span class="text-3xl font-black text-slate-800 mt-1 block">{{ pill.count }}</span>
            </button>
        </div>

        <!-- Filters -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex flex-col sm:flex-row gap-3 sm:items-center">
            <div class="relative flex-1">
                <Search class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                <input type="text" v-model="filters.q" @input="debouncedFetch"
                    placeholder="Search reference, plate, driver, client, corridor..."
                    class="w-full bg-slate-50 border-none rounded-xl pl-10 pr-4 py-2.5 text-xs font-bold outline-none focus:ring-2 focus:ring-indigo-500" />
            </div>
            <div class="flex gap-2">
                <input type="date" v-model="filters.from" @change="fetchTrips"
                    class="bg-slate-50 border-none rounded-xl text-xs font-bold px-3 py-2.5 outline-none focus:ring-2 focus:ring-indigo-500" />
                <span class="self-center text-slate-400 font-bold">→</span>
                <input type="date" v-model="filters.to" @change="fetchTrips"
                    class="bg-slate-50 border-none rounded-xl text-xs font-bold px-3 py-2.5 outline-none focus:ring-2 focus:ring-indigo-500" />
            </div>
        </div>

        <!-- Table -->
        <div v-if="loading" class="text-center py-20">
            <p class="text-slate-400 font-bold text-xs uppercase">Loading...</p>
        </div>

        <div v-else-if="!trips.length" class="text-center py-20">
            <p class="text-slate-400 font-bold text-xs uppercase">No trips found</p>
        </div>

        <div v-else class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-slate-50/50 text-[10px] font-black text-slate-400 uppercase border-b">
                        <tr>
                            <th class="p-4 pl-6">Reference</th>
                            <th class="p-4">Corridor</th>
                            <th class="p-4">Vehicle</th>
                            <th class="p-4">Driver</th>
                            <th class="p-4">Status</th>
                            <th class="p-4">Departure</th>
                            <th class="p-4">Arrival</th>
                            <th class="p-4 text-right pr-6">View</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="trip in trips" :key="trip.id"
                            @click="goToDetails(trip)"
                            class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors cursor-pointer text-xs">
                            <td class="p-4 pl-6">
                                <span class="font-black text-sm text-slate-800 block">{{ trip.reference }}</span>
                                <span class="text-[10px] font-bold text-slate-400 uppercase">{{ trip.order_reference || '—' }}</span>
                            </td>
                            <td class="p-4">
                                <span class="font-bold text-slate-700">{{ trip.origin || '—' }} → {{ trip.destination || '—' }}</span>
                                <span v-if="trip.route_name" class="text-[10px] font-bold text-slate-400 block">{{ trip.route_name }}</span>
                            </td>
                            <td class="p-4">
                                <span class="font-bold font-mono text-indigo-600">{{ trip.vehicle_plate_snapshot || '—' }}</span>
                                <span v-if="trip.trailer_plate_snapshot" class="text-[10px] font-bold text-amber-600 block">{{ trip.trailer_plate_snapshot }}</span>
                            </td>
                            <td class="p-4 text-slate-600">{{ trip.driver_name_snapshot || '—' }}</td>
                            <td class="p-4">
                                <span :class="statusBadgeClass(trip.status)"
                                    class="inline-flex items-center gap-1 text-[9px] font-black px-2.5 py-1 rounded-full uppercase">
                                    {{ trip.status }}
                                </span>
                            </td>
                            <td class="p-4 font-bold text-slate-600">{{ formatDateTime(trip.departure_time) }}</td>
                            <td class="p-4 font-bold text-slate-600">{{ formatDateTime(trip.arrival_time) }}</td>
                            <td class="p-4 text-right pr-6">
                                <button @click.stop="goToDetails(trip)"
                                    class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors active:scale-95" title="View details">
                                    <ArrowRight class="w-4 h-4" />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="meta.last_page > 1" class="flex items-center justify-between px-6 py-4 border-t border-slate-100">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">
                    Page {{ meta.current_page }} of {{ meta.last_page }} — {{ meta.total }} trips
                </span>
                <div class="flex gap-2">
                    <button @click="changePage(meta.current_page - 1)" :disabled="meta.current_page <= 1"
                        class="px-4 py-2 rounded-xl text-xs font-black uppercase bg-slate-100 text-slate-600 hover:bg-slate-200 disabled:opacity-40 transition-all">
                        Previous
                    </button>
                    <button @click="changePage(meta.current_page + 1)" :disabled="meta.current_page >= meta.last_page"
                        class="px-4 py-2 rounded-xl text-xs font-black uppercase bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-40 transition-all">
                        Next
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { ArrowRight, Plus, RefreshCw, Search } from 'lucide-vue-next';
import { tripsApi } from '../../api/trips';

const router = useRouter();

const loading = ref(false);
const trips = ref([]);
const counts = ref({});
const meta = ref({ current_page: 1, last_page: 1, total: 0, per_page: 15 });

const filters = reactive({
    status: '',
    q: '',
    from: '',
    to: '',
});

let debounceTimer = null;
const debouncedFetch = () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(fetchTrips, 350);
};

const countPills = computed(() => [
    { status: '', label: 'All', count: counts.value.total ?? 0, textClass: 'text-slate-500', dotClass: 'bg-slate-400' },
    { status: 'pending', label: 'Pending', count: counts.value.pending ?? 0, textClass: 'text-slate-500', dotClass: 'bg-slate-400' },
    { status: 'assigned', label: 'Assigned', count: counts.value.assigned ?? 0, textClass: 'text-blue-600', dotClass: 'bg-blue-500' },
    { status: 'on_route', label: 'On Route', count: counts.value.on_route ?? 0, textClass: 'text-amber-600', dotClass: 'bg-amber-500' },
    { status: 'delivered', label: 'Delivered', count: counts.value.delivered ?? 0, textClass: 'text-emerald-600', dotClass: 'bg-emerald-500' },
    { status: 'cancelled', label: 'Cancelled', count: counts.value.cancelled ?? 0, textClass: 'text-red-600', dotClass: 'bg-red-500' },
]);

const statusBadgeClass = (status) => {
    const map = {
        pending: 'bg-slate-100 text-slate-600',
        assigned: 'bg-blue-100 text-blue-700',
        on_route: 'bg-amber-100 text-amber-700',
        delivered: 'bg-emerald-100 text-emerald-700',
        cancelled: 'bg-red-100 text-red-700',
    };
    return map[status] || 'bg-slate-100 text-slate-600';
};

const formatDateTime = (value) => {
    if (!value) return '—';
    return new Date(value).toLocaleString(undefined, {
        year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit',
    });
};

const setStatusFilter = (status) => {
    filters.status = status;
    fetchTrips();
};

const changePage = (page) => {
    if (page < 1 || page > meta.value.last_page) return;
    fetchTrips(page);
};

const fetchTrips = async (page = 1) => {
    loading.value = true;
    try {
        const params = { page };
        if (filters.status) params.status = filters.status;
        if (filters.q) params.q = filters.q;
        if (filters.from) params.from = filters.from;
        if (filters.to) params.to = filters.to;
        const res = await tripsApi.list(params);
        trips.value = res.data.data;
        meta.value = res.data.meta;
        counts.value = res.data.counts;
    } catch (e) {
        console.error('Failed to fetch trips', e);
    } finally {
        loading.value = false;
    }
};

const goToDetails = (trip) => {
    router.push({ name: 'trips.show', params: { id: trip.id } });
};

const load = () => fetchTrips(meta.value.current_page);

onMounted(() => fetchTrips());
onUnmounted(() => clearTimeout(debounceTimer));
</script>
