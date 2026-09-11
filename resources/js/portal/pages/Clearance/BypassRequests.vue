<template>
    <div class="h-screen flex flex-col bg-slate-50 overflow-hidden relative">

        <!-- NOTIFICATION TOAST -->
        <Transition name="slide-fade">
            <div v-if="notification.show" class="fixed bottom-8 right-8 z-[130] flex items-center gap-3 px-6 py-3 bg-slate-900 text-white rounded-xl shadow-2xl border border-slate-700">
                <Check class="w-4 h-4 text-emerald-400" />
                <span class="text-sm font-bold">{{ notification.message }}</span>
            </div>
        </Transition>

        <!-- APPROVE MODAL -->
        <Transition name="fade">
            <div v-if="modal.show === 'approve'" class="fixed inset-0 z-[160] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
                <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full border border-slate-200 overflow-hidden">
                    <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                        <h3 class="font-black text-slate-800 uppercase text-sm tracking-widest">Approve Bypass</h3>
                        <button @click="modal.show = null" class="text-slate-400 hover:text-slate-600 transition-colors"><X class="w-5 h-5" /></button>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="flex justify-between border-b border-slate-50 pb-2">
                            <span class="text-slate-400 font-bold uppercase tracking-tighter text-xs">Check</span>
                            <span class="font-black text-slate-800 text-xs">{{ modal.request?.check_label }}</span>
                        </div>
                        <div class="flex justify-between border-b border-slate-50 pb-2">
                            <span class="text-slate-400 font-bold uppercase tracking-tighter text-xs">Trip</span>
                            <span class="font-black text-slate-800 text-xs">{{ modal.request?.trip?.reference || '—' }}</span>
                        </div>
                        <div class="flex justify-between border-b border-slate-50 pb-2">
                            <span class="text-slate-400 font-bold uppercase tracking-tighter text-xs">Requested By</span>
                            <span class="font-black text-slate-800 text-xs">{{ modal.request?.requester?.name || '—' }}</span>
                        </div>
                        <div class="bg-slate-50 border border-slate-100 rounded-xl p-4">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Reason</p>
                            <p class="text-sm text-slate-700 font-medium leading-relaxed">{{ modal.request?.reason }}</p>
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 block">Comment (optional)</label>
                            <textarea v-model="modal.comment" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-700 focus:ring-2 focus:ring-indigo-500 outline-none resize-none" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="p-4 bg-slate-50 flex gap-3">
                        <button @click="modal.show = null" class="flex-1 px-4 py-3 bg-slate-100 text-slate-600 text-xs font-black rounded-xl uppercase">Cancel</button>
                        <button @click="approve" :disabled="saving" class="flex-1 px-4 py-3 bg-emerald-600 text-white text-xs font-black rounded-xl shadow-lg uppercase hover:bg-emerald-700 transition-all flex items-center justify-center gap-2">
                            <span v-if="saving" class="w-3 h-3 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                            <template v-else><Check class="w-4 h-4" /> Approve</template>
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- REJECT MODAL -->
        <Transition name="fade">
            <div v-if="modal.show === 'reject'" class="fixed inset-0 z-[160] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
                <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full border border-slate-200 overflow-hidden">
                    <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                        <h3 class="font-black text-slate-800 uppercase text-sm tracking-widest">Reject Bypass</h3>
                        <button @click="modal.show = null" class="text-slate-400 hover:text-slate-600 transition-colors"><X class="w-5 h-5" /></button>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="flex justify-between border-b border-slate-50 pb-2">
                            <span class="text-slate-400 font-bold uppercase tracking-tighter text-xs">Check</span>
                            <span class="font-black text-slate-800 text-xs">{{ modal.request?.check_label }}</span>
                        </div>
                        <div class="flex justify-between border-b border-slate-50 pb-2">
                            <span class="text-slate-400 font-bold uppercase tracking-tighter text-xs">Trip</span>
                            <span class="font-black text-slate-800 text-xs">{{ modal.request?.trip?.reference || '—' }}</span>
                        </div>
                        <div class="flex justify-between border-b border-slate-50 pb-2">
                            <span class="text-slate-400 font-bold uppercase tracking-tighter text-xs">Requested By</span>
                            <span class="font-black text-slate-800 text-xs">{{ modal.request?.requester?.name || '—' }}</span>
                        </div>
                        <div class="bg-slate-50 border border-slate-100 rounded-xl p-4">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Reason</p>
                            <p class="text-sm text-slate-700 font-medium leading-relaxed">{{ modal.request?.reason }}</p>
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 block">Comment (required)</label>
                            <textarea v-model="modal.comment" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-700 focus:ring-2 focus:ring-indigo-500 outline-none resize-none" rows="2" required></textarea>
                        </div>
                    </div>
                    <div class="p-4 bg-slate-50 flex gap-3">
                        <button @click="modal.show = null" class="flex-1 px-4 py-3 bg-slate-100 text-slate-600 text-xs font-black rounded-xl uppercase">Cancel</button>
                        <button @click="reject" :disabled="saving || !modal.comment.trim()" class="flex-1 px-4 py-3 bg-rose-600 text-white text-xs font-black rounded-xl shadow-lg uppercase hover:bg-rose-700 transition-all flex items-center justify-center gap-2">
                            <span v-if="saving" class="w-3 h-3 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                            <template v-else><XCircle class="w-4 h-4" /> Reject</template>
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- HEADER -->
        <header class="bg-white border-b border-slate-200 px-8 py-4 flex flex-wrap items-center justify-between gap-4 shadow-sm z-10">
            <div class="flex items-center gap-4">
                <div class="p-2 bg-indigo-600 rounded-lg"><ShieldCheck class="w-6 h-6 text-white" /></div>
                <div>
                    <h1 class="text-xl font-black text-slate-800 uppercase leading-none">Bypass Requests</h1>
                    <div class="flex items-center gap-3 mt-1.5">
                        <div class="flex bg-slate-100 p-0.5 rounded-lg">
                            <button v-for="s in statuses" :key="s.value" @click="filterStatus = s.value; loadRequests(1)"
                                    class="px-3 py-1 text-[9px] font-black uppercase rounded-md transition-all"
                                    :class="filterStatus === s.value ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-400 hover:text-slate-600'">{{ s.label }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- COLUMN HEADERS -->
        <div class="px-8 py-3 bg-slate-800 text-white text-[10px] font-black uppercase tracking-widest grid grid-cols-12 gap-4 shadow-lg z-10">
            <div class="col-span-2">Trip</div>
            <div class="col-span-2">Check</div>
            <div class="col-span-3">Reason</div>
            <div class="col-span-2">Requested By</div>
            <div class="col-span-2">Date</div>
            <div class="col-span-1 text-right">Actions</div>
        </div>

        <!-- ROWS -->
        <div class="flex-1 overflow-y-auto px-8 py-4 space-y-2 custom-scrollbar">
            <div v-for="r in requests.data" :key="r.id"
                 class="bg-white border border-slate-200 rounded-lg p-3 grid grid-cols-12 gap-4 items-center transition-all hover:border-indigo-300">
                <div class="col-span-2">
                    <span class="font-black text-slate-900 uppercase tracking-tighter text-xs">{{ r.trip?.reference || '—' }}</span>
                </div>
                <div class="col-span-2 text-xs font-medium text-slate-600">{{ r.check_label }}</div>
                <div class="col-span-3 text-xs text-slate-500 font-medium truncate" :title="r.reason">{{ r.reason }}</div>
                <div class="col-span-2 text-xs font-black text-slate-700">{{ r.requester?.name || '—' }}</div>
                <div class="col-span-2 text-[10px] text-slate-400 font-medium">{{ formatDate(r.created_at) }}</div>
                <div class="col-span-1 flex items-center justify-end gap-1">
                    <template v-if="r.status === 'pending'">
                        <button @click="openModal('approve', r)" class="p-2 hover:bg-emerald-50 text-slate-300 hover:text-emerald-600 rounded-lg" title="Approve">
                            <CheckCircle class="w-4 h-4" />
                        </button>
                        <button @click="openModal('reject', r)" class="p-2 hover:bg-rose-50 text-slate-300 hover:text-rose-600 rounded-lg" title="Reject">
                            <XCircle class="w-4 h-4" />
                        </button>
                    </template>
                    <template v-else>
                        <span :class="statusBadge(r.status)" class="text-[8px] font-black px-1.5 py-0.5 rounded uppercase">{{ r.status }}</span>
                        <span v-if="r.approver" class="text-[8px] text-slate-400 font-medium ml-1">by {{ r.approver.name }}</span>
                    </template>
                </div>
            </div>

            <div v-if="!requests.data?.length" class="flex flex-col items-center justify-center py-20 text-slate-400">
                <ShieldCheck class="w-12 h-12 mb-4 text-slate-200" />
                <p class="text-sm font-black uppercase">No requests found</p>
            </div>

            <div v-if="requests.last_page > 1" class="flex justify-center gap-2 py-6">
                <button @click="loadRequests(requests.current_page - 1)" :disabled="!requests.prev_page_url"
                    class="px-4 py-2 bg-white border border-slate-200 text-xs font-black rounded-lg hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition-all uppercase">Prev</button>
                <span class="self-center text-xs font-bold text-slate-500 px-3">Page {{ requests.current_page }} of {{ requests.last_page }}</span>
                <button @click="loadRequests(requests.current_page + 1)" :disabled="!requests.next_page_url"
                    class="px-4 py-2 bg-white border border-slate-200 text-xs font-black rounded-lg hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition-all uppercase">Next</button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { clearanceApi } from "../../api/clearance";
import { Check, CheckCircle, XCircle, X, ShieldCheck } from 'lucide-vue-next';
import dayjs from 'dayjs';

const requests = ref({});
const filterStatus = ref('pending');
const saving = ref(false);
const notification = reactive({ show: false, message: '' });
const modal = reactive({ show: null, request: null, comment: '' });

const statuses = [
    { value: 'pending', label: 'Pending' },
    { value: 'approved', label: 'Approved' },
    { value: 'rejected', label: 'Rejected' },
    { value: '', label: 'All' },
];

const loadRequests = async (page = 1) => {
    const params = { page, per_page: 20 };
    if (filterStatus.value) params.status = filterStatus.value;
    const { data } = await clearanceApi.bypassRequests(params);
    requests.value = data;
};

const openModal = (type, r) => {
    modal.show = type;
    modal.request = r;
    modal.comment = '';
};

const triggerNotification = (msg) => {
    notification.message = msg;
    notification.show = true;
    setTimeout(() => notification.show = false, 3000);
};

const approve = async () => {
    saving.value = true;
    try {
        await clearanceApi.approveBypass(modal.request.id, { comment: modal.comment });
        modal.show = null;
        triggerNotification("Bypass approved");
        await loadRequests();
    } catch (e) {
        triggerNotification(e.response?.data?.message || 'Failed to approve');
    } finally { saving.value = false; }
};

const reject = async () => {
    saving.value = true;
    try {
        await clearanceApi.rejectBypass(modal.request.id, { comment: modal.comment });
        modal.show = null;
        triggerNotification("Bypass rejected");
        await loadRequests();
    } catch (e) {
        triggerNotification(e.response?.data?.message || 'Failed to reject');
    } finally { saving.value = false; }
};

const statusBadge = (status) => {
    const map = {
        pending: 'bg-amber-100 text-amber-700',
        approved: 'bg-emerald-100 text-emerald-700',
        rejected: 'bg-rose-100 text-rose-700',
    };
    return map[status] || 'bg-slate-100 text-slate-600';
};

const formatDate = (d) => dayjs(d).format('YYYY-MM-DD HH:mm');

onMounted(() => loadRequests());
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.slide-fade-enter-active, .slide-fade-leave-active { transition: all 0.3s; }
.slide-fade-enter-from, .slide-fade-leave-to { transform: translateY(20px); opacity: 0; }
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
</style>
