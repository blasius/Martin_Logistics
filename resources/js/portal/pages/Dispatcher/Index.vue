<template>
    <div class="h-screen flex flex-col bg-slate-50 overflow-hidden relative">

        <!-- NOTIFICATION TOAST -->
        <Transition name="slide-fade">
            <div v-if="notification.show" class="fixed bottom-8 right-8 z-[130] flex items-center gap-3 px-6 py-3 bg-slate-900 text-white rounded-xl shadow-2xl border border-slate-700">
                <Check class="w-4 h-4 text-emerald-400" />
                <span class="text-sm font-bold">{{ notification.message }}</span>
            </div>
        </Transition>

        <!-- CHECKLIST MODAL -->
        <Transition name="fade">
            <div v-if="selectedTrip" class="fixed inset-0 z-[160] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" @click.self="selectedTrip = null">
                <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full border border-slate-200 overflow-hidden max-h-[85vh] flex flex-col">
                    <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                        <div>
                            <h3 class="font-black text-slate-800 uppercase text-sm tracking-widest">Pre-Departure Checklist</h3>
                            <p class="text-xs font-bold text-indigo-600 mt-1">{{ selectedTrip.reference }} &middot; {{ selectedTrip.vehicle?.plate_number }}</p>
                        </div>
                        <button @click="selectedTrip = null" class="text-slate-400 hover:text-slate-600 transition-colors"><X class="w-5 h-5" /></button>
                    </div>
                    <div class="flex-1 overflow-y-auto p-6 space-y-3 custom-scrollbar">
                        <label class="flex items-center gap-3 p-3 rounded-xl border transition-all cursor-pointer"
                            :class="prepForm.fuel_confirmed ? 'border-emerald-200 bg-emerald-50' : 'border-slate-200 hover:border-slate-300'">
                            <input type="checkbox" v-model="prepForm.fuel_confirmed" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" />
                            <div class="flex-1">
                                <span class="text-sm font-black text-slate-800 uppercase">Fuel Confirmed</span>
                                <input v-model="prepForm.fuel_liters" type="number" step="0.1" placeholder="Liters"
                                    class="mt-1 w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-1.5 text-xs font-medium text-slate-700 focus:ring-2 focus:ring-emerald-500 outline-none" />
                            </div>
                        </label>

                        <label class="flex items-center gap-3 p-3 rounded-xl border transition-all cursor-pointer"
                            :class="prepForm.odometer_start ? 'border-emerald-200 bg-emerald-50' : 'border-slate-200 hover:border-slate-300'">
                            <input type="checkbox" :checked="!!prepForm.odometer_start" @change="prepForm.odometer_start = $event.target.checked ? 0 : null"
                                class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" />
                            <div class="flex-1">
                                <span class="text-sm font-black text-slate-800 uppercase">Odometer Start</span>
                                <input v-model="prepForm.odometer_start" type="number" step="0.1" placeholder="KM"
                                    class="mt-1 w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-1.5 text-xs font-medium text-slate-700 focus:ring-2 focus:ring-emerald-500 outline-none" />
                            </div>
                        </label>

                        <label class="flex items-center gap-3 p-3 rounded-xl border transition-all cursor-pointer"
                            :class="prepForm.documents_uploaded ? 'border-emerald-200 bg-emerald-50' : 'border-slate-200 hover:border-slate-300'">
                            <input type="checkbox" v-model="prepForm.documents_uploaded" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" />
                            <span class="text-sm font-black text-slate-800 uppercase">Documents Uploaded</span>
                        </label>

                        <label class="flex items-center gap-3 p-3 rounded-xl border transition-all cursor-pointer"
                            :class="prepForm.instructions_provided ? 'border-emerald-200 bg-emerald-50' : 'border-slate-200 hover:border-slate-300'">
                            <input type="checkbox" v-model="prepForm.instructions_provided" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" />
                            <span class="text-sm font-black text-slate-800 uppercase">Driver Instructions Provided</span>
                        </label>

                        <label class="flex items-center gap-3 p-3 rounded-xl border transition-all cursor-pointer"
                            :class="prepForm.inspection_confirmed ? 'border-emerald-200 bg-emerald-50' : 'border-slate-200 hover:border-slate-300'">
                            <input type="checkbox" v-model="prepForm.inspection_confirmed" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" />
                            <span class="text-sm font-black text-slate-800 uppercase">Pre-Trip Inspection Confirmed</span>
                        </label>

                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 block">Notes</label>
                            <textarea v-model="prepForm.notes" rows="2"
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-700 focus:ring-2 focus:ring-emerald-500 outline-none resize-none"></textarea>
                        </div>

                        <div v-if="!checklistComplete" class="text-[10px] font-black text-amber-600 bg-amber-50 border border-amber-100 p-3 rounded-xl uppercase tracking-wider">
                            Complete all checklist items before marking ready.
                        </div>
                    </div>
                    <div class="p-4 bg-slate-50 border-t border-slate-100 flex gap-3">
                        <button @click="saveChecklist" :disabled="saving"
                            class="flex-1 px-4 py-3 bg-slate-100 text-slate-600 text-xs font-black rounded-xl uppercase hover:bg-slate-200 transition-all disabled:opacity-40 flex items-center justify-center gap-2">
                            <span v-if="saving" class="w-3 h-3 border-2 border-slate-300 border-t-slate-600 rounded-full animate-spin"></span>
                            <template v-else><Save class="w-4 h-4" /> Save</template>
                        </button>
                        <button @click="markReady" :disabled="!checklistComplete || saving"
                            class="flex-1 px-4 py-3 bg-emerald-600 text-white text-xs font-black rounded-xl shadow-lg uppercase hover:bg-emerald-700 transition-all disabled:opacity-40 flex items-center justify-center gap-2">
                            <span v-if="saving" class="w-3 h-3 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                            <template v-else><CheckCircle class="w-4 h-4" /> Mark Ready</template>
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- CLEARANCE MODAL -->
        <Transition name="fade">
            <div v-if="showClearance" class="fixed inset-0 z-[160] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" @click.self="showClearance = false">
                <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full border border-slate-200 overflow-hidden max-h-[85vh] flex flex-col">
                    <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                        <div>
                            <h3 class="font-black text-slate-800 uppercase text-sm tracking-widest">Pre-Trip Clearance</h3>
                            <p class="text-xs font-bold text-indigo-600 mt-1">{{ selectedTrip?.reference }} &middot; {{ selectedTrip?.vehicle?.plate_number }}</p>
                        </div>
                        <button @click="showClearance = false" class="text-slate-400 hover:text-slate-600 transition-colors"><X class="w-5 h-5" /></button>
                    </div>
                    <div class="flex-1 overflow-y-auto p-6 space-y-3 custom-scrollbar">
                        <div v-if="clearanceLoading" class="flex flex-col items-center justify-center py-8 text-slate-400">
                            <span class="w-5 h-5 border-2 border-slate-200 border-t-indigo-600 rounded-full animate-spin mb-3"></span>
                            <span class="text-xs font-black uppercase tracking-widest">Running checks...</span>
                        </div>
                        <template v-else>
                            <div v-if="clearanceClear" class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-700 text-sm font-black uppercase tracking-wider flex items-center gap-2">
                                <CheckCircle class="w-5 h-5" /> All checks passed — cleared for dispatch
                            </div>
                            <div v-for="check in clearanceChecks" :key="check.check"
                                class="flex items-start gap-3 p-3 rounded-xl border transition-all"
                                :class="check.passed ? 'border-emerald-200 bg-emerald-50' : check.bypass_pending ? 'border-amber-200 bg-amber-50' : 'border-rose-200 bg-rose-50'">
                                <div class="p-1.5 rounded-full mt-0.5"
                                    :class="check.passed ? 'bg-emerald-100 text-emerald-600' : check.bypass_pending ? 'bg-amber-100 text-amber-600' : 'bg-rose-100 text-rose-600'">
                                    <CheckCircle v-if="check.passed" class="w-3.5 h-3.5" />
                                    <Clock v-else-if="check.bypass_pending" class="w-3.5 h-3.5" />
                                    <XCircle v-else class="w-3.5 h-3.5" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-black text-slate-800 uppercase">{{ check.label }}</span>
                                        <span v-if="check.bypass_pending" class="text-[8px] font-black text-amber-600 bg-amber-100 px-1.5 py-0.5 rounded uppercase">Bypass Pending</span>
                                    </div>
                                    <p class="text-xs font-medium mt-0.5" :class="severityClass(check)">{{ check.message }}</p>
                                    <div v-if="!check.passed && !check.bypass_pending" class="mt-2">
                                        <button @click="openBypassForm(check)"
                                            class="text-[10px] font-black text-amber-600 hover:text-amber-700 uppercase tracking-widest flex items-center gap-1">
                                            <ShieldAlert class="w-3 h-3" /> Request Manager Bypass
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                    <div class="p-4 bg-slate-50 border-t border-slate-100 flex justify-end">
                        <button @click="showClearance = false" class="px-4 py-3 bg-slate-100 text-slate-600 text-xs font-black rounded-xl uppercase hover:bg-slate-200 transition-all">Close</button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- BYPASS REQUEST MODAL -->
        <Transition name="fade">
            <div v-if="showBypassForm" class="fixed inset-0 z-[170] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" @click.self="showBypassForm = false">
                <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full border border-slate-200 overflow-hidden">
                    <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                        <div>
                            <h3 class="font-black text-slate-800 uppercase text-sm tracking-widest">Request Manager Bypass</h3>
                            <p class="text-xs font-bold text-amber-600 mt-1">Bypassing: {{ bypassForm.check_label }}</p>
                        </div>
                        <button @click="showBypassForm = false" class="text-slate-400 hover:text-slate-600 transition-colors"><X class="w-5 h-5" /></button>
                    </div>
                    <form @submit.prevent="submitBypass" class="p-6 space-y-4">
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 block">Reason</label>
                            <textarea v-model="bypassForm.reason" rows="3" required minlength="10"
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-700 focus:ring-2 focus:ring-amber-500 outline-none resize-none"></textarea>
                            <p class="text-[10px] text-slate-400 font-medium mt-1">Minimum 10 characters</p>
                        </div>
                    </form>
                    <div class="p-4 bg-slate-50 border-t border-slate-100 flex gap-3">
                        <button @click="showBypassForm = false" class="flex-1 px-4 py-3 bg-slate-100 text-slate-600 text-xs font-black rounded-xl uppercase hover:bg-slate-200 transition-all">Cancel</button>
                        <button @click="submitBypass" :disabled="savingBypass || bypassForm.reason.trim().length < 10"
                            class="flex-1 px-4 py-3 bg-amber-600 text-white text-xs font-black rounded-xl shadow-lg uppercase hover:bg-amber-700 transition-all disabled:opacity-40 flex items-center justify-center gap-2">
                            <span v-if="savingBypass" class="w-3 h-3 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                            <template v-else><ShieldAlert class="w-4 h-4" /> Submit Request</template>
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- HEADER -->
        <header class="bg-white border-b border-slate-200 px-8 py-4 flex flex-wrap items-center justify-between gap-4 shadow-sm z-10">
            <div class="flex items-center gap-4">
                <div class="p-2 bg-indigo-600 rounded-lg"><ClipboardCheck class="w-6 h-6 text-white" /></div>
                <div>
                    <h1 class="text-xl font-black text-slate-800 uppercase leading-none">Dispatch Preparation</h1>
                    <div class="flex items-center gap-3 mt-1.5">
                        <div class="flex bg-slate-100 p-0.5 rounded-lg">
                            <button v-for="tab in tabs" :key="tab.key" @click="activeTab = tab.key"
                                class="px-3 py-1 text-[9px] font-black uppercase rounded-md transition-all"
                                :class="activeTab === tab.key ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-400 hover:text-slate-600'">
                                {{ tab.label }} ({{ tab.count }})
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <button @click="fetchAll"
                class="flex items-center gap-2 px-4 py-2 bg-slate-700 text-white text-xs font-black rounded-lg hover:bg-slate-800 transition-all uppercase">
                <RefreshCw class="w-4 h-4" /> Refresh
            </button>
        </header>

        <!-- LOADING -->
        <div v-if="loading" class="flex-1 flex flex-col items-center justify-center text-slate-400">
            <span class="w-6 h-6 border-2 border-slate-200 border-t-indigo-600 rounded-full animate-spin mb-3"></span>
            <span class="text-xs font-black uppercase tracking-widest">Loading...</span>
        </div>

        <!-- NEEDS PREP TAB -->
        <template v-else-if="activeTab === 'needs-prep'">
            <div class="px-8 py-3 bg-slate-800 text-white text-[10px] font-black uppercase tracking-widest grid grid-cols-12 gap-4 shadow-lg z-10">
                <div class="col-span-3">Trip Reference</div>
                <div class="col-span-3">Vehicle</div>
                <div class="col-span-3">Destination</div>
                <div class="col-span-3 text-right">Actions</div>
            </div>
            <div class="flex-1 overflow-y-auto px-8 py-4 space-y-2 custom-scrollbar">
                <div v-for="trip in needsPrep" :key="trip.id"
                    class="bg-white border border-slate-200 rounded-lg p-3 grid grid-cols-12 gap-4 items-center transition-all hover:border-indigo-300">
                    <div class="col-span-3">
                        <span class="font-black text-slate-900 uppercase tracking-tighter text-xs">{{ trip.reference }}</span>
                    </div>
                    <div class="col-span-3 text-xs text-slate-600 font-medium">
                        <span class="font-black">{{ trip.vehicle?.plate_number }}</span>
                        <span class="text-slate-400 mx-1">&middot;</span>
                        {{ trip.vehicle?.make }} {{ trip.vehicle?.model }}
                    </div>
                    <div class="col-span-3 text-xs text-slate-500 font-medium">{{ trip.destination || '—' }}</div>
                    <div class="col-span-3 flex justify-end gap-1">
                        <button @click="runClearance(trip)"
                            class="flex items-center gap-1 px-3 py-1.5 bg-slate-100 text-slate-600 text-[10px] font-black rounded-lg hover:bg-slate-200 uppercase transition-all">
                            <ShieldCheck class="w-3.5 h-3.5" /> Clearance
                        </button>
                        <button @click="openChecklist(trip)"
                            class="flex items-center gap-1 px-3 py-1.5 bg-emerald-600 text-white text-[10px] font-black rounded-lg hover:bg-emerald-700 uppercase transition-all shadow-sm">
                            <ClipboardCheck class="w-3.5 h-3.5" /> Prepare
                        </button>
                    </div>
                </div>
                <div v-if="!needsPrep.length" class="flex flex-col items-center justify-center py-20 text-slate-400">
                    <ClipboardCheck class="w-12 h-12 mb-4 text-slate-200" />
                    <p class="text-sm font-black uppercase">All trips prepared</p>
                </div>
            </div>
        </template>

        <!-- READY TO DEPART TAB -->
        <template v-else-if="activeTab === 'ready'">
            <div class="px-8 py-3 bg-slate-800 text-white text-[10px] font-black uppercase tracking-widest grid grid-cols-12 gap-4 shadow-lg z-10">
                <div class="col-span-3">Trip Reference</div>
                <div class="col-span-3">Vehicle</div>
                <div class="col-span-3">Ready At</div>
                <div class="col-span-3 text-right">Status</div>
            </div>
            <div class="flex-1 overflow-y-auto px-8 py-4 space-y-2 custom-scrollbar">
                <div v-for="trip in readyTrips" :key="trip.id"
                    class="bg-white border border-slate-200 rounded-lg p-3 grid grid-cols-12 gap-4 items-center transition-all hover:border-indigo-300">
                    <div class="col-span-3">
                        <span class="font-black text-slate-900 uppercase tracking-tighter text-xs">{{ trip.reference }}</span>
                    </div>
                    <div class="col-span-3 text-xs text-slate-600 font-medium">
                        <span class="font-black">{{ trip.vehicle?.plate_number }}</span>
                        <span class="text-slate-400 mx-1">&middot;</span>
                        {{ trip.vehicle?.make }} {{ trip.vehicle?.model }}
                    </div>
                    <div class="col-span-3 text-[10px] text-slate-400 font-medium">{{ trip.preparation?.ready_at ? formatDate(trip.preparation.ready_at) : '—' }}</div>
                    <div class="col-span-3 flex justify-end">
                        <span class="text-[8px] font-black px-1.5 py-0.5 rounded uppercase" :class="statusBadge(trip.status)">{{ trip.status.replace('_', ' ') }}</span>
                    </div>
                </div>
                <div v-if="!readyTrips.length" class="flex flex-col items-center justify-center py-20 text-slate-400">
                    <CheckCircle class="w-12 h-12 mb-4 text-slate-200" />
                    <p class="text-sm font-black uppercase">No trips ready to depart</p>
                </div>
            </div>
        </template>

        <!-- MY TRIPS TAB -->
        <template v-else-if="activeTab === 'my-trips'">
            <div class="px-8 py-3 bg-slate-800 text-white text-[10px] font-black uppercase tracking-widest grid grid-cols-12 gap-4 shadow-lg z-10">
                <div class="col-span-3">Trip Reference</div>
                <div class="col-span-3">Vehicle</div>
                <div class="col-span-2">Request</div>
                <div class="col-span-2">Status</div>
                <div class="col-span-2 text-right">Actions</div>
            </div>
            <div class="flex-1 overflow-y-auto px-8 py-4 space-y-2 custom-scrollbar">
                <div v-for="trip in myTrips" :key="trip.id"
                    class="bg-white border border-slate-200 rounded-lg p-3 grid grid-cols-12 gap-4 items-center transition-all hover:border-indigo-300">
                    <div class="col-span-3">
                        <span class="font-black text-slate-900 uppercase tracking-tighter text-xs">{{ trip.reference }}</span>
                    </div>
                    <div class="col-span-3 text-xs text-slate-600 font-medium">
                        <span class="font-black">{{ trip.vehicle?.plate_number }}</span>
                        <span class="text-slate-400 mx-1">&middot;</span>
                        {{ trip.vehicle?.make }} {{ trip.vehicle?.model }}
                    </div>
                    <div class="col-span-2 text-[10px] text-slate-400 font-medium">{{ trip.truck_request?.reference ?? '—' }}</div>
                    <div class="col-span-2">
                        <span class="text-[8px] font-black px-1.5 py-0.5 rounded uppercase" :class="statusBadge(trip.status)">{{ trip.status.replace('_', ' ') }}</span>
                    </div>
                    <div class="col-span-2 flex justify-end">
                        <button @click="openChecklist(trip)"
                            class="flex items-center gap-1 px-3 py-1.5 bg-emerald-600 text-white text-[10px] font-black rounded-lg hover:bg-emerald-700 uppercase transition-all shadow-sm">
                            <ClipboardCheck class="w-3.5 h-3.5" /> Checklist
                        </button>
                    </div>
                </div>
                <div v-if="!myTrips.length" class="flex flex-col items-center justify-center py-20 text-slate-400">
                    <Truck class="w-12 h-12 mb-4 text-slate-200" />
                    <p class="text-sm font-black uppercase">No trips assigned to you</p>
                </div>
            </div>
        </template>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, reactive } from 'vue';
