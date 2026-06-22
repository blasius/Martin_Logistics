<template>
    <div class="bg-slate-50 min-h-screen">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-xl font-black text-slate-900 uppercase tracking-widest">Part Requests</h1>
            <button @click="showCreate = true"
                class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-black uppercase tracking-wider rounded-lg hover:bg-indigo-700 shadow-sm">
                <Plus class="w-4 h-4 mr-2" />
                New Request
            </button>
        </div>

        <div class="mb-4 flex gap-2">
            <button v-for="f in filters" :key="f.value" @click="activeFilter = f.value; fetch()"
                class="px-3 py-1.5 text-[10px] font-black uppercase tracking-wider rounded-lg transition-colors"
                :class="activeFilter === f.value ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-100'">
                {{ f.label }}
            </button>
        </div>

        <div v-if="loading" class="text-center py-12 text-slate-400 text-sm font-black uppercase tracking-wider">Loading...</div>

        <div v-else class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
            <table class="min-w-full">
                <thead>
                    <tr class="bg-slate-800">
                        <th class="px-6 py-3 text-left text-[10px] font-black text-white uppercase tracking-widest">Reference</th>
                        <th class="px-6 py-3 text-left text-[10px] font-black text-white uppercase tracking-widest">Part</th>
                        <th class="px-6 py-3 text-left text-[10px] font-black text-white uppercase tracking-widest">Qty</th>
                        <th class="px-6 py-3 text-left text-[10px] font-black text-white uppercase tracking-widest">Urgency</th>
                        <th class="px-6 py-3 text-left text-[10px] font-black text-white uppercase tracking-widest">Status</th>
                        <th class="px-6 py-3 text-left text-[10px] font-black text-white uppercase tracking-widest">Requested By</th>
                        <th class="px-6 py-3 text-left text-[10px] font-black text-white uppercase tracking-widest">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="pr in items" :key="pr.id" class="hover:bg-slate-50 cursor-pointer transition-colors" @click="$router.push(`/workshop/part-requests/${pr.id}`)">
                        <td class="px-6 py-4 text-sm font-bold text-indigo-600">{{ pr.reference }}</td>
                        <td class="px-6 py-4 text-sm text-slate-900 font-medium">{{ pr.part?.name }}</td>
                        <td class="px-6 py-4 text-sm text-slate-700">{{ pr.quantity }}</td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2 py-0.5 text-[10px] font-black uppercase tracking-wider rounded-full"
                                :class="pr.urgency === 'urgent' ? 'bg-rose-100 text-rose-700' : 'bg-blue-100 text-blue-700'">
                                {{ pr.urgency }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2 py-0.5 text-[10px] font-black uppercase tracking-wider rounded-full"
                                :class="statusClass(pr.status)">{{ pr.status.replace(/_/g, ' ') }}</span>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-500">{{ pr.requester?.name }}</td>
                        <td class="px-6 py-4 text-sm text-slate-500">{{ formatDate(pr.created_at) }}</td>
                    </tr>
                    <tr v-if="items.length === 0">
                        <td colspan="7" class="px-6 py-12 text-center text-sm text-slate-400 font-medium">No part requests found.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Transition name="fade">
            <div v-if="showCreate" class="fixed inset-0 z-[160] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" @click.self="showCreate = false">
                <div class="bg-white rounded-xl shadow-2xl p-6 w-full max-w-lg mx-4">
                    <h2 class="text-lg font-black text-slate-900 uppercase tracking-widest mb-4">New Part Request</h2>
                    <form @submit.prevent="save">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-black uppercase tracking-wider text-slate-600 mb-1">Part</label>
                                <select v-model="form.part_id" required
                                    class="w-full rounded-lg border-slate-200 bg-slate-50 text-xs font-black uppercase focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="" disabled class="normal-case font-normal">Select part...</option>
                                    <option v-for="p in parts" :key="p.id" :value="p.id" class="normal-case font-medium">{{ p.name }} ({{ p.sku }})</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-black uppercase tracking-wider text-slate-600 mb-1">Quantity</label>
                                <input v-model.number="form.quantity" type="number" min="1" required
                                    class="w-full rounded-lg border-slate-200 bg-slate-50 text-xs font-black uppercase focus:border-indigo-500 focus:ring-indigo-500" />
                            </div>
                            <div>
                                <label class="block text-xs font-black uppercase tracking-wider text-slate-600 mb-1">Urgency</label>
                                <select v-model="form.urgency"
                                    class="w-full rounded-lg border-slate-200 bg-slate-50 text-xs font-black uppercase focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="normal">Normal</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-black uppercase tracking-wider text-slate-600 mb-1">Notes</label>
                                <textarea v-model="form.notes" rows="3"
                                    class="w-full rounded-lg border-slate-200 bg-slate-50 text-xs font-black uppercase placeholder:font-normal placeholder:normal-case focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                            </div>
                        </div>
                        <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-slate-100">
                            <button type="button" @click="showCreate = false"
                                class="px-4 py-2 text-xs font-black uppercase tracking-wider text-slate-600 bg-slate-100 rounded-lg hover:bg-slate-200 transition-colors">Cancel</button>
                            <button type="submit" :disabled="saving"
                                class="px-4 py-2 text-xs font-black uppercase tracking-wider text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition-colors shadow-sm">
                                {{ saving ? 'Saving...' : 'Submit' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { Plus } from "lucide-vue-next";
import { partRequestsApi } from "../../../api/workshop/part-requests";
import { partsApi } from "../../../api/workshop/parts";
import dayjs from "dayjs";

const items = ref([]);
const parts = ref([]);
const loading = ref(true);
const showCreate = ref(false);
const saving = ref(false);
const activeFilter = ref("");
const form = ref({ part_id: "", quantity: 1, urgency: "normal", notes: "" });

const filters = [
    { label: "All", value: "" },
    { label: "Pending Clerk", value: "pending_clerk" },
    { label: "Pending Wks Mgr", value: "pending_workshop_manager" },
    { label: "Pending Log Mgr", value: "pending_logistics_manager" },
    { label: "Pending Ops Mgr", value: "pending_ops_manager" },
    { label: "Approved", value: "approved" },
    { label: "Rejected", value: "rejected" },
];

onMounted(async () => {
    await fetch();
    const { data } = await partsApi.index({ per_page: 200 });
    parts.value = data.data ?? data;
});

async function fetch() {
    loading.value = true;
    try {
        const params = {};
        if (activeFilter.value) params.status = activeFilter.value;
        const { data } = await partRequestsApi.index(params);
        items.value = data.data ?? data;
    } finally {
        loading.value = false;
    }
}

async function save() {
    saving.value = true;
    try {
        await partRequestsApi.store(form.value);
        showCreate.value = false;
        form.value = { part_id: "", quantity: 1, urgency: "normal", notes: "" };
        await fetch();
    } finally {
        saving.value = false;
    }
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
    return d ? dayjs(d).format("MMM D, YYYY") : "";
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
