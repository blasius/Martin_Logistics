<template>
    <div class="p-6 bg-slate-50 min-h-screen">
        <h1 class="text-2xl font-bold mb-6 text-slate-800">Traffic Fines</h1>

        <div class="flex flex-wrap gap-3 mb-6 bg-white p-4 rounded-xl shadow-sm border border-slate-200">
            <select v-model="filters.type" @change="fetch" class="border border-slate-200 px-3 py-2 rounded-lg text-sm">
                <option value="">All Types</option>
                <option value="vehicle">Vehicles</option>
                <option value="trailer">Trailers</option>
            </select>
            <input v-model="filters.plate" @input="debounced" type="text" placeholder="Search plate..." class="border border-slate-200 px-3 py-2 rounded-lg w-64 text-sm" />
            <select v-model="filters.status" @change="fetch" class="border border-slate-200 px-3 py-2 rounded-lg text-sm">
                <option value="">Any status</option>
                <option value="PENDING">Pending</option>
                <option value="PAID">Paid</option>
            </select>
        </div>

        <div v-if="loading" class="text-center p-12 animate-pulse">Loading...</div>

        <div v-else class="grid gap-4">
            <div v-for="fine in items" :key="fine.id"
                 class="bg-white p-5 rounded-xl shadow-sm border transition-all"
                 :class="fine.show_penalty_warning ? 'border-rose-300 bg-rose-50/30' : 'border-slate-200'">

                <div class="flex justify-between items-start">
                    <div>
                        <div class="flex items-center gap-3">
                            <span class="font-black text-xl text-slate-900 uppercase">{{ fine.plate_number }}</span>
                            <span v-if="fine.assigned_driver_name" class="bg-indigo-100 text-indigo-700 text-xs font-black px-2.5 py-1 rounded-full">
                                {{ fine.assigned_driver_name }}
                            </span>
                        </div>

                        <div class="text-xs mt-1 font-bold" :class="fine.is_overdue && fine.status === 'PENDING' ? 'text-rose-600' : 'text-slate-500'">
                            Issued: {{ fine.issued_at_human }}
                            <span v-if="fine.is_overdue && fine.status === 'PENDING'">— OVERDUE</span>
                        </div>
                    </div>

                    <div class="text-right">
                        <div class="text-xl font-black" :class="fine.show_penalty_warning ? 'text-rose-700' : 'text-slate-900'">
                            {{ formatAmount(fine.ticket_amount) }} FRW
                        </div>
                        <span class="text-[10px] font-black px-2 py-0.5 rounded uppercase" :class="statusClass(fine.status)">
                            {{ fine.status }}
                        </span>
                    </div>
                </div>

                <div v-if="fine.show_penalty_warning" class="mt-4 p-3 bg-rose-600 text-white rounded-lg flex items-center gap-3 shadow-lg animate-bounce-subtle">
                    <span class="text-lg">⚠️</span>
                    <div>
                        <p class="text-xs font-black uppercase tracking-tight">Late Penalty Applicable</p>
                        <p class="text-[10px] opacity-90">This fine is over 14 days old. Original amount may increase.</p>
                    </div>
                </div>

                <div class="mt-4 pt-4 border-t border-slate-100">
                    <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Violations</div>
                    <ul class="space-y-1">
                        <li v-for="v in fine.violations" :key="v.id" class="flex justify-between text-sm">
                            <span class="text-slate-700">{{ v.violation_name }}</span>
                            <span class="font-bold">{{ formatAmount(v.fine_amount) }} FRW</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { api } from '@/plugins/axios.js';

const filters = ref({ type: '', plate: '', status: '' });
const items = ref([]);
const loading = ref(false);
const meta = ref({});
let debounceTimer = null;

const fetch = async (page = 1) => {
    loading.value = true;
    try {
        const res = await api.get('/api/portal/fines', { params: { ...filters.value, page } });
        items.value = res.data.data;
        meta.value = res.data;
    } finally {
        loading.value = false;
    }
};

const debounced = () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => fetch(), 300);
};

const formatAmount = (a) => (a == null ? '0' : Number(a).toLocaleString());

const statusClass = (s) => {
    if (s === 'PAID') return 'bg-emerald-100 text-emerald-700';
    if (s === 'PENDING') return 'bg-amber-100 text-amber-700';
    return 'bg-slate-100 text-slate-700';
};

fetch();
</script>

<style scoped>
@keyframes bounce-subtle {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-3px); }
}
.animate-bounce-subtle {
    animation: bounce-subtle 2s infinite ease-in-out;
}
</style>
