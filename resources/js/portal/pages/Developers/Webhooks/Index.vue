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
                            <h3 class="font-black text-slate-800 uppercase text-sm tracking-widest">{{ editing ? 'Edit' : 'Create' }} Subscription</h3>
                            <p class="text-xs font-bold text-slate-400 mt-1">Webhook event delivery configuration</p>
                        </div>
                        <button @click="showModal = false" class="text-slate-400 hover:text-slate-600 transition-colors"><X class="w-5 h-5" /></button>
                    </div>
                    <div class="flex-1 overflow-y-auto p-6 space-y-4 custom-scrollbar">
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 block">Webhook URL *</label>
                            <input v-model="form.url" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500 outline-none font-mono" placeholder="https://example.com/webhook" />
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Events</label>
                            <div class="space-y-1.5 max-h-52 overflow-y-auto bg-slate-50 border border-slate-200 rounded-xl p-4 custom-scrollbar">
                                <label v-for="ev in availableEvents" :key="ev" class="flex items-center gap-2 text-xs font-black text-slate-700 uppercase cursor-pointer hover:text-indigo-600 transition-colors">
                                    <input type="checkbox" :value="ev" v-model="form.events" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                                    {{ ev }}
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="p-4 bg-slate-50 border-t border-slate-100 flex gap-3">
                        <button @click="showModal = false" class="flex-1 px-4 py-3 bg-slate-100 text-slate-600 text-xs font-black rounded-xl uppercase hover:bg-slate-200 transition-all">Cancel</button>
                        <button @click="save" :disabled="saving || !form.url"
                            class="flex-1 px-4 py-3 bg-indigo-600 text-white text-xs font-black rounded-xl shadow-lg uppercase hover:bg-indigo-700 transition-all disabled:opacity-40 flex items-center justify-center gap-2">
                            <span v-if="saving" class="w-3 h-3 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                            <template v-else><Save class="w-4 h-4" /> Save</template>
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- DELETE CONFIRM MODAL -->
        <Transition name="fade">
            <div v-if="confirm.show" class="fixed inset-0 z-[170] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
                <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-8 border border-slate-200">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="p-3 rounded-full bg-rose-100 text-rose-600">
                            <Trash2 class="w-7 h-7" />
                        </div>
                        <h3 class="text-xl font-black uppercase tracking-tight text-slate-800">Delete Subscription</h3>
                    </div>
                    <div class="bg-slate-50 border border-slate-100 rounded-xl p-4 mb-8">
                        <p class="text-sm text-slate-600 font-medium leading-relaxed">Delete the webhook subscription for <strong>{{ confirm.url }}</strong>? Events will no longer be delivered.</p>
                    </div>
                    <div class="flex gap-3">
                        <button @click="confirm.show = false" class="flex-1 px-4 py-3 bg-slate-100 text-slate-600 text-xs font-black rounded-xl uppercase">Cancel</button>
                        <button @click="proceedDelete" :disabled="saving" class="flex-1 px-4 py-3 bg-rose-600 text-white text-xs font-black rounded-xl shadow-lg uppercase hover:bg-rose-700 transition-all flex items-center justify-center gap-2">
                            <span v-if="saving" class="w-3 h-3 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                            <template v-else><Trash2 class="w-4 h-4" /> Delete</template>
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- HEADER -->
        <header class="bg-white border-b border-slate-200 px-8 py-4 flex flex-wrap items-center justify-between gap-4 shadow-sm z-10">
            <div class="flex items-center gap-4">
                <div class="p-2 bg-indigo-600 rounded-lg"><Webhook class="w-6 h-6 text-white" /></div>
                <div>
                    <h1 class="text-xl font-black text-slate-800 uppercase leading-none">Webhook Subscriptions</h1>
                    <p class="text-xs font-bold text-slate-400 mt-1">Receive real-time event notifications</p>
                </div>
            </div>
            <button @click="openCreate" class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-xs font-black rounded-lg hover:bg-indigo-700 transition-all uppercase shadow-sm">
                <Plus class="w-4 h-4" /> New Subscription
            </button>
        </header>

        <!-- COLUMN HEADERS -->
        <div class="px-8 py-3 bg-slate-800 text-white text-[10px] font-black uppercase tracking-widest grid grid-cols-12 gap-4 shadow-lg z-10">
            <div class="col-span-4">URL</div>
            <div class="col-span-5">Events</div>
            <div class="col-span-1">Status</div>
            <div class="col-span-2 text-right">Actions</div>
        </div>

        <!-- ROWS -->
        <div class="flex-1 overflow-y-auto px-8 py-4 space-y-2 custom-scrollbar">
            <div v-for="sub in subscriptions" :key="sub.id"
                class="bg-white border border-slate-200 rounded-lg p-3 grid grid-cols-12 gap-4 items-center transition-all hover:border-indigo-300"
                :class="!sub.is_active ? 'opacity-70 bg-slate-50' : ''">
                <div class="col-span-4">
                    <span class="font-black text-slate-900 uppercase tracking-tighter text-xs font-mono truncate block" :title="sub.url">{{ sub.url }}</span>
                </div>
                <div class="col-span-5 flex flex-wrap gap-1">
                    <span v-for="ev in (sub.events || [])" :key="ev" class="text-[8px] font-black px-1.5 py-0.5 bg-indigo-100 text-indigo-700 rounded uppercase">{{ ev }}</span>
                    <span v-if="!(sub.events || []).length" class="text-[10px] text-slate-400 font-medium">—</span>
                </div>
                <div class="col-span-1">
                    <span class="text-[8px] font-black px-1.5 py-0.5 rounded uppercase"
                        :class="sub.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'">{{ sub.is_active ? 'Active' : 'Inactive' }}</span>
                </div>
                <div class="col-span-2 flex justify-end gap-1">
                    <button @click="openEdit(sub)"
                        class="p-2 hover:bg-indigo-50 text-slate-300 hover:text-indigo-600 rounded-lg transition-colors" title="Edit">
                        <Pencil class="w-4 h-4" />
                    </button>
                    <button @click="openConfirmDelete(sub)"
                        class="p-2 hover:bg-rose-50 text-slate-300 hover:text-rose-600 rounded-lg transition-colors" title="Delete">
                        <Trash2 class="w-4 h-4" />
                    </button>
                </div>
            </div>

            <div v-if="!subscriptions.length" class="flex flex-col items-center justify-center py-20 text-slate-400">
                <Webhook class="w-12 h-12 mb-4 text-slate-200" />
                <p class="text-sm font-black uppercase">No subscriptions yet</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue';
