<script setup lang="ts">
import { ref, onMounted } from 'vue'
import hrApi from '../../../api/hr'

const records = ref<any[]>([])
const loading = ref(true)
const dateFilter = ref(new Date().toISOString().slice(0, 10))
const employeeFilter = ref('')
const employees = ref<any[]>([])

async function fetch() {
    loading.value = true
    try {
        const params: any = {}
        if (dateFilter.value) params.date = dateFilter.value
        if (employeeFilter.value) params.employee_id = employeeFilter.value
        const res = await hrApi.attendance.index(params)
        records.value = res.data.data ?? res.data
    } catch {} finally { loading.value = false }
}

async function loadEmployees() {
    try {
        const res = await hrApi.employees.index({ per_page: 200 })
        employees.value = res.data.data ?? res.data
    } catch {}
}

function statusClass(s: string) {
    const map: Record<string, string> = { present: 'bg-emerald-100 text-emerald-700', late: 'bg-amber-100 text-amber-700', absent: 'bg-red-100 text-red-700', half_day: 'bg-blue-100 text-blue-700' }
    return map[s] ?? 'bg-gray-100 text-gray-600'
}

import { useRouter } from 'vue-router'
const router = useRouter()

onMounted(() => { loadEmployees(); fetch() })
</script>

<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-slate-800">Attendance</h1>
            <router-link to="/hr/attendance/clock"
                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium">
                Clock In / Out
            </router-link>
        </div>

        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-4 mb-6">
            <div class="flex flex-wrap gap-4 items-end">
                <div><label class="block text-xs font-medium text-slate-500 mb-1">Date</label><input v-model="dateFilter" type="date" @change="fetch" class="text-sm border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500" /></div>
                <div><label class="block text-xs font-medium text-slate-500 mb-1">Employee</label><select v-model="employeeFilter" @change="fetch" class="text-sm border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500"><option value="">All</option><option v-for="e in employees" :key="e.id" :value="e.id">{{ e.first_name }} {{ e.last_name }}</option></select></div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
            <div v-if="loading" class="p-8 text-center text-slate-400">Loading...</div>
            <table v-else class="w-full">
                <thead><tr class="border-b border-slate-100 bg-slate-50/50"><th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Employee</th><th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Date</th><th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Clock In</th><th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Clock Out</th><th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Status</th></tr></thead>
                <tbody>
                    <tr v-for="r in records" :key="r.id" class="border-b border-slate-50">
                        <td class="px-4 py-3 text-sm text-slate-700">{{ r.employee?.first_name }} {{ r.employee?.last_name }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ r.date }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ r.clock_in ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ r.clock_out ?? '—' }}</td>
                        <td class="px-4 py-3"><span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-medium" :class="statusClass(r.status)">{{ r.status }}</span></td>
                    </tr>
                    <tr v-if="!records.length && !loading"><td colspan="5" class="px-4 py-8 text-center text-slate-400">No attendance records for this date.</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
