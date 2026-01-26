<template>
    <div class="p-6 space-y-6 bg-slate-50 min-h-screen">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-slate-800 flex items-center gap-3 tracking-tighter uppercase">
                    <Users class="text-indigo-600 w-8 h-8" />
                    Personnel: Drivers
                </h1>
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mt-1">Unified Fleet Management System</p>
            </div>
            <button @click="showModal = true" class="px-6 py-3 bg-indigo-600 text-white rounded-2xl shadow-xl shadow-indigo-100 hover:bg-indigo-700 transition-all font-black text-xs flex items-center gap-2 uppercase tracking-widest">
                <UserPlus class="w-4 h-4" />
                Register Driver
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div v-for="(val, label) in statsMap" :key="label" class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ label }}</p>
                <p class="text-2xl font-black text-slate-900">{{ val }}</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-4 border-b border-slate-50 flex justify-between items-center bg-slate-50/30">
                <div class="relative w-72">
                    <Search class="absolute left-3 top-2.5 w-4 h-4 text-slate-400" />
                    <input v-model="search" @input="debounceSearch" type="text" placeholder="Search drivers..." class="w-full pl-10 p-2 bg-white border border-slate-200 rounded-xl text-xs outline-none focus:ring-2 ring-indigo-500/20 font-bold">
                </div>
            </div>
            <table class="w-full text-left">
                <thead class="bg-slate-50/50 text-[10px] font-black text-slate-400 uppercase border-b border-slate-100">
                <tr>
                    <th class="p-5">Operator</th>
                    <th class="p-5">Identification</th>
                    <th class="p-5">Contact</th>
                    <th class="p-5 text-center">Actions</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                <tr v-for="d in drivers.data" :key="d.id" class="hover:bg-indigo-50/30 transition-colors">
                    <td class="p-5">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-black text-xs shadow-lg shadow-indigo-100">
                                {{ d.user?.name.substring(0,2).toUpperCase() }}
                            </div>
                            <div>
                                <p class="font-black text-slate-800 text-sm tracking-tight">{{ d.user?.name }}</p>
                                <p class="text-[10px] font-bold text-slate-400">{{ d.user?.email }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="p-5">
                        <div class="space-y-1">
                            <p class="text-xs font-black text-slate-700 flex items-center gap-1"><CreditCard class="w-3 h-3 text-slate-400"/> {{ d.driving_licence }}</p>
                            <p class="text-[9px] font-bold text-slate-400 uppercase">Passport: {{ d.passport_number || 'NONE' }}</p>
                        </div>
                    </td>
                    <td class="p-5 text-xs font-black text-slate-600">
                        {{ d.phone }}
                    </td>
                    <td class="p-5 text-center">
                        <button class="p-2 hover:bg-slate-100 rounded-lg transition-colors"><MoreHorizontal class="w-5 h-5 text-slate-400"/></button>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

        <Transition name="slide">
            <div v-if="showModal" class="fixed inset-0 z-[100] flex justify-end">
                <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="closeModal"></div>
                <div class="relative bg-white w-full max-w-lg h-full shadow-2xl p-8 flex flex-col overflow-y-auto border-l-4 border-indigo-600">
                    <div class="flex justify-between items-start mb-10">
                        <div>
                            <h2 class="text-2xl font-black text-slate-900 tracking-tighter uppercase">Operator Registration</h2>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Assign Driver profile to system user</p>
                        </div>
                        <button @click="closeModal" class="p-2 bg-slate-100 rounded-xl hover:bg-rose-50 hover:text-rose-500 transition-all"><X class="w-6 h-6" /></button>
                    </div>

                    <form @submit.prevent="saveDriver" class="space-y-8">
                        <div class="space-y-4">
                            <label class="text-[10px] font-black text-indigo-600 uppercase tracking-widest block border-b pb-2">1. Identity Lookup</label>
                            <div class="relative">
                                <Search class="absolute left-4 top-4 w-4 h-4 text-slate-400" />
                                <input v-model="userSearch" @input="debounceUserSearch" type="text" placeholder="Start typing driver name..." class="w-full pl-11 p-4 bg-slate-50 rounded-2xl outline-none focus:ring-2 ring-indigo-500/20 font-bold text-sm">

                                <div v-if="userResults.length" class="absolute z-50 w-full mt-2 bg-white border border-slate-200 shadow-2xl rounded-2xl overflow-hidden">
                                    <div v-for="user in userResults" :key="user.id" @click="selectUser(user)" class="p-4 hover:bg-indigo-50 cursor-pointer border-b border-slate-50 last:border-none transition-all">
                                        <p class="text-sm font-black text-slate-800">{{ user.name }}</p>
                                        <p class="text-[10px] text-slate-400 font-bold uppercase">{{ user.email }}</p>
                                    </div>
                                </div>
                            </div>
                            <div v-if="selectedUser" class="flex items-center justify-between p-4 bg-indigo-50 text-indigo-700 rounded-2xl border border-indigo-100 animate-in zoom-in">
                                <div class="flex items-center gap-3">
                                    <CheckCircle class="w-5 h-5" />
                                    <p class="text-xs font-black uppercase">{{ selectedUser.name }}</p>
                                </div>
                                <button @click="selectedUser = null; form.user_id = null" class="text-[10px] font-black underline">Change</button>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <label class="text-[10px] font-black text-indigo-600 uppercase tracking-widest block border-b pb-2">2. Communication</label>
                            <div class="flex gap-2">
                                <select v-model="countryCode" class="w-32 p-4 bg-slate-100 rounded-2xl text-xs font-black outline-none appearance-none">
                                    <option v-for="c in countries" :key="c.code" :value="c.prefix">{{ c.code }} {{ c.prefix }}</option>
                                </select>
                                <input v-model="form.phone" type="tel" placeholder="Primary Phone" class="flex-1 p-4 bg-slate-50 rounded-2xl outline-none focus:ring-2 ring-indigo-500/20 font-bold text-sm" required>
                            </div>
                            <input v-model="form.whatsapp_phone" type="tel" placeholder="WhatsApp (Optional - with code)" class="w-full p-4 bg-slate-50 rounded-2xl outline-none font-bold text-sm">
                        </div>

                        <div class="space-y-4">
                            <label class="text-[10px] font-black text-indigo-600 uppercase tracking-widest block border-b pb-2">3. Compliance Details</label>
                            <div class="grid grid-cols-2 gap-4">
                                <input v-model="form.driving_licence" type="text" placeholder="Licence Number" class="w-full p-4 bg-slate-50 rounded-2xl outline-none font-bold text-sm" required>
                                <input v-model="form.passport_number" type="text" placeholder="Passport Number" class="w-full p-4 bg-slate-50 rounded-2xl outline-none font-bold text-sm">
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <input v-model="form.nationality" type="text" placeholder="Nationality" class="w-full p-4 bg-slate-50 rounded-2xl outline-none font-bold text-sm" required>
                                <input v-model="form.date_of_birth" type="date" class="w-full p-4 bg-slate-50 rounded-2xl outline-none font-bold text-sm" required>
                            </div>
                            <select v-model="form.sex" class="w-full p-4 bg-slate-50 rounded-2xl outline-none font-bold text-sm" required>
                                <option value="" disabled>Select Sex</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </select>
                        </div>

                        <button type="submit" :disabled="submitting || !form.user_id" class="w-full py-5 bg-indigo-600 text-white rounded-3xl font-black uppercase text-xs tracking-widest hover:bg-indigo-700 transition-all disabled:opacity-30 shadow-2xl shadow-indigo-100">
                            {{ submitting ? 'Submitting...' : 'Register Operator' }}
                        </button>
                    </form>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { api } from '@/plugins/axios';
import { Users, UserPlus, Search, X, CheckCircle, CreditCard, Phone, MessageCircle, MoreHorizontal } from 'lucide-vue-next';

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

const countries = [
    { prefix: '+250', code: 'RW' }, { prefix: '+257', code: 'BI' },
    { prefix: '+255', code: 'TZ' }, { prefix: '+254', code: 'KE' },
    { prefix: '+256', code: 'UG' }, { prefix: '+260', code: 'ZM' }
];

const form = reactive({
    user_id: null, phone: '', whatsapp_phone: '',
    driving_licence: '', passport_number: '',
    nationality: '', sex: '', date_of_birth: ''
});

const statsMap = computed(() => ({
    'Workforce': stats.value.total || 0,
    'Male Ops': stats.value.male || 0,
    'Female Ops': stats.value.female || 0,
    'New Entry': stats.value.new_this_month || 0
}));

// Autocomplete Logic
let searchTimer = null;
const debounceUserSearch = () => {
    clearTimeout(searchTimer);
    if (userSearch.value.length < 2) return userResults.value = [];
    searchTimer = setTimeout(async () => {
        const { data } = await api.get('/api/portal/drivers/search-users', { params: { q: userSearch.value } });
        userResults.value = data;
    }, 300);
};

const selectUser = (u) => {
    selectedUser.value = u;
    form.user_id = u.id;
    userResults.value = [];
    userSearch.value = '';
};

// Main Actions
async function fetchDrivers() {
    const { data } = await api.get('/api/portal/drivers', { params: { search: search.value } });
    drivers.value = data.drivers;
    stats.value = data.stats;
}

async function saveDriver() {
    submitting.value = true;
    try {
        const payload = { ...form };
        if (!payload.phone.startsWith('+')) payload.phone = countryCode.value + payload.phone.replace(/^0+/, '');

        await api.post('/api/portal/drivers', payload);
        closeModal();
        fetchDrivers();
    } catch (e) {
        alert(e.response?.data?.message || 'Submission failed');
    } finally {
        submitting.value = false;
    }
}

const closeModal = () => {
    showModal.value = false;
    selectedUser.value = null;
    Object.assign(form, { user_id: null, phone: '', driving_licence: '', nationality: '', sex: '', date_of_birth: '' });
};

const debounceSearch = () => { clearTimeout(window.t); window.t = setTimeout(fetchDrivers, 500); };
onMounted(fetchDrivers);
</script>

<style scoped>
.slide-enter-active, .slide-leave-active { transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
.slide-enter-from, .slide-leave-to { transform: translateX(100%); }
</style>
