<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { rateCardsApi } from '../../api/rate-cards'

const router = useRouter()
const loading = ref(false)
const clients = ref<any[]>([])
const currencies = ref<any[]>([])

const form = ref({
    name: '',
    effective_from: '',
    effective_to: '',
    client_id: null as number | null,
    is_active: true,
    items: [] as any[],
})

function addItem() {
    form.value.items.push({
        charge_name: '',
        charge_type: 'flat',
        amount: 0,
        currency_id: null,
        min_charge: null,
        max_charge: null,
    })
}

function removeItem(index: number) {
    form.value.items.splice(index, 1)
}

async function submit() {
    loading.value = true
    try {
        await rateCardsApi.create(form.value)
        router.push('/rate-cards')
    } catch {} finally { loading.value = false }
}

async function loadDependencies() {
    try {
        const [clientsRes, currenciesRes] = await Promise.all([
            (await import('../../api/clients')).clientsApi.getAll({ per_page: 500 }),
            (await import('../../api/currencies')).currenciesApi.getAll({ per_page: 200 }),
        ])
        clients.value = clientsRes.data.data ?? clientsRes.data
        currencies.value = currenciesRes.data.data ?? currenciesRes.data
    } catch {}
}

onMounted(loadDependencies)
</script>

<template>
    <div>
        <div class="mb-6">
            <router-link to="/rate-cards" class="text-sm text-emerald-600 hover:text-emerald-700 mb-2 inline-block">&larr; Back</router-link>
            <h1 class="text-2xl font-bold text-slate-800">Create Rate Card</h1>
        </div>

        <form @submit.prevent="submit" class="max-w-3xl space-y-6">
            <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Name</label>
                        <input v-model="form.name" required
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Client (optional)</label>
                        <select v-model="form.client_id"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500">
                            <option :value="null">Default (all clients)</option>
                            <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Effective From</label>
                        <input v-model="form.effective_from" type="date" required
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Effective To (optional)</label>
                        <input v-model="form.effective_to" type="date"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500" />
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-slate-800">Charge Items</h2>
                    <button type="button" @click="addItem"
                        class="text-sm px-3 py-1.5 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700">
                        + Add Item
                    </button>
                </div>

                <div v-for="(item, idx) in form.items" :key="idx"
                    class="border border-slate-100 rounded-lg p-4 mb-3 relative">
                    <button type="button" @click="removeItem(idx)"
                        class="absolute top-2 right-2 text-slate-400 hover:text-red-500 text-sm">&times;</button>
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Charge Name</label>
                            <input v-model="item.charge_name" required placeholder="e.g. Per km rate"
                                class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Type</label>
                            <select v-model="item.charge_type"
                                class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500">
                                <option value="per_km">Per KM</option>
                                <option value="per_kg">Per KG</option>
                                <option value="per_container">Per Container</option>
                                <option value="flat">Flat</option>
                                <option value="per_stop">Per Stop</option>
                                <option value="fuel_surcharge">Fuel Surcharge (%)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Amount</label>
                            <input v-model="item.amount" type="number" step="0.01" min="0" required
                                class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500" />
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-3 mt-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Currency</label>
                            <select v-model="item.currency_id" required
                                class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500">
                                <option value="">Select</option>
                                <option v-for="c in currencies" :key="c.id" :value="c.id">{{ c.code }} - {{ c.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Min Charge</label>
                            <input v-model="item.min_charge" type="number" step="0.01" min="0"
                                class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Max Charge</label>
                            <input v-model="item.max_charge" type="number" step="0.01" min="0"
                                class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500" />
                        </div>
                    </div>
                </div>

                <p v-if="!form.items.length" class="text-sm text-slate-400 text-center py-4">
                    No items yet. Click "Add Item" to add charges.
                </p>
            </div>

            <div class="flex justify-end gap-3">
                <router-link to="/rate-cards"
                    class="px-4 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50">
                    Cancel
                </router-link>
                <button type="submit" :disabled="loading"
                    class="px-6 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 disabled:opacity-50">
                    {{ loading ? 'Creating...' : 'Create Rate Card' }}
                </button>
            </div>
        </form>
    </div>
</template>
