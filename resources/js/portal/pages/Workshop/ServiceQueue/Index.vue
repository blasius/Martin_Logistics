<template>
    <div class="p-6 bg-slate-50 min-h-screen space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-black text-slate-800 tracking-tight uppercase">Yard Service Queue</h1>
                <p class="text-xs font-bold text-slate-400">FIFO queue for offload, wash, workshop &amp; fuel services</p>
            </div>
            <div class="flex gap-2">
                <button @click="showCreateModal = true" class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl shadow-lg font-black text-xs flex items-center gap-2 uppercase">
                    <Plus class="w-4 h-4" /> Enqueue
                </button>
                <button @click="load" class="p-2.5 bg-white rounded-xl border border-slate-200 hover:bg-slate-50">
                    <RefreshCw class="w-4 h-4 text-slate-500" />
                </button>
            </div>
        </div>

        <div v-if="loading" class="grid grid-cols-4 gap-4">
            <div v-for="i in 4" class="h-20 bg-white rounded-2xl animate-pulse border border-slate-100"></div>
        </div>

        <div v-else class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <p class="text-[10px] font-black text-slate-400 uppercase">Total Queued</p>
                <p class="text-3xl font-black text-slate-800 mt-1">{{ stats.total_queued }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <p class="text-[10px] font-black text-slate-400 uppercase">In Progress</p>
                <p class="text-3xl font-black text-blue-600 mt-1">{{ stats.in_progress }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <p class="text-[10px] font-black text-slate-400 uppercase">Completed Today</p>
                <p class="text-3xl font-black text-emerald-600 mt-1">{{ stats.completed_today }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <p class="text-[10px] font-black text-slate-400 uppercase">By Type</p>
                <p class="text-xs font-bold text-slate-600 mt-1">
                    <span v-for="(count, type) in stats.by_type" :key="type" class="mr-2">{{ type }}: {{ count }}</span>
                    <span v-if="!Object.keys(stats.by_type).length" class="text-slate-400">—</span>
                </p>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex flex-wrap gap-4">
            <select v-model="filters.service_type" @change="load" class="bg-slate-50 border-none rounded-xl text-xs font-bold px-3 outline-none">
                <option value="">All Types</option>
                <option value="offload">Offload</option>
                <option value="wash">Wash</option>
                <option value="workshop">Workshop</option>
                <option value="fuel">Fuel</option>
            </select>
            <select v-model="filters.status" @change="load" class="bg-slate-50 border-none rounded-xl text-xs font-bold px-3 outline-none">
                <option value="">All Statuses</option>
                <option value="queued">Queued</option>
                <option value="in_progress">In Progress</option>
                <option value="completed">Completed</option>
                <option value="skipped">Skipped</option>
            </select>
        </div>

        <div v-if="entries.length" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-slate-50/50 text-[10px] font-black text-slate-400 uppercase border-b">
                    <tr>
                        <th class="p-4">#</th>
                        <th class="p-4">Vehicle</th>
                        <th class="p-4">Service</th>
                        <th class="p-4">Priority</th>
                        <th class="p-4">Status</th>
                        <th class="p-4">Entered At</th>
                        <th class="p-4">Geofence</th>
                        <th class="p-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="entry in entries" :key="entry.id" class="border-b border-slate-50 hover:bg-slate-50/50">
                        <td class="p-4 font-black text-xs text-slate-400">{{ entry.position }}</td>
                        <td class="p-4 font-bold text-xs text-indigo-600 font-mono">{{ entry.vehicle?.plate_number }}</td>
                        <td class="p-4">
                            <span class="text-[10px] font-black px-2 py-1 rounded-full" :class="typeBadge(entry.service_type)">{{ entry.service_type }}</span>
                        </td>
                        <td class="p-4">
                            <span class="text-[10px] font-black px-2 py-1 rounded-full" :class="priorityBadge(entry.priority)">{{ entry.priority }}</span>
                        </td>
                        <td class="p-4">
                            <span class="text-[10px] font-black px-2 py-1 rounded-full" :class="statusBadge(entry.status)">{{ entry.status }}</span>
                        </td>
                        <td class="p-4 text-xs text-slate-500">{{ formatDate(entry.entered_at) }}</td>
                        <td class="p-4">
                            <span v-if="entry.geofence_verified" class="text-emerald-600 text-xs font-bold">Verified</span>
                            <span v-else class="text-amber-600 text-xs font-bold">Unverified</span>
                        </td>
                        <td class="p-4 text-right whitespace-nowrap">
                            <button v-if="entry.status === 'queued'" @click="startService(entry.id)" class="text-indigo-600 hover:text-indigo-800 mr-2" title="Start"><Play class="w-4 h-4 inline" /></button>
                            <button v-if="entry.status === 'in_progress'" @click="completeService(entry.id)" class="text-emerald-600 hover:text-emerald-800 mr-2" title="Complete"><Check class="w-4 h-4 inline" /></button>
                            <button v-if="entry.status === 'queued'" @click="skipEntry(entry.id)" class="text-rose-500 hover:text-rose-700 mr-2" title="Skip"><SkipForward class="w-4 h-4 inline" /></button>
                            <button v-if="entry.status === 'queued'" @click="promptReorder(entry)" class="text-slate-500 hover:text-slate-700" title="Reorder"><ArrowUpDown class="w-4 h-4 inline" /></button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-else-if="!loading" class="text-center py-20">
            <p class="text-slate-400 font-bold text-xs uppercase">No queue entries</p>
        </div>

        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" @click.self="showCreateModal = false">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full border border-slate-200">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center">
                    <h3 class="font-black text-slate-800 uppercase text-sm">Enqueue Vehicle</h3>
                    <button @click="showCreateModal = false" class="text-slate-400 hover:text-slate-600"><X class="w-5 h-5" /></button>
                </div>
                <form @submit.prevent="enqueueVehicle" class="p-6 space-y-4">
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase">Vehicle</label>
                        <select v-model="createForm.vehicle_id" required class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                            <option value="">Select Vehicle</option>
                            <option v-for="v in vehicles" :key="v.id" :value="v.id">{{ v.plate_number }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase">Service Type</label>
                        <select v-model="createForm.service_type" required class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                            <option value="">Select Type</option>
                            <option value="offload">Offload</option>
                            <option value="wash">Wash</option>
                            <option value="workshop">Workshop</option>
                            <option value="fuel">Fuel</option>
                        </select>
                    </div>
                    <button type="submit" class="w-full py-3 bg-indigo-600 text-white rounded-xl font-black text-xs uppercase">Add to Queue</button>
                </form>
            </div>
        </div>

        <div v-if="showReorderModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" @click.self="showReorderModal = false">
            <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full border border-slate-200">
                <div class="p-6 border-b border-slate-100">
                    <h3 class="font-black text-slate-800 uppercase text-sm">Reorder Queue</h3>
                </div>
                <form @submit.prevent="doReorder" class="p-6 space-y-4">
                    <p class="text-xs text-slate-500">Set new position for <strong>{{ reorderEntry?.vehicle?.plate_number }}</strong> (current: #{{ reorderEntry?.position }})</p>
                    <input v-model.number="reorderPosition" type="number" min="1" class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                    <div class="flex gap-3">
                        <button type="button" @click="showReorderModal = false" class="flex-1 px-4 py-3 bg-slate-100 text-slate-600 text-xs font-black rounded-xl uppercase">Cancel</button>
                        <button type="submit" class="flex-1 px-4 py-3 bg-indigo-600 text-white text-xs font-black rounded-xl uppercase">Move</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { serviceQueueApi } from '../../../api/workshop/service-queue';
import { Plus, RefreshCw, X, Play, Check, SkipForward, ArrowUpDown } from 'lucide-vue-next';

const loading = ref(true);
const entries = ref([]);
const stats = ref({ total_queued: 0, in_progress: 0, completed_today: 0, by_type: {} });
const filters = ref({ service_type: '', status: '' });
const vehicles = ref([]);
const showCreateModal = ref(false);
const createForm = ref({ vehicle_id: '', service_type: '' });
const showReorderModal = ref(false);
const reorderEntry = ref(null);
const reorderPosition = ref(1);

async function load() {
    loading.value = true;
    try {
        const params = { ...filters.value };
        Object.keys(params).forEach(k => { if (!params[k]) delete params[k]; });
        const [queueRes, statsRes, vehiclesRes] = await Promise.all([
            serviceQueueApi.index(params),
            serviceQueueApi.stats(),
            import('../../../api/workshop/repair-requests').then(m => m.repairRequestsApi.vehicles()),
        ]);
        entries.value = queueRes.data.data || [];
        stats.value = statsRes.data;
        vehicles.value = vehiclesRes.data;
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
}

async function enqueueVehicle() {
    try {
        await serviceQueueApi.store(createForm.value);
        showCreateModal.value = false;
        createForm.value = { vehicle_id: '', service_type: '' };
        await load();
    } catch (e) { console.error(e); }
}

async function startService(id) {
    try { await serviceQueueApi.start(id); await load(); } catch (e) { console.error(e); }
}

async function completeService(id) {
    try { await serviceQueueApi.complete(id); await load(); } catch (e) { console.error(e); }
}

async function skipEntry(id) {
    if (!confirm('Skip this entry?')) return;
    try { await serviceQueueApi.skip(id); await load(); } catch (e) { console.error(e); }
}

function promptReorder(entry) {
    reorderEntry.value = entry;
    reorderPosition.value = entry.position;
    showReorderModal.value = true;
}

async function doReorder() {
    try {
        await serviceQueueApi.reorder(reorderEntry.value.id, { position: reorderPosition.value });
        showReorderModal.value = false;
        await load();
    } catch (e) { console.error(e); }
}

function formatDate(d) {
    if (!d) return '';
    return new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
}

function typeBadge(t) {
    const map = { offload: 'bg-slate-100 text-slate-600', wash: 'bg-cyan-100 text-cyan-700', workshop: 'bg-amber-100 text-amber-700', fuel: 'bg-green-100 text-green-700' };
    return map[t] || 'bg-slate-100 text-slate-600';
}

function priorityBadge(p) {
    const map = { normal: 'bg-slate-100 text-slate-600', high: 'bg-orange-100 text-orange-700', critical: 'bg-rose-100 text-rose-700' };
    return map[p] || 'bg-slate-100 text-slate-600';
}

function statusBadge(s) {
    const map = { queued: 'bg-slate-100 text-slate-600', in_progress: 'bg-blue-100 text-blue-700', completed: 'bg-emerald-100 text-emerald-700', skipped: 'bg-rose-100 text-rose-700' };
    return map[s] || 'bg-slate-100 text-slate-600';
}

onMounted(load);
</script>
