<template>
    <div class="bg-slate-50 min-h-screen">
        <button @click="$router.back()"
            class="mb-4 inline-flex items-center text-xs font-black uppercase tracking-wider text-slate-500 hover:text-slate-800 transition-colors">
            <ArrowLeft class="w-4 h-4 mr-1" /> Back
        </button>

        <div v-if="loading" class="text-center py-12 text-slate-400 text-sm font-black uppercase tracking-wider">Loading...</div>

        <template v-else-if="pr">
            <div class="bg-white border border-slate-200 rounded-xl p-6 mb-6 shadow-sm">
                <div class="flex items-start justify-between mb-6">
                    <div>
                        <h1 class="text-xl font-black text-slate-900 uppercase tracking-widest">{{ pr.reference }}</h1>
                        <p class="text-xs text-slate-500 mt-1 font-medium">Requested by {{ pr.requester?.name }} on {{ formatDate(pr.created_at) }}</p>
                    </div>
                    <span class="inline-flex items-center px-3 py-1 text-[10px] font-black uppercase tracking-wider rounded-full shadow-sm"
                        :class="statusClass(pr.status)">{{ pr.status.replace(/_/g, ' ') }}</span>
                </div>

                <div class="grid grid-cols-2 gap-6">
                    <div class="p-3 bg-slate-50 rounded-lg border border-slate-100">
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Part</label>
                        <p class="text-sm font-bold text-slate-900">{{ pr.part?.name }}</p>
                        <p class="text-[10px] text-slate-400 font-medium">SKU: {{ pr.part?.sku }} | UOM: {{ pr.part?.unit_of_measure }}</p>
                    </div>
                    <div class="p-3 bg-slate-50 rounded-lg border border-slate-100">
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Quantity</label>
                        <p class="text-sm font-bold text-slate-900">{{ pr.quantity }}</p>
                    </div>
                    <div class="p-3 bg-slate-50 rounded-lg border border-slate-100">
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Urgency</label>
                        <p class="text-sm font-bold text-slate-900 capitalize">{{ pr.urgency }}</p>
                    </div>
                    <div class="p-3 bg-slate-50 rounded-lg border border-slate-100">
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Unit Price</label>
                        <p class="text-sm font-bold text-slate-900">{{ pr.part?.unit_price ? '$' + parseFloat(pr.part.unit_price).toFixed(2) : '—' }}</p>
                    </div>
                    <div v-if="pr.repair_request" class="col-span-2 p-3 bg-slate-50 rounded-lg border border-slate-100">
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Repair Request</label>
                        <p class="text-sm font-bold text-indigo-600">{{ pr.repair_request.reference }}</p>
                    </div>
                    <div v-if="pr.notes" class="col-span-2 p-3 bg-slate-50 rounded-lg border border-slate-100">
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Notes</label>
                        <p class="text-sm text-slate-700">{{ pr.notes }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-slate-200 rounded-xl p-6 mb-6 shadow-sm">
                <h2 class="text-base font-black text-slate-900 uppercase tracking-widest mb-4">Approval Chain</h2>
                <div class="space-y-3">
                    <div v-for="(level, i) in approvalLevels" :key="i"
                        class="flex items-center gap-4 p-3 rounded-lg border transition-colors"
                        :class="getApprovalRowClass(i)">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-black shadow-sm"
                            :class="getApprovalDotClass(i)">
                            {{ i + 1 }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold text-slate-900">{{ level.label }}</p>
                            <p v-if="getApproval(i)" class="text-xs font-medium" :class="getApproval(i).action === 'approved' ? 'text-emerald-600' : 'text-rose-600'">
                                {{ getApproval(i).action }} by {{ getApproval(i).approver?.name }}
                                <span v-if="getApproval(i).comment"> — {{ getApproval(i).comment }}</span>
                            </p>
                            <p v-else-if="i === currentLevelIndex()" class="text-xs font-bold text-amber-600 uppercase tracking-wider">Awaiting approval</p>
                            <p v-else class="text-xs text-slate-400 font-medium">Pending</p>
                        </div>
                    </div>
                </div>
            </div>

            <Transition name="fade">
                <div v-if="canAct" class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
                    <h2 class="text-base font-black text-slate-900 uppercase tracking-widest mb-4">Take Action</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-black uppercase tracking-wider text-slate-600 mb-1">Comment / Reason</label>
                            <textarea v-model="actionComment" rows="3"
                                class="w-full rounded-lg border-slate-200 bg-slate-50 text-xs font-black uppercase placeholder:font-normal placeholder:normal-case focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                        </div>
                        <div class="flex gap-3">
                            <button @click="approve"
                                class="inline-flex items-center px-6 py-2 text-xs font-black uppercase tracking-wider text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition-colors shadow-sm">
                                <Check class="w-4 h-4 mr-1" /> Approve
                            </button>
                            <button @click="reject"
                                class="inline-flex items-center px-6 py-2 text-xs font-black uppercase tracking-wider text-white bg-rose-600 rounded-lg hover:bg-rose-700 transition-colors shadow-sm">
                                <X class="w-4 h-4 mr-1" /> Reject
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </template>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { useRoute } from "vue-router";
import { ArrowLeft, Check, X } from "lucide-vue-next";
import { partRequestsApi } from "../../../api/workshop/part-requests";
import dayjs from "dayjs";

const route = useRoute();
const pr = ref(null);
const loading = ref(true);
const actionComment = ref("");

const approvalLevels = [
    { label: "Procurement Clerk", key: "clerk" },
    { label: "Workshop Manager", key: "workshop_manager" },
    { label: "Logistics Manager", key: "logistics_manager" },
    { label: "Operations Manager", key: "ops_manager" },
];

onMounted(fetch);

async function fetch() {
    loading.value = true;
    try {
        const { data } = await partRequestsApi.show(route.params.id);
        pr.value = data;
    } finally {
        loading.value = false;
    }
}

function getApproval(i) {
    return pr.value?.approvals?.find(a => a.approval_level === approvalLevels[i].key);
}

function currentLevelIndex() {
    return pr.value ? (pr.value.current_approval_level || 1) - 1 : -1;
}

const canAct = computed(() => {
    if (!pr.value) return false;
    return ["pending_clerk", "pending_workshop_manager", "pending_logistics_manager", "pending_ops_manager"].includes(pr.value.status);
});

function getApprovalRowClass(i) {
    const a = getApproval(i);
    if (a?.action === "approved") return "bg-emerald-50 border-emerald-200";
    if (a?.action === "rejected") return "bg-rose-50 border-rose-200";
    if (i === currentLevelIndex()) return "bg-amber-50 border-amber-200";
    return "border-slate-100";
}

function getApprovalDotClass(i) {
    const a = getApproval(i);
    if (a?.action === "approved") return "bg-emerald-500 text-white";
    if (a?.action === "rejected") return "bg-rose-500 text-white";
    if (i === currentLevelIndex()) return "bg-amber-500 text-white";
    return "bg-slate-300 text-slate-600";
}

async function approve() {
    await partRequestsApi.approve(route.params.id, { comment: actionComment.value || null });
    actionComment.value = "";
    await fetch();
}

async function reject() {
    const reason = actionComment.value || prompt("Enter rejection reason:");
    if (!reason) return;
    await partRequestsApi.reject(route.params.id, { reason });
    actionComment.value = "";
    await fetch();
}

function statusClass(status) {
    const map = {
        pending_clerk: "bg-amber-100 text-amber-700",
        pending_workshop_manager: "bg-orange-100 text-orange-700",
        pending_logistics_manager: "bg-orange-100 text-orange-700",
        pending_ops_manager: "bg-orange-100 text-orange-700",
        approved: "bg-emerald-100 text-emerald-700",
        rejected: "bg-rose-100 text-rose-700",
    };
    return map[status] || "bg-slate-100 text-slate-700";
}

function formatDate(d) {
    return d ? dayjs(d).format("MMM D, YYYY h:mm A") : "";
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity .3s;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
