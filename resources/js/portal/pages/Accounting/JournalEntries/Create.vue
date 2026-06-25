<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { journalEntriesApi, chartOfAccountsApi, fiscalYearsApi } from '../../../api/accounting'
import { Plus, Trash2 } from 'lucide-vue-next'

const router = useRouter()

const accounts = ref<any[]>([])
const fiscalYears = ref<any[]>([])
const saving = ref(false)

const form = ref({
    date: new Date().toISOString().split('T')[0],
    description: '',
    type: 'manual',
    fiscal_year_id: null as number | null,
})

const lines = ref<any[]>([
    { account_id: null, debit: 0, credit: 0, notes: '' },
    { account_id: null, debit: 0, credit: 0, notes: '' },
])

const totalDebit = computed(() => lines.value.reduce((s, l) => s + (Number(l.debit) || 0), 0))
const totalCredit = computed(() => lines.value.reduce((s, l) => s + (Number(l.credit) || 0), 0))
const balanced = computed(() => Math.abs(totalDebit.value - totalCredit.value) < 0.01)

function addLine() {
    lines.value.push({ account_id: null, debit: 0, credit: 0, notes: '' })
}

function removeLine(i: number) {
    if (lines.value.length > 1) lines.value.splice(i, 1)
}

async function saveAsDraft() {
    await save(false)
}

async function saveAndPost() {
    await save(true)
}

async function save(post: boolean) {
    saving.value = true
    try {
        const payload = {
            ...form.value,
            lines: lines.value.map(l => ({
                account_id: l.account_id,
                debit: Number(l.debit) || 0,
                credit: Number(l.credit) || 0,
                notes: l.notes || null,
            })),
        }
        const res = await journalEntriesApi.create(payload)
        const entry = res.data

        if (post) {
            await journalEntriesApi.post(entry.id)
        }

        router.push(`/accounting/journal-entries/${entry.id}`)
    } catch (e: any) {
        alert(e.response?.data?.message || 'Save failed')
    } finally { saving.value = false }
}

async function fetchAccounts() {
    try {
        const res = await chartOfAccountsApi.getAll({ per_page: 500 })
        accounts.value = res.data.data ?? res.data
    } catch {}
}

async function fetchFiscalYears() {
    try {
        const res = await fiscalYearsApi.getAll({ per_page: 100 })
        fiscalYears.value = res.data.data ?? res.data
    } catch {}
}

function accountTypeClass(type: string) {
    const map: Record<string, string> = {
        asset: 'text-blue-600',
        liability: 'text-orange-600',
        equity: 'text-purple-600',
        revenue: 'text-green-600',
        expense: 'text-red-600',
    }
    return map[type] ?? ''
}

onMounted(() => {
    fetchAccounts()
    fetchFiscalYears()
})
</script>

<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-slate-800">New Journal Entry</h1>
            <router-link to="/accounting/journal-entries"
                class="text-sm text-slate-500 hover:text-emerald-600">
                &larr; Back to List
            </router-link>
        </div>

        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6 mb-6">
            <div class="grid grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Date</label>
                    <input type="date" v-model="form.date"
                        class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Type</label>
                    <select v-model="form.type"
                        class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500">
                        <option value="manual">Manual</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Fiscal Year</label>
                    <select v-model="form.fiscal_year_id"
                        class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500">
                        <option :value="null">Auto-detect</option>
                        <option v-for="fy in fiscalYears" :key="fy.id" :value="fy.id">{{ fy.name }}</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
                <textarea v-model="form.description" rows="2"
                    class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500"></textarea>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden mb-6">
            <div class="px-4 py-3 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-slate-700">Line Items</h3>
                <button @click="addLine"
                    class="inline-flex items-center gap-1 text-xs font-medium text-emerald-600 hover:text-emerald-700">
                    <Plus class="w-3 h-3" /> Add Line
                </button>
            </div>
            <table class="w-full">
                <thead>
                    <tr class="border-b border-slate-100 text-xs text-slate-500 uppercase tracking-wider">
                        <th class="text-left px-4 py-2">Account</th>
                        <th class="text-right px-4 py-2">Debit</th>
                        <th class="text-right px-4 py-2">Credit</th>
                        <th class="text-left px-4 py-2">Notes</th>
                        <th class="text-center px-4 py-2 w-10"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(line, i) in lines" :key="i" class="border-b border-slate-50">
                        <td class="px-4 py-2">
                            <select v-model="line.account_id"
                                class="w-full border border-slate-200 rounded-lg px-2 py-1.5 text-sm focus:ring-2 focus:ring-emerald-500">
                                <option :value="null">Select account...</option>
                                <option v-for="a in accounts" :key="a.id" :value="a.id"
                                    :class="accountTypeClass(a.type)">
                                    [{{ a.code }}] {{ a.name }} ({{ a.type }})
                                </option>
                            </select>
                        </td>
                        <td class="px-4 py-2">
                            <input type="number" step="0.01" min="0" v-model="line.debit"
                                class="w-full border border-slate-200 rounded-lg px-2 py-1.5 text-sm text-right focus:ring-2 focus:ring-emerald-500" />
                        </td>
                        <td class="px-4 py-2">
                            <input type="number" step="0.01" min="0" v-model="line.credit"
                                class="w-full border border-slate-200 rounded-lg px-2 py-1.5 text-sm text-right focus:ring-2 focus:ring-emerald-500" />
                        </td>
                        <td class="px-4 py-2">
                            <input v-model="line.notes" placeholder="Optional"
                                class="w-full border border-slate-200 rounded-lg px-2 py-1.5 text-sm focus:ring-2 focus:ring-emerald-500" />
                        </td>
                        <td class="px-4 py-2 text-center">
                            <button @click="removeLine(i)" :disabled="lines.length <= 1"
                                class="text-red-400 hover:text-red-600 disabled:opacity-30">
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </td>
                    </tr>
                </tbody>
                <tfoot class="bg-slate-50">
                    <tr>
                        <td class="px-4 py-2 text-sm font-semibold text-slate-700">Totals</td>
                        <td class="px-4 py-2 text-right text-sm font-semibold text-slate-800">{{ totalDebit.toFixed(2) }}</td>
                        <td class="px-4 py-2 text-right text-sm font-semibold text-slate-800">{{ totalCredit.toFixed(2) }}</td>
                        <td colspan="2" class="px-4 py-2">
                            <span v-if="balanced" class="text-xs text-green-600 font-medium">Balanced</span>
                            <span v-else class="text-xs text-red-600 font-medium">
                                Difference: {{ (totalDebit - totalCredit).toFixed(2) }}
                            </span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="flex justify-end gap-3">
            <router-link to="/accounting/journal-entries"
                class="px-4 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-200 rounded-lg">
                Cancel
            </router-link>
            <button @click="saveAsDraft" :disabled="saving || !form.description || !lines.length"
                class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg disabled:opacity-50">
                {{ saving ? 'Saving...' : 'Save as Draft' }}
            </button>
            <button @click="saveAndPost" :disabled="saving || !balanced || !form.description || !lines.length"
                class="px-6 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 disabled:opacity-50"
                :title="!balanced ? 'Debits and credits must balance' : ''">
                {{ saving ? 'Saving...' : 'Save & Post' }}
            </button>
        </div>
    </div>
</template>
