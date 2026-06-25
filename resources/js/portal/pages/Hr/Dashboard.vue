<script setup lang="ts">
import { ref, onMounted } from 'vue'
import hrApi from '../../api/hr'

const stats = ref<any>({})
const loading = ref(true)

onMounted(async () => {
    try {
        const res = await hrApi.stats()
        stats.value = res.data
    } catch {} finally { loading.value = false }
})
</script>

<template>
    <div>
        <h1 class="text-2xl font-bold text-slate-800 mb-6">HR Dashboard</h1>

        <div v-if="loading" class="text-center py-12 text-slate-400">Loading...</div>

        <div v-else class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
            <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-4 text-center">
                <p class="text-3xl font-bold text-slate-800">{{ stats.total_employees ?? 0 }}</p>
                <p class="text-xs text-slate-500 mt-1">Total Employees</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-4 text-center">
                <p class="text-3xl font-bold text-emerald-600">{{ stats.active_employees ?? 0 }}</p>
                <p class="text-xs text-slate-500 mt-1">Active</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-4 text-center">
                <p class="text-3xl font-bold text-slate-800">{{ stats.departments ?? 0 }}</p>
                <p class="text-xs text-slate-500 mt-1">Departments</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-4 text-center">
                <p class="text-3xl font-bold text-amber-600">{{ stats.pending_leave ?? 0 }}</p>
                <p class="text-xs text-slate-500 mt-1">Pending Leave</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-4 text-center">
                <p class="text-3xl font-bold text-blue-600">{{ stats.attendance_today ?? 0 }}</p>
                <p class="text-xs text-slate-500 mt-1">Clocked In Today</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-4 text-center">
                <p class="text-3xl font-bold text-purple-600">{{ stats.open_pay_period ?? 0 }}</p>
                <p class="text-xs text-slate-500 mt-1">Open Pay Periods</p>
            </div>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <router-link to="/hr/employees"
                class="bg-white rounded-xl border border-slate-100 shadow-sm p-5 hover:border-emerald-200 transition-colors">
                <p class="font-semibold text-slate-700">Employees</p>
                <p class="text-sm text-slate-400 mt-1">Manage staff records</p>
            </router-link>
            <router-link to="/hr/leave"
                class="bg-white rounded-xl border border-slate-100 shadow-sm p-5 hover:border-emerald-200 transition-colors">
                <p class="font-semibold text-slate-700">Leave Requests</p>
                <p class="text-sm text-slate-400 mt-1">Approve or review leave</p>
            </router-link>
            <router-link to="/hr/attendance"
                class="bg-white rounded-xl border border-slate-100 shadow-sm p-5 hover:border-emerald-200 transition-colors">
                <p class="font-semibold text-slate-700">Attendance</p>
                <p class="text-sm text-slate-400 mt-1">View clock in/out records</p>
            </router-link>
            <router-link to="/hr/payroll"
                class="bg-white rounded-xl border border-slate-100 shadow-sm p-5 hover:border-emerald-200 transition-colors">
                <p class="font-semibold text-slate-700">Payroll</p>
                <p class="text-sm text-slate-400 mt-1">Pay periods & payslips</p>
            </router-link>
        </div>
    </div>
</template>
