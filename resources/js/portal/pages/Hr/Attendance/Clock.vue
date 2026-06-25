<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import hrApi from '../../../api/hr'

const router = useRouter()
const employees = ref<any[]>([])
const employeeId = ref('')
const loading = ref(false)
const message = ref('')

async function load() {
    try {
        const res = await hrApi.employees.index({ per_page: 200, status: 'active' })
        employees.value = res.data.data ?? res.data
    } catch {}
}

async function clockIn() {
    if (!employeeId.value) return
    loading.value = true; message.value = ''
    try {
        await hrApi.attendance.clockIn({ employee_id: employeeId.value })
        message.value = 'Clocked in successfully'
    } catch (e: any) { message.value = e.response?.data?.message || 'Error' } finally { loading.value = false }
}

async function clockOut() {
    if (!employeeId.value) return
    loading.value = true; message.value = ''
    try {
        await hrApi.attendance.clockOut({ employee_id: employeeId.value })
        message.value = 'Clocked out successfully'
    } catch (e: any) { message.value = e.response?.data?.message || 'Error' } finally { loading.value = false }
}

onMounted(load)
</script>

<template>
    <div>
        <div class="flex items-center gap-4 mb-6">
            <router-link to="/hr/attendance" class="text-slate-400 hover:text-slate-600">&larr; Attendance</router-link>
            <h1 class="text-2xl font-bold text-slate-800">Clock In / Out</h1>
        </div>

        <div class="max-w-md mx-auto mt-8 bg-white rounded-xl border border-slate-100 shadow-sm p-8 text-center space-y-6">
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Select Employee</label>
                <select v-model="employeeId" class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500">
                    <option value="">Choose...</option>
                    <option v-for="e in employees" :key="e.id" :value="e.id">{{ e.first_name }} {{ e.last_name }} ({{ e.employee_number }})</option>
                </select>
            </div>

            <div class="flex gap-4 justify-center">
                <button @click="clockIn" :disabled="!employeeId || loading" class="px-6 py-3 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 text-sm font-medium disabled:opacity-50">Clock In</button>
                <button @click="clockOut" :disabled="!employeeId || loading" class="px-6 py-3 bg-amber-600 text-white rounded-xl hover:bg-amber-700 text-sm font-medium disabled:opacity-50">Clock Out</button>
            </div>

            <p v-if="message" class="text-sm mt-4" :class="{'text-emerald-600': !message.includes('Error'), 'text-red-600': message.includes('Error')}">{{ message }}</p>
        </div>
    </div>
</template>
