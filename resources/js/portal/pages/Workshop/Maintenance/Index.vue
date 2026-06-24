<template>
    <div class="p-6 bg-slate-50 min-h-screen space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-black text-slate-800 tracking-tight uppercase">Preventive Maintenance</h1>
                <p class="text-xs font-bold text-slate-400">Schedule-based maintenance by km or time intervals</p>
            </div>
            <div class="flex gap-2">
                <button @click="showCreateModal = true" class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl shadow-lg font-black text-xs flex items-center gap-2 uppercase">
                    <Plus class="w-4 h-4" /> New Schedule
                </button>
                <button @click="load" class="p-2.5 bg-white rounded-xl border border-slate-200 hover:bg-slate-50">
                    <RefreshCw class="w-4 h-4 text-slate-500" />
                </button>
            </div>
        </div>

        <div v-if="loading" class="text-center py-20">
            <p class="text-slate-400 font-bold text-xs uppercase">Loading...</p>
        </div>

        <template v-else>
            <div class="grid grid-cols-3 gap-4">
                <div @click="showDue = true" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 hover:shadow-md cursor-pointer transition-shadow">
                    <p class="text-[10px] font-black text-slate-400 uppercase">Due Now</p>
                    <p class="text-3xl font-black text-amber-600 mt-1">{{ dueItems.length }}</p>
                </div>
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                    <p class="text-[10px] font-black text-slate-400 uppercase">Active Schedules</p>
                    <p class="text-3xl font-black text-slate-800 mt-1">{{ schedules.length }}</p>
                </div>
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                    <p class="text-[10px] font-black text-slate-400 uppercase">Vehicles Covered</p>
                    <p class="text-3xl font-black text-blue-600 mt-1">{{ coveredVehicles }}</p>
                </div>
            </div>

            <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex flex-wrap gap-4">
                <select v-model="filters.vehicle_id" @change="load" class="bg-slate-50 border-none rounded-xl text-xs font-bold px-3 py-2 outline-none">
                    <option value="">All Vehicles</option>
                    <option v-for="v in vehicles" :key="v.id" :value="v.id">{{ v.plate_number }}</option>
                </select>
                <select v-model="filters.type" @change="load" class="bg-slate-50 border-none rounded-xl text-xs font-bold px-3 py-2 outline-none">
                    <option value="">All Types</option>
                    <option v-for="t in types" :key="t" :value="t">{{ formatType(t) }}</option>
                </select>
            </div>

            <div v-if="!schedules.length" class="text-center py-20">
                <p class="text-slate-400 font-bold text-xs uppercase">No schedules configured</p>
            </div>

            <div v-else class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-slate-50/50 text-[10px] font-black text-slate-400 uppercase border-b">
                        <tr>
                            <th class="p-4">Vehicle</th>
                            <th class="p-4">Type</th>
                            <th class="p-4">Interval</th>
                            <th class="p-4">Last Done</th>
                            <th class="p-4">Status</th>
                            <th class="p-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="s in schedules" :key="s.id" class="border-b border-slate-50 hover:bg-slate-50/50 text-xs">
                            <td class="p-4 font-bold text-indigo-600 font-mono">{{ s.vehicle?.plate_number }}</td>
                            <td class="p-4">
                                <span class="text-[10px] font-black px-2 py-1 rounded-full" :class="typeBadge(s.type)">{{ formatType(s.type) }}</span>
                            </td>
                            <td class="p-4 text-slate-600">
                                {{ s.interval_km ? s.interval_km + ' km' : '' }}
                                {{ s.interval_km && s.interval_days ? ' / ' : '' }}
                                {{ s.interval_days ? s.interval_days + ' days' : '' }}
                                <span v-if="!s.interval_km && !s.interval_days" class="text-slate-300">—</span>
                            </td>
                            <td class="p-4">
                                <span v-if="s.last_done_at" class="text-slate-600">{{ formatDate(s.last_done_at) }}</span>
                                <span v-else class="text-amber-600 font-bold">Never</span>
                            </td>
                            <td class="p-4">
                                <span v-if="s.is_active" class="text-[10px] font-black px-2 py-1 bg-emerald-100 text-emerald-700 rounded-full">Active</span>
                                <span v-else class="text-[10px] font-black px-2 py-1 bg-slate-100 text-slate-400 rounded-full">Inactive</span>
                            </td>
                            <td class="p-4 text-right whitespace-nowrap">
                                <button v-if="s.is_active" @click="completeSchedule(s)" class="text-emerald-600 hover:text-emerald-800 mr-2" title="Mark Done"><Check class="w-4 h-4 inline" /></button>
                                <button v-if="s.is_active" @click="generateRequest(s)" class="text-indigo-600 hover:text-indigo-800 mr-2" title="Generate Repair Request"><Wrench class="w-4 h-4 inline" /></button>
                                <button @click="deleteSchedule(s.id)" class="text-rose-500 hover:text-rose-700" title="Delete"><Trash2 class="w-4 h-4 inline" /></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </template>

        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" @click.self="showCreateModal = false">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full border border-slate-200">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center">
                    <h3 class="font-black text-slate-800 uppercase text-sm">New Maintenance Schedule</h3>
                    <button @click="showCreateModal = false" class="text-slate-400 hover:text-slate-600"><X class="w-5 h-5" /></button>
                </div>
                <form @submit.prevent="createSchedule" class="p-6 space-y-4">
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase">Vehicle</label>
                        <select v-model="form.vehicle_id" required class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                            <option value="">Select Vehicle</option>
                            <option v-for="v in vehicles" :key="v.id" :value="v.id">{{ v.plate_number }} — {{ v.make }} {{ v.model }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase">Type</label>
                        <select v-model="form.type" required class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                            <option value="">Select</option>
                            <option v-for="t in types" :key="t" :value="t">{{ formatType(t) }}</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase">Interval (km)</label>
                            <input v-model.number="form.interval_km" type="number" min="0" step="0.1" class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase">Interval (days)</label>
                            <input v-model.number="form.interval_days" type="number" min="0" class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                        </div>
                    </div>
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase">Notes</label>
                        <textarea v-model="form.notes" class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold"></textarea>
                    </div>
                    <button type="submit" class="w-full py-3 bg-indigo-600 text-white rounded-xl font-black text-xs uppercase">Create Schedule</button>
                </form>
            </div>
        </div>

        <div v-if="showDue" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" @click.self="showDue = false">
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full border border-slate-200">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center">
                    <h3 class="font-black text-slate-800 uppercase text-sm">Due Schedules</h3>
                    <button @click="showDue = false" class="text-slate-400 hover:text-slate-600"><X class="w-5 h-5" /></button>
                </div>
                <div class="p-6 space-y-3 max-h-96 overflow-y-auto">
                    <div v-if="!dueItems.length" class="text-center py-6">
                        <p class="text-slate-400 font-bold text-xs uppercase">All up to date</p>
                    </div>
                    <div v-for="item in dueItems" :key="item.schedule.id" class="border border-amber-200 rounded-xl p-4 bg-amber-50">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-bold text-sm text-slate-800">{{ item.vehicle?.plate_number }}</p>
                                <p class="text-[10px] font-black text-amber-700 uppercase">{{ formatType(item.schedule.type) }}</p>
                                <p class="text-xs text-slate-600 mt-1">{{ item.reason }}</p>
                            </div>
                            <button @click="generateFromDue(item.schedule)" class="px-3 py-1.5 bg-indigo-600 text-white rounded-lg font-black text-[10px] uppercase">Generate</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Plus, RefreshCw, X, Check, Trash2, Wrench } from 'lucide-vue-next';
