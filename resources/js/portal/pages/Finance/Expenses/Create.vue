<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '../../../../plugins/axios'
import { expensesApi } from '../../../api/expenses'
import { expenseTypesApi } from '../../../api/expense-types'

const router = useRouter()

const form = ref({
    expense_type_id: null as number | null,
    vehicle_id: null as number | null,
    driver_id: null as number | null,
    trip_id: null as number | null,
    route_id: null as number | null,
    name: '',
    description: '',
    category: '',
    amount: null as number | null,
    currency_id: null as number | null,
    location: '',
    odometer: null as number | null,
    expense_class: 'variable',
})

const expenseTypes = ref<any[]>([])
const vehicles = ref<any[]>([])
const drivers = ref<any[]>([])
const trips = ref<any[]>([])
const routes = ref<any[]>([])
const currencies = ref<any[]>([])
const categories = ref<string[]>([])
const submitting = ref(false)

const selectedTypeIsFixed = ref(false)

async function loadDependencies() {
    try {
        const [etRes, vehRes, curRes, catRes] = await Promise.all([
            expenseTypesApi.getAll({ active_only: '1' }),
            api.get('/portal/vehicles'),
            api.get('/portal/currencies'),
            expenseTypesApi.categories(),
        ])
        expenseTypes.value = etRes.data.data ?? etRes.data
        vehicles.value = vehRes.data.data ?? vehRes.data
        currencies.value = curRes.data
        categories.value = catRes.data
    } catch {}
}

function onTypeChange() {
    const t = expenseTypes.value.find((et: any) => et.id === form.value.expense_type_id)
    if (t) {
        selectedTypeIsFixed.value = t.expense_class === 'fixed'
        form.value.expense_class = t.expense_class
        form.value.name = t.name
        form.value.category = t.category ?? ''
        if (t.default_amount != null) {
            form.value.amount = Number(t.default_amount)
        }
        if (t.currency_id) {
            form.value.currency_id = t.currency_id
        }
    } else {
        selectedTypeIsFixed.value = false
    }
}

async function submit() {
    submitting.value = true
    try {
        const fd = new FormData()
        Object.entries(form.value).forEach(([k, v]) => {
            if (v !== null && v !== undefined && v !== '') fd.append(k, String(v))
        })
        const res = await expensesApi.create(fd)
        router.push(`/finance/expenses/${res.data.id}`)
    } catch (e: any) {
        alert(e?.response?.data?.message || 'Failed to create expense')
    } finally { submitting.value = false }
}

onMounted(loadDependencies)
</script>

<template>
    <div>
        <router-link to="/finance/expenses" class="text-sm text-emerald-600 hover:text-emerald-700 mb-4 inline-block">
            &larr; Back to Expenses
        </router-link>
        <h1 class="text-2xl font-bold text-slate-800 mb-6">New Expense</h1>

        <form @submit.prevent="submit" class="bg-white rounded-xl border border-slate-100 shadow-sm p-6 space-y-5 max-w-3xl">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Expense Type (from catalog)</label>
                <select v-model.number="form.expense_type_id" @change="onTypeChange"
                    class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500">
                    <option :value="null">-- Custom (no type) --</option>
                    <option v-for="et in expenseTypes" :key="et.id" :value="et.id">
                        {{ et.name }} {{ et.default_amount != null ? `(${Number(et.default_amount).toLocaleString()})` : '' }}
                    </option>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Vehicle <span class="text-red-500">*</span></label>
                    <select v-model.number="form.vehicle_id" required
                        class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500">
                        <option :value="null">Select vehicle</option>
                        <option v-for="v in vehicles" :key="v.id" :value="v.id">{{ v.plate_number }} - {{ v.make }} {{ v.model }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Driver</label>
                    <input v-model.number="form.driver_id" type="number" placeholder="Driver ID"
                        class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Name <span class="text-red-500">*</span></label>
                <input v-model="form.name" required
                    class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
                <textarea v-model="form.description" rows="2"
                    class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500"></textarea>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Amount <span class="text-red-500">*</span></label>
                    <input v-model.number="form.amount" type="number" min="0" step="0.01" required
                        class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Currency</label>
                    <select v-model.number="form.currency_id"
                        class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500">
                        <option :value="null">Default</option>
                        <option v-for="c in currencies" :key="c.id" :value="c.id">{{ c.code }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Category</label>
                    <select v-model="form.category"
                        class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500">
                        <option value="">--</option>
                        <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Expense Class</label>
                    <select v-model="form.expense_class"
                        class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500">
                        <option value="fixed">Fixed (auto-approved)</option>
                        <option value="variable">Variable (needs approval)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Location</label>
                    <input v-model="form.location" placeholder="e.g. Kigali"
                        class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Odometer (km)</label>
                    <input v-model.number="form.odometer" type="number" min="0"
                        class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Trip ID</label>
                    <input v-model.number="form.trip_id" type="number" placeholder="Trip ID"
                        class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Route ID</label>
                    <input v-model.number="form.route_id" type="number" placeholder="Route ID"
                        class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500">
                </div>
            </div>

            <div class="flex items-center gap-3 pt-4 border-t border-slate-100">
                <button type="submit" :disabled="submitting"
                    class="px-6 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors text-sm font-medium disabled:opacity-50">
                    {{ submitting ? 'Creating...' : 'Create Expense' }}
                </button>
                <router-link to="/finance/expenses"
                    class="text-sm text-slate-500 hover:text-slate-700">Cancel</router-link>
            </div>
        </form>
    </div>
</template>
