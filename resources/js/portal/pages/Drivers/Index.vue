<template>
    <div class="p-6 space-y-6 bg-slate-50 min-h-screen">

        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-slate-800 flex items-center gap-3">
                    <i class="lucide lucide-users text-indigo-600"></i>
                    Personnel: Drivers
                </h1>
                <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mt-1">Manage {{ stats.total }} certified fleet operators</p>
            </div>
            <button class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl shadow-lg shadow-indigo-200 hover:bg-indigo-700 transition font-black text-sm flex items-center gap-2">
                <i class="lucide lucide-user-plus w-4 h-4"></i>
                Register New Driver
            </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm">
                <p class="text-[10px] font-black text-slate-400 uppercase">Total Workforce</p>
                <p class="text-2xl font-black text-slate-900">{{ stats.total }}</p>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm">
                <p class="text-[10px] font-black text-slate-400 uppercase">New Registrations</p>
                <p class="text-2xl font-black text-green-600">+{{ stats.new_this_month }} <span class="text-xs text-slate-300">this month</span></p>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm">
                <p class="text-[10px] font-black text-slate-400 uppercase">Gender Diversity</p>
                <div class="flex items-center gap-4 mt-1">
                    <span class="text-sm font-bold text-indigo-600">♂ {{ stats.male }}</span>
                    <span class="text-sm font-bold text-pink-500">♀ {{ stats.female }}</span>
                </div>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm border-l-4 border-l-orange-400">
                <p class="text-[10px] font-black text-orange-500 uppercase">Documentation</p>
                <p class="text-sm font-bold text-slate-700 mt-1">100% Certified</p>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex flex-col md:flex-row gap-4 justify-between items-center">
            <div class="relative w-full md:w-96">
                <i class="lucide lucide-search absolute left-3 top-2.5 text-slate-400 w-4 h-4"></i>
                <input v-model="search" @input="debounceSearch" type="text" placeholder="Search name, license or phone..." class="w-full pl-10 pr-4 py-2 bg-slate-50 border-transparent focus:bg-white focus:ring-2 ring-indigo-500 rounded-xl text-sm outline-none transition-all">
            </div>
            <div class="flex items-center gap-2">
                <button class="p-2 text-slate-400 hover:text-indigo-600 transition"><i class="lucide lucide-filter w-5 h-5"></i></button>
                <button class="p-2 text-slate-400 hover:text-indigo-600 transition"><i class="lucide lucide-download w-5 h-5"></i></button>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-slate-50 text-[10px] font-black text-slate-400 uppercase border-b">
                <tr>
                    <th class="p-4">Driver Details</th>
                    <th class="p-4">Nationality & DOB</th>
                    <th class="p-4">Documents</th>
                    <th class="p-4">Contact</th>
                    <th class="p-4 text-center">Actions</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                <tr v-for="d in drivers.data" :key="d.id" class="hover:bg-slate-50/80 transition-colors group">
                    <td class="p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-black text-xs shadow-inner">
                                {{ getInitials(d.user?.name) }}
                            </div>
                            <div>
                                <div class="font-black text-slate-800 text-sm">{{ d.user?.name }}</div>
                                <div class="text-[10px] font-bold text-slate-400 tracking-tight">{{ d.user?.email }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="p-4">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-slate-700">{{ d.nationality }}</span>
                            <span v-if="d.sex === 'female'" class="text-[10px] text-pink-500">♀</span>
                            <span v-else class="text-[10px] text-indigo-500">♂</span>
                        </div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase">Born: {{ d.date_of_birth }}</div>
                    </td>
                    <td class="p-4">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <i class="lucide lucide-credit-card w-3 h-3 text-slate-400"></i>
                                <span class="text-[11px] font-black text-slate-600">{{ d.driving_licence }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="lucide lucide-file-text w-3 h-3 text-slate-400"></i>
                                <span class="text-[11px] font-bold text-slate-500">PP: {{ d.passport_number || 'N/A' }}</span>
                            </div>
                        </div>
                    </td>
                    <td class="p-4">
                        <div class="flex items-center gap-2">
                            <a :href="'tel:' + d.phone" class="p-2 bg-slate-100 hover:bg-indigo-100 hover:text-indigo-600 rounded-lg transition text-slate-600">
                                <i class="lucide lucide-phone w-4 h-4"></i>
                            </a>
                            <a v-if="d.whatsapp_phone" :href="'https://wa.me/' + d.whatsapp_phone" target="_blank" class="p-2 bg-green-50 hover:bg-green-500 hover:text-white rounded-lg transition text-green-600">
                                <i class="lucide lucide-message-circle w-4 h-4"></i>
                            </a>
                        </div>
                    </td>
                    <td class="p-4 text-center">
                        <button class="px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-[10px] font-black text-slate-600 hover:bg-slate-800 hover:text-white transition uppercase shadow-sm">
                            View Profile
                        </button>
                    </td>
                </tr>
                </tbody>
            </table>

            <div class="p-4 border-t bg-slate-50 flex justify-between items-center">
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Page {{ drivers.current_page }} of {{ drivers.last_page }}</span>
                <div class="flex gap-1">
                    <button @click="fetchDrivers(drivers.current_page - 1)" :disabled="!drivers.prev_page_url" class="p-2 bg-white border rounded-lg disabled:opacity-30"><i class="lucide lucide-chevron-left w-4 h-4"></i></button>
                    <button @click="fetchDrivers(drivers.current_page + 1)" :disabled="!drivers.next_page_url" class="p-2 bg-white border rounded-lg disabled:opacity-30"><i class="lucide lucide-chevron-right w-4 h-4"></i></button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { api } from '@/plugins/axios';
import {
    Users, UserPlus, Search, Filter, Download,
    Phone, MessageCircle, CreditCard, FileText,
    ChevronLeft, ChevronRight
} from 'lucide-vue-next';

const drivers = ref({ data: [] });
const stats = ref({});
const search = ref('');
const loading = ref(false);
let timer = null;

async function fetchDrivers(page = 1) {
    loading.value = true;
    try {
        const { data } = await api.get('/api/portal/drivers', {
            params: { page, search: search.value }
        });
        drivers.value = data.drivers;
        stats.value = data.stats;
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
}

const debounceSearch = () => {
    clearTimeout(timer);
    timer = setTimeout(() => fetchDrivers(1), 500);
};

const getInitials = (name) => {
    return name ? name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2) : '??';
};

onMounted(() => fetchDrivers());
</script>
