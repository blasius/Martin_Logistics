<template>
    <div class="p-6 bg-slate-50 min-h-screen space-y-6">
        <div v-if="rr" class="flex items-center gap-3">
            <router-link to="/workshop/repair-requests" class="text-indigo-600 hover:text-indigo-800"><ArrowLeft class="w-5 h-5" /></router-link>
            <h1 class="text-2xl font-black text-slate-800 tracking-tight uppercase">{{ rr.reference }}</h1>
        </div>

        <div v-if="loading" class="h-64 bg-white rounded-2xl animate-pulse border border-slate-100"></div>

        <template v-else-if="rr">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h2 class="font-black text-slate-800">Repair Details</h2>
                                <p class="text-xs text-slate-400">Type: {{ rr.type }}</p>
                            </div>
                            <span class="text-[10px] font-black px-3 py-1.5 rounded-full" :class="statusBadge(rr.status)">{{ displayStatus(rr.status) }}</span>
                        </div>
                        <div class="grid grid-cols-2 gap-4 mb-4 text-sm">
                            <div>
                                <span class="text-[10px] font-black text-slate-400 uppercase">Vehicle</span>
                                <p class="font-bold text-slate-800">{{ rr.vehicle?.plate_number }}</p>
                            </div>
                            <div>
                                <span class="text-[10px] font-black text-slate-400 uppercase">Priority</span>
                                <p class="font-bold" :class="rr.priority === 'critical' ? 'text-rose-600' : 'text-slate-800'">{{ rr.priority }}</p>
                            </div>
                            <div>
                                <span class="text-[10px] font-black text-slate-400 uppercase">Driver</span>
                                <p class="font-bold text-slate-800">{{ rr.driver?.name || 'Unknown' }}</p>
                            </div>
                            <div>
                                <span class="text-[10px] font-black text-slate-400 uppercase">Vehicle Status</span>
                                <p class="font-bold text-slate-800">{{ rr.vehicle?.status }}</p>
                            </div>
                        </div>
                        <div>
                            <span class="text-[10px] font-black text-slate-400 uppercase">Description</span>
                            <p class="text-sm text-slate-600 mt-1">{{ rr.description }}</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                        <h3 class="text-xs font-black text-slate-500 uppercase tracking-wider mb-4">Items &amp; Parts</h3>
                        <table class="w-full text-left">
                            <thead class="text-[10px] font-black text-slate-400 uppercase border-b">
                                <tr>
                                    <th class="pb-3">Description</th>
                                    <th class="pb-3">Part</th>
                                    <th class="pb-3 text-right">Est. Qty</th>
                                    <th class="pb-3 text-right">Est. Price</th>
                                    <th class="pb-3 text-right">Est. Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in rr.items" :key="item.id" class="border-b border-slate-50">
                                    <td class="py-3 text-sm font-bold text-slate-800">{{ item.description }}</td>
                                    <td class="py-3 text-sm text-slate-500">{{ item.part?.name || '—' }}</td>
                                    <td class="py-3 text-right text-sm">{{ item.estimated_quantity || '-' }}</td>
                                    <td class="py-3 text-right text-sm">{{ formatAmount(item.estimated_unit_price) }}</td>
                                    <td class="py-3 text-right text-sm font-bold">{{ formatAmount(item.estimated_total) }}</td>
                                </tr>
                                <tr v-if="!rr.items?.length">
                                    <td colspan="5" class="py-6 text-center text-slate-400 text-xs font-bold uppercase">No items</td>
                                </tr>
                            </tbody>
                        </table>
                        <p v-if="hasParts" class="mt-2 text-[10px] font-bold text-amber-600">Parts involved — requires Logistics Manager → Operations Manager approval.</p>
                    </div>

                    <div v-if="rr.assignments?.length" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                        <h3 class="text-xs font-black text-slate-500 uppercase tracking-wider mb-4">Assignments</h3>
                        <div v-for="a in rr.assignments" :key="a.id" class="py-3 border-b border-slate-50 last:border-0">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <p class="font-bold text-sm text-slate-800">{{ a.mechanic?.name }}</p>
                                        <span class="text-[10px] font-black px-2 py-0.5 rounded-full" :class="assignmentStatusBadge(a.status)">{{ a.status }}</span>
                                    </div>
                                    <p v-if="a.instructions" class="text-xs text-slate-500 mt-1 italic">"{{ a.instructions }}"</p>
                                    <div class="flex gap-4 mt-1 text-[10px] text-slate-400">
                                        <span>Assigned: {{ formatDate(a.assigned_at) }}</span>
                                        <span v-if="a.started_at">Started: {{ formatDate(a.started_at) }}</span>
                                        <span v-if="a.completed_at">Completed: {{ formatDate(a.completed_at) }}</span>
                                    </div>
                                    <p v-if="a.duration" class="text-[10px] font-bold text-slate-500 mt-1">Duration: {{ a.duration }}</p>
                                    <p v-if="a.completed_note" class="text-xs text-slate-500 mt-1">Note: {{ a.completed_note }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="rr.approvals?.length" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                        <h3 class="text-xs font-black text-slate-500 uppercase tracking-wider mb-4">Approvals</h3>
                        <div class="space-y-3">
                            <div v-for="ap in rr.approvals" :key="ap.id" class="flex items-center justify-between py-2 px-3 rounded-lg border" :class="approvalBorder(ap)">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <p class="font-bold text-sm text-slate-800">{{ ap.approver_role }}</p>
                                        <span class="text-[10px] font-black uppercase text-slate-400">Stage {{ ap.stage }}</span>
                                    </div>
                                    <p v-if="ap.comment" class="text-xs text-slate-500 italic">"{{ ap.comment }}"</p>
                                </div>
                                <span class="text-[10px] font-black px-2 py-1 rounded-full" :class="ap.status === 'approved' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'">
                                    {{ ap.status }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                        <h3 class="text-xs font-black text-slate-500 uppercase tracking-wider mb-4">Workflow</h3>
                        <div class="space-y-3">

                            <button v-if="can('submit')" @click="submitRequest" class="w-full py-3 bg-indigo-600 text-white rounded-xl font-black text-xs uppercase shadow-lg">Submit for Review</button>

                            <div v-if="can('request_approval')" class="space-y-2">
                                <button @click="requestApproval" class="w-full py-3 bg-indigo-600 text-white rounded-xl font-black text-xs uppercase shadow-lg">
                                    Request Approval
                                </button>
                                <p class="text-[10px] text-slate-400 text-center font-bold">Assign a mechanic first before requesting approval</p>
                            </div>

                            <template v-if="can('approve')">
                                <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 mb-2">
                                    <p class="text-[10px] font-black text-amber-700 uppercase text-center">{{ approvalHint }}</p>
                                </div>
                                <div class="flex gap-2">
                                    <input v-model="approveComment" placeholder="Comment (optional)" class="flex-1 p-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                                    <button @click="approveRequest" class="px-4 py-2 bg-emerald-600 text-white rounded-xl font-black text-xs uppercase">Approve</button>
                                </div>
                            </template>

                            <div v-if="can('reject')" class="flex gap-2">
                                <input v-model="rejectComment" placeholder="Reason" class="flex-1 p-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                                <button @click="rejectRequest" class="px-4 py-2 bg-rose-600 text-white rounded-xl font-black text-xs uppercase">Reject</button>
                            </div>

                            <div v-if="can('assign')">
                                <select v-model="selectedMechanic" class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold mb-2">
                                    <option value="">Select Mechanic</option>
                                    <option v-for="m in mechanics" :key="m.id" :value="m.id">{{ m.name }}</option>
                                </select>
                                <button @click="assignMechanic" :disabled="!selectedMechanic" class="w-full py-2.5 bg-blue-600 text-white rounded-xl font-black text-xs uppercase">Assign Mechanic</button>
                            </div>

                            <div v-if="can('reassign')">
                                <select v-model="reassignMechanicId" class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold mb-2">
                                    <option value="">Reassign to Mechanic</option>
                                    <option v-for="m in mechanics" :key="m.id" :value="m.id">{{ m.name }}</option>
                                </select>
                                <button @click="reassignMechanicAction" :disabled="!reassignMechanicId" class="w-full py-2.5 bg-amber-600 text-white rounded-xl font-black text-xs uppercase">Reassign &amp; Send Back</button>
                            </div>

                            <button v-if="can('start_work')" @click="startWork" class="w-full py-2.5 bg-indigo-600 text-white rounded-xl font-black text-xs uppercase">Start Work</button>
                            <button v-if="can('complete_work')" @click="completeWork" class="w-full py-2.5 bg-emerald-600 text-white rounded-xl font-black text-xs uppercase">Complete Work</button>
                            <button v-if="can('use_parts')" @click="showUseParts = true" class="w-full py-2.5 bg-amber-600 text-white rounded-xl font-black text-xs uppercase">Use Parts</button>

                            <div v-if="can('release')" class="space-y-2">
                                <input v-model="releaseForm.odometer_at_release" type="number" placeholder="Odometer" class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                                <textarea v-model="releaseForm.unresolved_issues" placeholder="Unresolved issues (mandatory)" rows="2" class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold"></textarea>
                                <label class="flex items-center gap-2 text-xs font-bold text-slate-600">
                                    <input type="checkbox" v-model="releaseForm.checklist_completed" class="rounded border-slate-300">
                                    Checklist completed
                                </label>
                                <button @click="releaseVehicle" :disabled="!releaseForm.checklist_completed" class="w-full py-2.5 bg-green-600 text-white rounded-xl font-black text-xs uppercase">Release from Workshop</button>
                            </div>

                            <button v-if="can('cancel')" @click="cancelRequest" class="w-full py-2.5 bg-rose-100 text-rose-700 rounded-xl font-black text-xs uppercase">Cancel Request</button>
                        </div>
                    </div>

                    <div v-if="rr.purchaseOrders?.length" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                        <h3 class="text-xs font-black text-slate-500 uppercase tracking-wider mb-4">Purchase Orders</h3>
                        <div v-for="po in rr.purchaseOrders" :key="po.id" class="flex items-center justify-between py-2 border-b border-slate-50">
                            <span class="font-bold text-xs text-indigo-600">{{ po.reference }}</span>
                            <span class="text-[10px] px-2 py-0.5 rounded-full font-black" :class="po.status === 'received' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'">{{ po.status }}</span>
                        </div>
                    </div>

                    <div v-if="rr.release" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                        <h3 class="text-xs font-black text-slate-500 uppercase tracking-wider mb-4">Release Info</h3>
                        <p class="text-xs"><span class="font-bold">By:</span> {{ rr.release.released_by?.name }}</p>
                        <p class="text-xs"><span class="font-bold">At:</span> {{ formatDate(rr.release.released_at) }}</p>
                        <p v-if="rr.release.odometer_at_release" class="text-xs"><span class="font-bold">Odometer:</span> {{ rr.release.odometer_at_release }}</p>
                        <p v-if="rr.release.unresolved_issues" class="text-xs mt-2"><span class="font-bold text-rose-600">Unresolved Issues:</span> {{ rr.release.unresolved_issues }}</p>
                    </div>
                </div>
            </div>

            <div v-if="showUseParts" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" @click.self="showUseParts = false">
                <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full border border-slate-200">
                    <div class="p-6 border-b border-slate-100 flex justify-between items-center">
                        <h3 class="font-black text-slate-800 uppercase text-sm">Use Parts</h3>
                        <button @click="showUseParts = false" class="text-slate-400 hover:text-slate-600"><X class="w-5 h-5" /></button>
                    </div>
                    <form @submit.prevent="useParts" class="p-6 space-y-4">
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase">Warehouse</label>
                            <select v-model="partsForm.warehouse_id" required class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                                <option value="">Select Warehouse</option>
                                <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">{{ wh.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase">Part</label>
                            <select v-model="partsForm.part_id" required class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                                <option value="">Select Part</option>
                                <option v-for="p in parts" :key="p.id" :value="p.id">{{ p.name }} ({{ p.sku }})</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase">Quantity</label>
                            <input v-model.number="partsForm.quantity" type="number" step="0.01" min="0.01" required class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                        </div>
                        <button type="submit" class="w-full py-3 bg-amber-600 text-white rounded-xl font-black text-xs uppercase">Consume Parts</button>
                    </form>
                </div>
            </div>
        </template>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useAuthStore } from '../../../store/authStore';
import { repairRequestsApi } from '../../../api/workshop/repair-requests';
import { warehousesApi } from '../../../api/workshop/warehouses';
import { partsApi } from '../../../api/workshop/parts';
import { ArrowLeft, X } from 'lucide-vue-next';

const route = useRoute();
const authStore = useAuthStore();
const loading = ref(true);
const rr = ref(null);
const mechanics = ref([]);
const warehouses = ref([]);
const parts = ref([]);
const selectedMechanic = ref('');
const reassignMechanicId = ref('');
const approveComment = ref('');
const rejectComment = ref('');
const showUseParts = ref(false);
const partsForm = ref({ warehouse_id: '', part_id: '', quantity: 0 });
const releaseForm = ref({ odometer_at_release: null, unresolved_issues: '', checklist_completed: false });

const userRoles = computed(() => authStore.user?.roles_list || []);
const hasParts = computed(() => rr.value?.items?.some(i => i.part_id) ?? false);

const isAdmin = computed(() => ['super_admin', 'Admin'].some(r => userRoles.value.includes(r)));
const isLogisticsManager = computed(() => isAdmin.value || userRoles.value.some(r => r.toLowerCase().includes('logistics')));
const isOpsManager = computed(() => isAdmin.value || userRoles.value.some(r => r.toLowerCase().includes('operations')));

const approvalHint = computed(() => {
    if (rr.value?.status === 'pending_approval') return 'You are approving as Logistics Manager (Stage 1)';
    if (rr.value?.status === 'pending_ops_approval') return 'You are approving as Operations Manager (Stage 2)';
    return '';
});

async function load() {
    loading.value = true;
    try {
        const { data } = await repairRequestsApi.show(route.params.id);
        rr.value = data;
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
}

async function loadMechanics() {
    try {
        const { data } = await repairRequestsApi.mechanics();
        mechanics.value = data;
    } catch (e) { console.error(e); }
}

async function loadWarehouses() {
    try {
        const { data } = await warehousesApi.list();
        warehouses.value = data;
    } catch (e) { console.error(e); }
}

async function loadParts() {
    try {
        const { data } = await partsApi.index({ per_page: 200 });
        parts.value = data.parts?.data || [];
    } catch (e) { console.error(e); }
}

function displayStatus(s) {
    const map = {
        draft: 'Draft',
        pending_approval: 'Pending (Logistics)',
        pending_ops_approval: 'Pending (Operations)',
        approved: 'Approved',
        in_progress: 'In Progress',
        completed: 'Completed',
        released: 'Released',
        cancelled: 'Cancelled',
    };
    return map[s] || s;
}

function can(action) {
    if (!rr.value) return false;
    const s = rr.value.status;
    const hasMechanic = rr.value.assignments?.length > 0;
    switch (action) {
        case 'submit': return s === 'draft';
        case 'request_approval': return s === 'pending_approval' && hasMechanic && !rr.value.approval_requested_at;
        case 'approve':
            if (s === 'pending_approval' && rr.value.approval_requested_at) return isLogisticsManager.value;
            if (s === 'pending_ops_approval') return isOpsManager.value;
            return false;
        case 'reject':
            if (s === 'pending_approval' && rr.value.approval_requested_at) return isLogisticsManager.value;
            if (s === 'pending_ops_approval') return isOpsManager.value;
            return false;
        case 'assign': return (s === 'approved' || (s === 'pending_approval' && !hasMechanic)) && !isAdmin.value;
        case 'reassign': return s === 'completed';
        case 'start_work': return s === 'in_progress' && rr.value.assignments?.some(a => a.status === 'assigned');
        case 'complete_work': return s === 'in_progress' && rr.value.assignments?.some(a => a.status === 'in_progress');
        case 'use_parts': return s === 'in_progress';
        case 'release': return s === 'completed';
        case 'cancel': return ['draft', 'pending_approval', 'pending_ops_approval', 'approved', 'in_progress'].includes(s);
        default: return false;
    }
}

async function submitRequest() {
    try {
        await repairRequestsApi.submit(rr.value.id);
        await load();
    } catch (e) { console.error(e); }
}

async function requestApproval() {
    try {
        await repairRequestsApi.requestApproval(rr.value.id);
        await load();
    } catch (e) { console.error(e); }
}

async function approveRequest() {
    try {
        await repairRequestsApi.approve(rr.value.id, { comment: approveComment.value });
        approveComment.value = '';
        await load();
    } catch (e) { console.error(e); }
}

async function rejectRequest() {
    try {
        await repairRequestsApi.reject(rr.value.id, { comment: rejectComment.value });
        rejectComment.value = '';
        await load();
    } catch (e) { console.error(e); }
}

async function assignMechanic() {
    if (!selectedMechanic.value) return;
    try {
        await repairRequestsApi.assignMechanic(rr.value.id, { mechanic_id: selectedMechanic.value });
        selectedMechanic.value = '';
        await load();
    } catch (e) { console.error(e); }
}

async function reassignMechanicAction() {
    if (!reassignMechanicId.value) return;
    try {
        await repairRequestsApi.reassignMechanic(rr.value.id, { mechanic_id: reassignMechanicId.value });
        reassignMechanicId.value = '';
        await load();
    } catch (e) { console.error(e); }
}

async function startWork() {
    const assignment = rr.value.assignments?.find(a => a.status === 'assigned');
    if (!assignment) return;
    try {
        await repairRequestsApi.startWork(assignment.id);
        await load();
    } catch (e) { console.error(e); }
}

async function completeWork() {
    const assignment = rr.value.assignments?.find(a => a.status === 'in_progress');
    if (!assignment) return;
    try {
        await repairRequestsApi.completeWork(assignment.id);
        await load();
    } catch (e) { console.error(e); }
}

async function useParts() {
    try {
        await repairRequestsApi.useParts(rr.value.id, partsForm.value);
        showUseParts.value = false;
        partsForm.value = { warehouse_id: '', part_id: '', quantity: 0 };
    } catch (e) { console.error(e); }
}

async function releaseVehicle() {
    try {
        await repairRequestsApi.release(rr.value.id, releaseForm.value);
        await load();
    } catch (e) { console.error(e); }
}

async function cancelRequest() {
    if (!confirm('Cancel this repair request?')) return;
    try {
        await repairRequestsApi.cancel(rr.value.id);
        await load();
    } catch (e) { console.error(e); }
}

function statusBadge(s) {
    const map = {
        draft: 'bg-slate-100 text-slate-600',
        pending_approval: 'bg-amber-100 text-amber-700',
        pending_ops_approval: 'bg-orange-100 text-orange-700',
        approved: 'bg-blue-100 text-blue-700',
        in_progress: 'bg-indigo-100 text-indigo-700',
        completed: 'bg-emerald-100 text-emerald-700',
        released: 'bg-green-100 text-green-700',
        cancelled: 'bg-rose-100 text-rose-700',
    };
    return map[s] || 'bg-slate-100 text-slate-600';
}

function assignmentStatusBadge(s) {
    const map = { assigned: 'bg-slate-100 text-slate-600', in_progress: 'bg-blue-100 text-blue-700', completed: 'bg-emerald-100 text-emerald-700' };
    return map[s] || 'bg-slate-100 text-slate-600';
}

function approvalBorder(ap) {
    if (ap.status === 'approved') return 'border-emerald-200 bg-emerald-50/30';
    return 'border-rose-200 bg-rose-50/30';
}

function formatAmount(v) { return (v || v === 0) ? v.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '-'; }
function formatDate(d) { return d ? new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }) : ''; }

onMounted(async () => {
    await Promise.all([loadMechanics(), loadWarehouses(), loadParts()]);
    await load();
});
</script>
