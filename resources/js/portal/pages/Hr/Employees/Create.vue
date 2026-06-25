<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import hrApi from '../../../api/hr'

const router = useRouter()

const form = ref({
    first_name: '', last_name: '', email: '', phone: '',
    hire_date: '', employment_status: 'active',
    department_id: '', position_id: '',
    salary: 0, salary_currency_id: '',
    emergency_contact_name: '', emergency_contact_phone: '',
    bank_name: '', bank_account: '', bank_code: '',
    user_id: '',
})

const departments = ref<any[]>([])
const positions = ref<any[]>([])
const currencies = ref<any[]>([])
const submitting = ref(false)

async function loadRefs() {
    try {
        const [dRes, cRes] = await Promise.all([
            hrApi.departments.index(),
            (await import('../../../api/currencies')).default.index(),
        ])
        departments.value = dRes.data
        currencies.value = cRes.data.data ?? cRes.data
    } catch {}
}

watch(() => form.value.department_id, async (deptId) => {
    if (!deptId) { positions.value = []; return }
    try {
        const res = await hrApi.positions.index({ department_id: deptId })
        positions.value = res.data
    } catch { positions.value = [] }
})

async function submit() {
    submitting.value = true
    try {
        const res = await hrApi.employees.store(form.value)
        router.push(`/hr/employees/${res.data.id}`)
    } catch (e: any) {
        alert(e.response?.data?.message || 'Failed to create employee')
    } finally { submitting.value = false }
}

onMounted(loadRefs)
</script>

<template>
    <div>
        <div class="flex items-center gap-4 mb-6">
            <router-link to="/hr/employees" class="text-slate-400 hover:text-slate-600">&larr; Back</router-link>
            <h1 class="text-2xl font-bold text-slate-800">Add Employee</h1>
        </div>

        <form @submit.prevent="submit" class="max-w-3xl space-y-6">
            <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6 space-y-4">
                <h2 class="text-lg font-semibold text-slate-700">Personal Information</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="block text-xs font-medium text-slate-500 mb-1">First Name</label><input v-model="form.first_name" required class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500" /></div>
                    <div><label class="block text-xs font-medium text-slate-500 mb-1">Last Name</label><input v-model="form.last_name" required class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500" /></div>
                    <div><label class="block text-xs font-medium text-slate-500 mb-1">Email</label><input v-model="form.email" type="email" class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500" /></div>
                    <div><label class="block text-xs font-medium text-slate-500 mb-1">Phone</label><input v-model="form.phone" class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500" /></div>
                    <div><label class="block text-xs font-medium text-slate-500 mb-1">Hire Date</label><input v-model="form.hire_date" type="date" required class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500" /></div>
                    <div><label class="block text-xs font-medium text-slate-500 mb-1">Status</label><select v-model="form.employment_status" class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500"><option value="active">Active</option><option value="suspended">Suspended</option><option value="terminated">Terminated</option><option value="resigned">Resigned</option></select></div>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6 space-y-4">
                <h2 class="text-lg font-semibold text-slate-700">Employment Details</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="block text-xs font-medium text-slate-500 mb-1">Department</label><select v-model="form.department_id" class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500"><option value="">Select...</option><option v-for="d in departments" :key="d.id" :value="d.id">{{ d.name }}</option></select></div>
                    <div><label class="block text-xs font-medium text-slate-500 mb-1">Position</label><select v-model="form.position_id" class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500"><option value="">Select...</option><option v-for="p in positions" :key="p.id" :value="p.id">{{ p.title }}</option></select></div>
                    <div><label class="block text-xs font-medium text-slate-500 mb-1">Salary</label><input v-model.number="form.salary" type="number" min="0" step="0.01" class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500" /></div>
                    <div><label class="block text-xs font-medium text-slate-500 mb-1">Currency</label><select v-model="form.salary_currency_id" class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500"><option value="">Select...</option><option v-for="c in currencies" :key="c.id" :value="c.id">{{ c.code }}</option></select></div>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6 space-y-4">
                <h2 class="text-lg font-semibold text-slate-700">Emergency Contact</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="block text-xs font-medium text-slate-500 mb-1">Name</label><input v-model="form.emergency_contact_name" class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500" /></div>
                    <div><label class="block text-xs font-medium text-slate-500 mb-1">Phone</label><input v-model="form.emergency_contact_phone" class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500" /></div>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6 space-y-4">
                <h2 class="text-lg font-semibold text-slate-700">Bank Details</h2>
                <div class="grid grid-cols-3 gap-4">
                    <div><label class="block text-xs font-medium text-slate-500 mb-1">Bank Name</label><input v-model="form.bank_name" class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500" /></div>
                    <div><label class="block text-xs font-medium text-slate-500 mb-1">Account #</label><input v-model="form.bank_account" class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500" /></div>
                    <div><label class="block text-xs font-medium text-slate-500 mb-1">Bank Code</label><input v-model="form.bank_code" class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500" /></div>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <router-link to="/hr/employees" class="px-4 py-2 text-sm text-slate-600 border border-slate-200 rounded-lg hover:bg-slate-50">Cancel</router-link>
                <button type="submit" :disabled="submitting" class="px-6 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 text-sm font-medium disabled:opacity-50">{{ submitting ? 'Saving...' : 'Create Employee' }}</button>
            </div>
        </form>
    </div>
</template>