import { dispatchPrepApi } from '../../api/truck-requests';
import { clearanceApi } from '../../api/clearance';
import { Check, CheckCircle, XCircle, X, ClipboardCheck, ShieldCheck, ShieldAlert, Clock, RefreshCw, Save, Truck } from 'lucide-vue-next';
import dayjs from 'dayjs';

const activeTab = ref('needs-prep');
const needsPrep = ref([]);
const readyTrips = ref([]);
const myTrips = ref([]);
const loading = ref(true);
const notification = reactive({ show: false, message: '' });

const selectedTrip = ref(null);
const prepForm = ref({});
const saving = ref(false);

const clearanceChecks = ref([]);
const clearanceLoading = ref(false);
const clearanceClear = ref(false);
const showClearance = ref(false);
const bypassForm = ref({ check_name: '', check_label: '', reason: '' });
const showBypassForm = ref(false);
const savingBypass = ref(false);

const tabs = computed(() => [
    { key: 'needs-prep', label: 'Needs Prep', count: needsPrep.value.length },
    { key: 'ready', label: 'Ready', count: readyTrips.value.length },
    { key: 'my-trips', label: 'My Trips', count: myTrips.value.length },
]);

const triggerNotification = (msg) => {
    notification.message = msg;
    notification.show = true;
    setTimeout(() => notification.show = false, 3000);
};

