<template>
    <div class="p-6 bg-slate-50 min-h-screen">
        <header class="mb-8">
            <h1 class="text-3xl font-black text-slate-800 tracking-tight">AUDIT LOGS</h1>
            <p class="text-sm font-bold text-slate-400 uppercase">Activity Trail</p>
        </header>

        <div class="flex flex-wrap gap-4 mb-6 bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
            <div class="flex flex-col gap-1">
                <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Model</label>
                <select v-model="filters.auditable_type" @change="fetch" class="border-slate-200 px-3 py-2 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-500 outline-none bg-slate-50">
                    <option value="">All Models</option>
                    <option v-for="(label, type) in modelMap" :key="type" :value="type">{{ label }}</option>
                </select>
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Action</label>
                <select v-model="filters.action" @change="fetch" class="border-slate-200 px-3 py-2 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-500 outline-none bg-slate-50">
                    <option value="">All Actions</option>
                    <option value="created">Created</option>
                    <option value="updated">Updated</option>
                    <option value="deleted">Deleted</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-[10px] font-black text-slate-400 uppercase ml-1">From</label>
                <input v-model="filters.date_from" @change="fetch" type="date" class="border-slate-200 px-3 py-2 rounded-xl text-sm font-medium focus:ring-2 focus:ring-blue-500 outline-none bg-slate-50" />
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-[10px] font-black text-slate-400 uppercase ml-1">To</label>
                <input v-model="filters.date_to" @change="fetch" type="date" class="border-slate-200 px-3 py-2 rounded-xl text-sm font-medium focus:ring-2 focus:ring-blue-500 outline-none bg-slate-50" />
            </div>

            <button @click="resetFilters" class="bg-slate-800 hover:bg-slate-900 text-white px-5 py-2 rounded-xl text-xs font-black transition-all shadow-md active:scale-95 self-end">
                CLEAR
            </button>
        </div>

        <div v-if="loading" class="grid gap-4">
            <div v-for="i in 5" :key="i" class="h-20 bg-white rounded-2xl border border-slate-100 animate-pulse"></div>
        </div>

        <div v-else class="grid gap-4">
            <div v-if="items.length === 0" class="bg-white p-20 text-center rounded-2xl border-2 border-dashed border-slate-200">
                <p class="text-slate-400 font-bold uppercase tracking-widest">No audit logs found</p>
            </div>

            <div v-for="log in items" :key="log.id" class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200 transition-all hover:shadow-md">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center gap-3">
                        <span class="text-xs font-black px-2.5 py-1 rounded-full uppercase tracking-wider" :class="actionClass(log.action)">
                            {{ log.action }}
                        </span>
                        <span class="text-sm font-bold text-slate-700">{{ formatModel(log.auditable_type) }} #{{ log.auditable_id }}</span>
                    </div>
                    <span class="text-xs font-bold text-slate-400 whitespace-nowrap">{{ formatDate(log.created_at) }}</span>
                </div>

                <p class="text-sm text-slate-600 mb-2">{{ log.description }}</p>

                <div class="flex items-center gap-4 text-xs font-bold text-slate-400">
                    <span v-if="log.user">by {{ log.user.name }}</span>
                    <span v-if="log.ip_address">{{ log.ip_address }}</span>
                </div>

                <div v-if="log.old_values || log.new_values" class="mt-3 pt-3 border-t border-slate-100">
                    <div v-for="(value, field) in log.new_values" :key="field" class="flex items-center gap-3 py-1 text-xs">
                        <span class="font-bold text-slate-500 w-32 shrink-0">{{ field }}</span>
                        <span v-if="log.old_values && log.old_values[field] !== undefined" class="text-rose-600 line-through">{{ displayValue(log.old_values[field]) }}</span>
                        <span class="text-emerald-600 font-bold">{{ displayValue(value) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="meta.last_page > 1" class="mt-10 flex items-center justify-center gap-4">
            <button @click="goto(meta.current_page - 1)" :disabled="meta.current_page === 1"
                    class="p-2 w-10 h-10 flex items-center justify-center bg-white border border-slate-200 rounded-xl disabled:opacity-30 hover:bg-slate-50 transition-all font-black text-lg">‹</button>
            <span class="text-xs font-black text-slate-500 uppercase tracking-tighter">Page {{ meta.current_page }} / {{ meta.last_page }}</span>
            <button @click="goto(meta.current_page + 1)" :disabled="meta.current_page === meta.last_page"
                    class="p-2 w-10 h-10 flex items-center justify-center bg-white border border-slate-200 rounded-xl disabled:opacity-30 hover:bg-slate-50 transition-all font-black text-lg">›</button>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { api } from "../../../plugins/axios";

const modelMap = {
    'App\\Models\\Order': 'Order',
    'App\\Models\\Trip': 'Trip',
    'App\\Models\\Vehicle': 'Vehicle',
    'App\\Models\\Driver': 'Driver',
    'App\\Models\\Client': 'Client',
    'App\\Models\\Requisition': 'Requisition',
    'App\\Models\\VehicleInspection': 'Vehicle Inspection',
    'App\\Models\\VehicleInsurance': 'Vehicle Insurance',
    'App\\Models\\TrafficFine': 'Traffic Fine',
    'App\\Models\\SupportTicket': 'Support Ticket',
    'App\\Models\\User': 'User',
};

const filters = ref({ auditable_type: '', action: '', date_from: '', date_to: '' });
const items = ref([]);
const loading = ref(false);
const meta = ref({});

const fetch = async (page = 1) => {
    loading.value = true;
    try {
        const params = { ...filters.value, page, per_page: 25 };
        Object.keys(params).forEach(k => { if (!params[k]) delete params[k]; });
        const res = await api.get('/portal/audit-logs', { params });
        items.value = res.data.data;
        meta.value = res.data;
    } finally {
        loading.value = false;
    }
};

const goto = (page) => {
    if (page >= 1 && page <= meta.value.last_page) fetch(page);
};

const resetFilters = () => {
    filters.value = { auditable_type: '', action: '', date_from: '', date_to: '' };
    fetch();
};

const formatModel = (type) => modelMap[type] || type.split('\\').pop();
const formatDate = (d) => {
    if (!d) return '';
    const date = new Date(d);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};
const displayValue = (v) => {
    if (v === null || v === undefined) return '—';
    if (typeof v === 'boolean') return v ? 'Yes' : 'No';
    if (typeof v === 'object') return JSON.stringify(v);
    return String(v);
};
const actionClass = (a) => {
    const map = {
        created: 'bg-emerald-100 text-emerald-700',
        updated: 'bg-blue-100 text-blue-700',
        deleted: 'bg-rose-100 text-rose-700',
        approved: 'bg-emerald-100 text-emerald-700',
        rejected: 'bg-orange-100 text-orange-700',
    };
    return map[a] || 'bg-slate-100 text-slate-500';
};

fetch();
</script>
