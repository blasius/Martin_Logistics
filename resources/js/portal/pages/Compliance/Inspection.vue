<template>
    <div class="h-screen flex flex-col bg-slate-50 overflow-hidden relative">

        <Transition name="fade">
            <div v-if="showAddModal" class="fixed inset-0 z-[150] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
                <div class="bg-white rounded-3xl shadow-2xl max-w-xl w-full p-8 border border-slate-200">
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <h3 class="text-xl font-black text-slate-800 uppercase tracking-tight">Record Inspection</h3>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">Technical Safety Compliance</p>
                        </div>
                        <button @click="showAddModal = false" class="text-slate-400 hover:text-slate-600"><X class="w-6 h-6"/></button>
                    </div>

                    <form @submit.prevent="submitInspection" class="space-y-5">
                        <div class="grid grid-cols-2 gap-5">
                            <div class="col-span-2">
                                <label class="text-[10px] font-black text-slate-500 uppercase ml-1">Vehicle</label>
                                <select v-model="form.vehicle_id" required class="w-full mt-1.5 p-3 bg-slate-100 border-none rounded-xl text-sm font-bold outline-none focus:ring-2 focus:ring-violet-500">
                                    <option value="" disabled>Select unit...</option>
                                    <option v-for="v in data.vehicles" :key="v.id" :value="v.id">{{ v.plate_number }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-[10px] font-black text-slate-500 uppercase ml-1">Inspection Date (Issue)</label>
                                <input v-model="form.scheduled_date" type="date" required class="w-full mt-1.5 p-3 bg-slate-100 border-none rounded-xl text-sm font-bold" />
                            </div>
                            <div>
                                <label class="text-[10px] font-black text-slate-500 uppercase ml-1">Expiry Date</label>
                                <input v-model="form.completed_date" type="date" required class="w-full mt-1.5 p-3 bg-slate-100 border-none rounded-xl text-sm font-bold" />
                            </div>
                            <div class="col-span-2">
                                <label class="text-[10px] font-black text-slate-500 uppercase ml-1">Inspector / Station Name</label>
                                <input v-model="form.inspector_name" type="text" placeholder="e.g. Official Safety Center" class="w-full mt-1.5 p-3 bg-slate-100 border-none rounded-xl text-sm font-bold" />
                            </div>
                            <div class="col-span-2">
                                <label class="text-[10px] font-black text-slate-500 uppercase ml-1">Certificate Scan</label>
                                <div class="mt-1.5 relative border-2 border-dashed border-slate-200 rounded-xl p-4 hover:bg-slate-50 transition-colors">
                                    <input type="file" @change="handleFile" required class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" />
                                    <div class="flex items-center gap-3 text-slate-400">
                                        <Upload class="w-5 h-5" />
                                        <span class="text-xs font-bold">{{ fileName || 'Upload Certificate (PDF/Image)' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="submit" :disabled="processing" class="w-full py-4 bg-violet-600 text-white font-black rounded-2xl shadow-xl hover:bg-violet-700 transition-all uppercase tracking-widest text-xs">
                            {{ processing ? 'Processing...' : 'Save Inspection Record' }}
                        </button>
                    </form>
                </div>
            </div>
        </Transition>

        <header class="bg-white border-b border-slate-200 px-8 py-5 flex items-center justify-between shadow-sm z-10">
            <div class="flex items-center gap-4">
                <div class="p-2.5 bg-violet-600 rounded-xl shadow-lg shadow-violet-100">
                    <ClipboardCheck class="w-6 h-6 text-white" />
                </div>
                <div>
                    <h1 class="text-xl font-black text-slate-800 tracking-tight uppercase leading-none">Technical Radar</h1>
                    <p class="text-[10px] font-bold text-slate-400 uppercase mt-1 tracking-widest">Inspection & Safety Audit</p>
                </div>
            </div>
            <button @click="showAddModal = true" class="flex items-center gap-2 px-6 py-3 bg-slate-900 text-white text-xs font-black rounded-xl hover:bg-slate-800 transition-all uppercase tracking-widest">
                <Plus class="w-4 h-4" /> New Inspection
            </button>
        </header>

        <div class="flex-1 overflow-y-auto px-8 py-8 space-y-12 custom-scrollbar">

            <section v-if="data.grounded.length > 0">
                <div class="flex items-center gap-3 mb-4">
                    <div class="h-2 w-2 rounded-full bg-rose-600 animate-pulse"></div>
                    <h2 class="text-xs font-black text-rose-600 uppercase tracking-widest">Grounded: Inspection Expired</h2>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div v-for="insp in data.grounded" :key="insp.id" class="bg-white border-2 border-rose-100 rounded-2xl p-5 shadow-sm group">
                        <div class="flex justify-between items-start">
                            <span class="text-2xl font-black text-slate-900 tracking-tighter">{{ insp.vehicle?.plate_number }}</span>
                            <div class="p-2 bg-rose-50 rounded-lg text-rose-600">
                                <AlertOctagon class="w-5 h-5" />
                            </div>
                        </div>
                        <div class="mt-4 flex items-center justify-between">
                            <span class="text-[10px] font-black px-2 py-1 bg-rose-600 text-white rounded uppercase tracking-tighter">Safety Risk</span>
                            <a :href="'/storage/' + insp.document_path" target="_blank" class="text-slate-400 hover:text-violet-600">
                                <FileText class="w-5 h-5" />
                            </a>
                        </div>
                    </div>
                </div>
            </section>

            <section v-if="data.critical.length > 0">
                <h2 class="text-xs font-black text-amber-500 uppercase tracking-widest mb-4 flex items-center gap-2">
                    <Clock class="w-4 h-4" /> Upcoming Technical Expiry
                </h2>
                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase">Unit</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase">Timeline</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase text-right">Proof</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                        <tr v-for="insp in data.critical" :key="insp.id" class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4 font-black text-slate-800 uppercase">{{ insp.vehicle?.plate_number }}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-32 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                        <div class="h-full bg-amber-500" :style="{ width: (Math.max(0, insp.days_left) / 14 * 100) + '%' }"></div>
                                    </div>
                                    <span class="text-xs font-black text-amber-600 uppercase">{{ insp.days_left }} Days</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a :href="'/storage/' + insp.document_path" target="_blank" class="p-2 inline-block bg-slate-50 rounded-lg text-slate-400 hover:text-violet-600">
                                    <FileText class="w-5 h-5" />
                                </a>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section>
                <h2 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4">Compliant Units</h2>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                    <div v-for="insp in data.upcoming" :key="insp.id" class="bg-white border border-slate-200 rounded-xl p-3 flex items-center justify-between">
                        <div>
                            <p class="text-xs font-black text-slate-800 uppercase leading-none">{{ insp.vehicle?.plate_number }}</p>
                            <p class="text-[9px] font-bold text-emerald-600 uppercase mt-1">{{ insp.days_left }} Days Left</p>
                        </div>
                        <CheckCircle class="w-4 h-4 text-emerald-400" />
                    </div>
                </div>
            </section>

            <section class="mt-20 border-t border-slate-200 pt-10 pb-20">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-lg font-black text-slate-400 uppercase tracking-tight">Inspection Archive</h2>
                        <p class="text-[10px] font-bold text-slate-400 uppercase mt-1 tracking-widest">Historical Safety Records</p>
                    </div>
                </div>
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase">Unit</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase">Dates</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase">Inspector</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase text-right">Scan</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 opacity-60">
                        <tr v-for="insp in data.archive" :key="insp.id" class="hover:opacity-100 transition-opacity">
                            <td class="px-6 py-4 font-black text-slate-700 uppercase">{{ insp.vehicle?.plate_number }}</td>
                            <td class="px-6 py-4">
                                <p class="text-[10px] font-bold text-slate-500">{{ insp.scheduled_date }} to {{ insp.completed_date }}</p>
                            </td>
                            <td class="px-6 py-4 text-xs font-medium text-slate-600">{{ insp.inspector_name || 'N/A' }}</td>
                            <td class="px-6 py-4 text-right">
                                <a :href="'/storage/' + insp.document_path" target="_blank" class="p-2 inline-block bg-slate-50 text-slate-400 hover:text-violet-600">
                                    <FileText class="w-4 h-4" />
                                </a>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue';
import axios from 'axios';
import { ClipboardCheck, Plus, X, Upload, AlertOctagon, FileText, Clock, CheckCircle } from 'lucide-vue-next';

const showAddModal = ref(false);
const processing = ref(false);
const fileName = ref('');
const data = reactive({ grounded: [], critical: [], upcoming: [], archive: [], vehicles: [] });

const form = reactive({
    vehicle_id: '',
    scheduled_date: '',
    completed_date: '',
    inspector_name: '',
    document: null
});

const loadRadar = async () => {
    const res = await axios.get('/api/portal/inspections');
    Object.assign(data, res.data);
};

const handleFile = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.document = file;
        fileName.value = file.name;
    }
};

const submitInspection = async () => {
    processing.value = true;
    const fd = new FormData();
    Object.keys(form).forEach(key => fd.append(key, form[key]));

    try {
        const res = await axios.post('/api/portal/inspections', fd, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        Object.assign(data, res.data);
        showAddModal.value = false;
        Object.assign(form, { vehicle_id: '', scheduled_date: '', completed_date: '', inspector_name: '', document: null });
        fileName.value = '';
    } finally {
        processing.value = false;
    }
};

onMounted(loadRadar);
</script>