const formatDate = (d) => dayjs(d).format('YYYY-MM-DD HH:mm');

const fetchAll = async () => {
    loading.value = true;
    try {
        const [needs, ready, mine] = await Promise.all([
            dispatchPrepApi.needsPreparation(),
            dispatchPrepApi.readyToDepart(),
            dispatchPrepApi.myTrips(),
        ]);
        needsPrep.value = needs.data;
        readyTrips.value = ready.data;
        myTrips.value = mine.data;
    } catch {} finally { loading.value = false; }
};

const openChecklist = async (trip) => {
    selectedTrip.value = trip;
    try {
        const res = await dispatchPrepApi.show(trip.id);
        prepForm.value = {
            fuel_confirmed: res.data.fuel_confirmed ?? false,
            fuel_liters: res.data.fuel_liters ?? null,
            odometer_start: res.data.odometer_start ?? null,
            documents_uploaded: res.data.documents_uploaded ?? false,
            instructions_provided: res.data.instructions_provided ?? false,
            inspection_confirmed: res.data.inspection_confirmed ?? false,
            notes: res.data.notes ?? '',
        };
    } catch { prepForm.value = { fuel_confirmed: false, fuel_liters: null, odometer_start: null, documents_uploaded: false, instructions_provided: false, inspection_confirmed: false, notes: '' }; }
};

