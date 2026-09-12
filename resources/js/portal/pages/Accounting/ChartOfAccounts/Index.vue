<script setup lang="ts">
import { ref, onMounted, reactive } from 'vue'
import { chartOfAccountsApi } from '../../../api/accounting'
import { Plus, Pencil, CircleCheck, CircleX, Check, Lock, Landmark, Search, X } from 'lucide-vue-next'

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
const notification = reactive({ show: false, message: '' })

const typeLabels: Record<string, string> = {
    asset: 'Assets',
    liability: 'Liabilities',
    equity: 'Equity',
    revenue: 'Revenue',
    expense: 'Expenses',
}

const triggerNotification = (msg: string) => {
    notification.message = msg
    notification.show = true
    setTimeout(() => notification.show = false, 3000)
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
            triggerNotification('Account updated')
        } else {
            await chartOfAccountsApi.create(form.value)
            triggerNotification('Account created')
        }
        showModal.value = false
        await fetchAccounts()
    } catch (e: any) {
        triggerNotification(e.response?.data?.message || 'Save failed')
    } finally { saving.value = false }
}

async function toggleActive(account: any) {
    try {
        await chartOfAccountsApi.update(account.id, { is_active: !account.is_active })
        triggerNotification(account.is_active ? 'Account deactivated' : 'Account activated')
        await fetchAccounts()
    } catch { triggerNotification('Failed to update account') }
}

onMounted(fetchAccounts)
</script>

