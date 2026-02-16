<template>
    <div class="p-6 bg-slate-50 min-h-screen">
        <header class="mb-8">
            <h1 class="text-3xl font-black text-slate-800 tracking-tight">TRAFFIC FINES</h1>
            <p class="text-sm font-bold text-slate-400 uppercase">Monitoring</p>
        </header>

        <div class="flex flex-wrap gap-4 mb-6 bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
            <div class="flex flex-col gap-1">
                <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Filter Type</label>
                <select v-model="filters.type" @change="fetch" class="border-slate-200 px-3 py-2 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-500 outline-none bg-slate-50">
                    <option value="">All Units</option>
                    <option value="vehicle">Vehicles Only</option>
                    <option value="trailer">Trailers Only</option>
                </select>
            </div>

            <div class="flex flex-col gap-1 flex-1">
                <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Search Database</label>
                <input v-model="filters.plate" @input="debounced" type="text" placeholder="Search plate or driver name..."
                       class="border-slate-200 px-4 py-2 rounded-xl text-sm font-medium focus:ring-2 focus:ring-blue-500 outline-none bg-slate-50 w-full" />
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Status</label>
                <select v-model="filters.status" @change="fetch" class="border-slate-200 px-3 py-2 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-500 outline-none bg-slate-50">
                    <option value="">Any Status</option>
                    <option value="PENDING">Pending</option>
                    <option value="PAID">Paid</option>
                </select>
            </div>

            <div class="flex items-end">
                <button @click="manualCheck" class="bg-slate-800 hover:bg-slate-900 text-white px-6 py-2.5 rounded-xl text-xs font-black transition-all shadow-md active:scale-95" :disabled="!filters.plate">
                    CHECK AGAIN
                </button>
            </div>
        </div>

        <div v-if="loading" class="grid gap-4">
            <div v-for="i in 3" :key="i" class="h-32 bg-white rounded-2xl border border-slate-100 animate-pulse"></div>
        </div>

        <div v-else class="grid gap-4">
            <div v-if="items.length === 0" class="bg-white p-20 text-center rounded-2xl border-2 border-dashed border-slate-200">
                <p class="text-slate-400 font-bold uppercase tracking-widest">No fines found</p>
            </div>

            <div v-for="fine in items" :key="fine.id"
                 class="bg-white p-6 rounded-2xl shadow-sm border-l-8 transition-all hover:shadow-md"
                 :class="fine.show_penalty_warning ? 'border-rose-500' : 'border-slate-200'">

                <div class="flex justify-between items-start">
                    <div>
                        <div class="flex items-center gap-3">
                            <h2 class="font-black text-2xl text-slate-900 tracking-tighter uppercase">{{ fine.plate_number }}</h2>
                            <span v-if="fine.assigned_driver_name" class="bg-blue-100 text-blue-700 text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-widest">
                                {{ fine.assigned_driver_name }}
                            </span>
                            <span v-else class="bg-slate-100 text-slate-400 text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-widest">
                                Unassigned
                            </span>
                        </div>

                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-xs font-bold" :class="fine.is_overdue && fine.status === 'PENDING' ? 'text-rose-600' : 'text-slate-500'">
                                {{ fine.issued_at_human }}
                            </span>
                            <span v-if="fine.is_overdue && fine.status === 'PENDING'" class="text-[10px] bg-rose-600 text-white px-1.5 py-0.5 rounded font-black">OVERDUE</span>
                        </div>
                    </div>

                    <div class="text-right">
                        <div class="text-2xl font-black text-slate-900">{{ formatAmount(fine.ticket_amount) }} <span class="text-xs text-slate-400 font-bold">FRW</span></div>
                        <span class="text-[10px] font-black px-2.5 py-1 rounded-md uppercase tracking-wider" :class="statusClass(fine.status)">
                            {{ fine.status }}
                        </span>
                    </div>
                </div>

                <div v-if="fine.show_penalty_warning" class="mt-4 bg-rose-50 border border-rose-100 p-3 rounded-xl flex items-center gap-3">
                    <div class="w-8 h-8 bg-rose-500 text-white rounded-full flex items-center justify-center font-bold italic">!</div>
                    <div>
                        <p class="text-xs font-black text-rose-700 uppercase">Penalty Alert</p>
                        <p class="text-[10px] text-rose-600 font-medium">This ticket is more than 14 days old and remains unpaid.</p>
                    </div>
                </div>

                <div class="mt-5 pt-5 border-t border-slate-50">
                    <div v-for="v in fine.violations" :key="v.id" class="flex justify-between items-center mb-1">
                        <span class="text-sm font-bold text-slate-600">{{ v.violation_name }}</span>
                        <span class="text-sm font-black text-slate-800 tabular-nums">{{ formatAmount(v.fine_amount) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="meta.last_page > 1" class="mt-10 flex items-center justify-center gap-4">
            <button @click="goto(meta.current_page - 1)" :disabled="meta.current_page === 1"
                    class="p-2 w-10 h-10 flex items-center justify-center bg-white border border-slate-200 rounded-xl disabled:opacity-30 hover:bg-slate-50 transition-all font-black text-lg"> ‹ </button>
            <span class="text-xs font-black text-slate-500 uppercase tracking-tighter">Page {{ meta.current_page }} / {{ meta.last_page }}</span>
            <button @click="goto(meta.current_page + 1)" :disabled="meta.current_page === meta.last_page"
                    class="p-2 w-10 h-10 flex items-center justify-center bg-white border border-slate-200 rounded-xl disabled:opacity-30 hover:bg-slate-50 transition-all font-black text-lg"> › </button>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { api } from "../../../plugins/axios";

const filters = ref({ type: '', plate: '', status: '' });
const items = ref([]);
const loading = ref(false);
const meta = ref({});
let debounceTimer = null;

const fetch = async (page = 1) => {
    loading.value = true;
    try {
        const res = await api.get('/portal/fines', { params: { ...filters.value, page } });
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

const goto = (page) => {
    if (page >= 1 && page <= meta.value.last_page) fetch(page);
};

const manualCheck = async () => {
    if (!filters.value.plate) return;
    try {
        await api.post('/portal/fines/check', {
            plate: filters.value.plate,
            type: filters.value.type || 'vehicle'
        });
        alert('Check request queued.');
    } catch (e) { console.error(e); }
};

const formatAmount = (num) => num ? Number(num).toLocaleString() : '0';

const statusClass = (s) => {
    if (s === 'PAID') return 'bg-emerald-100 text-emerald-700';
    if (s === 'PENDING') return 'bg-amber-100 text-amber-700';
    return 'bg-slate-100 text-slate-500';
};

fetch();
</script>