const saveChecklist = async () => {
    if (!selectedTrip.value) return;
    saving.value = true;
    try {
        await dispatchPrepApi.update(selectedTrip.value.id, prepForm.value);
        triggerNotification("Checklist saved");
        await fetchAll();
    } catch { triggerNotification("Failed to save"); } finally { saving.value = false; }
};

const markReady = async () => {
    if (!selectedTrip.value) return;
    saving.value = true;
    try {
        await dispatchPrepApi.markReady(selectedTrip.value.id);
        selectedTrip.value = null;
        triggerNotification("Trip marked ready");
        await fetchAll();
    } catch { triggerNotification("Failed to mark ready"); } finally { saving.value = false; }
};

const runClearance = async (trip) => {
    selectedTrip.value = trip;
    clearanceLoading.value = true;
    showClearance.value = true;
    try {
        const res = await clearanceApi.check({ vehicle_id: trip.vehicle_id, driver_id: trip.driver_id });
        clearanceChecks.value = res.data.checks;
        clearanceClear.value = res.data.is_clear;
    } catch {
        clearanceChecks.value = [];
        clearanceClear.value = false;
    } finally { clearanceLoading.value = false; }
};

const openBypassForm = (check) => {
    bypassForm.value = { check_name: check.check, check_label: check.label, reason: '' };
    showBypassForm.value = true;
};

