<script setup lang="ts">
import { ref, onMounted } from 'vue'
import hrApi from '../../../api/hr'

const requests = ref<any[]>([])
const loading = ref(true)
const statusFilter = ref('')
const showCreate = ref(false)
const leaveTypes = ref<any[]>([])
const employees = ref<any[]>([])

const form = ref({ employee_id: '', leave_type_id: '', start_date: '', end_date: '', reason: '' })
const submitting = ref(false)

async function fetch() {
    loading.value = true
    try {
        const params: any = {}
        if (statusFilter.value) params.status = statusFilter.value
        const res = await hrApi.leaveRequests.index(params)
        requests.value = res.data.data ?? res.data
    } catch {} finally { loading.value = false }
}

function statusClass(s: string) {
    const map: Record<string, string> = { pending: 'bg-amber-100 text-amber-700', approved: 'bg-emerald-100 text-emerald-700', rejected: 'bg-red-100 text-red-700', cancelled: 'bg-gray-100 text-gray-500' }
    return map[s] ?? 'bg-gray-100 text-gray-600'
}

async function doApprove(id: number) {
    try { await hrApi.leaveRequests.approve(id); await fetch() } catch {}
}
async function doReject(id: number) {
    const reason = prompt('Rejection reason:')
    if (!reason) return
    try { await hrApi.leaveRequests.reject(id, reason); await fetch() } catch {}
}
async function doCancel(id: number) {
    if (!confirm('Cancel this leave request?')) return
    try { await hrApi.leaveRequests.cancel(id); await fetch() } catch {}
}

async function openCreate() {
    showCreate.value = true
    try {
        const [ltRes, empRes] = await Promise.all([
            hrApi.leaveTypes.index(),
            hrApi.employees.index({ per_page: 200 }),
        ])
        leaveTypes.value = ltRes.data
        employees.value = empRes.data.data ?? empRes.data
    } catch {}
}

async function submitLeave() {
    submitting.value = true
    try {
        await hrApi.leaveRequests.store(form.value)
        showCreate.value = false
        form.value = { employee_id: '', leave_type_id: '', start_date: '', end_date: '', reason: '' }
        await fetch()
    } catch (e: any) { alert(e.response?.data?.message || 'Failed') } finally { submitting.value = false }
}

onMounted(fetch)
</script>

<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-slate-800">Leave Requests</h1>
            <button @click="openCreate" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors text-sm font-medium">New Request</button>
        </div>

        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-4 mb-6">
            <select v-model="statusFilter" @change="fetch" class="text-sm border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500">
                <option value="">All Statuses</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
                <option value="cancelled">Cancelled</option>
            </select>
        </div>

        <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
            <div v-if="loading" class="p-8 text-center text-slate-400">Loading...</div>
            <table v-else class="w-full">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/50">
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Employee</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Type</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">From</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">To</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Days</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Status</th>
                        <th class="text-right px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="r in requests" :key="r.id" class="border-b border-slate-50 hover:bg-slate-50/50">
                        <td class="px-4 py-3 text-sm text-slate-700">{{ r.employee?.first_name }} {{ r.employee?.last_name }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ r.leave_type?.name ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ r.start_date }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ r.end_date }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ r.days }}</td>
                        <td class="px-4 py-3"><span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-medium" :class="statusClass(r.status)">{{ r.status }}</span></td>
                        <td class="px-4 py-3 text-right">
                            <span v-if="r.status === 'pending'" class="flex gap-1 justify-end">
                                <button @click="doApprove(r.id)" class="px-2 py-1 text-xs bg-emerald-100 text-emerald-700 rounded hover:bg-emerald-200">Approve</button>
                                <button @click="doReject(r.id)" class="px-2 py-1 text-xs bg-red-100 text-red-700 rounded hover:bg-red-200">Reject</button>
                                <button @click="doCancel(r.id)" class="px-2 py-1 text-xs bg-gray-100 text-gray-600 rounded hover:bg-gray-200">Cancel</button>
                            </span>
                        </td>
                    </tr>
                    <tr v-if="!requests.length && !loading"><td colspan="7" class="px-4 py-8 text-center text-slate-400">No leave requests found.</td></tr>
                </tbody>
            </table>
        </div>

        <!-- Create Modal -->
        <div v-if="showCreate" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" @click.self="showCreate = false">
            <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-lg">
                <h3 class="text-lg font-semibold text-slate-700 mb-4">New Leave Request</h3>
                <form @submit.prevent="submitLeave" class="space-y-3">
                    <div><label class="block text-xs font-medium text-slate-500 mb-1">Employee</label><select v-model="form.employee_id" required class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500"><option value="">Select...</option><option v-for="e in employees" :key="e.id" :value="e.id">{{ e.first_name }} {{ e.last_name }}</option></select></div>
                    <div><label class="block text-xs font-medium text-slate-500 mb-1">Leave Type</label><select v-model="form.leave_type_id" required class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500"><option value="">Select...</option><option v-for="lt in leaveTypes" :key="lt.id" :value="lt.id">{{ lt.name }}</option></select></div>
                    <div class="grid grid-cols-2 gap-3">
                        <div><label class="block text-xs font-medium text-slate-500 mb-1">Start Date</label><input v-model="form.start_date" type="date" required class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500" /></div>
                        <div><label class="block text-xs font-medium text-slate-500 mb-1">End Date</label><input v-model="form.end_date" type="date" required class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500" /></div>
                    </div>
                    <div><label class="block text-xs font-medium text-slate-500 mb-1">Reason</label><textarea v-model="form.reason" rows="2" class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500"></textarea></div>
                    <div class="flex justify-end gap-3 mt-4">
                        <button type="button" @click="showCreate = false" class="px-4 py-2 text-sm text-slate-600 border border-slate-200 rounded-lg">Cancel</button>
                        <button type="submit" :disabled="submitting" class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-medium disabled:opacity-50">{{ submitting ? 'Submitting...' : 'Submit' }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
