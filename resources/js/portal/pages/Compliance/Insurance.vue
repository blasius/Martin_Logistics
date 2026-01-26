<template>
    <div class="h-screen flex flex-col bg-slate-50 overflow-hidden relative">

        <Transition name="fade">
            <div v-if="showAddModal" class="fixed inset-0 z-[150] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
                <div class="bg-white rounded-3xl shadow-2xl max-w-xl w-full p-8 border border-slate-200">
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <h3 class="text-xl font-black text-slate-800 uppercase tracking-tight">Record New Policy</h3>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">Compliance Knowledge Hub</p>
                        </div>
                        <button @click="showAddModal = false" class="p-2 hover:bg-slate-100 rounded-full transition-colors text-slate-400">
                            <X class="w-6 h-6"/>
                        </button>
                    </div>

                    <form @submit.prevent="submitPolicy" class="space-y-5">
                        <div class="grid grid-cols-2 gap-5">
                            <div class="col-span-2">
                                <label class="text-[10px] font-black text-slate-500 uppercase ml-1">Assigned Vehicle</label>
                                <select v-model="form.vehicle_id" required class="w-full mt-1.5 p-3 bg-slate-100 border-none rounded-xl text-sm font-bold focus:ring-2 focus:ring-indigo-500 outline-none">
                                    <option value="" disabled>Select a unit...</option>
                                    <option v-for="v in data.vehicles" :key="v.id" :value="v.id">{{ v.plate_number }}</option>
                                </select>
                            </div>

                            <div>
                                <label class="text-[10px] font-black text-slate-500 uppercase ml-1">Policy Number</label>
                                <input v-model="form.policy_number" type="text" required placeholder="AXA-0000" class="w-full mt-1.5 p-3 bg-slate-100 border-none rounded-xl text-sm font-bold focus:ring-2 focus:ring-indigo-500 outline-none" />
                            </div>

                            <div>
                                <label class="text-[10px] font-black text-slate-500 uppercase ml-1">Provider</label>
                                <input v-model="form.provider_name" type="text" placeholder="Provider Name" class="w-full mt-1.5 p-3 bg-slate-100 border-none rounded-xl text-sm font-bold focus:ring-2 focus:ring-indigo-500 outline-none" />
                            </div>

                            <div>
                                <label class="text-[10px] font-black text-slate-500 uppercase ml-1">Issue Date</label>
                                <input v-model="form.issue_date" type="date" required class="w-full mt-1.5 p-3 bg-slate-100 border-none rounded-xl text-sm font-bold" />
                            </div>

                            <div>
                                <label class="text-[10px] font-black text-slate-500 uppercase ml-1">Expiry Date</label>
                                <input v-model="form.expiry_date" type="date" required class="w-full mt-1.5 p-3 bg-slate-100 border-none rounded-xl text-sm font-bold" />
                            </div>

                            <div class="col-span-2">
                                <label class="text-[10px] font-black text-slate-500 uppercase ml-1">Document Scan</label>
                                <div class="mt-1.5 relative border-2 border-dashed border-slate-200 rounded-xl p-4 hover:bg-slate-50 transition-colors">
                                    <input type="file" @change="handleFile" required class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" />
                                    <div class="flex items-center gap-3 text-slate-400">
                                        <Upload class="w-5 h-5" />
                                        <span class="text-xs font-bold">{{ fileName || 'Upload PDF/Image (Max 5MB)' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="pt-4">
                            <button type="submit" :disabled="processing" class="w-full py-4 bg-indigo-600 text-white font-black rounded-2xl shadow-xl hover:bg-indigo-700 transition-all uppercase tracking-widest text-xs disabled:opacity-50">
                                {{ processing ? 'Verifying...' : 'Finalize Policy Record' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Transition>

        <header class="bg-white border-b border-slate-200 px-8 py-5 flex items-center justify-between shadow-sm z-10">
            <div class="flex items-center gap-4">
                <div class="p-2.5 bg-rose-600 rounded-xl shadow-lg shadow-rose-100">
                    <ShieldAlert class="w-6 h-6 text-white" />
                </div>
                <div>
                    <h1 class="text-xl font-black text-slate-800 tracking-tight uppercase leading-none">Insurance Radar</h1>
                    <p class="text-[10px] font-bold text-slate-400 uppercase mt-1 tracking-widest">Fleet Compliance Command</p>
                </div>
            </div>
            <button @click="showAddModal = true" class="flex items-center gap-2 px-6 py-3 bg-slate-900 text-white text-xs font-black rounded-xl hover:bg-slate-800 transition-all uppercase tracking-widest">
                <Plus class="w-4 h-4" /> New Record
            </button>
        </header>

        <div class="flex-1 overflow-y-auto px-8 py-8 space-y-12 custom-scrollbar">

            <section v-if="data.grounded.length > 0">
                <div class="flex items-center gap-3 mb-4">
                    <div class="h-2 w-2 rounded-full bg-rose-600 animate-pulse"></div>
                    <h2 class="text-xs font-black text-rose-600 uppercase tracking-widest">Critical: Grounded / Expired</h2>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div v-for="ins in data.grounded" :key="ins.id" class="bg-white border-2 border-rose-100 rounded-2xl p-5 shadow-sm hover:border-rose-300 transition-all group">
                        <div class="flex justify-between items-start">
                            <span class="text-2xl font-black text-slate-900 tracking-tighter">{{ ins.vehicle?.plate_number }}</span>
                            <div class="p-2 bg-rose-50 rounded-lg text-rose-600">
                                <AlertOctagon class="w-5 h-5" />
                            </div>
                        </div>
                        <div class="mt-4 flex items-center justify-between">
                            <span class="text-[10px] font-black px-2 py-1 bg-rose-600 text-white rounded">NO DISPATCH</span>
                            <a :href="'/storage/' + ins.document_path" target="_blank" class="text-slate-400 hover:text-indigo-600">
                                <FileText class="w-5 h-5" />
                            </a>
                        </div>
                    </div>
                </div>
            </section>

            <section v-if="data.critical.length > 0">
                <h2 class="text-xs font-black text-amber-500 uppercase tracking-widest mb-4 flex items-center gap-2">
                    <Clock class="w-4 h-4" /> Action Required: Expiring Soon
                </h2>
                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase">Unit</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase">Provider</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase">Timeline</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase text-right">Certificate</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                        <tr v-for="ins in data.critical" :key="ins.id" class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4 font-black text-slate-800 uppercase">{{ ins.vehicle?.plate_number }}</td>
                            <td class="px-6 py-4">
                                <p class="text-xs font-bold text-slate-700 leading-none">{{ ins.provider_name }}</p>
                                <p class="text-[10px] text-slate-400 font-bold uppercase mt-1">#{{ ins.policy_number }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-32 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                        <div class="h-full bg-amber-500" :style="{ width: (Math.max(0, ins.days_left) / 14 * 100) + '%' }"></div>
                                    </div>
                                    <span class="text-xs font-black text-amber-600 uppercase">{{ ins.days_left }} Days</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a :href="'/storage/' + ins.document_path" target="_blank" class="p-2 inline-block bg-slate-50 rounded-lg text-slate-400 hover:text-indigo-600">
                                    <FileText class="w-5 h-5" />
                                </a>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section>
                <h2 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4">Stable Fleet Compliance</h2>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
                    <div v-for="ins in data.upcoming" :key="ins.id" class="bg-white border border-slate-200 rounded-xl p-3 flex items-center justify-between">
                        <div>
                            <p class="text-xs font-black text-slate-800 uppercase leading-none">{{ ins.vehicle?.plate_number }}</p>
                            <p class="text-[9px] font-bold text-emerald-600 uppercase mt-1">{{ ins.days_left }} Days Left</p>
                        </div>
                        <CheckCircle class="w-4 h-4 text-emerald-400" />
                    </div>
                </div>
            </section>

            <section class="mt-20 border-t border-slate-200 pt-10 pb-20">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-lg font-black text-slate-400 uppercase tracking-tight">Audit Archive</h2>
                        <p class="text-[10px] font-bold text-slate-400 uppercase mt-1 tracking-widest">Historical Policy History</p>
                    </div>
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-300" />
                        <input v-model="archiveSearch" type="text" placeholder="Search archive..." class="pl-10 pr-4 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold outline-none focus:ring-2 focus:ring-indigo-500 w-64 shadow-sm" />
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase">Unit</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase">Policy Info</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase">Validity Period</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase text-right">Scan</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                        <tr v-for="ins in filteredArchive" :key="ins.id" class="opacity-60 grayscale-[0.4] hover:grayscale-0 hover:opacity-100 transition-all">
                            <td class="px-6 py-4 font-black text-slate-700 uppercase">{{ ins.vehicle?.plate_number }}</td>
                            <td class="px-6 py-4">
                                <p class="text-xs font-bold text-slate-600">{{ ins.provider_name }}</p>
                                <p class="text-[10px] text-slate-400 font-medium tracking-tight">POL: {{ ins.policy_number }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-[10px] font-bold text-slate-500">{{ ins.issue_date }} - {{ ins.expiry_date }}</p>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a :href="'/storage/' + ins.document_path" target="_blank" class="p-2 inline-block bg-slate-50 hover:bg-indigo-50 text-slate-400 hover:text-indigo-600 rounded-lg transition-all">
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
import { reactive, ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { ShieldAlert, Plus, X, Upload, Search, AlertOctagon, FileText, Clock, CheckCircle } from 'lucide-vue-next';

// --- STATE ---
const showAddModal = ref(false);
const processing = ref(false);
const fileName = ref('');
const archiveSearch = ref('');

const data = reactive({
    grounded: [],
    critical: [],
    upcoming: [],
    archive: [],
    vehicles: []
});

const form = reactive({
    vehicle_id: '',
    policy_number: '',
    provider_name: '',
    issue_date: '',
    expiry_date: '',
    document: null
});

// --- COMPUTED ---
const filteredArchive = computed(() => {
    if (!archiveSearch.value) return data.archive;
    const q = archiveSearch.value.toLowerCase();
    return data.archive.filter(ins =>
        ins.vehicle?.plate_number.toLowerCase().includes(q) ||
        ins.policy_number.toLowerCase().includes(q)
    );
});

// --- METHODS ---
const loadRadar = async () => {
    const res = await axios.get('/api/portal/insurances');
    Object.assign(data, res.data);
};

const handleFile = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.document = file;
        fileName.value = file.name;
    }
};

const submitPolicy = async () => {
    processing.value = true;
    const fd = new FormData();
    Object.keys(form).forEach(key => fd.append(key, form[key]));

    try {
        const res = await axios.post('/api/portal/insurances', fd, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });

        // Refresh local data with returned payload
        Object.assign(data, res.data);

        // Reset Form & UI
        showAddModal.value = false;
        Object.assign(form, { vehicle_id: '', policy_number: '', provider_name: '', issue_date: '', expiry_date: '', document: null });
        fileName.value = '';
    } catch (error) {
        alert("Failed to save policy. Ensure dates are correct and file is under 5MB.");
    } finally {
        processing.value = false;
    }
};

onMounted(loadRadar);
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
