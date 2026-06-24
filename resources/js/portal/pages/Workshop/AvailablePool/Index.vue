<template>
    <div class="p-6 bg-slate-50 min-h-screen space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-black text-slate-800 tracking-tight uppercase">Available Pool</h1>
                <p class="text-xs font-bold text-slate-400">Vehicles released from workshop — ready to deploy</p>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex gap-4">
            <div class="relative flex-1">
                <Search class="absolute left-3 top-2.5 w-4 h-4 text-slate-400" />
                <input v-model="filters.search" @input="debounceSearch" type="text" placeholder="Search by plate, make or model..." class="w-full pl-10 p-2 bg-slate-50 border-none rounded-xl text-xs font-bold outline-none">
            </div>
        </div>

        <div v-if="loading" class="grid gap-4">
            <div v-for="i in 5" class="h-16 bg-white rounded-2xl animate-pulse border border-slate-100"></div>
        </div>

        <div v-else class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-slate-50/50 text-[10px] font-black text-slate-400 uppercase border-b">
                    <tr>
                        <th class="p-4">Plate Number</th>
                        <th class="p-4">Make / Model</th>
                        <th class="p-4">Year</th>
                        <th class="p-4">Odometer</th>
                        <th class="p-4">Last Repair</th>
                        <th class="p-4">Released At</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="vehicle in vehicles" :key="vehicle.id" class="border-b border-slate-50 hover:bg-slate-50/50">
                        <td class="p-4 font-bold text-xs text-indigo-600 font-mono">{{ vehicle.plate_number }}</td>
                        <td class="p-4 font-black text-sm text-slate-800">{{ vehicle.make }} {{ vehicle.model }}</td>
                        <td class="p-4 text-xs text-slate-500 font-medium">{{ vehicle.year || '—' }}</td>
                        <td class="p-4 text-xs font-bold text-slate-700 font-mono">{{ formatOdometer(vehicle.last_odometer) }}</td>
                        <td class="p-4 text-xs text-slate-500">{{ vehicle.repair_requests?.[0]?.reference || '—' }}</td>
                        <td class="p-4 text-xs text-slate-500">{{ formatDate(vehicle.updated_at) }}</td>
                    </tr>
                    <tr v-if="vehicles.length === 0">
                        <td colspan="6" class="p-10 text-center text-slate-400 font-bold text-xs uppercase">No vehicles in available pool</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="meta" class="flex items-center justify-between text-xs text-slate-400 font-bold">
            <p>Page {{ meta.current_page }} of {{ meta.last_page }} ({{ meta.total }} vehicles)</p>
            <div class="flex gap-2">
                <button @click="goToPage(meta.current_page - 1)" :disabled="!meta.prev_page_url" class="px-4 py-2 bg-white rounded-xl border border-slate-200 disabled:opacity-40 font-black text-xs">Previous</button>
                <button @click="goToPage(meta.current_page + 1)" :disabled="!meta.next_page_url" class="px-4 py-2 bg-white rounded-xl border border-slate-200 disabled:opacity-40 font-black text-xs">Next</button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { availablePoolApi } from '../../../api/workshop/available-pool';
import { Search } from 'lucide-vue-next';

const loading = ref(true);
const vehicles = ref([]);
const meta = ref(null);
const filters = ref({ search: '' });

let debounceTimer;

function debounceSearch() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(fetch, 300);
}

async function fetch() {
    loading.value = true;
    try {
        const { data } = await availablePoolApi.index(filters.value);
        vehicles.value = data.data || [];
        meta.value = {
            current_page: data.current_page,
            last_page: data.last_page,
            total: data.total,
            prev_page_url: data.prev_page_url,
            next_page_url: data.next_page_url,
        };
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
}

function goToPage(page) {
    filters.value = { ...filters.value, page };
    fetch();
}

function formatOdometer(val) {
    if (!val && val !== 0) return '—';
    return Number(val).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) + ' km';
}

function formatDate(date) {
    if (!date) return '';
    return new Date(date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

onMounted(fetch);
</script>