import { webhooksApi } from '../../../api/webhooks';
import { Webhook, Plus, X, Trash2, Pencil, Save, Check } from 'lucide-vue-next';

const subscriptions = ref([]);
const availableEvents = ref([]);
const showModal = ref(false);
const editing = ref(null);
const saving = ref(false);
const notification = reactive({ show: false, message: '' });
const confirm = reactive({ show: false, id: null, url: '' });

const form = ref({ url: '', events: [] });

const triggerNotification = (msg) => {
    notification.message = msg;
    notification.show = true;
    setTimeout(() => notification.show = false, 3000);
};

async function load() {
    const [subRes, evRes] = await Promise.all([
        webhooksApi.subscriptions(),
        webhooksApi.events().catch(() => ({ data: [] }))
    ]);
    subscriptions.value = subRes.data;
    availableEvents.value = evRes.data;
}

function openCreate() {
    editing.value = null;
    form.value = { url: '', events: [] };
    showModal.value = true;
}

function openEdit(sub) {
    editing.value = sub;
    form.value = { url: sub.url, events: [...(sub.events || [])] };
    showModal.value = true;
}

async function save() {
    saving.value = true;
    try {
        if (editing.value) {
            await webhooksApi.updateSubscription(editing.value.id, form.value);
            triggerNotification('Subscription updated');
        } else {
            await webhooksApi.storeSubscription(form.value);
            triggerNotification('Subscription created');
        }
        showModal.value = false;
        await load();
    } catch (e) { triggerNotification(e.response?.data?.message || 'Failed to save subscription'); }
    finally { saving.value = false; }
}

function openConfirmDelete(sub) {
    confirm.id = sub.id;
    confirm.url = sub.url;
    confirm.show = true;
}

async function proceedDelete() {
    saving.value = true;
    try {
        await webhooksApi.deleteSubscription(confirm.id);
        confirm.show = false;
        triggerNotification('Subscription deleted');
        await load();
    } catch (e) { triggerNotification(e.response?.data?.message || 'Failed to delete'); }
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