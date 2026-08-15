<template>
    <div class="h-screen flex flex-col bg-slate-50 overflow-hidden relative">
        <header class="bg-white border-b border-slate-200 px-8 py-4 flex flex-wrap items-center justify-between gap-4 shadow-sm z-10 no-print">
            <div class="flex items-center gap-4">
                <div class="p-2 bg-rose-600 rounded-lg"><Clock class="w-6 h-6 text-white" /></div>
                <div>
                    <h1 class="text-xl font-black text-slate-800 uppercase leading-none">Aging Report</h1>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1.5">Receivables by overdue period</p>
                </div>
            </div>
            <button @click="refresh" class="flex items-center gap-2 px-4 py-2 bg-slate-700 text-white text-xs font-black rounded-lg hover:bg-slate-800 transition-all">
                <RefreshCw class="w-4 h-4" /> Refresh
            </button>
        </header>

        <div class="flex-1 overflow-y-auto px-8 py-6 custom-scrollbar">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div v-for="(bucket, key) in report.buckets" :key="key" class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-[10px] font-black uppercase tracking-widest" :class="bucketColors[key].label">{{ bucket.label }}</h3>
                        <span class="text-[9px] font-black px-2 py-1 rounded-full uppercase" :class="bucketColors[key].badge">{{ bucket.invoices.length }} inv</span>
                    </div>
                    <p class="text-2xl font-black text-slate-900">{{ fmtAmount(bucket.total) }}</p>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mt-1">{{ bucket.invoices.length }} invoice(s)</p>
                </div>
            </div>

            <div v-for="(bucket, key) in report.buckets" :key="key" class="mb-6">
                <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
                    <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                        <h3 class="text-[10px] font-black uppercase tracking-widest" :class="bucketColors[key].label">{{ bucket.label }}</h3>
                        <span class="text-xs font-black text-slate-500">{{ fmtAmount(bucket.total) }}</span>
                    </div>
                    <table v-if="bucket.invoices.length" class="w-full">
                        <thead class="bg-slate-800 text-white">
                            <tr>
                                <th class="px-4 py-3 text-[10px] font-black uppercase tracking-widest text-left">Reference</th>
                                <th class="px-4 py-3 text-[10px] font-black uppercase tracking-widest text-left">Client</th>
                                <th class="px-4 py-3 text-[10px] font-black uppercase tracking-widest text-left">Total</th>
                                <th class="px-4 py-3 text-[10px] font-black uppercase tracking-widest text-left">Balance</th>
                                <th class="px-4 py-3 text-[10px] font-black uppercase tracking-widest text-left">Due Date</th>
                                <th class="px-4 py-3 text-[10px] font-black uppercase tracking-widest text-left">Days Overdue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="inv in bucket.invoices" :key="inv.id" class="border-t border-slate-100 hover:bg-indigo-50/60 transition-colors">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <router-link :to="`/invoices/${inv.id}`" class="text-xs font-black text-indigo-600 hover:text-indigo-800 uppercase">{{ inv.reference }}</router-link>
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-600 whitespace-nowrap">{{ inv.client_name }}</td>
                                <td class="px-4 py-3 text-xs text-slate-600 whitespace-nowrap">{{ fmtAmount(inv.total) }}</td>
                                <td class="px-4 py-3 text-xs font-black text-slate-900 whitespace-nowrap">{{ fmtAmount(inv.balance) }}</td>
                                <td class="px-4 py-3 text-xs text-slate-500 whitespace-nowrap">{{ inv.due_date }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="text-[9px] font-black px-2 py-1 rounded-full uppercase" :class="daysOverdueBadge(inv.days_overdue)">{{ inv.days_overdue }} days</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div v-else class="px-4 py-8 text-center">
                        <p class="text-xs font-black text-slate-400 uppercase tracking-widest">No invoices in this bucket</p>
                    </div>
                </div>
            </div>

            <div class="bg-slate-800 text-white rounded-xl p-5 flex items-center justify-between shadow-lg">
                <span class="text-[10px] font-black uppercase tracking-widest text-slate-300">Total Outstanding</span>
                <span class="text-2xl font-black">{{ fmtAmount(report.grand_total) }}</span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Clock, RefreshCw } from 'lucide-vue-next';
import { paymentsApi } from "../../api/payments";

const report = ref({
    buckets: {
        current: { label: "0-30 Days", invoices: [], total: 0 },
        "31_60": { label: "31-60 Days", invoices: [], total: 0 },
        "61_90": { label: "61-90 Days", invoices: [], total: 0 },
        "90_plus": { label: "90+ Days", invoices: [], total: 0 },
    },
    grand_total: 0,
});

const bucketColors = {
    current: { label: 'text-emerald-600', badge: 'bg-emerald-100 text-emerald-700' },
    '31_60': { label: 'text-amber-600', badge: 'bg-amber-100 text-amber-700' },
    '61_90': { label: 'text-orange-600', badge: 'bg-orange-100 text-orange-700' },
    '90_plus': { label: 'text-rose-600', badge: 'bg-rose-100 text-rose-700' },
};

const daysOverdueBadge = (days) =>
    days >= 90 ? 'bg-rose-100 text-rose-700'
    : days >= 60 ? 'bg-orange-100 text-orange-700'
    : days >= 30 ? 'bg-amber-100 text-amber-700'
    : 'bg-emerald-100 text-emerald-700';

const fmtAmount = (a) => Number(a || 0).toLocaleString();

const refresh = async () => {
    const { data } = await paymentsApi.aging();
    report.value = data;
};

onMounted(refresh);
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
</style>
