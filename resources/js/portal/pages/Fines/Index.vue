<template>
    <div class="p-6">
        <h1 class="text-2xl font-bold mb-4">Traffic Fines</h1>

        <div class="flex gap-3 mb-4">
            <select v-model="filters.type" @change="fetch" class="border px-3 py-2 rounded">
                <option value="">All</option>
                <option value="vehicle">Vehicles</option>
                <option value="trailer">Trailers</option>
            </select>

            <input
                v-model="filters.plate"
                @input="debounced"
                type="text"
                placeholder="Search plate number..."
                class="border px-3 py-2 rounded w-64"
            />

            <select v-model="filters.status" @change="fetch" class="border px-3 py-2 rounded">
                <option value="">Any status</option>
                <option value="PENDING">Pending</option>
                <option value="PAID">Paid</option>
                <option value="CANCELLED">Cancelled</option>
                <option value="DISPUTED">Disputed</option>
            </select>

            <button @click="manualCheck" class="ml-auto px-3 py-2 bg-blue-600 text-white rounded" v-if="selectedPlate">
                Force check {{ selectedPlate }}
            </button>
        </div>

        <div v-if="loading" class="text-gray-500">Loading…</div>

        <div v-else>
            <div v-if="items.length === 0" class="text-gray-600">No fines found.</div>

            <div class="space-y-4">
                <div v-for="fine in items" :key="fine.id" class="bg-white rounded shadow p-4">
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="text-lg font-semibold">{{ fine.plate_number }}</div>
                            <div class="text-sm text-gray-600">
                                <span v-if="fine.fineable_type">{{ typeLabel(fine.fineable_type) }}</span>
                                <span v-if="fine.fineable"> — {{ fine.fineable?.plate_number ?? '' }}</span>
                            </div>
                        </div>

                        <div class="text-right">
                            <div class="text-lg font-bold">{{ formatAmount(fine.ticket_amount) }} FRW</div>
                            <div class="text-sm text-gray-600">Status: <span class="font-medium">{{ fine.status }}</span></div>
                        </div>
                    </div>

                    <div class="mt-3 grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <div class="text-xs text-gray-500">Issued at</div>
                            <div class="text-sm">{{ formatDate(fine.issued_at) }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500">Pay by</div>
                            <div class="text-sm">{{ formatDate(fine.pay_by) }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500">Last checked</div>
                            <div class="text-sm">{{ formatDate(fine.updated_at) }}</div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <div class="text-sm font-medium mb-1">Violations</div>
                        <ul class="list-disc ml-5 text-sm text-gray-700">
                            <li v-for="v in fine.violations" :key="v.id">{{ v.violation_name }} — {{ formatAmount(v.fine_amount) }} FRW</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
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
import { api } from "@/plugins/axios.js"; // adjust path to your axios instance, it should call /api/portal/fines

const filters = ref({ type: '', plate: '', status: '' });
const loading = ref(false);
const items = ref([]);
const meta = ref({});
const selectedPlate = ref(null);

let debounceTimer = null;

const fetch = async (page = 1) => {
    loading.value = true;
    try {
        const res = await api.get('/api/portal/fines', { params: { ...filters.value, page } });
        items.value = res.data.data || res.data;
        meta.value = res.data.meta ?? res.data;
    } catch (e) {
        console.error(e);
    }
    loading.value = false;
};

const debounced = () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => fetch(), 400);
};

const goto = (page) => {
    if (page < 1) return;
    fetch(page);
};

const manualCheck = async () => {
    if (!selectedPlate.value) return;
    try {
        await axios.post('/api/portal/fines/check', { plate: selectedPlate.value, type: filters.value.type || 'vehicle' });
        // optional: show toast
    } catch (e) {
        console.error(e);
    }
};

const formatDate = (d) => d ? new Date(d).toLocaleString() : '-';
const formatAmount = (a) => (a === null || a === undefined) ? '0' : Number(a).toLocaleString();

function typeLabel(type) {
    if (!type) return '';
    if (type.includes('Vehicle')) return 'Vehicle';
    if (type.includes('Trailer')) return 'Trailer';
    return type;
}

// initial fetch
await fetch();
</script>

<style scoped>
/* small UI polish */
</style>
