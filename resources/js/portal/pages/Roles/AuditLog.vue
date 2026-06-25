<template>
    <div class="p-6 max-w-5xl mx-auto">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">Audit Log</h1>
            <button @click="fetchLog" class="border border-slate-200 text-slate-600 hover:bg-slate-50 px-3 py-1.5 rounded-lg text-sm flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Refresh
            </button>
        </div>

        <div v-if="loading" class="text-center py-12 text-slate-400">Loading audit log...</div>

        <template v-else>
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <table class="w-full">
                    <thead class="bg-slate-50 text-left text-xs font-semibold text-slate-500 uppercase">
                        <tr>
                            <th class="px-4 py-3">Time</th>
                            <th class="px-4 py-3">Admin</th>
                            <th class="px-4 py-3">Action</th>
                            <th class="px-4 py-3">Target</th>
                            <th class="px-4 py-3">Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="entry in logs" :key="entry.id" class="text-sm">
                            <td class="px-4 py-3 text-slate-500 whitespace-nowrap">{{ dayjs(entry.created_at).format('MMM D, YYYY h:mm A') }}</td>
                            <td class="px-4 py-3">{{ entry.admin?.name || 'System' }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded text-xs font-medium"
                                      :class="actionClass(entry.action)">
                                    {{ entry.action.replace(/_/g, ' ') }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ entry.target_type }} #{{ entry.target_id }}</td>
                            <td class="px-4 py-3 text-xs text-slate-400 max-w-xs truncate">{{ formatDetails(entry.details) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="meta && meta.last_page > 1" class="flex items-center justify-between mt-4 text-sm">
                <span class="text-slate-500">Page {{ meta.current_page }} of {{ meta.last_page }}</span>
                <div class="flex gap-2">
                    <button @click="changePage(meta.current_page - 1)" :disabled="meta.current_page <= 1"
                            class="px-3 py-1 border border-slate-200 rounded-lg disabled:opacity-30 hover:bg-slate-50">Prev</button>
                    <button @click="changePage(meta.current_page + 1)" :disabled="meta.current_page >= meta.last_page"
                            class="px-3 py-1 border border-slate-200 rounded-lg disabled:opacity-30 hover:bg-slate-50">Next</button>
                </div>
            </div>

            <div v-if="!logs.length && !loading" class="text-center py-12 text-slate-400">No audit log entries.</div>
        </template>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { rolesApi } from '../../api/roles';
import dayjs from 'dayjs';

const logs = ref([]);
const loading = ref(true);
const meta = ref(null);

function actionClass(action) {
    if (action.includes('created') || action.includes('assigned')) return 'bg-green-100 text-green-700';
    if (action.includes('deleted') || action.includes('revoked')) return 'bg-red-100 text-red-700';
    if (action.includes('updated')) return 'bg-yellow-100 text-yellow-700';
    return 'bg-blue-100 text-blue-700';
}

function formatDetails(details) {
    if (!details) return '-';
    if (typeof details === 'string') return details;
    return JSON.stringify(details);
}

async function fetchLog() {
    loading.value = true;
    try {
        const res = await rolesApi.auditLog();
        logs.value = res.data.data;
        meta.value = { current_page: res.data.current_page, last_page: res.data.last_page };
    } catch (e) { console.error(e); }
    finally { loading.value = false; }
}

function changePage(page) {
    if (page < 1 || (meta.value && page > meta.value.last_page)) return;
    meta.value.current_page = page;
    fetchLog();
}

onMounted(fetchLog);
</script>
