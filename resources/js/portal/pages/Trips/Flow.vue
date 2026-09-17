<template>
    <div class="h-screen flex flex-col bg-slate-50 overflow-hidden">
        <!-- Header -->
        <div class="bg-white border-b border-slate-200 px-6 py-4 flex items-center gap-3">
            <span class="p-2 bg-indigo-600 rounded-lg text-white">
                <Workflow class="w-5 h-5" />
            </span>
            <div class="flex-1">
                <h1 class="text-lg font-black text-slate-800 uppercase tracking-tight">Trip Flow</h1>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                    Configurable trip lifecycle state machine
                </p>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto p-6 space-y-6">
            <!-- Operational defaults -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xs font-black text-slate-500 uppercase tracking-wider">Operational Defaults</h3>
                    <button @click="saveDefaults"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-[10px] font-black uppercase tracking-wider shadow-lg transition-colors">
                        Save Defaults
                    </button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div v-for="def in defaultsList" :key="def.key" class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block">{{ def.label }}</label>
                        <select v-model="defaults[def.key]"
                                class="w-full p-3 bg-slate-50 rounded-xl text-xs font-bold border border-slate-100 outline-none">
                            <option v-for="s in states" :key="s.key" :value="s.key">{{ s.label }}</option>
                        </select>
                    </div>
                </div>
                <p class="text-[10px] font-bold text-slate-400 mt-3">
                    These map automation and dispatcher screens to a state from the flow below — reconfigure the flow without touching code.
                </p>
            </div>

            <!-- States + Transitions -->
            <div class="grid grid-cols-1 xl:grid-cols-5 gap-6">
                <!-- States -->
                <div class="xl:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
                    <div class="bg-slate-800 px-4 py-3 flex items-center justify-between">
                        <span class="text-[10px] font-black text-white uppercase tracking-widest">States</span>
                        <button @click="openStateModal()" class="flex items-center gap-1 text-[10px] font-black uppercase tracking-wider px-3 py-1.5 bg-indigo-500 hover:bg-indigo-400 text-white rounded-lg transition-colors">
                            <Plus class="w-3.5 h-3.5" /> New State
                        </button>
                    </div>
                    <div class="flex-1 overflow-y-auto divide-y divide-slate-50">
                        <div v-for="(state, idx) in states" :key="state.id"
                             class="flex items-center gap-3 p-4 hover:bg-indigo-50/30 transition-colors">
                            <div class="flex flex-col gap-1">
                                <button @click="moveState(idx, -1)" :disabled="idx === 0"
                                        class="p-0.5 rounded hover:bg-slate-100 disabled:opacity-20 text-slate-400">
                                    <ChevronUp class="w-3.5 h-3.5" />
                                </button>
                                <button @click="moveState(idx, 1)" :disabled="idx === states.length - 1"
                                        class="p-0.5 rounded hover:bg-slate-100 disabled:opacity-20 text-slate-400">
                                    <ChevronDown class="w-3.5 h-3.5" />
                                </button>
                            </div>
                            <span class="w-3.5 h-3.5 rounded-full shrink-0" :style="{ backgroundColor: state.color || '#cbd5e1' }"></span>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <p class="font-black text-slate-800 text-sm tracking-tight">{{ state.label }}</p>
                                    <span v-if="state.is_initial" class="text-[8px] font-black px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-700 uppercase">Start</span>
                                    <span v-if="state.is_terminal" class="text-[8px] font-black px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-700 uppercase">End</span>
                                    <span v-if="!state.is_active" class="text-[8px] font-black px-1.5 py-0.5 rounded bg-slate-100 text-slate-500 uppercase">Off</span>
                                </div>
                                <code class="text-[10px] font-bold text-slate-400">{{ state.key }}</code>
                            </div>
                            <div class="flex items-center gap-1">
                                <button @click="openStateModal(state)" class="p-2 rounded-lg hover:bg-indigo-100 text-slate-400 hover:text-indigo-600 transition-colors" title="Edit">
                                    <Pencil class="w-4 h-4" />
                                </button>
                                <button @click="confirmRemoveState(state)" class="p-2 rounded-lg hover:bg-rose-100 text-slate-400 hover:text-rose-600 transition-colors" title="Delete">
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Transitions -->
                <div class="xl:col-span-3 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
                    <div class="bg-slate-800 px-4 py-3 flex items-center justify-between">
                        <span class="text-[10px] font-black text-white uppercase tracking-widest">Transitions</span>
                        <button @click="openTransitionModal()" class="flex items-center gap-1 text-[10px] font-black uppercase tracking-wider px-3 py-1.5 bg-indigo-500 hover:bg-indigo-400 text-white rounded-lg transition-colors">
                            <Plus class="w-3.5 h-3.5" /> New Transition
                        </button>
                    </div>
                    <div class="flex-1 overflow-y-auto divide-y divide-slate-50">
                        <div v-for="(t, i) in transitions" :key="t.id"
                             class="flex items-center gap-3 p-4 hover:bg-indigo-50/30 transition-colors flex-wrap">
                            <span class="inline-flex items-center gap-1.5 text-[10px] font-black">
                                <span class="px-2 py-1 rounded-lg text-white uppercase tracking-wide" :style="{ backgroundColor: t.from_state?.color || '#64748b' }">{{ t.from_state?.label }}</span>
                                <ArrowRight class="w-4 h-4 text-slate-300" />
                                <span class="px-2 py-1 rounded-lg text-white uppercase tracking-wide" :style="{ backgroundColor: t.to_state?.color || '#64748b' }">{{ t.to_state?.label }}</span>
                            </span>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-black text-slate-700">{{ t.label }}</p>
                                <p class="text-[10px] font-bold text-slate-400">
                                    <code v-if="t.code" class="text-indigo-500">{{ t.code }}</code>
                                    <span v-if="t.code"> · </span>
                                    <span class="uppercase">{{ t.trigger }}</span>
                                    <span v-if="!t.is_active" class="text-slate-400 ml-1">(off)</span>
                                </p>
                            </div>
                            <div class="flex items-center gap-1">
                                <button @click="openTransitionModal(t)" class="p-2 rounded-lg hover:bg-indigo-100 text-slate-400 hover:text-indigo-600 transition-colors" title="Edit">
                                    <Pencil class="w-4 h-4" />
                                </button>
                                <button @click="confirmRemoveTransition(t)" class="p-2 rounded-lg hover:bg-rose-100 text-slate-400 hover:text-rose-600 transition-colors" title="Delete">
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                        <div v-if="!transitions.length" class="p-10 text-center text-slate-400 text-xs font-bold uppercase tracking-wider">
                            No transitions configured
                        </div>
                    </div>
                    <div class="px-4 py-3 bg-slate-50 border-t border-slate-100 text-[10px] font-bold text-slate-400">
                        New trips enter the "<span class="text-indigo-600 font-black">{{ initialLabel }}</span>" state. Existing trips keep their status; the flow governs which moves are allowed.
                    </div>
                </div>
            </div>
        </div>

        <!-- State modal -->
        <Transition name="fade">
            <div v-if="stateModal.show" class="fixed inset-0 z-[160] flex items-center justify-center">
                <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="stateModal.show = false"></div>
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
                    <div class="bg-slate-50 p-5 border-b border-slate-200 flex items-center justify-between">
                        <h3 class="text-sm font-black text-slate-800 uppercase tracking-wide">{{ stateModal.editId ? 'Edit State' : 'New State' }}</h3>
                        <button @click="stateModal.show = false" class="p-1.5 rounded-lg hover:bg-slate-200 text-slate-400 transition-colors">
                            <X class="w-4 h-4" />
                        </button>
                    </div>
                    <form @submit.prevent="saveState" class="p-5 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block">Key</label>
                                <input v-model="stateForm.key" type="text" placeholder="e.g. on_route" required
                                       class="w-full p-3 bg-slate-50 rounded-xl text-xs font-bold border border-slate-100 outline-none" />
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block">Label</label>
                                <input v-model="stateForm.label" type="text" placeholder="e.g. On Route" required
                                       class="w-full p-3 bg-slate-50 rounded-xl text-xs font-bold border border-slate-100 outline-none" />
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block">Description</label>
                            <textarea v-model="stateForm.description" rows="2" placeholder="What does this state mean?"
                                      class="w-full p-3 bg-slate-50 rounded-xl text-xs font-bold border border-slate-100 outline-none resize-none"></textarea>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block">Color</label>
                            <div class="flex flex-wrap gap-2">
                                <button v-for="c in palette" :key="c" type="button" @click="stateForm.color = c"
                                        class="w-8 h-8 rounded-lg transition-transform"
                                        :class="stateForm.color === c ? 'ring-2 ring-offset-2 ring-slate-400 scale-110' : ''"
                                        :style="{ backgroundColor: c }"></button>
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-3">
                            <label class="flex items-center gap-2 text-[10px] font-black text-slate-500 uppercase tracking-wider p-3 bg-slate-50 rounded-xl cursor-pointer">
                                <input type="checkbox" v-model="stateForm.is_initial" class="accent-indigo-600" /> Initial
                            </label>
                            <label class="flex items-center gap-2 text-[10px] font-black text-slate-500 uppercase tracking-wider p-3 bg-slate-50 rounded-xl cursor-pointer">
                                <input type="checkbox" v-model="stateForm.is_terminal" class="accent-indigo-600" /> Terminal
                            </label>
                            <label class="flex items-center gap-2 text-[10px] font-black text-slate-500 uppercase tracking-wider p-3 bg-slate-50 rounded-xl cursor-pointer">
                                <input type="checkbox" v-model="stateForm.is_active" class="accent-indigo-600" /> Active
                            </label>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block">Sort Order</label>
                            <input v-model.number="stateForm.sort_order" type="number" min="0"
                                   class="w-full p-3 bg-slate-50 rounded-xl text-xs font-bold border border-slate-100 outline-none" />
                        </div>
                        <div class="flex gap-3 pt-2">
                            <button type="button" @click="stateModal.show = false"
                                    class="flex-1 py-3 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-black uppercase tracking-wider transition-colors">
                                Cancel
                            </button>
                            <button type="submit" :disabled="saving"
                                    class="flex-1 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-black uppercase tracking-wider shadow-lg transition-colors disabled:opacity-50">
                                {{ saving ? 'Saving...' : 'Save State' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Transition>

        <!-- Transition modal -->
        <Transition name="fade">
            <div v-if="transitionModal.show" class="fixed inset-0 z-[160] flex items-center justify-center">
                <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="transitionModal.show = false"></div>
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden max-h-[90vh] flex flex-col">
                    <div class="bg-slate-50 p-5 border-b border-slate-200 flex items-center justify-between">
                        <h3 class="text-sm font-black text-slate-800 uppercase tracking-wide">{{ transitionModal.editId ? 'Edit Transition' : 'New Transition' }}</h3>
                        <button @click="transitionModal.show = false" class="p-1.5 rounded-lg hover:bg-slate-200 text-slate-400 transition-colors">
                            <X class="w-4 h-4" />
                        </button>
                    </div>
                    <form @submit.prevent="saveTransition" class="p-5 space-y-4 overflow-y-auto">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block">From State</label>
                                <select v-model="transitionForm.from_state_id" required
                                        class="w-full p-3 bg-slate-50 rounded-xl text-xs font-bold border border-slate-100 outline-none">
                                    <option v-for="s in states" :key="s.id" :value="s.id">{{ s.label }}</option>
                                </select>
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block">To State</label>
                                <select v-model="transitionForm.to_state_id" required
                                        class="w-full p-3 bg-slate-50 rounded-xl text-xs font-bold border border-slate-100 outline-none">
                                    <option v-for="s in states" :key="s.id" :value="s.id">{{ s.label }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block">Label</label>
                                <input v-model="transitionForm.label" type="text" placeholder="e.g. Depart" required
                                       class="w-full p-3 bg-slate-50 rounded-xl text-xs font-bold border border-slate-100 outline-none" />
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block">Code</label>
                                <input v-model="transitionForm.code" type="text" placeholder="e.g. depart (optional)"
                                       class="w-full p-3 bg-slate-50 rounded-xl text-xs font-bold border border-slate-100 outline-none" />
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block">Trigger</label>
                                <select v-model="transitionForm.trigger" required
                                        class="w-full p-3 bg-slate-50 rounded-xl text-xs font-bold border border-slate-100 outline-none">
                                    <option v-for="t in triggers" :key="t" :value="t">{{ t }}</option>
                                </select>
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block">Sort Order</label>
                                <input v-model.number="transitionForm.sort_order" type="number" min="0"
                                       class="w-full p-3 bg-slate-50 rounded-xl text-xs font-bold border border-slate-100 outline-none" />
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block">Allowed Roles <span class="normal-case text-slate-300">(when trigger is manual/dispatcher)</span></label>
                            <div class="flex flex-wrap gap-2 max-h-28 overflow-y-auto p-3 bg-slate-50 rounded-xl">
                                <label v-for="role in allRoles" :key="role" class="flex items-center gap-1.5 text-[10px] font-black text-slate-600 cursor-pointer">
                                    <input type="checkbox" :value="role" v-model="transitionForm.roles" class="accent-indigo-600" />
                                    {{ role }}
                                </label>
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-3">
                            <label class="flex items-center gap-2 text-[10px] font-black text-slate-500 uppercase tracking-wider p-3 bg-slate-50 rounded-xl cursor-pointer">
                                <input type="checkbox" v-model="transitionForm.records_departure_time" class="accent-indigo-600" /> Stamp Departure
                            </label>
                            <label class="flex items-center gap-2 text-[10px] font-black text-slate-500 uppercase tracking-wider p-3 bg-slate-50 rounded-xl cursor-pointer">
                                <input type="checkbox" v-model="transitionForm.records_arrival_time" class="accent-indigo-600" /> Stamp Arrival
                            </label>
                            <label class="flex items-center gap-2 text-[10px] font-black text-slate-500 uppercase tracking-wider p-3 bg-slate-50 rounded-xl cursor-pointer">
                                <input type="checkbox" v-model="transitionForm.is_active" class="accent-indigo-600" /> Active
                            </label>
                        </div>
                        <div class="flex gap-3 pt-2">
                            <button type="button" @click="transitionModal.show = false"
                                    class="flex-1 py-3 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-black uppercase tracking-wider transition-colors">
                                Cancel
                            </button>
                            <button type="submit" :disabled="saving"
                                    class="flex-1 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-black uppercase tracking-wider shadow-lg transition-colors disabled:opacity-50">
                                {{ saving ? 'Saving...' : 'Save Transition' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Transition>

        <!-- Delete confirm -->
        <Transition name="fade">
            <div v-if="deleteModal.show" class="fixed inset-0 z-[170] flex items-center justify-center">
                <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="deleteModal.show = false"></div>
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
                    <div class="p-8 text-center">
                        <span class="inline-flex w-14 h-14 bg-rose-100 text-rose-600 rounded-full items-center justify-center">
                            <Trash2 class="w-7 h-7" />
                        </span>
                        <h3 class="text-lg font-black text-slate-900 mt-4">{{ deleteModal.title }}</h3>
                        <p class="text-xs font-bold text-slate-400 mt-2">{{ deleteModal.message }}</p>
                    </div>
                    <div class="p-4 bg-slate-50 flex gap-3">
                        <button @click="deleteModal.show = false"
                                class="flex-1 py-3 bg-white border border-slate-200 text-slate-600 rounded-xl text-xs font-black uppercase tracking-wider hover:bg-slate-100 transition-colors">
                            Cancel
                        </button>
                        <button @click="confirmDelete" :disabled="deleting"
                                class="flex-1 py-3 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-black uppercase tracking-wider shadow-lg transition-colors disabled:opacity-50">
                            {{ deleting ? 'Removing...' : 'Delete' }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Toast -->
        <Transition name="slide-fade">
            <div v-if="toast.show" class="fixed bottom-8 right-8 z-[130] bg-slate-900 text-white rounded-xl px-5 py-3 shadow-2xl flex items-center gap-2 text-xs font-black uppercase tracking-wider">
                <CheckCircle class="w-4 h-4 text-emerald-400" />
                {{ toast.message }}
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from "vue";
import { api } from "../../../plugins/axios";
import {
    Workflow, Plus, Pencil, Trash2, ChevronUp, ChevronDown,
    X, CheckCircle, ArrowRight,
} from "lucide-vue-next";

const states = ref([]);
const transitions = ref([]);
const defaults = reactive({ needs_preparation_state: '', ready_to_depart_state: '', pod_delivery_state: '' });
const triggers = ref([]);
const allRoles = ref([]);
const saving = ref(false);
const deleting = ref(false);
const toast = reactive({ show: false, message: '' });

const palette = ['#6366f1', '#64748b', '#f59e0b', '#3b82f6', '#d97706', '#10b981', '#ef4444', '#ec4899', '#8b5cf6', '#14b8a6'];

const defaultsList = [
    { key: 'needs_preparation_state', label: 'Needs Preparation State' },
    { key: 'ready_to_depart_state', label: 'Ready To Depart State' },
    { key: 'pod_delivery_state', label: 'POD Delivery State' },
];

const initialLabel = computed(() => states.value.find(s => s.is_initial)?.label || '—');

const stateModal = reactive({ show: false, editId: null });
const stateForm = reactive({ key: '', label: '', description: '', color: palette[0], is_initial: false, is_terminal: false, is_active: true, sort_order: 0 });

const transitionModal = reactive({ show: false, editId: null });
const transitionForm = reactive({
    from_state_id: null, to_state_id: null, code: '', label: '', trigger: 'any',
    roles: [], records_departure_time: false, records_arrival_time: false, is_active: true, sort_order: 0,
});

const deleteModal = reactive({ show: false, kind: '', id: null, title: '', message: '' });

function showToast(message) {
    toast.message = message;
    toast.show = true;
    setTimeout(() => (toast.show = false), 3000);
}

async function fetchFlow() {
    const { data } = await api.get('/portal/trip-flow');
    states.value = data.states;
    transitions.value = data.transitions;
    Object.assign(defaults, data.defaults);
    triggers.value = data.triggers;
    allRoles.value = data.roles;
}

// ---- States ----
function openStateModal(state = null) {
    stateModal.editId = state?.id || null;
    Object.assign(stateForm, {
        key: state?.key ?? '',
        label: state?.label ?? '',
        description: state?.description ?? '',
        color: state?.color ?? palette[0],
        is_initial: state?.is_initial ?? false,
        is_terminal: state?.is_terminal ?? false,
        is_active: state?.is_active ?? true,
        sort_order: state?.sort_order ?? states.value.length,
    });
    stateModal.show = true;
}

async function saveState() {
    saving.value = true;
    try {
        const payload = { ...stateForm };
        if (stateModal.editId) {
            await api.put(`/portal/trip-flow/states/${stateModal.editId}`, payload);
        } else {
            await api.post('/portal/trip-flow/states', payload);
        }
        stateModal.show = false;
        await fetchFlow();
        showToast(stateModal.editId ? 'State updated' : 'State created');
    } catch (e) {
        showToast(e.response?.data?.message || 'Failed to save state');
    } finally {
        saving.value = false;
    }
}

function moveState(idx, dir) {
    const target = idx + dir;
    if (target < 0 || target >= states.value.length) return;
    const arr = [...states.value];
    [arr[idx], arr[target]] = [arr[target], arr[idx]];
    states.value = arr;
    api.post('/portal/trip-flow/states/reorder', { ids: states.value.map(s => s.id) })
        .catch(() => fetchFlow());
}

function confirmRemoveState(state) {
    deleteModal.kind = 'state';
    deleteModal.id = state.id;
    deleteModal.title = `Remove "${state.label}"?`;
    deleteModal.message = 'The state and its transitions will be removed. States still used by trips cannot be deleted.';
    deleteModal.show = true;
}

// ---- Transitions ----
function openTransitionModal(transition = null) {
    transitionModal.editId = transition?.id || null;
    Object.assign(transitionForm, {
        from_state_id: transition?.from_state_id ?? states.value.find(s => s.is_initial)?.id ?? null,
        to_state_id: transition?.to_state_id ?? null,
        code: transition?.code ?? '',
        label: transition?.label ?? '',
        trigger: transition?.trigger ?? 'any',
        roles: transition?.roles ?? [],
        records_departure_time: transition?.records_departure_time ?? false,
        records_arrival_time: transition?.records_arrival_time ?? false,
        is_active: transition?.is_active ?? true,
        sort_order: transition?.sort_order ?? transitions.value.length,
    });
    transitionModal.show = true;
}

async function saveTransition() {
    saving.value = true;
    try {
        const payload = { ...transitionForm, roles: transitionForm.roles };
        if (transitionModal.editId) {
            await api.put(`/portal/trip-flow/transitions/${transitionModal.editId}`, payload);
        } else {
            await api.post('/portal/trip-flow/transitions', payload);
        }
        transitionModal.show = false;
        await fetchFlow();
        showToast(transitionModal.editId ? 'Transition updated' : 'Transition created');
    } catch (e) {
        showToast(e.response?.data?.message || 'Failed to save transition');
    } finally {
        saving.value = false;
    }
}

function confirmRemoveTransition(transition) {
    deleteModal.kind = 'transition';
    deleteModal.id = transition.id;
    deleteModal.title = `Remove "${transition.label}"?`;
    deleteModal.message = 'This transition will no longer be allowed in the flow.';
    deleteModal.show = true;
}

// ---- Delete + Defaults ----
async function confirmDelete() {
    deleting.value = true;
    try {
        if (deleteModal.kind === 'state') {
            await api.delete(`/portal/trip-flow/states/${deleteModal.id}`);
        } else {
            await api.delete(`/portal/trip-flow/transitions/${deleteModal.id}`);
        }
        deleteModal.show = false;
        await fetchFlow();
        showToast('Removed');
    } catch (e) {
        showToast(e.response?.data?.message || 'Failed to remove');
    } finally {
        deleting.value = false;
    }
}

async function saveDefaults() {
    saving.value = true;
    try {
        await api.put('/portal/trip-flow/defaults', { ...defaults });
        showToast('Defaults saved');
    } catch (e) {
        showToast(e.response?.data?.message || 'Failed to save defaults');
    } finally {
        saving.value = false;
    }
}

onMounted(fetchFlow);
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.slide-fade-enter-active { transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
.slide-fade-leave-active { transition: all 0.2s ease; }
.slide-fade-enter-from, .slide-fade-leave-to { opacity: 0; transform: translateY(12px); }
</style>