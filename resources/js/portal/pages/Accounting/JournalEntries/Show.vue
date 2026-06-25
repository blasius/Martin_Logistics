<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { journalEntriesApi } from '../../../api/accounting'

const route = useRoute()
const router = useRouter()
const entry = ref<any>(null)
const loading = ref(true)
const showReverseModal = ref(false)
const reverseReason = ref('')

const lineTotal = computed(() => {
    if (!entry.value?.lines) return { debit: 0, credit: 0 }
    return {
        debit: entry.value.lines.reduce((s: number, l: any) => s + (Number(l.debit) || 0), 0),
        credit: entry.value.lines.reduce((s: number, l: any) => s + (Number(l.credit) || 0), 0),
    }
})

function statusClass(s: string) {
    const map: Record<string, string> = {
        draft: 'bg-gray-100 text-gray-600',
        posted: 'bg-green-100 text-green-700',
        reversed: 'bg-red-100 text-red-700',
    }
    return map[s] ?? 'bg-gray-100 text-gray-600'
}

function typeLabel(t: string) {
    return t.replace('auto_', 'Auto — ').replace('_', ' ')
}

async function fetchEntry() {
    loading.value = true
    try {
        const res = await journalEntriesApi.show(route.params.id)
        entry.value = res.data
    } catch {} finally { loading.value = false }
}

async function doPost() {
    try {
        await journalEntriesApi.post(entry.value.id)
        await fetchEntry()
    } catch (e: any) {
        alert(e.response?.data?.message || 'Post failed')
    }
}

async function doReverse() {
    if (!reverseReason.value) return
    try {
        await journalEntriesApi.reverse(entry.value.id, { reason: reverseReason.value })
        showReverseModal.value = false
        await fetchEntry()
    } catch (e: any) {
        alert(e.response?.data?.message || 'Reverse failed')
    }
}

onMounted(fetchEntry)
</script>

<template>
    <div>
        <div v-if="loading" class="p-8 text-center text-slate-400">Loading...</div>

        <template v-else-if="entry">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold text-slate-800 font-mono">{{ entry.reference }}</h1>
                    <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium"
                        :class="statusClass(entry.status)">{{ entry.status }}</span>
                </div>
                <div class="flex gap-2">
                    <router-link to="/accounting/journal-entries"
                        class="px-3 py-1.5 text-sm text-slate-500 hover:text-emerald-600">
                        &larr; Back
                    </router-link>
                    <button v-if="entry.status === 'draft'" @click="doPost"
                        class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700">
                        Post Entry
                    </button>
                    <button v-if="entry.status === 'posted'" @click="showReverseModal = true"
                        class="px-4 py-2 text-sm font-medium text-white bg-orange-600 rounded-lg hover:bg-orange-700">
                        Reverse Entry
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4 mb-6">
                <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-4">
                    <span class="text-xs font-medium text-slate-500 uppercase">Date</span>
                    <p class="text-sm font-semibold text-slate-800 mt-1">{{ entry.date }}</p>
                </div>
                <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-4">
                    <span class="text-xs font-medium text-slate-500 uppercase">Type</span>
                    <p class="text-sm font-semibold text-slate-800 mt-1">{{ typeLabel(entry.type) }}</p>
                </div>
                <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-4">
                    <span class="text-xs font-medium text-slate-500 uppercase">Created By</span>
                    <p class="text-sm font-semibold text-slate-800 mt-1">{{ entry.created_by?.name || 'System' }}</p>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-4 mb-6">
                <span class="text-xs font-medium text-slate-500 uppercase">Description</span>
                <p class="text-sm text-slate-800 mt-1">{{ entry.description }}</p>
            </div>

            <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="px-4 py-3 bg-slate-50 border-b border-slate-100">
                    <h3 class="text-sm font-semibold text-slate-700">Line Items</h3>
                </div>
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-slate-100 text-xs text-slate-500 uppercase tracking-wider">
                            <th class="text-left px-4 py-2">Account</th>
                            <th class="text-left px-4 py-2">Code</th>
                            <th class="text-left px-4 py-2">Type</th>
                            <th class="text-right px-4 py-2">Debit</th>
                            <th class="text-right px-4 py-2">Credit</th>
                            <th class="text-left px-4 py-2">Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="line in entry.lines" :key="line.id" class="border-b border-slate-50">
                            <td class="px-4 py-2.5 text-sm text-slate-800">{{ line.account?.name }}</td>
                            <td class="px-4 py-2.5 text-sm font-mono text-slate-600">{{ line.account?.code }}</td>
                            <td class="px-4 py-2.5">
                                <span class="text-xs capitalize" :class="{
                                    'text-blue-600': line.account?.type === 'asset',
                                    'text-orange-600': line.account?.type === 'liability',
                                    'text-purple-600': line.account?.type === 'equity',
                                    'text-green-600': line.account?.type === 'revenue',
                                    'text-red-600': line.account?.type === 'expense',
                                }">{{ line.account?.type }}</span>
                            </td>
                            <td class="px-4 py-2.5 text-right text-sm font-mono text-slate-800">
                                {{ Number(line.debit) > 0 ? Number(line.debit).toLocaleString() : '-' }}
                            </td>
                            <td class="px-4 py-2.5 text-right text-sm font-mono text-slate-800">
                                {{ Number(line.credit) > 0 ? Number(line.credit).toLocaleString() : '-' }}
                            </td>
                            <td class="px-4 py-2.5 text-sm text-slate-500">{{ line.notes || '-' }}</td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-slate-50">
                        <tr>
                            <td colspan="3" class="px-4 py-2 text-sm font-semibold text-slate-700">Totals</td>
                            <td class="px-4 py-2 text-right text-sm font-semibold text-slate-800">
                                {{ lineTotal.debit.toLocaleString() }}</td>
                            <td class="px-4 py-2 text-right text-sm font-semibold text-slate-800">
                                {{ lineTotal.credit.toLocaleString() }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Reversal info -->
            <div v-if="entry.reversal_entry" class="mt-4 bg-orange-50 border border-orange-200 rounded-xl p-4">
                <p class="text-sm text-orange-800">
                    This entry has been reversed by
                    <router-link :to="`/accounting/journal-entries/${entry.reversal_entry.id}`"
                        class="font-semibold underline">{{ entry.reversal_entry.reference }}</router-link>
                </p>
            </div>

            <!-- Reverse Modal -->
            <div v-if="showReverseModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
                @click.self="showReverseModal = false">
                <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-4">
                    <div class="flex items-center justify-between p-6 border-b border-slate-100">
                        <h2 class="text-lg font-semibold text-slate-800">Reverse Entry</h2>
                        <button @click="showReverseModal = false"
                            class="text-slate-400 hover:text-slate-600 text-xl">&times;</button>
                    </div>
                    <div class="p-6">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Reason for reversal</label>
                        <textarea v-model="reverseReason" rows="3"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500"></textarea>
                    </div>
                    <div class="flex justify-end gap-3 p-6 border-t border-slate-100">
                        <button @click="showReverseModal = false"
                            class="px-4 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-200 rounded-lg">
                            Cancel
                        </button>
                        <button @click="doReverse" :disabled="!reverseReason"
                            class="px-6 py-2 text-sm font-medium text-white bg-orange-600 rounded-lg hover:bg-orange-700 disabled:opacity-50">
                            Reverse
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>
