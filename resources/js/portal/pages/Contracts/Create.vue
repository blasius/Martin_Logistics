<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { contractsApi } from '../../api/contracts'

const router = useRouter()
const loading = ref(false)
const clients = ref<any[]>([])
const rateCards = ref<any[]>([])

const form = ref({
    client_id: null as number | null,
    type: 'monthly',
    rate_card_id: null as number | null,
    start_date: '',
    end_date: '',
    status: 'draft',
    terms: '',
    sla_response_hours: null as number | null,
    sla_resolution_hours: null as number | null,
    auto_renew: false,
})

async function submit() {
    loading.value = true
    try {
        await contractsApi.create(form.value)
        router.push('/contracts')
    } catch {} finally { loading.value = false }
}

async function loadDeps() {
    try {
        const [clientsRes, cardsRes] = await Promise.all([
            (await import('../../api/clients')).clientsApi.getAll({ per_page: 500 }),
            (await import('../../api/rate-cards')).rateCardsApi.getAll({ per_page: 500, active_only: '1' }),
        ])
        clients.value = clientsRes.data.data ?? clientsRes.data
        rateCards.value = cardsRes.data.data ?? cardsRes.data
    } catch {}
}

onMounted(loadDeps)
</script>

<template>
    <div>
        <div class="mb-6">
            <router-link to="/contracts" class="text-sm text-emerald-600 hover:text-emerald-700 mb-2 inline-block">&larr; Back</router-link>
            <h1 class="text-2xl font-bold text-slate-800">Create Contract</h1>
        </div>

        <form @submit.prevent="submit" class="max-w-3xl space-y-6">
            <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Client</label>
                        <select v-model="form.client_id" required
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500">
                            <option value="">Select client</option>
                            <option v-for="cl in clients" :key="cl.id" :value="cl.id">{{ cl.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Type</label>
                        <select v-model="form.type"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500">
                            <option value="monthly">Monthly</option>
                            <option value="yearly">Yearly</option>
                            <option value="spot">Spot</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Start Date</label>
                        <input v-model="form.start_date" type="date" required
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">End Date</label>
                        <input v-model="form.end_date" type="date" required
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500" />
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Rate Card (optional)</label>
                    <select v-model="form.rate_card_id"
                        class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500">
                        <option :value="null">None</option>
                        <option v-for="rc in rateCards" :key="rc.id" :value="rc.id">{{ rc.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Terms & Conditions</label>
                    <textarea v-model="form.terms" rows="4"
                        class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500"></textarea>
                </div>
                <div>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" v-model="form.auto_renew"
                            class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" />
                        <span class="text-sm text-slate-700">Auto-renew</span>
                    </label>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6 space-y-4">
                <h2 class="text-lg font-semibold text-slate-800">SLA Configuration</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Response (hours)</label>
                        <input v-model="form.sla_response_hours" type="number" min="1"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Resolution (hours)</label>
                        <input v-model="form.sla_resolution_hours" type="number" min="1"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500" />
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <router-link to="/contracts"
                    class="px-4 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50">
                    Cancel
                </router-link>
                <button type="submit" :disabled="loading"
                    class="px-6 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 disabled:opacity-50">
                    {{ loading ? 'Creating...' : 'Create Contract' }}
                </button>
            </div>
        </form>
    </div>
</template>