<template>
    <div class="h-screen flex flex-col bg-slate-50 overflow-hidden relative">

        <!-- NOTIFICATION TOAST -->
        <Transition name="slide-fade">
            <div v-if="notification.show" class="fixed bottom-8 right-8 z-[130] flex items-center gap-3 px-6 py-3 bg-slate-900 text-white rounded-xl shadow-2xl border border-slate-700">
                <Check class="w-4 h-4 text-emerald-400" />
                <span class="text-sm font-bold">{{ notification.message }}</span>
            </div>
        </Transition>

        <!-- CREATE/EDIT MODAL -->
        <Transition name="slide-fade">
            <div v-if="showModal" class="fixed inset-0 z-[160] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" @click.self="showModal = false">
                <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full border border-slate-200 overflow-hidden max-h-[90vh] flex flex-col">
                    <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                        <div>
                            <h3 class="font-black text-slate-800 uppercase text-sm tracking-widest">{{ editing ? 'Edit Account' : 'New Account' }}</h3>
                            <p class="text-xs font-bold text-slate-400 mt-1">Chart of accounts entry</p>
                        </div>
                        <button @click="showModal = false" class="text-slate-400 hover:text-slate-600 transition-colors"><X class="w-5 h-5" /></button>
                    </div>
                    <div class="flex-1 overflow-y-auto p-6 space-y-4 custom-scrollbar">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 block">Code</label>
                                <input v-model="form.code" placeholder="e.g. 1000"
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-emerald-500 outline-none font-mono" />
                            </div>
                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 block">Type</label>
                                <select v-model="form.type"
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-emerald-500 outline-none">
                                    <option value="asset">Asset</option>
                                    <option value="liability">Liability</option>
                                    <option value="equity">Equity</option>
                                    <option value="revenue">Revenue</option>
                                    <option value="expense">Expense</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 block">Name</label>
                            <input v-model="form.name"
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-emerald-500 outline-none" />
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 block">Description</label>
                            <textarea v-model="form.description" rows="2"
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-700 focus:ring-2 focus:ring-emerald-500 outline-none resize-none"></textarea>
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 block">Parent Account</label>
                            <select v-model="form.parent_id"
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-emerald-500 outline-none">
                                <option :value="null">None (Top Level)</option>
                                <option v-for="a in accounts.filter(a => !a.parent_id)" :key="a.id" :value="a.id">
                                    {{ a.code }} — {{ a.name }}
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="p-4 bg-slate-50 border-t border-slate-100 flex gap-3">
                        <button @click="showModal = false" class="flex-1 px-4 py-3 bg-slate-100 text-slate-600 text-xs font-black rounded-xl uppercase hover:bg-slate-200 transition-all">Cancel</button>
                        <button @click="save" :disabled="saving"
                            class="flex-1 px-4 py-3 bg-emerald-600 text-white text-xs font-black rounded-xl shadow-lg uppercase hover:bg-emerald-700 transition-all disabled:opacity-40 flex items-center justify-center gap-2">
                            <span v-if="saving" class="w-3 h-3 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                            <template v-else><Check class="w-4 h-4" /> Save</template>
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- HEADER -->
        <header class="bg-white border-b border-slate-200 px-8 py-4 flex flex-wrap items-center justify-between gap-4 shadow-sm z-10">
            <div class="flex items-center gap-4">
                <div class="p-2 bg-emerald-700 rounded-lg"><Landmark class="w-6 h-6 text-white" /></div>
                <div>
                    <h1 class="text-xl font-black text-slate-800 uppercase leading-none">Chart of Accounts</h1>
                    <p class="text-xs font-bold text-slate-400 mt-1">Structure your general ledger</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="relative">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                    <input v-model="search" @input="fetchAccounts" placeholder="Search code or name..."
                        class="pl-10 pr-4 py-2 w-64 bg-slate-100 border-none rounded-full text-sm font-bold focus:ring-2 focus:ring-emerald-500 outline-none" />
                </div>
                <button @click="openCreate" class="flex items-center gap-2 px-4 py-2 bg-emerald-700 text-white text-xs font-black rounded-lg hover:bg-emerald-800 transition-all uppercase shadow-sm">
                    <Plus class="w-4 h-4" /> New Account
                </button>
            </div>
        </header>

        <!-- LOADING -->
        <div v-if="loading" class="flex-1 flex flex-col items-center justify-center text-slate-400">
            <span class="w-6 h-6 border-2 border-slate-200 border-t-emerald-600 rounded-full animate-spin mb-3"></span>
            <span class="text-xs font-black uppercase tracking-widest">Loading...</span>
        </div>

        <!-- GROUPS -->
        <div v-else class="flex-1 overflow-y-auto px-8 py-4 space-y-4 custom-scrollbar">
            <div v-for="type in ['asset', 'liability', 'equity', 'revenue', 'expense']" :key="type"
                class="rounded-2xl border border-slate-200 overflow-hidden bg-white shadow-sm">
                <div class="px-5 py-3 bg-slate-800 text-white flex items-center gap-2">
                    <Lock class="w-3 h-3 text-emerald-400" />
                    <h3 class="text-[10px] font-black uppercase tracking-widest">{{ typeLabels[type] }}</h3>
                    <span class="ml-auto text-[9px] font-black text-slate-400 uppercase">{{ byType(type).length + byType(type).reduce((n, a) => n + childrenOf(a.id).length, 0) }} accounts</span>
                </div>
                <div v-if="!byType(type).length" class="p-6 text-center text-slate-400 text-xs font-black uppercase">No accounts</div>
                <div v-else>
                    <div class="px-5 py-2 bg-slate-50 border-b border-slate-100 text-[9px] font-black text-slate-400 uppercase tracking-widest grid grid-cols-12 gap-4">
                        <div class="col-span-2">Code</div>
                        <div class="col-span-3">Name</div>
                        <div class="col-span-5">Description</div>
                        <div class="col-span-1 text-center">Status</div>
                        <div class="col-span-1 text-right">Actions</div>
                    </div>
                    <template v-for="account in byType(type)" :key="account.id">
                        <div class="grid grid-cols-12 gap-4 px-5 py-3 border-b border-slate-50 last:border-0 items-center transition-all hover:bg-slate-50/50">
                            <div class="col-span-2 font-mono font-black text-slate-800 text-xs">{{ account.code }}</div>
                            <div class="col-span-3 font-black text-slate-800 text-xs uppercase">{{ account.name }}</div>
                            <div class="col-span-5 text-xs text-slate-500 font-medium truncate">{{ account.description || '-' }}</div>
                            <div class="col-span-1 flex justify-center">
                                <button @click="toggleActive(account)" class="transition-transform hover:scale-110" :title="account.is_active ? 'Deactivate' : 'Activate'">
                                    <CircleCheck v-if="account.is_active" class="w-4 h-4 text-emerald-500" />
                                    <CircleX v-else class="w-4 h-4 text-rose-400" />
                                </button>
                            </div>
                            <div class="col-span-1 flex justify-end">
                                <button @click="openEdit(account)" class="p-1.5 text-slate-300 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors">
                                    <Pencil class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                        <div v-for="child in childrenOf(account.id)" :key="child.id"
                            class="grid grid-cols-12 gap-4 px-5 py-3 border-b border-slate-50 last:border-0 items-center bg-slate-50/50 transition-all hover:bg-slate-100/60">
                            <div class="col-span-2 font-mono text-slate-600 text-xs pl-6">↳ {{ child.code }}</div>
                            <div class="col-span-3 font-bold text-slate-600 text-xs uppercase">{{ child.name }}</div>
                            <div class="col-span-5 text-xs text-slate-400 font-medium truncate">{{ child.description || '-' }}</div>
                            <div class="col-span-1 flex justify-center">
                                <button @click="toggleActive(child)" class="transition-transform hover:scale-110" :title="child.is_active ? 'Deactivate' : 'Activate'">
                                    <CircleCheck v-if="child.is_active" class="w-4 h-4 text-emerald-500" />
                                    <CircleX v-else class="w-4 h-4 text-rose-400" />
                                </button>
                            </div>
                            <div class="col-span-1 flex justify-end">
                                <button @click="openEdit(child)" class="p-1.5 text-slate-300 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors">
                                    <Pencil class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.slide-fade-enter-active, .slide-fade-leave-active { transition: all 0.3s; }
.slide-fade-enter-from, .slide-fade-leave-to { transform: translateY(20px); opacity: 0; }
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
</style>