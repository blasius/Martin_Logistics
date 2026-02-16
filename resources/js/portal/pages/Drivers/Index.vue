<template>
    <div class="p-6 space-y-6 bg-slate-50 min-h-screen">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-slate-800 flex items-center gap-3 tracking-tighter uppercase">
                    <Users class="text-indigo-600 w-8 h-8" /> Personnel: Drivers
                </h1>
            </div>
            <button @click="showModal = true" class="px-6 py-3 bg-indigo-600 text-white rounded-2xl shadow-xl font-black text-xs flex items-center gap-2 uppercase">
                <UserPlus class="w-4 h-4" /> Register Driver
            </button>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
            <div class="relative w-72">
                <Search class="absolute left-3 top-2.5 w-4 h-4 text-slate-400" />
                <input v-model="search" @input="debounceSearch" type="text" placeholder="Search drivers..." class="w-full pl-10 p-2 bg-slate-50 border-none rounded-xl text-xs font-bold outline-none">
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-slate-50/50 text-[10px] font-black text-slate-400 uppercase border-b">
                <tr>
                    <th class="p-5">Driver</th>
                    <th class="p-5">Licence</th>
                    <th class="p-5">Contact</th>
                    <th class="p-5 text-center">Actions</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                <tr v-for="d in drivers.data" :key="d.id" class="hover:bg-indigo-50/30 transition-colors">
                    <td class="p-5">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-black text-xs shadow-lg">
                                {{ d.user?.name.substring(0,2).toUpperCase() }}
                            </div>
                            <div>
                                <p class="font-black text-slate-800 text-sm tracking-tight">{{ d.user?.name }}</p>
                                <p class="text-[10px] font-bold text-slate-400">{{ d.user?.email }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="p-5">
                        <p class="text-xs font-black text-slate-700">{{ d.driving_licence }}</p>
                        <p class="text-[9px] font-bold text-orange-500 uppercase">Exp: {{ d.licence_expiry }}</p>
                    </td>
                    <td class="p-5 text-xs font-black text-slate-600">{{ d.phone }}</td>
                    <td class="p-5 text-center"><MoreHorizontal class="w-5 h-5 text-slate-300 mx-auto cursor-pointer" /></td>
                </tr>
                </tbody>
            </table>
        </div>

        <Transition name="slide">
            <div v-if="showModal" class="fixed inset-0 z-[100] flex justify-end">
                <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="closeModal"></div>
                <div class="relative bg-white w-full max-w-xl h-full shadow-2xl p-8 flex flex-col overflow-y-auto border-l-4 border-indigo-600">
                    <div class="flex justify-between items-center mb-8">
                        <h2 class="text-2xl font-black text-slate-900 uppercase">Register driver</h2>
                        <button @click="closeModal" class="p-2 bg-slate-100 rounded-xl"><X class="w-6 h-6" /></button>
                    </div>

                    <form @submit.prevent="saveDriver" class="space-y-6">
                        <div class="space-y-3">
                            <label class="text-[10px] font-black text-indigo-600 uppercase tracking-widest block">1. User Search</label>
                            <div class="relative">
                                <Search class="absolute left-4 top-4 w-4 h-4 text-slate-400" />
                                <input v-model="userSearch" @input="debounceUserSearch" type="text" placeholder="Type name..." class="w-full pl-11 p-4 bg-slate-50 rounded-2xl outline-none font-bold text-sm">
                                <div v-if="userResults.length" class="absolute z-50 w-full mt-2 bg-white border border-slate-200 shadow-2xl rounded-2xl overflow-hidden">
                                    <div v-for="user in userResults" :key="user.id" @click="selectUser(user)" class="p-4 hover:bg-indigo-50 cursor-pointer border-b last:border-none">
                                        <p class="text-sm font-black text-slate-800">{{ user.name }}</p>
                                        <p class="text-[10px] text-slate-400 uppercase font-bold">{{ user.email }}</p>
                                    </div>
                                </div>
                            </div>
                            <div v-if="selectedUser" class="p-4 bg-indigo-50 text-indigo-700 rounded-2xl border border-indigo-100 flex items-center gap-2">
                                <CheckCircle class="w-5 h-5" /> <span class="text-xs font-black uppercase">{{ selectedUser.name }}</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-3">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block">Driving Licence</label>
                                <div class="relative h-32 border-2 border-dashed border-slate-200 rounded-2xl flex flex-col items-center justify-center bg-slate-50 overflow-hidden group">
                                    <img v-if="previews.licence" :src="previews.licence" class="absolute inset-0 w-full h-full object-cover">
                                    <template v-else>
                                        <FileText class="w-6 h-6 text-slate-300 mb-1" />
                                        <span class="text-[8px] font-black text-slate-400 uppercase">Upload Scan</span>
                                    </template>
                                    <input type="file" @change="e => handleUpload(e, 'licence')" class="absolute inset-0 opacity-0 cursor-pointer">
                                </div>
                                <input v-model="form.driving_licence" type="text" placeholder="Licence No." class="w-full p-3 bg-slate-50 rounded-xl text-xs font-bold border-none">
                                <input v-model="form.licence_expiry" type="date" class="w-full p-3 bg-slate-50 rounded-xl text-xs font-bold border-none">
                            </div>

                            <div class="space-y-3">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block">Passport</label>
                                <div class="relative h-32 border-2 border-dashed border-slate-200 rounded-2xl flex flex-col items-center justify-center bg-slate-50 overflow-hidden group">
                                    <img v-if="previews.passport" :src="previews.passport" class="absolute inset-0 w-full h-full object-cover">
                                    <template v-else>
                                        <ShieldCheck class="w-6 h-6 text-slate-300 mb-1" />
                                        <span class="text-[8px] font-black text-slate-400 uppercase">Upload Scan</span>
                                    </template>
                                    <input type="file" @change="e => handleUpload(e, 'passport')" class="absolute inset-0 opacity-0 cursor-pointer">
                                </div>
                                <input v-model="form.passport_number" type="text" placeholder="Passport No." class="w-full p-3 bg-slate-50 rounded-xl text-xs font-bold border-none">
                                <input v-model="form.passport_expiry" type="date" class="w-full p-3 bg-slate-50 rounded-xl text-xs font-bold border-none">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="flex gap-1">
                                <select v-model="countryCode" class="w-20 p-4 bg-slate-100 rounded-2xl text-[10px] font-black border-none">
                                    <option v-for="c in countries" :key="c.code" :value="c.prefix">{{ c.code }}</option>
                                </select>
                                <input v-model="form.phone" type="tel" placeholder="Phone" class="flex-1 p-4 bg-slate-50 rounded-2xl font-bold text-sm border-none">
                            </div>
                            <select v-model="form.sex" class="w-full p-4 bg-slate-50 rounded-2xl font-bold text-sm border-none">
                                <option value="" disabled>Sex</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <input v-model="form.nationality" type="text" placeholder="Nationality" class="p-4 bg-slate-50 rounded-2xl font-bold text-sm border-none">
                            <input v-model="form.date_of_birth" type="date" class="p-4 bg-slate-50 rounded-2xl font-bold text-sm border-none">
                        </div>

                        <button type="submit" :disabled="submitting || !form.user_id" class="w-full py-5 bg-indigo-600 text-white rounded-3xl font-black uppercase text-xs tracking-widest shadow-xl">
                            {{ submitting ? 'Processing Uploads...' : 'Register driver' }}
                        </button>
                    </form>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { api } from "../../../plugins/axios";
import {
    Users, UserPlus, Search, X, CheckCircle, CreditCard,
    MoreHorizontal, FileText, ShieldCheck
} from 'lucide-vue-next';

// State
const drivers = ref({ data: [] });
const stats = ref({});
const search = ref('');
const showModal = ref(false);
const submitting = ref(false);
const userSearch = ref('');
const userResults = ref([]);
const selectedUser = ref(null);
const countryCode = ref('+250');

const previews = reactive({ licence: null, passport: null });
const countries = [{ prefix: '+250', code: 'RW' }, { prefix: '+257', code: 'BI' }, { prefix: '+255', code: 'TZ' }, { prefix: '+254', code: 'KE' }];

const form = reactive({
    user_id: null, phone: '', driving_licence: '', licence_expiry: '', licence_file: null,
    passport_number: '', passport_expiry: '', passport_file: null, nationality: '', sex: '', date_of_birth: ''
});

// Autocomplete
let timer = null;
const debounceUserSearch = () => {
    clearTimeout(timer);
    if (userSearch.value.length < 2) return userResults.value = [];
    timer = setTimeout(async () => {
        const { data } = await api.get('/portal/drivers/search-users', { params: { q: userSearch.value } });
        userResults.value = data;
    }, 300);
};

const selectUser = (u) => { selectedUser.value = u; form.user_id = u.id; userResults.value = []; userSearch.value = ''; };

// File Handling
const handleUpload = (event, type) => {
    const file = event.target.files[0];
    if (!file) return;

    // Store file in form
    form[`${type}_file`] = file;

    // Create Preview
    const reader = new FileReader();
    reader.onload = (e) => previews[type] = e.target.result;
    reader.readAsDataURL(file);
};

async function fetchDrivers() {
    const { data } = await api.get('/portal/drivers', { params: { search: search.value } });
    drivers.value = data.drivers;
    stats.value = data.stats;
}

async function saveDriver() {
    submitting.value = true;
    try {
        const formData = new FormData();

        // Explicitly append every field to ensure nothing is missed
        formData.append('user_id', form.user_id);
        formData.append('driving_licence', form.driving_licence);
        formData.append('licence_expiry', form.licence_expiry);
        formData.append('nationality', form.nationality);
        formData.append('sex', form.sex);
        formData.append('date_of_birth', form.date_of_birth);

        // Handle Phone with prefix
        const cleanPhone = form.phone.startsWith('+') ? form.phone : countryCode.value + form.phone.replace(/^0+/, '');
        formData.append('phone', cleanPhone);

        // Optional Fields: Only append if they have values
        if (form.whatsapp_phone) formData.append('whatsapp_phone', form.whatsapp_phone);
        if (form.passport_number) formData.append('passport_number', form.passport_number);
        if (form.passport_expiry) formData.append('passport_expiry', form.passport_expiry);

        // Files: Check if file objects exist before appending
        if (form.licence_file instanceof File) {
            formData.append('licence_file', form.licence_file);
        }
        if (form.passport_file instanceof File) {
            formData.append('passport_file', form.passport_file);
        }

        await api.post('/portal/drivers', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });

        closeModal();
        fetchDrivers();
    } catch (e) {
        console.error("Save Error:", e.response?.data);
        alert(e.response?.data?.message || 'Check console for errors');
    } finally {
        submitting.value = false;
    }
}

const closeModal = () => {
    showModal.value = false;
    Object.assign(form, { user_id: null, phone: '', licence_file: null, passport_file: null });
    previews.licence = null; previews.passport = null;
    selectedUser.value = null;
};

const debounceSearch = () => { clearTimeout(window.t); window.t = setTimeout(fetchDrivers, 500); };
onMounted(fetchDrivers);
</script>

<style scoped>
.slide-enter-active, .slide-leave-active { transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
.slide-enter-from, .slide-leave-to { transform: translateX(100%); }
</style>
