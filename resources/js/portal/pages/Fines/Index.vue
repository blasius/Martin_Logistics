<template>
    <div class="p-6">
        <h1 class="text-2xl font-bold mb-4">Traffic Fines</h1>

        <div class="flex gap-3 mb-4">
            <select v-model="filters.type" @change="fetch" class="border px-3 py-2 rounded">
                <option value="">All</option>
                <option value="vehicle">Vehicles</option>
                <option value="trailer">Trailers</option>
            </select>

            <input v-model="filters.plate" @input="debounced" type="text" placeholder="Search plate..." class="border px-3 py-2 rounded w-64" />

            <select v-model="filters.status" @change="fetch" class="border px-3 py-2 rounded">
                <option value="">Any status</option>
                <option value="PENDING">Pending</option>
                <option value="PAID">Paid</option>
                <option value="CANCELLED">Cancelled</option>
            </select>

            <button @click="manualCheck" class="ml-auto bg-blue-600 text-white px-3 py-2 rounded" :disabled="!selectedPlate">Force check</button>
        </div>

        <div v-if="loading" class="text-gray-500">Loading...</div>

        <div v-else>
            <div v-if="items.length === 0" class="text-gray-600">No fines found.</div>

            <div class="grid gap-4">
                <div v-for="fine in items" :key="fine.id" class="bg-white p-4 rounded shadow">
                    <div class="flex justify-between">
                        <div>
                            <div class="font-semibold text-lg">{{ fine.plate_number }}</div>
                            <div class="text-sm text-gray-600">{{ fine.fineable ? typeLabel(fine.fineable_type) + ' • ' + (fine.fineable?.plate_number ?? '') : '' }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-lg font-bold">{{ formatAmount(fine.ticket_amount) }} FRW</div>
                            <div class="text-sm text-gray-600">Status: <span class="font-medium">{{ fine.status }}</span></div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <div class="text-sm font-medium">Violations</div>
                        <ul class="list-disc ml-5 text-sm text-gray-700">
                            <li v-for="v in fine.violations" :key="v.id">{{ v.violation_name }} — {{ formatAmount(v.fine_amount) }} FRW</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div v-if="meta.last_page" class="mt-6 flex items-center gap-3">
                <button @click="goto(meta.current_page - 1)" :disabled="!meta.prev_page_url" class="px-3 py-1 border rounded">Previous</button>
                <div>Page {{ meta.current_page }} / {{ meta.last_page }}</div>
                <button @click="goto(meta.current_page + 1)" :disabled="!meta.next_page_url" class="px-3 py-1 border rounded">Next</button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { api } from '@/plugins/axios.js';

const filters = ref({ type: '', plate: '', status: '' });
const loading = ref(false);
const items = ref([]);
const meta = ref({});
const selectedPlate = ref('');

let debounceTimer = null;

const fetch = async (page = 1) => {
    loading.value = true;
    try {
        const res = await api.get('/api/portal/fines', { params: { ...filters.value, page } });
        items.value = res.data.data ?? res.data;
        meta.value = res.data.meta ?? res.data;
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
};

const debounced = () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => fetch(), 300);
};

const goto = (page) => {
    if (page < 1) return;
    fetch(page);
};

const manualCheck = async () => {
    if (!selectedPlate.value) return;
    try {
        await api.post('/api/portal/fines/check', { plate: selectedPlate.value, type: filters.value.type || 'vehicle' });
        // optionally show toast
    } catch (e) {
        console.error(e);
    }
};

const formatAmount = (a) => (a == null ? '0' : Number(a).toLocaleString());
const typeLabel = (t) => t && t.includes('Vehicle') ? 'Vehicle' : (t && t.includes('Trailer') ? 'Trailer' : '');

fetch();
</script>
