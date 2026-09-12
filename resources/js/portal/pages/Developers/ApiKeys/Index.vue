<template>
    <div class="h-screen flex flex-col bg-slate-50 overflow-hidden relative">

        <!-- NOTIFICATION TOAST -->
        <Transition name="slide-fade">
            <div v-if="notification.show" class="fixed bottom-8 right-8 z-[130] flex items-center gap-3 px-6 py-3 bg-slate-900 text-white rounded-xl shadow-2xl border border-slate-700">
                <Check class="w-4 h-4 text-emerald-400" />
                <span class="text-sm font-bold">{{ notification.message }}</span>
            </div>
        </Transition>

        <!-- GENERATE MODAL -->
        <Transition name="slide-fade">
            <div v-if="showCreate" class="fixed inset-0 z-[160] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" @click.self="closeGenerate">
                <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full border border-slate-200 overflow-hidden">
                    <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                        <div>
                            <h3 class="font-black text-slate-800 uppercase text-sm tracking-widest">Generate API Key</h3>
                            <p class="text-xs font-bold text-slate-400 mt-1">Create a new access key</p>
                        </div>
                        <button @click="closeGenerate" class="text-slate-400 hover:text-slate-600 transition-colors"><X class="w-5 h-5" /></button>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 block">Key Name</label>
                            <input v-model="newKeyName" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500 outline-none" placeholder="e.g. Production" @keyup.enter="generate" />
                        </div>
                        <div v-if="generatedKey" class="p-4 bg-amber-50 border border-amber-200 rounded-xl">
                            <p class="text-[10px] font-black text-amber-700 uppercase tracking-widest mb-2">Copy this key now. You won't see it again.</p>
                            <div class="flex items-center gap-2">
                                <code class="flex-1 p-2.5 bg-white border border-amber-200 rounded-lg text-xs font-mono break-all text-amber-900">{{ generatedKey }}</code>
                                <button @click="copyKey" class="p-2.5 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition-all" title="Copy">
                                    <Copy class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="p-4 bg-slate-50 border-t border-slate-100 flex gap-3">
                        <button @click="closeGenerate" class="flex-1 px-4 py-3 bg-slate-100 text-slate-600 text-xs font-black rounded-xl uppercase hover:bg-slate-200 transition-all">Close</button>
                        <button v-if="!generatedKey" @click="generate" :disabled="saving"
                            class="flex-1 px-4 py-3 bg-indigo-600 text-white text-xs font-black rounded-xl shadow-lg uppercase hover:bg-indigo-700 transition-all flex items-center justify-center gap-2">
                            <span v-if="saving" class="w-3 h-3 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                            <template v-else><KeyRound class="w-4 h-4" /> Generate</template>
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- REVOKE CONFIRM MODAL -->
        <Transition name="fade">
            <div v-if="confirm.show" class="fixed inset-0 z-[170] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
                <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-8 border border-slate-200">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="p-3 rounded-full bg-rose-100 text-rose-600">
                            <KeyRound class="w-7 h-7" />
                        </div>
                        <h3 class="text-xl font-black uppercase tracking-tight text-slate-800">Revoke Key</h3>
                    </div>
                    <div class="bg-slate-50 border border-slate-100 rounded-xl p-4 mb-8">
                        <p class="text-sm text-slate-600 font-medium leading-relaxed">Revoke API key <strong>{{ confirm.name }}</strong>? Applications using it will immediately lose access.</p>
                    </div>
                    <div class="flex gap-3">
                        <button @click="confirm.show = false" class="flex-1 px-4 py-3 bg-slate-100 text-slate-600 text-xs font-black rounded-xl uppercase">Cancel</button>
                        <button @click="proceedRevoke" :disabled="saving" class="flex-1 px-4 py-3 bg-rose-600 text-white text-xs font-black rounded-xl shadow-lg uppercase hover:bg-rose-700 transition-all flex items-center justify-center gap-2">
                            <span v-if="saving" class="w-3 h-3 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                            <template v-else><KeyRound class="w-4 h-4" /> Revoke</template>
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- HEADER -->
        <header class="bg-white border-b border-slate-200 px-8 py-4 flex flex-wrap items-center justify-between gap-4 shadow-sm z-10">
            <div class="flex items-center gap-4">
                <div class="p-2 bg-indigo-600 rounded-lg"><KeyRound class="w-6 h-6 text-white" /></div>
                <div>
                    <h1 class="text-xl font-black text-slate-800 uppercase leading-none">API Keys</h1>
                    <p class="text-xs font-bold text-slate-400 mt-1">Manage access keys for integrations</p>
                </div>
            </div>
            <button @click="showCreate = true" class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-xs font-black rounded-lg hover:bg-indigo-700 transition-all uppercase shadow-sm">
                <Plus class="w-4 h-4" /> Generate Key
            </button>
        </header>

        <!-- COLUMN HEADERS -->
        <div class="px-8 py-3 bg-slate-800 text-white text-[10px] font-black uppercase tracking-widest grid grid-cols-12 gap-4 shadow-lg z-10">
            <div class="col-span-3">Name</div>
            <div class="col-span-2">Created</div>
            <div class="col-span-3">Last Used</div>
            <div class="col-span-2">Status</div>
            <div class="col-span-2 text-right">Actions</div>
        </div>

        <!-- ROWS -->
        <div class="flex-1 overflow-y-auto px-8 py-4 space-y-2 custom-scrollbar">
            <div v-for="key in keys" :key="key.id"
                class="bg-white border border-slate-200 rounded-lg p-3 grid grid-cols-12 gap-4 items-center transition-all hover:border-indigo-300"
                :class="!key.is_active ? 'opacity-70 bg-slate-50' : ''">
                <div class="col-span-3">
                    <span class="font-black text-slate-900 uppercase tracking-tighter text-xs">{{ key.name }}</span>
                </div>
                <div class="col-span-2 text-[10px] text-slate-400 font-medium">{{ key.created_at ? formatDate(key.created_at) : '—' }}</div>
                <div class="col-span-3 text-[10px] text-slate-400 font-medium">{{ key.last_used_at ? formatDate(key.last_used_at) : 'Never' }}</div>
                <div class="col-span-2">
                    <span class="text-[8px] font-black px-1.5 py-0.5 rounded uppercase"
                        :class="key.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'">{{ key.is_active ? 'Active' : 'Revoked' }}</span>
                </div>
                <div class="col-span-2 flex justify-end gap-1">
                    <button v-if="key.is_active" @click="openRevoke(key)"
                        class="p-2 hover:bg-rose-50 text-slate-300 hover:text-rose-600 rounded-lg transition-colors" title="Revoke">
                        <Ban class="w-4 h-4" />
                    </button>
                </div>
            </div>

            <div v-if="!keys.length" class="flex flex-col items-center justify-center py-20 text-slate-400">
                <KeyRound class="w-12 h-12 mb-4 text-slate-200" />
                <p class="text-sm font-black uppercase">No keys yet</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue';
