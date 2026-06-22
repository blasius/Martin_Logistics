<template>
    <div class="bg-slate-50 min-h-screen">
        <div v-if="roleMissing" class="bg-amber-50 border border-amber-200 rounded-xl p-6 text-center">
            <h2 class="text-lg font-black text-amber-800 mb-2 uppercase tracking-widest">Role Not Found</h2>
            <p class="text-sm text-amber-700 mb-4">
                The "mechanic" role has not been created yet. Create it before assigning mechanics.
            </p>
            <a href="/admin/roles" target="_blank"
                class="inline-flex items-center px-4 py-2 bg-amber-600 text-white text-sm font-black uppercase tracking-wider rounded-lg hover:bg-amber-700">
                <Settings class="w-4 h-4 mr-2" />
                Roles Management
            </a>
        </div>

        <template v-else>
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-xl font-black text-slate-800 uppercase tracking-tight">Mechanics</h1>
                <button @click="openCreateModal"
                    class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl shadow-lg font-black text-xs flex items-center gap-2 uppercase">
                    <Plus class="w-4 h-4" /> Add Mechanic
                </button>
            </div>

            <div v-if="loading" class="text-center py-12 text-slate-400 text-sm font-black uppercase tracking-wider">Loading...</div>

            <div v-else class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
                <table class="min-w-full">
                    <thead>
                        <tr class="bg-slate-800">
                            <th class="px-6 py-3 text-left text-[10px] font-black text-white uppercase tracking-widest">Name</th>
                            <th class="px-6 py-3 text-left text-[10px] font-black text-white uppercase tracking-widest">Email</th>
                            <th class="px-6 py-3 text-left text-[10px] font-black text-white uppercase tracking-widest">Role</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="m in mechanics" :key="m.id" class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 text-sm font-bold text-slate-800">{{ m.name }}</td>
                            <td class="px-6 py-4 text-sm text-slate-500 font-medium">{{ m.email }}</td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2 py-0.5 text-[10px] font-black uppercase tracking-wider rounded-full bg-indigo-100 text-indigo-700">
                                    Mechanic
                                </span>
                            </td>
                        </tr>
                        <tr v-if="mechanics.length === 0">
                            <td colspan="3" class="px-6 py-12 text-center text-sm text-slate-400 font-bold">No mechanics found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </template>

        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" @click.self="closeModal">
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full border border-slate-200 max-h-[90vh] overflow-y-auto">

                <div class="p-6 border-b border-slate-100 flex justify-between items-center">
                    <h3 class="font-black text-slate-800 uppercase text-sm">Add Mechanic</h3>
                    <button @click="closeModal" class="text-slate-400 hover:text-slate-600"><X class="w-5 h-5" /></button>
                </div>

                <div v-if="saveError" class="mx-6 mt-6 p-3 bg-amber-50 border border-amber-200 rounded-xl text-sm">
                    <p class="text-amber-800 font-bold text-xs">{{ saveError }}</p>
                    <a href="/admin/roles" target="_blank"
                        class="inline-flex items-center mt-2 text-amber-700 font-black uppercase tracking-wider text-[10px] hover:text-amber-800">
                        <Settings class="w-3 h-3 mr-1" />
                        Create "mechanic" role
                    </a>
                </div>

                <form @submit.prevent="save" class="p-6 space-y-4">
                    <div class="relative">
                        <label class="text-[10px] font-black text-slate-400 uppercase">Search User</label>
                        <input ref="searchInput" v-model="searchQuery" @input="searchUsers" type="text" placeholder="Type name or email..." required
                            class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold" />
                        <ul v-if="searchResults.length > 0" class="absolute z-10 mt-1 w-full bg-white border border-slate-200 rounded-xl shadow-lg overflow-hidden">
                            <li v-for="u in searchResults" :key="u.id" @mousedown.prevent="selectUser(u)" :class="selectedUser?.id === u.id ? 'bg-indigo-50' : ''"
                                class="px-3 py-2 text-xs font-bold hover:bg-indigo-50 cursor-pointer border-b border-slate-100 last:border-0">
                                <span class="text-slate-800">{{ u.name }}</span>
                                <span class="text-slate-400 ml-2 font-medium">{{ u.email }}</span>
                            </li>
                            <li v-if="searchQuery && searchResults.length === 0 && !searching" class="px-3 py-3 text-xs text-slate-400 font-bold text-center">No users found</li>
                        </ul>
                    </div>

                    <div v-if="selectedUser">
                        <label class="text-[10px] font-black text-slate-400 uppercase">Selected User</label>
                        <div class="flex items-center gap-3 mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl">
                            <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-xs font-black text-indigo-700 uppercase shrink-0">
                                {{ initials(selectedUser.name) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-bold text-slate-800 truncate">{{ selectedUser.name }}</p>
                                <p class="text-[10px] text-slate-400 font-medium truncate">{{ selectedUser.email }}</p>
                            </div>
                            <button type="button" @click="selectedUser = null" class="text-slate-400 hover:text-slate-600 shrink-0"><X class="w-4 h-4" /></button>
                        </div>
                    </div>

                    <div v-if="selectedUser">
                        <label class="text-[10px] font-black text-slate-400 uppercase">Roles</label>
                        <div class="flex items-center gap-2 mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl">
                            <input type="checkbox" checked disabled
                                class="w-4 h-4 text-indigo-600 bg-white border-slate-300 rounded opacity-60" />
                            <span class="text-xs font-bold text-slate-700">Mechanic <span class="text-slate-400 font-medium">(assigned on save)</span></span>
                        </div>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="closeModal" class="flex-1 px-4 py-3 bg-slate-100 text-slate-600 text-xs font-black rounded-xl uppercase">Cancel</button>
                        <button type="submit" :disabled="saving || !selectedUser"
                            class="flex-1 px-4 py-3 bg-indigo-600 text-white text-xs font-black rounded-xl shadow-lg uppercase disabled:opacity-50">
                            {{ saving ? 'Saving...' : 'Assign Mechanic Role' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from "vue";
import { Plus, X, Settings } from "lucide-vue-next";
import { mechanicsApi } from "../../../api/workshop/mechanics";
import { api } from "../../../../plugins/axios";

const mechanics = ref([]);
const loading = ref(true);
const roleMissing = ref(false);
const showModal = ref(false);
const saving = ref(false);
const saveError = ref("");
const searchQuery = ref("");
const searchResults = ref([]);
const selectedUser = ref(null);
const searching = ref(false);
const searchInput = ref(null);

onMounted(fetch);

async function fetch() {
    loading.value = true;
    try {
        const { data } = await mechanicsApi.index();
        mechanics.value = data.mechanics ?? data;
        roleMissing.value = data.role_missing ?? false;
    } finally {
        loading.value = false;
    }
}

function openCreateModal() {
    selectedUser.value = null;
    searchQuery.value = "";
    searchResults.value = [];
    saveError.value = "";
    showModal.value = true;
    nextTick(() => searchInput.value?.focus());
}

function closeModal() {
    showModal.value = false;
    saveError.value = "";
}

let searchTimeout;
function searchUsers() {
    clearTimeout(searchTimeout);
    if (!searchQuery.value.trim()) {
        searchResults.value = [];
        return;
    }
    searching.value = true;
    searchTimeout = setTimeout(async () => {
        try {
            const { data } = await api.get("/portal/workshop/mechanics/search-users", { params: { q: searchQuery.value } });
            searchResults.value = data;
        } finally {
            searching.value = false;
        }
    }, 300);
}

function selectUser(u) {
    selectedUser.value = u;
    searchResults.value = [];
    searchQuery.value = "";
}

function initials(name) {
    return name.split(" ").map(w => w[0]).join("").toUpperCase().slice(0, 2);
}

async function save() {
    if (!selectedUser.value) return;
    saving.value = true;
    saveError.value = "";
    try {
        await mechanicsApi.store({ user_id: selectedUser.value.id });
        showModal.value = false;
        await fetch();
    } catch (e) {
        const msg = e?.response?.data?.message || e?.response?.data?.error || "An error occurred";
        saveError.value = msg;
    } finally {
        saving.value = false;
    }
}
</script>
