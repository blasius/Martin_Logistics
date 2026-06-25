<template>
    <div class="p-6 max-w-7xl mx-auto">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">Roles &amp; Permissions</h1>
            <button @click="$router.push('/roles/create')" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Create Role
            </button>
        </div>

        <div v-if="loading" class="text-center py-12 text-slate-400">Loading roles...</div>

        <div v-else class="grid gap-4">
            <div v-for="role in roles" :key="role.id"
                 class="bg-white rounded-xl border border-slate-200 p-5 hover:shadow-sm transition cursor-pointer"
                 @click="$router.push(`/roles/${role.id}`)">

                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold"
                             :class="role.is_super_admin ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'">
                            {{ role.name.charAt(0).toUpperCase() }}
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-semibold text-slate-800">{{ role.name }}</span>
                                <span v-if="role.is_super_admin" class="bg-purple-100 text-purple-700 text-xs px-2 py-0.5 rounded-full font-medium">Super Admin</span>
                                <span v-if="!role.is_active" class="bg-red-100 text-red-600 text-xs px-2 py-0.5 rounded-full font-medium">Inactive</span>
                            </div>
                            <p v-if="role.description" class="text-sm text-slate-500 mt-0.5">{{ role.description }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-6 text-sm text-slate-500">
                        <div class="text-center">
                            <div class="font-semibold text-slate-700">{{ role.users_count ?? 0 }}</div>
                            <div class="text-xs">users</div>
                        </div>
                        <div class="text-center">
                            <div class="font-semibold text-slate-700">{{ role.permissions?.length ?? 0 }}</div>
                            <div class="text-xs">permissions</div>
                        </div>
                        <svg class="w-5 h-5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </div>

                <div v-if="role.permissions?.length" class="flex flex-wrap gap-1.5 mt-3 ml-13">
                    <span v-for="p in role.permissions.slice(0, 6)" :key="p.id"
                          class="bg-slate-100 text-slate-600 text-xs px-2 py-0.5 rounded">{{ p.name }}</span>
                    <span v-if="role.permissions.length > 6" class="text-xs text-slate-400 px-1">+{{ role.permissions.length - 6 }} more</span>
                </div>
            </div>

            <div v-if="!roles.length && !loading" class="text-center py-12 text-slate-400">
                No roles found. Create your first role.
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { rolesApi } from '../../api/roles';

const roles = ref([]);
const loading = ref(true);

onMounted(async () => {
    try {
        const res = await rolesApi.getAll();
        roles.value = res.data;
    } catch (e) { console.error(e); }
    finally { loading.value = false; }
});
</script>
