<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '../../../../plugins/axios'
import { expenseTypesApi } from '../../../api/expense-types'

const router = useRouter()

const form = ref({
    name: '',
    description: '',
    category: '',
    expense_class: 'fixed',
    default_amount: null as number | null,
    currency_id: null as number | null,
})

const categories = ref([])
const currencies = ref([])
const submitting = ref(false)

onMounted(async () => {
    try {
        const [catRes, curRes] = await Promise.all([
            expenseTypesApi.categories(),
            api.get('/portal/currencies'),
        ])
        categories.value = catRes.data
        currencies.value = curRes.data
    } catch {}
})

async function submit() {
    submitting.value = true
    try {
        const res = await expenseTypesApi.create(form.value)
        router.push('/finance/expense-types')
    } catch (e: any) {
        alert(e?.response?.data?.message || 'Failed to create expense type')
    } finally { submitting.value = false }
}
</script>

<template>
    <div>
        <router-link to="/finance/expense-types" class="text-sm text-emerald-600 hover:text-emerald-700 mb-4 inline-block">
            &larr; Back to Expense Types
        </router-link>
        <h1 class="text-2xl font-bold text-slate-800 mb-6">New Expense Type</h1>

        <form @submit.prevent="submit" class="bg-white rounded-xl border border-slate-100 shadow-sm p-6 space-y-5 max-w-2xl">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Name</label>
                <input v-model="form.name" required
                    class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
                <textarea v-model="form.description" rows="3"
                    class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Category</label>
                    <select v-model="form.category"
                        class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500">
                        <option value="">Select category</option>
                        <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Expense Class</label>
                    <select v-model="form.expense_class"
                        class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500">
                        <option value="fixed">Fixed (pre-approved, no further approval)</option>
                        <option value="variable">Variable (requires approval per instance)</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Default Amount</label>
                    <input v-model.number="form.default_amount" type="number" min="0" step="0.01"
                        class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Currency</label>
                    <select v-model.number="form.currency_id"
                        class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500">
                        <option :value="null">Select currency</option>
                        <option v-for="c in currencies" :key="c.id" :value="c.id">{{ c.code }} - {{ c.name }}</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-4 border-t border-slate-100">
                <button type="submit" :disabled="submitting"
                    class="px-6 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors text-sm font-medium disabled:opacity-50">
                    {{ submitting ? 'Creating...' : 'Create Expense Type' }}
                </button>
                <router-link to="/finance/expense-types"
                    class="text-sm text-slate-500 hover:text-slate-700">Cancel</router-link>
            </div>
        </form>
    </div>
</template>
