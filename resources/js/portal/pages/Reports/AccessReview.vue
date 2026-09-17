<template>
    <div class="h-screen flex flex-col bg-slate-50 overflow-hidden relative">
        <header class="bg-white border-b border-slate-200 px-8 py-4 flex flex-wrap items-center justify-between gap-4 shadow-sm z-10 no-print">
            <div class="flex items-center gap-4">
                <div class="p-2 bg-indigo-600 rounded-lg"><ShieldAlert class="w-6 h-6 text-white" /></div>
                <div>
                    <h1 class="text-xl font-black text-slate-800 uppercase leading-none">Access Review</h1>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1.5">Account hygiene, integrity &amp; permission audit</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <button @click="exportCsv" :disabled="!report?.findings.length" class="flex items-center gap-2 px-4 py-2 bg-emerald-700 text-white text-xs font-black rounded-lg hover:bg-emerald-800 transition-all disabled:opacity-40 disabled:cursor-not-allowed">
                    <Download class="w-4 h-4" /> CSV
                </button>
                <button @click="generate" class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-xs font-black rounded-lg hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-200">
                    <RefreshCw v-if="!loading" class="w-4 h-4" />
                    <span v-else class="w-3 h-3 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                    {{ loading ? 'Running...' : 'Run Review' }}
                </button>
            </div>
        </header>

        <div class="px-8 py-3 bg-white border-b border-slate-200 z-10 no-print">
            <div class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="block text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">Category</label>
                    <select v-model="filters.category" class="w-44 pl-3 pr-3 py-2 bg-slate-100 border-none rounded-lg text-xs font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500 outline-none">
                        <option value="">All Categories</option>
                        <option v-for="c in options.categories" :key="c" :value="c">{{ label(c) }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">Severity</label>
                    <select v-model="filters.severity" class="w-36 pl-3 pr-3 py-2 bg-slate-100 border-none rounded-lg text-xs font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500 outline-none">
                        <option value="">All</option>
                        <option v-for="s in options.severities" :key="s" :value="s">{{ s }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">Role</label>
                    <select v-model="filters.role" class="w-48 pl-3 pr-3 py-2 bg-slate-100 border-none rounded-lg text-xs font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500 outline-none">
                        <option value="">All Roles</option>
                        <option v-for="r in options.roles" :key="r" :value="r">{{ r }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">Perimeter</label>
                    <select v-model="filters.perimeter" class="w-36 pl-3 pr-3 py-2 bg-slate-100 border-none rounded-lg text-xs font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500 outline-none">
                        <option value="">All</option>
                        <option v-for="p in options.perimeters" :key="p" :value="p">{{ p }}</option>
                    </select>
                </div>
                <button @click="reset" class="px-4 py-2 bg-slate-100 text-slate-600 text-xs font-black rounded-lg hover:bg-slate-200 transition-all">Reset</button>
                <span class="ml-auto text-[10px] font-bold uppercase tracking-widest" :class="report?.auto_deactivate ? 'text-rose-500' : 'text-slate-400'">
                    Auto-deactivation {{ report?.auto_deactivate ? 'enabled' : 'off (report only)' }}
                </span>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto px-8 py-6 custom-scrollbar">
            <div v-if="loading" class="flex flex-col items-center justify-center py-24 gap-3">
                <span class="w-8 h-8 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin"></span>
                <p class="text-xs font-black text-slate-400 uppercase tracking-widest">Reviewing accounts...</p>
            </div>

            <template v-else-if="report">
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4 mb-6">
                    <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Total Users</p>
                        <p class="text-xl font-black text-slate-900">{{ report.summary.total_users }}</p>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mt-1">{{ report.summary.with_roles }} with roles</p>
                    </div>
                    <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Flagged</p>
                        <p class="text-xl font-black text-rose-600">{{ report.summary.flagged }}</p>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mt-1">Needs review</p>
                    </div>
                    <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Inactive</p>
                        <p class="text-xl font-black text-orange-500">{{ report.summary.inactive_users }}</p>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mt-1">Stale logins</p>
                    </div>
                    <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Locked</p>
                        <p class="text-xl font-black text-amber-500">{{ report.summary.locked }}</p>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mt-1">Deactivated accounts</p>
                    </div>
                    <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Idle Dispatchers</p>
                        <p class="text-xl font-black text-cyan-600">{{ report.summary.idle_dispatchers }}</p>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mt-1">No trips/ownership</p>
                    </div>
                    <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Drivers &amp; Mechanics</p>
                        <p class="text-xl font-black text-indigo-600">{{ report.summary.drivers_without_vehicle + report.summary.mechanics_inactive }}</p>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mt-1">{{ report.summary.drivers_without_vehicle }} drivers · {{ report.summary.mechanics_inactive }} mechanics</p>
                    </div>
                    <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Orphan Roles</p>
                        <p class="text-xl font-black text-fuchsia-600">{{ report.summary.orphan_roles }}</p>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mt-1">Deactivated role links</p>
                    </div>
                </div>

                <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden mb-6">
                    <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                        <h3 class="text-[10px] font-black uppercase tracking-widest text-slate-500">Access Findings</h3>
                        <span class="text-xs font-black text-slate-500">{{ report.findings.length }} entries</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-slate-800 text-white">
                                <tr>
                                    <th class="px-4 py-3 text-[10px] font-black uppercase tracking-widest text-left">#</th>
                                    <th class="px-4 py-3 text-[10px] font-black uppercase tracking-widest text-left">Category</th>
                                    <th class="px-4 py-3 text-[10px] font-black uppercase tracking-widest text-left">Severity</th>
                                    <th class="px-4 py-3 text-[10px] font-black uppercase tracking-widest text-left">User</th>
                                    <th class="px-4 py-3 text-[10px] font-black uppercase tracking-widest text-left">Roles</th>
                                    <th class="px-4 py-3 text-[10px] font-black uppercase tracking-widest text-left">Perimeters</th>
                                    <th class="px-4 py-3 text-[10px] font-black uppercase tracking-widest text-left">Detail</th>
                                    <th class="px-4 py-3 text-[10px] font-black uppercase tracking-widest text-left">Last Activity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(f, i) in report.findings" :key="i" class="border-t border-slate-100 hover:bg-indigo-50/60 transition-colors" :class="f.severity === 'high' ? 'bg-rose-50/40' : (f.severity === 'medium' ? 'bg-amber-50/30' : '')">
                                    <td class="px-4 py-3 text-xs font-bold text-slate-400">{{ i + 1 }}</td>
                                    <td class="px-4 py-3 text-xs font-black text-slate-800 whitespace-nowrap">{{ label(f.category) }}</td>
                                    <td class="px-4 py-3 text-left">
                                        <span class="text-[9px] font-black px-2 py-1 rounded-full uppercase" :class="severityClass(f.severity)">{{ f.severity }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-slate-700">
                                        <span class="font-bold block">{{ f.name }}</span>
                                        <span class="text-slate-400">{{ f.email }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-slate-600">{{ f.roles.join(', ') }}</td>
                                    <td class="px-4 py-3 text-left">
                                        <span v-for="p in f.perimeters" :key="p" class="text-[9px] font-black px-2 py-1 rounded-full uppercase bg-slate-100 text-slate-600 mr-1">{{ p }}</span>
                                        <span v-if="!f.perimeters.length" class="text-slate-300">—</span>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-slate-600">{{ f.detail }}</td>
                                    <td class="px-4 py-3 text-xs text-slate-500 whitespace-nowrap">{{ fmtDate(f.last_activity) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-if="!report.findings.length" class="px-4 py-12 text-center">
                        <p class="text-xs font-black text-slate-400 uppercase tracking-widest">No findings for the selected filters — all clear</p>
                    </div>
                </div>

                <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
                    <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                        <h3 class="text-[10px] font-black uppercase tracking-widest text-slate-500">Role &amp; Permission Audit</h3>
                        <span class="text-xs font-black text-slate-500">{{ report.roles.length }} roles</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-slate-800 text-white">
                                <tr>
                                    <th class="px-4 py-3 text-[10px] font-black uppercase tracking-widest text-left">Role</th>
                                    <th class="px-4 py-3 text-[10px] font-black uppercase tracking-widest text-right">Users</th>
                                    <th class="px-4 py-3 text-[10px] font-black uppercase tracking-widest text-right">Permissions</th>
                                    <th class="px-4 py-3 text-[10px] font-black uppercase tracking-widest text-left">Perimeters</th>
                                    <th class="px-4 py-3 text-[10px] font-black uppercase tracking-widest text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="r in report.roles" :key="r.id" class="border-t border-slate-100 hover:bg-indigo-50/60 transition-colors">
                                    <td class="px-4 py-3 text-xs font-black text-slate-800">
                                        {{ r.name }}
                                        <span v-if="r.is_super_admin" class="ml-1 text-[9px] font-black px-2 py-0.5 rounded-full uppercase bg-indigo-100 text-indigo-700">super</span>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-right font-bold text-slate-700">{{ r.users_count }}</td>
                                    <td class="px-4 py-3 text-xs text-right text-slate-600">{{ r.permissions_count }}</td>
                                    <td class="px-4 py-3 text-left">
                                        <span v-for="p in r.perimeters" :key="p" class="text-[9px] font-black px-2 py-1 rounded-full uppercase bg-slate-100 text-slate-600 mr-1">{{ p }}</span>
                                        <span v-if="!r.perimeters.length" class="text-slate-300">—</span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="text-[9px] font-black px-2 py-1 rounded-full uppercase" :class="r.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'">{{ r.is_active ? 'active' : 'disabled' }}</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </template>

            <div v-else class="flex flex-col items-center justify-center py-24 gap-3">
                <ShieldAlert class="w-10 h-10 text-slate-300" />
                <p class="text-sm font-black text-slate-400 uppercase tracking-widest">Run the review to inspect account hygiene</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { Download, RefreshCw, ShieldAlert } from 'lucide-vue-next';
import dayjs from 'dayjs';
import { reportsApi } from '../../api/reports';

const options = reactive({ roles: [], categories: [], severities: [], perimeters: [] });
const filters = reactive({ category: '', severity: '', role: '', perimeter: '' });
const loading = ref(false);
const report = ref(null);

const label = (v) => String(v || '').replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());

const severityClass = (s) => ({
    high: 'bg-rose-100 text-rose-700',
    medium: 'bg-amber-100 text-amber-700',
    low: 'bg-slate-100 text-slate-500',
}[s] || 'bg-slate-100 text-slate-500');

const fetchOptions = async () => {
    const { data } = await reportsApi.accessReviewOptions();
    Object.assign(options, data);
};

const generate = async () => {
    loading.value = true;
    try {
        const params = {};
        Object.keys(filters).forEach((k) => {
            if (filters[k] !== '' && filters[k] !== null) params[k] = filters[k];
        });
        const { data } = await reportsApi.accessReview(params);
        report.value = data;
    } catch (e) {
        alert('Failed to run access review.');
    } finally {
        loading.value = false;
    }
};

const reset = () => {
    filters.category = '';
    filters.severity = '';
    filters.role = '';
    filters.perimeter = '';
    generate();
};

const fmtDate = (d) => (d ? dayjs(d).format('DD MMM YYYY HH:mm') : '—');

const exportCsv = () => {
    const rows = report.value?.findings || [];
    if (!rows.length) return;
    const headers = ['Category', 'Severity', 'Name', 'Email', 'Roles', 'Perimeters', 'Detail', 'Last Activity'];
    const csv = [
        headers.join(','),
        ...rows.map((r) => [
            r.category, r.severity, r.name, r.email, r.roles.join('|'),
            r.perimeters.join('|'), r.detail, fmtDate(r.last_activity),
        ].map((c) => `"${String(c ?? '').replace(/"/g, '""')}"`).join(',')),
    ].join('\n');
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `access_review_${dayjs().format('YYYY-MM-DD')}.csv`;
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
};

onMounted(() => {
    fetchOptions();
    generate();
});
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
</style>
