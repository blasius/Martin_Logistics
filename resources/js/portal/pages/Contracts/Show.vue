<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { contractsApi } from '../../api/contracts'

const route = useRoute()
const router = useRouter()
const contract = ref<any>(null)
const loading = ref(true)
const editing = ref(false)

const editForm = ref<any>({})

function statusClass(status: string) {
    const map: Record<string, string> = {
        active: 'bg-green-100 text-green-700',
        draft: 'bg-gray-100 text-gray-600',
        expired: 'bg-red-100 text-red-700',
        cancelled: 'bg-slate-100 text-slate-600',
    }
    return map[status] ?? 'bg-gray-100 text-gray-600'
}

function daysLeft(endDate: string) {
    const end = new Date(endDate)
    const now = new Date()
    return Math.ceil((end.getTime() - now.getTime()) / (1000 * 60 * 60 * 24))
}

function startEdit() {
    editForm.value = {
        client_id: contract.value.client_id,
        type: contract.value.type,
        rate_card_id: contract.value.rate_card_id,
        start_date: contract.value.start_date,
        end_date: contract.value.end_date,
        status: contract.value.status,
        terms: contract.value.terms,
        sla_response_hours: contract.value.sla_response_hours,
        sla_resolution_hours: contract.value.sla_resolution_hours,
        auto_renew: contract.value.auto_renew,
    }
    editing.value = true
}

async function saveEdit() {
    try {
        await contractsApi.update(contract.value.id, editForm.value)
        editing.value = false
        await fetchContract()
    } catch {}
}

async function cancelEdit() {
    editing.value = false
}

async function fetchContract() {
    loading.value = true
    try {
        const res = await contractsApi.show(route.params.id as string)
        contract.value = res.data
    } catch {} finally { loading.value = false }
}

async function remove() {
    if (!confirm('Delete this contract?')) return
    try {
        await contractsApi.destroy(contract.value.id)
        router.push('/contracts')
    } catch {}
}

onMounted(fetchContract)
</script>

<template>
    <div>
        <div class="mb-6">
            <router-link to="/contracts" class="text-sm text-emerald-600 hover:text-emerald-700 mb-2 inline-block">&larr; Back to Contracts</router-link>
            <div v-if="contract" class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-slate-800">{{ contract.reference }}</h1>
                <div class="flex gap-2">
                    <button v-if="!editing" @click="startEdit"
                        class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700">Edit</button>
                    <button v-if="!editing" @click="remove"
                        class="px-4 py-2 text-sm font-medium text-red-600 bg-white border border-red-200 rounded-lg hover:bg-red-50">Delete</button>
                </div>
            </div>
        </div>

        <div v-if="loading" class="p-8 text-center text-slate-400">Loading...</div>

        <template v-else-if="contract">
            <!-- Read-only view -->
            <div v-if="!editing" class="space-y-6">
                <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                        <div>
                            <label class="block text-xs font-medium text-slate-500 uppercase tracking-wider mb-1">Client</label>
                            <p class="text-sm font-medium text-slate-800">{{ contract.client?.name }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 uppercase tracking-wider mb-1">Type</label>
                            <p class="text-sm font-medium text-slate-800 capitalize">{{ contract.type }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 uppercase tracking-wider mb-1">Status</label>
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium"
                                :class="statusClass(contract.status)">{{ contract.status }}</span>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 uppercase tracking-wider mb-1">Days Left</label>
                            <p class="text-sm font-medium" :class="daysLeft(contract.end_date) <= 7 ? 'text-red-600' : 'text-slate-800'">
                                {{ daysLeft(contract.end_date) }} days
                            </p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-6 mt-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-500 uppercase tracking-wider mb-1">Period</label>
                            <p class="text-sm text-slate-700">{{ contract.start_date }} → {{ contract.end_date }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 uppercase tracking-wider mb-1">Rate Card</label>
                            <p class="text-sm text-slate-700">{{ contract.rate_card?.name ?? 'None' }}</p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="block text-xs font-medium text-slate-500 uppercase tracking-wider mb-1">Auto-Renew</label>
                        <p class="text-sm text-slate-700">{{ contract.auto_renew ? 'Yes' : 'No' }}</p>
                    </div>
                </div>

                <div v-if="contract.terms" class="bg-white rounded-xl border border-slate-100 shadow-sm p-6">
                    <label class="block text-xs font-medium text-slate-500 uppercase tracking-wider mb-2">Terms & Conditions</label>
                    <p class="text-sm text-slate-700 whitespace-pre-wrap">{{ contract.terms }}</p>
                </div>

                <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-slate-800 mb-4">SLA Configuration</h2>
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-medium text-slate-500 uppercase tracking-wider mb-1">Response (hours)</label>
                            <p class="text-sm text-slate-700">{{ contract.sla_response_hours ?? 'Not set' }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 uppercase tracking-wider mb-1">Resolution (hours)</label>
                            <p class="text-sm text-slate-700">{{ contract.sla_resolution_hours ?? 'Not set' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit form -->
            <div v-else class="max-w-3xl space-y-6">
                <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Type</label>
                            <select v-model="editForm.type"
                                class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500">
                                <option value="monthly">Monthly</option>
                                <option value="yearly">Yearly</option>
                                <option value="spot">Spot</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Status</label>
                            <select v-model="editForm.status"
                                class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500">
                                <option value="draft">Draft</option>
                                <option value="active">Active</option>
                                <option value="expired">Expired</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Start Date</label>
                            <input v-model="editForm.start_date" type="date"
                                class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">End Date</label>
                            <input v-model="editForm.end_date" type="date"
                                class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Terms</label>
                        <textarea v-model="editForm.terms" rows="4"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">SLA Response (hours)</label>
                            <input v-model="editForm.sla_response_hours" type="number" min="1"
                                class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">SLA Resolution (hours)</label>
                            <input v-model="editForm.sla_resolution_hours" type="number" min="1"
                                class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500" />
                        </div>
                    </div>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" v-model="editForm.auto_renew"
                            class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" />
                        <span class="text-sm text-slate-700">Auto-renew</span>
                    </label>
                </div>
                <div class="flex justify-end gap-3">
                    <button @click="cancelEdit"
                        class="px-4 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50">
                        Cancel
                    </button>
                    <button @click="saveEdit"
                        class="px-6 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700">
                        Save Changes
                    </button>
                </div>
            </div>
        </template>
    </div>
</template>
