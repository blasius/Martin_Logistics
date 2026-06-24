<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { invoicesApi } from '../../api/invoices'

const router = useRouter()
const loading = ref(false)
const clients = ref<any[]>([])
const currencies = ref<any[]>([])

const form = ref({
    client_id: null as number | null,
    type: 'invoice',
    issue_date: new Date().toISOString().slice(0, 10),
    due_date: '',
    currency_id: null as number | null,
    tax_total: 0,
    discount_total: 0,
    notes: '',
    items: [] as any[],
})

function addItem() {
    form.value.items.push({ description: '', charge_type: 'flat', quantity: 1, unit_price: 0 })
}

function removeItem(idx: number) {
    form.value.items.splice(idx, 1)
}

async function submit() {
    loading.value = true
    try {
        await invoicesApi.create(form.value)
        router.push('/invoices')
    } catch {} finally { loading.value = false }
}

async function loadDeps() {
    try {
        const [clientsRes, currenciesRes] = await Promise.all([
            (await import('../../api/clients')).clientsApi.getAll({ per_page: 500 }),
            (await import('../../api/currencies')).currenciesApi.getAll({ per_page: 200 }),
        ])
        clients.value = clientsRes.data.data ?? clientsRes.data
        currencies.value = currenciesRes.data.data ?? currenciesRes.data
    } catch {}
}

onMounted(loadDeps)
</script>

<template>
    <div>
        <div class="mb-6">
            <router-link to="/invoices" class="text-sm text-emerald-600 hover:text-emerald-700 mb-2 inline-block">&larr; Back</router-link>
            <h1 class="text-2xl font-bold text-slate-800">Create Invoice</h1>
        </div>

        <form @submit.prevent="submit" class="max-w-3xl space-y-6">
            <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Client</label>
                        <select v-model="form.client_id" required
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500">
                            <option value="">Select client</option>
                            <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Type</label>
                        <select v-model="form.type"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500">
                            <option value="invoice">Invoice</option>
                            <option value="credit_note">Credit Note</option>
                            <option value="debit_note">Debit Note</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Issue Date</label>
                        <input v-model="form.issue_date" type="date" required
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Due Date</label>
                        <input v-model="form.due_date" type="date" required
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Currency</label>
                        <select v-model="form.currency_id" required
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500">
                            <option value="">Select currency</option>
                            <option v-for="c in currencies" :key="c.id" :value="c.id">{{ c.code }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Tax Total</label>
                        <input v-model="form.tax_total" type="number" step="0.01" min="0"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500" />
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Discount Total</label>
                    <input v-model="form.discount_total" type="number" step="0.01" min="0"
                        class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Notes</label>
                    <textarea v-model="form.notes" rows="2"
                        class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500"></textarea>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-slate-800">Line Items</h2>
                    <button type="button" @click="addItem"
                        class="text-sm px-3 py-1.5 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700">
                        + Add Item
                    </button>
                </div>

                <div v-for="(item, idx) in form.items" :key="idx"
                    class="border border-slate-100 rounded-lg p-3 mb-2 relative">
                    <button type="button" @click="removeItem(idx)"
                        class="absolute top-2 right-2 text-slate-400 hover:text-red-500 text-sm">&times;</button>
                    <div class="grid grid-cols-4 gap-2">
                        <div class="col-span-2">
                            <label class="block text-xs font-medium text-slate-500 mb-1">Description</label>
                            <input v-model="item.description" placeholder="Freight charge"
                                class="w-full border border-slate-200 rounded-lg px-2 py-1.5 text-sm focus:ring-2 focus:ring-emerald-500" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Qty</label>
                            <input v-model="item.quantity" type="number" step="0.01" min="0"
                                class="w-full border border-slate-200 rounded-lg px-2 py-1.5 text-sm focus:ring-2 focus:ring-emerald-500" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Unit Price</label>
                            <input v-model="item.unit_price" type="number" step="0.01" min="0"
                                class="w-full border border-slate-200 rounded-lg px-2 py-1.5 text-sm focus:ring-2 focus:ring-emerald-500" />
                        </div>
                    </div>
                    <div class="text-xs text-slate-400 mt-1 text-right">
                        Line total: {{ (Number(item.quantity) * Number(item.unit_price)).toLocaleString() }}
                    </div>
                </div>

                <p v-if="!form.items.length" class="text-sm text-slate-400 text-center py-4">
                    No items yet. Click "Add Item" to add line items.
                </p>
            </div>

            <div class="flex justify-end gap-3">
                <router-link to="/invoices"
                    class="px-4 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50">
                    Cancel
                </router-link>
                <button type="submit" :disabled="loading"
                    class="px-6 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 disabled:opacity-50">
                    {{ loading ? 'Creating...' : 'Create Invoice' }}
                </button>
            </div>
        </form>
    </div>
</template>