import { api } from '../../../../plugins/axios';

const loading = ref(true);
const schedules = ref([]);
const dueItems = ref([]);
const vehicles = ref([]);
const filters = ref({ vehicle_id: '', type: '' });
const showCreateModal = ref(false);
const showDue = ref(false);
const types = ['oil_change', 'tire_rotation', 'brake_check', 'inspection', 'general'];
const form = ref({ vehicle_id: '', type: '', interval_km: null, interval_days: null, notes: '' });

const coveredVehicles = computed(() => {
    const set = new Set(schedules.value.map(s => s.vehicle_id));
    return set.size;
});

function formatType(t) {
    return t.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
}

function typeBadge(t) {
    const map = {
        oil_change: 'bg-yellow-100 text-yellow-700',
        tire_rotation: 'bg-blue-100 text-blue-700',
        brake_check: 'bg-rose-100 text-rose-700',
        inspection: 'bg-purple-100 text-purple-700',
        general: 'bg-slate-100 text-slate-600',
    };
    return map[t] || 'bg-slate-100 text-slate-600';
}

async function load() {
    loading.value = true;
    try {
        const params = {};
        Object.entries(filters.value).forEach(([k, v]) => { if (v) params[k] = v; });
        const [schedRes, dueRes, vehRes] = await Promise.all([
            api.get('/portal/workshop/maintenance-schedules', { params }),
            api.get('/portal/workshop/maintenance-schedules/due'),
            api.get('/portal/workshop/repair-requests/vehicles'),
        ]);
        schedules.value = schedRes.data.data || [];
        dueItems.value = dueRes.data || [];
        vehicles.value = vehRes.data || [];
    } catch (e) { console.error(e); }
    finally { loading.value = false; }
}

async function createSchedule() {
    try {
        await api.post('/portal/workshop/maintenance-schedules', form.value);
        showCreateModal.value = false;
        form.value = { vehicle_id: '', type: '', interval_km: null, interval_days: null, notes: '' };
        await load();
    } catch (e) { console.error(e); }
}

async function completeSchedule(s) {
    const odometer = prompt(`Enter current odometer reading for ${s.vehicle?.plate_number}:`);
    if (!odometer) return;
    try {
        await api.post(`/portal/workshop/maintenance-schedules/${s.id}/complete`, { odometer });
        await load();
    } catch (e) { console.error(e); }
}

async function generateRequest(s) {
    if (!confirm(`Generate repair request for ${formatType(s.type)} on ${s.vehicle?.plate_number}?`)) return;
    try {
        await api.post(`/portal/workshop/maintenance-schedules/${s.id}/generate`);
        await load();
    } catch (e) { console.error(e); }
}

async function generateFromDue(schedule) {
    try {
        await api.post(`/portal/workshop/maintenance-schedules/${schedule.id}/generate`);
        await load();
    } catch (e) { console.error(e); }
}

async function deleteSchedule(id) {
    if (!confirm('Delete this schedule?')) return;
    try {
        await api.delete(`/portal/workshop/maintenance-schedules/${id}`);
        await load();
    } catch (e) { console.error(e); }
}

function formatDate(d) {
    if (!d) return '';
    return new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
}

onMounted(load);
</script>
