<template>
    <div class="p-6 max-w-7xl mx-auto">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">Users</h1>
            <button @click="$router.push('/users/create')" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Create User
            </button>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-4 mb-6">
            <div class="flex flex-wrap gap-3">
                <input v-model="filters.search" @input="debouncedSearch" placeholder="Search by name or email..."
                       class="flex-1 min-w-[200px] border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                <select v-model="filters.role" @change="fetchUsers" class="border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none">
                    <option value="">All Roles</option>
                    <option v-for="r in allRoles" :key="r" :value="r">{{ r }}</option>
                </select>
            </div>
        </div>

        <div v-if="loading" class="text-center py-12 text-slate-400">Loading users...</div>

        <template v-else>
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <table class="w-full">
                    <thead class="bg-slate-50 text-left text-xs font-semibold text-slate-500 uppercase">
                        <tr>
                            <th class="px-4 py-3">Name</th>
                            <th class="px-4 py-3">Email</th>
                            <th class="px-4 py-3">Roles</th>
                            <th class="px-4 py-3">Created</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="user in users" :key="user.id"
                            class="hover:bg-slate-50 cursor-pointer" @click="$router.push(`/users/${user.id}`)">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-xs font-bold">
                                        {{ user.name.charAt(0).toUpperCase() }}
                                    </div>
                                    <span class="font-medium text-sm">{{ user.name }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ user.email }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1">
                                    <span v-for="r in user.roles" :key="r.id"
                                          class="bg-slate-100 text-slate-600 text-xs px-2 py-0.5 rounded">{{ r.name }}</span>
                                    <span v-if="!user.roles?.length" class="text-xs text-slate-400">No roles</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-500">{{ dayjs(user.created_at).format('MMM D, YYYY') }}</td>
                            <td class="px-4 py-3 text-right">
                                <svg class="w-4 h-4 text-slate-300 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="meta && meta.last_page > 1" class="flex items-center justify-between mt-4 text-sm">
                <span class="text-slate-500">Page {{ meta.current_page }} of {{ meta.last_page }} ({{ meta.total }} total)</span>
                <div class="flex gap-2">
                    <button @click="changePage(meta.current_page - 1)" :disabled="meta.current_page <= 1"
                            class="px-3 py-1 border border-slate-200 rounded-lg disabled:opacity-30 hover:bg-slate-50">Prev</button>
                    <button @click="changePage(meta.current_page + 1)" :disabled="meta.current_page >= meta.last_page"
                            class="px-3 py-1 border border-slate-200 rounded-lg disabled:opacity-30 hover:bg-slate-50">Next</button>
                </div>
            </div>

            <div v-if="!users.length && !loading" class="text-center py-12 text-slate-400">
                No users found.
            </div>
        </template>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { usersApi } from '../../api/users';
import dayjs from 'dayjs';

const users = ref([]);
const allRoles = ref([]);
const loading = ref(true);
const meta = ref(null);

const filters = ref({ search: '', role: '' });
let debounceTimer = null;

function debouncedSearch() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(fetchUsers, 300);
}

async function fetchUsers() {
    loading.value = true;
    try {
        const res = await usersApi.getAll({ ...filters.value, page: meta.value?.current_page || 1 });
        users.value = res.data.data;
        meta.value = { current_page: res.data.current_page, last_page: res.data.last_page, total: res.data.total };
    } catch (e) { console.error(e); }
    finally { loading.value = false; }
}

function changePage(page) {
    if (page < 1 || (meta.value && page > meta.value.last_page)) return;
    meta.value.current_page = page;
    fetchUsers();
}

onMounted(async () => {
    try {
        const [usersRes, rolesRes] = await Promise.all([
            usersApi.getAll(),
            usersApi.rolesList(),
        ]);
        users.value = usersRes.data.data;
        allRoles.value = rolesRes.data;
        meta.value = {
            current_page: usersRes.data.current_page,
            last_page: usersRes.data.last_page,
            total: usersRes.data.total,
        };
    } catch (e) { console.error(e); }
    finally { loading.value = false; }
});
</script>