import { apiKeysApi } from '../../../api/apiKeys';
import { KeyRound, Plus, X, Ban, Check, Copy } from 'lucide-vue-next';
import dayjs from 'dayjs';

const keys = ref([]);
const showCreate = ref(false);
const newKeyName = ref('');
const generatedKey = ref(null);
const saving = ref(false);
const notification = reactive({ show: false, message: '' });
const confirm = reactive({ show: false, id: null, name: '' });

const triggerNotification = (msg) => {
    notification.message = msg;
    notification.show = true;
    setTimeout(() => notification.show = false, 3000);
};

const formatDate = (d) => dayjs(d).format('YYYY-MM-DD');

const closeGenerate = () => {
    showCreate.value = false;
    generatedKey.value = null;
    newKeyName.value = '';
};

async function load() {
    const { data } = await apiKeysApi.index();
    keys.value = data;
}

async function generate() {
    if (!newKeyName.value) return;
    saving.value = true;
    try {
        const { data } = await apiKeysApi.store({ name: newKeyName.value });
        generatedKey.value = data.plain_text_key;
        await load();
    } catch (e) { triggerNotification(e.response?.data?.message || 'Failed to generate key'); }
    finally { saving.value = false; }
}

const copyKey = async () => {
    await navigator.clipboard.writeText(generatedKey.value);
    triggerNotification('Key copied');
};

const openRevoke = (key) => {
    confirm.id = key.id;
    confirm.name = key.name;
    confirm.show = true;
};

async function proceedRevoke() {
    saving.value = true;
    try {
        await apiKeysApi.destroy(confirm.id);
        confirm.show = false;
        triggerNotification('Key revoked');
        await load();
    } catch (e) { triggerNotification(e.response?.data?.message || 'Failed to revoke'); }
    finally { saving.value = false; }
}

onMounted(load);
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.slide-fade-enter-active, .slide-fade-leave-active { transition: all 0.3s; }
.slide-fade-enter-from, .slide-fade-leave-to { transform: translateY(20px); opacity: 0; }
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
</style>