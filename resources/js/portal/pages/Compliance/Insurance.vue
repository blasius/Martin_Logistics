<template>
    <div class="h-screen flex flex-col bg-slate-50 overflow-hidden relative">

        <Transition name="fade">
            <div v-if="showAddModal" class="fixed inset-0 z-[150] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
                <div class="bg-white rounded-3xl shadow-2xl max-w-xl w-full p-8 border border-slate-200">
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <h3 class="text-xl font-black text-slate-800 uppercase tracking-tight">Record New Policy</h3>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">Update Fleet Compliance Hub</p>
                        </div>
                        <button @click="showAddModal = false" class="p-2 hover:bg-slate-100 rounded-full transition-colors text-slate-400"><X class="w-6 h-6"/></button>
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
                                <input v-model="form.policy_number" type="text" required placeholder="e.g. AXA-9902" class="w-full mt-1.5 p-3 bg-slate-100 border-none rounded-xl text-sm font-bold focus:ring-2 focus:ring-indigo-500 outline-none" />
                            </div>

                            <div>
                                <label class="text-[10px] font-black text-slate-500 uppercase ml-1">Provider Name</label>
                                <input v-model="form.provider_name" type="text" placeholder="Insurance Co." class="w-full mt-1.5 p-3 bg-slate-100 border-none rounded-xl text-sm font-bold focus:ring-2 focus:ring-indigo-500 outline-none" />
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
                                <label class="text-[10px] font-black text-slate-500 uppercase ml-1">Document Upload (PDF/Image)</label>
                                <div class="mt-1.5 relative border-2 border-dashed border-slate-200 rounded-xl p-4 hover:bg-slate-50 transition-colors">
                                    <input type="file" @change="handleFile" required class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" />
                                    <div class="flex items-center gap-3 text-slate-400">
                                        <Upload class="w-5 h-5" />
                                        <span class="text-xs font-bold">{{ fileName || 'Click to select file (Max 5MB)' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="pt-4">
                            <button type="submit" :disabled="processing" class="w-full py-4 bg-indigo-600 text-white font-black rounded-2xl shadow-xl shadow-indigo-100 hover:bg-indigo-700 transition-all uppercase tracking-widest text-xs disabled:opacity-50 disabled:cursor-not-allowed">
                                {{ processing ? 'Processing...' : 'Securely Record Policy' }}
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
                    <p class="text-[10px] font-bold text-slate-400 uppercase mt-1 tracking-widest">Live Compliance Monitoring</p>
                </div>
            </div>
            <button @click="showAddModal = true" class="flex items-center gap-2 px-5 py-2.5 bg-slate-900 text-white text-xs font-black rounded-xl hover:bg-slate-800 transition-all shadow-xl shadow-slate-200 uppercase tracking-widest">
                <Plus class="w-4 h-4" /> Record Policy
            </button>
        </header>

        <div class="flex-1 overflow-y-auto px-8 py-8 space-y-10 custom-scrollbar">
            <section v-if="data.grounded.length > 0">
                <div class="flex items-center gap-3 mb-4">
                    <div class="h-2 w-2 rounded-full bg-rose-600 animate-pulse"></div>
                    <h2 class="text-xs font-black text-rose-600 uppercase tracking-widest">Grounded / Expired ({{ data.grounded.length }})</h2>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div v-for="ins in data.grounded" :key="ins.id" class="bg-white border-2 border-rose-100 rounded-2xl p-5 shadow-sm hover:border-rose-300 transition-all group">
                        <div class="flex justify-between items-start">
                            <span class="text-2xl font-black text-slate-900 tracking-tighter">{{ ins.vehicle?.plate_number }}</span>
                            <div class="p-2 bg-rose-50 rounded-lg text-rose-600 group-hover:bg-rose-600 group-hover:text-white transition-colors">
                                <AlertOctagon class="w-5 h-5" />
                            </div>
                        </div>
                        <div class="mt-4 flex items-center justify-between">
                            <span class="text-[10px] font-black px-2 py-1 bg-rose-600 text-white rounded">DO NOT DISPATCH</span>
                            <a :href="'/storage/' + ins.document_path" target="_blank" class="text-slate-400 hover:text-indigo-600 transition-colors">
                                <FileText class="w-5 h-5" />
                            </a>
                        </div>
                    </div>
                </div>
            </section>

            <section v-if="data.critical.length > 0">
                <h2 class="text-xs font-black text-amber-500 uppercase tracking-widest mb-4 flex items-center gap-2">
                    <Clock class="w-4 h-4" /> Immediate Renewal Required ({{ data.critical.length }})
                </h2>
                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase">Unit</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase">Timeline</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase text-right">Actions</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                        <tr v-for="ins in data.critical" :key="ins.id">
                            <td class="px-6 py-4 font-black text-slate-800 uppercase">{{ ins.vehicle?.plate_number }}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-32 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                        <div class="h-full bg-amber-500" :style="{ width: (Math.max(0, ins.days_left) / 14 * 100) + '%' }"></div>
                                    </div>
                                    <span class="text-xs font-black text-amber-600 uppercase">{{ ins.days_left }} Days</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a :href="'/storage/' + ins.document_path" target="_blank" class="text-slate-400 hover:text-indigo-600">
                                    <FileText class="w-5 h-5 inline" />
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
import { ShieldAlert, Plus, X, Upload, AlertOctagon, FileText, Clock, CheckCircle } from 'lucide-vue-next';

const showAddModal = ref(false);
const processing = ref(false);
const fileName = ref('');

const data = reactive({
    grounded: [],
    critical: [],
    upcoming: [],
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

        // Update radar with the data returned from store()
        Object.assign(data, res.data);

        // Reset form
        showAddModal.value = false;
        Object.assign(form, { vehicle_id: '', policy_number: '', provider_name: '', issue_date: '', expiry_date: '', document: null });
        fileName.value = '';
    } catch (error) {
        alert("Check your inputs. The expiry date must be after the issue date.");
    } finally {
        processing.value = false;
    }
};

onMounted(loadRadar);
</script>