const submitBypass = async () => {
    savingBypass.value = true;
    try {
        await clearanceApi.requestBypass({
            trip_id: selectedTrip.value?.id,
            check_name: bypassForm.value.check_name,
            check_label: bypassForm.value.check_label,
            reason: bypassForm.value.reason,
        });
        showBypassForm.value = false;
        triggerNotification("Bypass request submitted");
        await runClearance(selectedTrip.value);
    } catch (e) { triggerNotification(e.response?.data?.message || 'Failed to submit bypass request'); } finally { savingBypass.value = false; }
};

const checklistComplete = computed(() => {
    return prepForm.value.fuel_confirmed
        && prepForm.value.documents_uploaded
        && prepForm.value.instructions_provided
        && prepForm.value.inspection_confirmed;
});

const severityClass = (check) => {
    if (check.bypass_pending) return 'text-amber-600';
    return check.passed ? 'text-emerald-600' : 'text-rose-600';
};

const statusBadge = (status) => {
    const map = {
        pre_departure: 'bg-amber-100 text-amber-700',
        assigned: 'bg-blue-100 text-blue-700',
        on_route: 'bg-emerald-100 text-emerald-700',
    };
    return map[status] ?? 'bg-slate-100 text-slate-600';
};

onMounted(fetchAll);
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.slide-fade-enter-active, .slide-fade-leave-active { transition: all 0.3s; }
.slide-fade-enter-from, .slide-fade-leave-to { transform: translateY(20px); opacity: 0; }
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
</style>
