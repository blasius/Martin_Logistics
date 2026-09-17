<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { podApi } from '../../api/proofs-of-delivery'

const pods = ref([])
const loading = ref(true)
const statusFilter = ref('')

const statusTabs = [
    { value: '', label: 'All' },
    { value: 'submitted', label: 'Submitted' },
    { value: 'confirmed', label: 'Confirmed' },
    { value: 'draft', label: 'Draft' },
]

onMounted(load)

async function load() {
    loading.value = true
    try {
        const res = await podApi.getAll(statusFilter.value ? { status: statusFilter.value } : {})
        pods.value = res.data
    } catch {} finally {
        loading.value = false
    }
}

function setFilter(value: string) {
    statusFilter.value = value
    load()
}

function formatDate(d: string | null) {
    if (!d) return '-'
    return new Date(d).toLocaleDateString('en-US', {
        month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit',
    })
}

function statusClass(s: string) {
    const map: Record<string, string> = {
        draft: 'bg-slate-100 text-slate-600',
        submitted: 'bg-amber-50 text-amber-700',
        confirmed: 'bg-emerald-50 text-emerald-700',
    }
    return map[s] || 'bg-slate-100 text-slate-600'
}

async function confirmPod(id: number) {
    if (!confirm('Confirm this proof of delivery?')) return
    try {
        await podApi.confirm(id)
        const pod = pods.value.find((p: any) => p.id === id)
        if (pod) pod.status = 'confirmed'
    } catch {}
}

async function rejectPod(id: number) {
    const reason = prompt('Rejection reason (required):')
    if (reason === null || !reason.trim()) {
        if (reason !== null) alert('A reason is required to reject a proof of delivery.')
        return
    }
    if (!confirm('Reject this proof of delivery?')) return
    try {
        await podApi.reject(id, reason.trim())
        await load()
    } catch {}
}
</script>

<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-slate-800">Proofs of Delivery</h1>
            <router-link to="/proofs-of-delivery/create"
                class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                New POD
            </router-link>
        </div>

        <div class="flex items-center gap-2 mb-6">
            <div class="inline-flex bg-slate-100 rounded-lg p-1 gap-1">
                <button v-for="tab in statusTabs" :key="tab.value" @click="setFilter(tab.value)"
                    class="px-4 py-2 text-xs font-semibold rounded-md transition-colors"
                    :class="statusFilter === tab.value ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                    {{ tab.label }}
                </button>
            </div>
        </div>

        <div v-if="loading" class="bg-white rounded-xl border border-slate-100 shadow-sm p-8">
            <div class="animate-pulse space-y-4">
                <div class="h-4 bg-slate-100 rounded w-1/4"></div>
                <div class="h-10 bg-slate-50 rounded"></div>
                <div class="h-10 bg-slate-50 rounded"></div>
            </div>
        </div>

        <div v-else-if="pods.length === 0" class="bg-white rounded-xl border border-slate-100 shadow-sm p-12 text-center">
            <div class="w-16 h-16 rounded-2xl bg-slate-50 flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
            </div>
            <p class="text-slate-600 font-semibold">No proofs of delivery yet</p>
            <p class="text-slate-400 text-sm mt-1">PODs will appear here once drivers submit them.</p>
        </div>

        <div v-else class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
            <table class="w-full">
                <thead>
                    <tr class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider border-b border-slate-100">
                        <th class="px-6 py-4">Order</th>
                        <th class="px-6 py-4">Received By</th>
                        <th class="px-6 py-4">Delivered At</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4">Signature</th>
                        <th class="px-6 py-4">Photo</th>
                        <th class="px-6 py-4">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="pod in pods" :key="pod.id" class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4">
                            <router-link :to="`/proofs-of-delivery/${pod.id}`" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">
                                {{ pod.order?.reference || '-' }}
                            </router-link>
                            <p class="text-xs text-slate-400">{{ pod.order?.client_name || '' }}</p>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-700">{{ pod.received_by_name }}</td>
                        <td class="px-6 py-4 text-sm text-slate-600">{{ formatDate(pod.delivered_at) }}</td>
                        <td class="px-6 py-4">
                            <span class="inline-flex text-xs font-bold px-3 py-1 rounded-full" :class="statusClass(pod.status)">
                                {{ pod.status }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span v-if="pod.signature_data" class="text-xs text-emerald-600 font-semibold">Captured</span>
                            <span v-else class="text-xs text-slate-400">—</span>
                        </td>
                        <td class="px-6 py-4">
                            <span v-if="pod.photo_path" class="text-xs text-emerald-600 font-semibold">Uploaded</span>
                            <span v-else class="text-xs text-slate-400">—</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <router-link :to="`/proofs-of-delivery/${pod.id}`"
                                    class="text-xs font-semibold text-indigo-600 hover:text-indigo-700">View</router-link>
                                <button v-if="pod.status === 'submitted'"
                                    @click="confirmPod(pod.id)"
                                    class="text-xs font-semibold text-emerald-600 hover:text-emerald-700">Confirm</button>
                                <button v-if="pod.status === 'submitted'"
                                    @click="rejectPod(pod.id)"
                                    class="text-xs font-semibold text-rose-500 hover:text-rose-700">Reject</button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
