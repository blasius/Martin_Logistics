<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { rateCardsApi } from '../../api/rate-cards'

const cards = ref<any[]>([])
const loading = ref(true)
const activeOnly = ref(false)
const clientFilter = ref('')

async function fetchCards() {
    loading.value = true
    try {
        const params: any = {}
        params.active_only = activeOnly.value ? '1' : '0'
        if (clientFilter.value) params.client_id = clientFilter.value
        const res = await rateCardsApi.getAll(params)
        cards.value = res.data.data ?? res.data
    } catch {} finally { loading.value = false }
}

function cardStatus(card: any) {
    const now = new Date()
    const from = new Date(card.effective_from)
    if (!card.is_active) return 'inactive'
    if (card.effective_to && new Date(card.effective_to) < now) return 'expired'
    if (from > now) return 'scheduled'
    return 'active'
}

function statusBadge(status: string) {
    const map: Record<string, string> = {
        active: 'bg-green-100 text-green-700',
        scheduled: 'bg-blue-100 text-blue-700',
        expired: 'bg-gray-100 text-gray-600',
        inactive: 'bg-red-100 text-red-700',
    }
    return map[status] ?? 'bg-gray-100 text-gray-600'
}

async function toggleActive(card: any) {
    try {
        await rateCardsApi.update(card.id, { is_active: !card.is_active })
        await fetchCards()
    } catch {}
}

const selectedCard = ref<any>(null)
const showPreview = ref(false)

async function showRateCard(card: any) {
    selectedCard.value = null
    try {
        const res = await rateCardsApi.show(card.id)
        selectedCard.value = res.data
        showPreview.value = true
    } catch {}
}

onMounted(fetchCards)
</script>

<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-slate-800">Rate Cards</h1>
            <router-link to="/rate-cards/create"
                class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors text-sm font-medium">
                New Rate Card
            </router-link>
        </div>

        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-4 mb-6">
            <div class="flex flex-wrap gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Filter</label>
                    <select v-model="activeOnly" @change="fetchCards"
                        class="text-sm border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500">
                        <option :value="false">All</option>
                        <option :value="true">Active Only</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
            <div v-if="loading" class="p-8 text-center text-slate-400">Loading...</div>
            <table v-else class="w-full">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/50">
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Name</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Client</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Effective</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Items</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="card in cards" :key="card.id"
                        class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors cursor-pointer"
                        @click="showRateCard(card)">
                        <td class="px-4 py-3 text-sm font-medium text-slate-800">{{ card.name }}</td>
                        <td class="px-4 py-3 text-sm text-slate-500">{{ card.client?.name ?? 'Default' }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">
                            {{ card.effective_from }}
                            <span v-if="card.effective_to" class="text-slate-400"> → {{ card.effective_to }}</span>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ card.items?.length ?? 0 }} items</td>
                        <td class="px-4 py-3">
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium"
                                :class="statusBadge(cardStatus(card))">{{ cardStatus(card) }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button @click.stop="toggleActive(card)"
                                class="text-xs text-slate-400 hover:text-emerald-600 transition-colors">
                                {{ card.is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                        </td>
                    </tr>
                    <tr v-if="!cards.length">
                        <td colspan="6" class="px-4 py-8 text-center text-slate-400">No rate cards found</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Rate Card Detail Modal -->
        <div v-if="showPreview && selectedCard"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
            @click.self="showPreview = false">
            <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full mx-4 max-h-[80vh] overflow-y-auto">
                <div class="flex items-center justify-between p-6 border-b border-slate-100">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-800">{{ selectedCard.name }}</h2>
                        <p class="text-sm text-slate-500 mt-1">
                            {{ selectedCard.client?.name ?? 'Default Rate Card' }}
                        </p>
                    </div>
                    <button @click="showPreview = false"
                        class="text-slate-400 hover:text-slate-600 text-xl leading-none">&times;</button>
                </div>
                <div class="p-6 space-y-3">
                    <div class="flex gap-6 text-sm">
                        <div><span class="text-slate-400">From:</span> <span class="font-medium">{{ selectedCard.effective_from }}</span></div>
                        <div><span class="text-slate-400">To:</span> <span class="font-medium">{{ selectedCard.effective_to ?? 'Ongoing' }}</span></div>
                    </div>
                    <table class="w-full text-sm mt-4">
                        <thead>
                            <tr class="border-b border-slate-100">
                                <th class="text-left py-2 text-xs font-semibold text-slate-500 uppercase">Charge</th>
                                <th class="text-left py-2 text-xs font-semibold text-slate-500 uppercase">Type</th>
                                <th class="text-right py-2 text-xs font-semibold text-slate-500 uppercase">Amount</th>
                                <th class="text-right py-2 text-xs font-semibold text-slate-500 uppercase">Min</th>
                                <th class="text-right py-2 text-xs font-semibold text-slate-500 uppercase">Max</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in selectedCard.items" :key="item.id" class="border-b border-slate-50">
                                <td class="py-2 font-medium text-slate-700">{{ item.charge_name }}</td>
                                <td class="py-2 text-slate-500">{{ item.charge_type }}</td>
                                <td class="py-2 text-right">{{ Number(item.amount).toLocaleString() }} {{ item.currency?.code }}</td>
                                <td class="py-2 text-right text-slate-400">{{ item.min_charge ? Number(item.min_charge).toLocaleString() : '-' }}</td>
                                <td class="py-2 text-right text-slate-400">{{ item.max_charge ? Number(item.max_charge).toLocaleString() : '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="flex justify-end gap-3 p-6 border-t border-slate-100">
                    <router-link :to="`/rate-cards/${selectedCard.id}/edit`"
                        class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700">
                        Edit
                    </router-link>
                    <button @click="showPreview = false"
                        class="px-4 py-2 text-sm font-medium text-slate-600 bg-slate-100 rounded-lg hover:bg-slate-200">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
