<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { chartOfAccountsApi } from '../../../api/accounting'
import { Plus, Pencil, CircleCheck, CircleX } from 'lucide-vue-next'

const accounts = ref<any[]>([])
const loading = ref(true)
const showModal = ref(false)
const editing = ref<any>(null)
const form = ref({
    code: '',
    name: '',
    type: 'asset',
    description: '',
    parent_id: null as number | null,
    is_active: true,
})
const saving = ref(false)
const search = ref('')

const typeLabels: Record<string, string> = {
    asset: 'Assets',
    liability: 'Liabilities',
    equity: 'Equity',
    revenue: 'Revenue',
    expense: 'Expenses',
}

async function fetchAccounts() {
    loading.value = true
    try {
        const params: any = { per_page: 200 }
        if (search.value) params.search = search.value
        const res = await chartOfAccountsApi.getAll(params)
        accounts.value = res.data.data ?? res.data
    } catch {} finally { loading.value = false }
}

function byType(type: string) {
    return accounts.value
        .filter((a: any) => a.type === type && !a.parent_id)
        .sort((a: any, b: any) => a.code.localeCompare(b.code))
}

function childrenOf(parentId: number) {
    return accounts.value
        .filter((a: any) => a.parent_id === parentId)
        .sort((a: any, b: any) => a.code.localeCompare(b.code))
}

function openCreate() {
    editing.value = null
    form.value = { code: '', name: '', type: 'asset', description: '', parent_id: null, is_active: true }
    showModal.value = true
}

function openEdit(account: any) {
    editing.value = account
    form.value = {
        code: account.code,
        name: account.name,
        type: account.type,
        description: account.description || '',
        parent_id: account.parent_id,
        is_active: account.is_active,
    }
    showModal.value = true
}

async function save() {
    saving.value = true
    try {
        if (editing.value) {
            await chartOfAccountsApi.update(editing.value.id, form.value)
        } else {
            await chartOfAccountsApi.create(form.value)
        }
        showModal.value = false
        await fetchAccounts()
    } catch (e: any) {
        alert(e.response?.data?.message || 'Save failed')
    } finally { saving.value = false }
}

async function toggleActive(account: any) {
    try {
        await chartOfAccountsApi.update(account.id, { is_active: !account.is_active })
        await fetchAccounts()
    } catch {}
}

onMounted(fetchAccounts)
</script>

<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-slate-800">Chart of Accounts</h1>
            <button @click="openCreate"
                class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 text-sm font-medium">
                <Plus class="w-4 h-4" /> New Account
            </button>
        </div>

        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-4 mb-6">
            <input v-model="search" @input="fetchAccounts" placeholder="Search by code or name..."
                class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500" />
        </div>

        <div v-if="loading" class="p-8 text-center text-slate-400">Loading...</div>

        <div v-else class="space-y-6">
            <div v-for="type in ['asset', 'liability', 'equity', 'revenue', 'expense']" :key="type"
                class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="px-4 py-3 bg-slate-50 border-b border-slate-100">
                    <h3 class="text-sm font-semibold text-slate-700 uppercase tracking-wider">{{ typeLabels[type] }}</h3>
                </div>
                <div v-if="!byType(type).length" class="p-4 text-sm text-slate-400 text-center">No accounts</div>
                <table v-else class="w-full">
                    <thead>
                        <tr class="border-b border-slate-50 text-xs text-slate-500 uppercase tracking-wider">
                            <th class="text-left px-4 py-2">Code</th>
                            <th class="text-left px-4 py-2">Name</th>
                            <th class="text-left px-4 py-2">Description</th>
                            <th class="text-center px-4 py-2">Status</th>
                            <th class="text-right px-4 py-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="account in byType(type)" :key="account.id">
                            <tr class="border-b border-slate-50 hover:bg-slate-50/50">
                                <td class="px-4 py-2.5 text-sm font-mono font-medium text-slate-800">{{ account.code }}</td>
                                <td class="px-4 py-2.5 text-sm font-medium text-slate-800">{{ account.name }}</td>
                                <td class="px-4 py-2.5 text-sm text-slate-500">{{ account.description || '-' }}</td>
                                <td class="px-4 py-2.5 text-center">
                                    <button @click="toggleActive(account)" class="inline-flex">
                                        <CircleCheck v-if="account.is_active" class="w-4 h-4 text-green-500" />
                                        <CircleX v-else class="w-4 h-4 text-red-400" />
                                    </button>
                                </td>
                                <td class="px-4 py-2.5 text-right">
                                    <button @click="openEdit(account)"
                                        class="text-slate-400 hover:text-emerald-600 transition-colors">
                                        <Pencil class="w-4 h-4" />
                                    </button>
                                </td>
                            </tr>
                            <tr v-for="child in childrenOf(account.id)" :key="child.id"
                                class="border-b border-slate-50 hover:bg-slate-50/50">
                                <td class="px-4 py-2.5 text-sm font-mono text-slate-600 pl-8">↳ {{ child.code }}</td>
                                <td class="px-4 py-2.5 text-sm text-slate-600">{{ child.name }}</td>
                                <td class="px-4 py-2.5 text-sm text-slate-400">{{ child.description || '-' }}</td>
                                <td class="px-4 py-2.5 text-center">
                                    <button @click="toggleActive(child)" class="inline-flex">
                                        <CircleCheck v-if="child.is_active" class="w-4 h-4 text-green-500" />
                                        <CircleX v-else class="w-4 h-4 text-red-400" />
                                    </button>
                                </td>
                                <td class="px-4 py-2.5 text-right">
                                    <button @click="openEdit(child)"
                                        class="text-slate-400 hover:text-emerald-600 transition-colors">
                                        <Pencil class="w-4 h-4" />
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <div v-if="showModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
            @click.self="showModal = false">
            <div class="bg-white rounded-xl shadow-xl max-w-lg w-full mx-4">
                <div class="flex items-center justify-between p-6 border-b border-slate-100">
                    <h2 class="text-lg font-semibold text-slate-800">{{ editing ? 'Edit Account' : 'New Account' }}</h2>
                    <button @click="showModal = false" class="text-slate-400 hover:text-slate-600 text-xl">&times;</button>
                </div>
                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Code</label>
                            <input v-model="form.code"
                                class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Type</label>
                            <select v-model="form.type"
                                class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500">
                                <option value="asset">Asset</option>
                                <option value="liability">Liability</option>
                                <option value="equity">Equity</option>
                                <option value="revenue">Revenue</option>
                                <option value="expense">Expense</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Name</label>
                        <input v-model="form.name"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
                        <textarea v-model="form.description" rows="2"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Parent Account</label>
                        <select v-model="form.parent_id"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500">
                            <option :value="null">None (Top Level)</option>
                            <option v-for="a in accounts.filter(a => !a.parent_id)" :key="a.id" :value="a.id">
                                {{ a.code }} — {{ a.name }}
                            </option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end gap-3 p-6 border-t border-slate-100">
                    <button @click="showModal = false"
                        class="px-4 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-200 rounded-lg">
                        Cancel
                    </button>
                    <button @click="save" :disabled="saving"
                        class="px-6 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 disabled:opacity-50">
                        {{ saving ? 'Saving...' : 'Save' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
