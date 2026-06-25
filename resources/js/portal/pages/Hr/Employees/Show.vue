<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import hrApi from '../../../api/hr'

const route = useRoute()
const emp = ref<any>(null)
const loading = ref(true)
const tab = ref('details')

async function fetch() {
    loading.value = true
    try {
        const res = await hrApi.employees.show(route.params.id)
        emp.value = res.data
    } catch {} finally { loading.value = false }
}

function statusClass(s: string) {
    const map: Record<string, string> = { active: 'bg-emerald-100 text-emerald-700', suspended: 'bg-amber-100 text-amber-700', terminated: 'bg-red-100 text-red-700', resigned: 'bg-gray-100 text-gray-600' }
    return map[s] ?? 'bg-gray-100 text-gray-600'
}

onMounted(fetch)
</script>

<template>
    <div>
        <div class="flex items-center gap-4 mb-6">
            <router-link to="/hr/employees" class="text-slate-400 hover:text-slate-600">&larr; Employees</router-link>
            <h1 v-if="emp" class="text-2xl font-bold text-slate-800">{{ emp.first_name }} {{ emp.last_name }}</h1>
        </div>

        <div v-if="loading" class="text-center py-12 text-slate-400">Loading...</div>

        <template v-if="emp && !loading">
            <div class="flex items-center gap-4 mb-6">
                <span class="text-sm text-slate-400 font-mono">{{ emp.employee_number }}</span>
                <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-medium" :class="statusClass(emp.employment_status)">{{ emp.employment_status }}</span>
            </div>

            <div class="flex gap-2 mb-6 border-b border-slate-200">
                <button @click="tab = 'details'" :class="['px-4 py-2 text-sm font-medium border-b-2 transition-colors', tab === 'details' ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-slate-500 hover:text-slate-700']">Details</button>
                <button @click="tab = 'attendance'" :class="['px-4 py-2 text-sm font-medium border-b-2 transition-colors', tab === 'attendance' ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-slate-500 hover:text-slate-700']">Attendance</button>
                <button @click="tab = 'leave'" :class="['px-4 py-2 text-sm font-medium border-b-2 transition-colors', tab === 'leave' ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-slate-500 hover:text-slate-700']">Leave</button>
            </div>

            <div v-if="tab === 'details'" class="grid grid-cols-2 gap-6">
                <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6 space-y-4">
                    <h2 class="text-lg font-semibold text-slate-700">Personal Info</h2>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div><span class="text-slate-400">Email</span><p class="font-medium text-slate-700">{{ emp.email ?? '—' }}</p></div>
                        <div><span class="text-slate-400">Phone</span><p class="font-medium text-slate-700">{{ emp.phone ?? '—' }}</p></div>
                        <div><span class="text-slate-400">Hire Date</span><p class="font-medium text-slate-700">{{ emp.hire_date }}</p></div>
                        <div><span class="text-slate-400">Created by</span><p class="font-medium text-slate-700">{{ emp.creator?.name ?? '—' }}</p></div>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6 space-y-4">
                    <h2 class="text-lg font-semibold text-slate-700">Employment</h2>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div><span class="text-slate-400">Department</span><p class="font-medium text-slate-700">{{ emp.department?.name ?? '—' }}</p></div>
                        <div><span class="text-slate-400">Position</span><p class="font-medium text-slate-700">{{ emp.position?.title ?? '—' }}</p></div>
                        <div><span class="text-slate-400">Salary</span><p class="font-medium text-slate-700">{{ emp.salary_currency?.code ?? '' }} {{ emp.salary }}</p></div>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6 space-y-4">
                    <h2 class="text-lg font-semibold text-slate-700">Emergency Contact</h2>
                    <div class="text-sm">
                        <p class="text-slate-700 font-medium">{{ emp.emergency_contact_name ?? '—' }}</p>
                        <p class="text-slate-500">{{ emp.emergency_contact_phone ?? '' }}</p>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6 space-y-4">
                    <h2 class="text-lg font-semibold text-slate-700">Bank Details</h2>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div><span class="text-slate-400">Bank</span><p class="font-medium text-slate-700">{{ emp.bank_name ?? '—' }}</p></div>
                        <div><span class="text-slate-400">Account</span><p class="font-medium text-slate-700">{{ emp.bank_account ?? '—' }}</p></div>
                    </div>
                </div>
            </div>

            <div v-if="tab === 'attendance'" class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead><tr class="border-b border-slate-100 bg-slate-50/50"><th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Date</th><th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Clock In</th><th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Clock Out</th><th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Status</th></tr></thead>
                    <tbody>
                        <tr v-for="a in emp.attendance_records" :key="a.id" class="border-b border-slate-50">
                            <td class="px-4 py-2 text-slate-700">{{ a.date }}</td>
                            <td class="px-4 py-2 text-slate-700">{{ a.clock_in ?? '—' }}</td>
                            <td class="px-4 py-2 text-slate-700">{{ a.clock_out ?? '—' }}</td>
                            <td class="px-4 py-2"><span class="inline-block px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-600">{{ a.status }}</span></td>
                        </tr>
                        <tr v-if="!emp.attendance_records?.length"><td colspan="4" class="px-4 py-8 text-center text-slate-400">No attendance records.</td></tr>
                    </tbody>
                </table>
            </div>

            <div v-if="tab === 'leave'" class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead><tr class="border-b border-slate-100 bg-slate-50/50"><th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Type</th><th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">From</th><th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">To</th><th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Days</th><th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Status</th></tr></thead>
                    <tbody>
                        <tr v-for="l in emp.leave_requests" :key="l.id" class="border-b border-slate-50">
                            <td class="px-4 py-2 text-slate-700">{{ l.leave_type?.name ?? '—' }}</td>
                            <td class="px-4 py-2 text-slate-700">{{ l.start_date }}</td>
                            <td class="px-4 py-2 text-slate-700">{{ l.end_date }}</td>
                            <td class="px-4 py-2 text-slate-700">{{ l.days }}</td>
                            <td class="px-4 py-2"><span class="inline-block px-2 py-0.5 rounded text-xs font-medium" :class="{'bg-amber-100 text-amber-700': l.status === 'pending', 'bg-emerald-100 text-emerald-700': l.status === 'approved', 'bg-red-100 text-red-700': l.status === 'rejected', 'bg-gray-100 text-gray-500': l.status === 'cancelled'}">{{ l.status }}</span></td>
                        </tr>
                        <tr v-if="!emp.leave_requests?.length"><td colspan="5" class="px-4 py-8 text-center text-slate-400">No leave requests.</td></tr>
                    </tbody>
                </table>
            </div>
        </template>
    </div>
</template>
