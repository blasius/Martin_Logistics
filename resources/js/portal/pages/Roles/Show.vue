<template>
    <div class="p-6 max-w-5xl mx-auto">
        <div class="flex items-center gap-2 text-sm text-slate-500 mb-4">
            <a @click="$router.push('/roles')" class="hover:text-blue-600 cursor-pointer">Roles</a>
            <span>/</span>
            <span class="text-slate-800 font-medium">{{ isNew ? 'New Role' : role?.name }}</span>
        </div>

        <div v-if="loading" class="text-center py-12 text-slate-400">Loading...</div>

        <template v-else>
            <div class="bg-white rounded-xl border border-slate-200 p-6 mb-6">
                <h2 class="text-lg font-bold mb-4">{{ isNew ? 'Create Role' : 'Edit Role' }}</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Role Name</label>
                        <input v-model="form.name" type="text" required
                               class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none"
                               placeholder="e.g. Logistics Manager">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Slug</label>
                        <input v-model="form.slug" type="text"
                               class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none"
                               placeholder="Auto-generated from name">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
                    <textarea v-model="form.description" rows="2"
                              class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none"
                              placeholder="What this role is responsible for..."></textarea>
                </div>

                <div class="flex items-center gap-6 mb-4">
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" v-model="form.is_super_admin" class="rounded border-slate-300">
                        <span class="font-medium">Super Admin</span>
                        <span class="text-slate-400">(bypasses all permission checks)</span>
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" v-model="form.is_active" class="rounded border-slate-300">
                        <span class="font-medium">Active</span>
                    </label>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 p-6 mb-6">
                <h3 class="text-lg font-bold mb-1">Permissions</h3>
                <p class="text-sm text-slate-500 mb-4">Select the permissions this role grants.</p>

                <div v-if="loadingPerms" class="text-center py-8 text-slate-400">Loading permissions...</div>

                <div v-else class="space-y-4">
                    <div v-for="(perms, group) in groupedPermissions" :key="group" class="border border-slate-100 rounded-lg">
                        <button @click="toggleGroup(group)"
                                class="w-full flex items-center justify-between px-4 py-3 bg-slate-50 hover:bg-slate-100 rounded-lg text-left">
                            <span class="font-semibold text-sm capitalize text-slate-700">{{ group }}</span>
                            <div class="flex items-center gap-3">
                                <span class="text-xs text-slate-400">{{ selectedCountInGroup(group) }} / {{ perms.length }} selected</span>
                                <svg class="w-4 h-4 text-slate-400 transition" :class="{ 'rotate-90': openGroups[group] }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </div>
                        </button>
                        <div v-if="openGroups[group]" class="px-4 pb-3 pt-1 grid grid-cols-2 md:grid-cols-3 gap-1.5">
                            <label v-for="perm in perms" :key="perm.id"
                                   class="flex items-center gap-2 text-sm py-1 px-2 rounded hover:bg-slate-50 cursor-pointer">
                                <input type="checkbox" :checked="isSelected(perm.name)"
                                       @change="togglePermission(perm.name)"
                                       class="rounded border-slate-300">
                                <div>
                                    <span class="text-slate-700">{{ perm.name }}</span>
                                    <p v-if="perm.description" class="text-xs text-slate-400">{{ perm.description }}</p>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 p-6 mb-6" v-if="!isNew">
                <h3 class="text-lg font-bold mb-1">Assigned Users</h3>
                <p class="text-sm text-slate-500 mb-4">{{ users.length }} user(s) have this role.</p>
                <div v-if="users.length" class="divide-y">
                    <div v-for="user in users" :key="user.id" class="flex items-center justify-between py-2">
                        <div>
                            <span class="font-medium text-sm">{{ user.name }}</span>
                            <span class="text-xs text-slate-400 ml-2">{{ user.email }}</span>
                        </div>
                        <button @click="removeUserRole(user)"
                                class="text-xs text-red-500 hover:text-red-700">Remove</button>
                    </div>
                </div>
                <p v-else class="text-sm text-slate-400 italic">No users assigned yet.</p>
            </div>

            <div class="flex items-center gap-3">
                <button @click="save" :disabled="saving"
                        class="bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white px-6 py-2 rounded-lg text-sm">
                    {{ saving ? 'Saving...' : (isNew ? 'Create Role' : 'Save Changes') }}
                </button>
                <button @click="$router.push('/roles')"
                        class="border border-slate-200 text-slate-600 hover:bg-slate-50 px-6 py-2 rounded-lg text-sm">
                    Cancel
                </button>
                <button v-if="!isNew" @click="deleteRole"
                        class="ml-auto text-red-500 hover:text-red-700 text-sm">Delete Role</button>
            </div>
        </template>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { rolesApi } from '../../api/roles';
import { usersApi } from '../../api/users';

const route = useRoute();
const router = useRouter();

const isNew = computed(() => route.path.endsWith('/create'));
const loading = ref(true);
const loadingPerms = ref(true);
const saving = ref(false);
const role = ref(null);
const users = ref([]);
const allPermissions = ref([]);
const selectedPermissions = ref([]);
const openGroups = ref({});

const form = ref({
    name: '', slug: '', description: '', is_super_admin: false, is_active: true, permissions: [],
});

const groupedPermissions = computed(() => {
    return allPermissions.value.reduce((acc, p) => {
        const group = p.group || 'general';
        if (!acc[group]) acc[group] = [];
        acc[group].push(p);
        return acc;
    }, {});
});

function isSelected(name) {
    return selectedPermissions.value.includes(name);
}

function togglePermission(name) {
    const idx = selectedPermissions.value.indexOf(name);
    if (idx > -1) selectedPermissions.value.splice(idx, 1);
    else selectedPermissions.value.push(name);
}

function selectedCountInGroup(group) {
    const perms = groupedPermissions.value[group] || [];
    return perms.filter(p => isSelected(p.name)).length;
}

function toggleGroup(group) {
    openGroups.value[group] = !openGroups.value[group];
}

async function loadPermissions() {
    try {
        const res = await rolesApi.permissionsGrouped();
        allPermissions.value = Object.values(res.data).flat();
    } catch (e) { console.error(e); }
    finally { loadingPerms.value = false; }
}

async function loadRole() {
    try {
        const res = await rolesApi.get(route.params.id);
        role.value = res.data;
        form.value.name = res.data.name;
        form.value.slug = res.data.slug || '';
        form.value.description = res.data.description || '';
        form.value.is_super_admin = res.data.is_super_admin ?? false;
        form.value.is_active = res.data.is_active ?? true;
        selectedPermissions.value = res.data.permissions?.map(p => p.name) || [];
        users.value = res.data.users || [];
    } catch (e) { console.error(e); }
}

async function save() {
    saving.value = true;
    try {
        const data = { ...form.value, permissions: selectedPermissions.value };
        if (isNew.value) {
            await rolesApi.create(data);
            router.push('/roles');
        } else {
            await rolesApi.update(route.params.id, data);
            router.push('/roles');
        }
    } catch (e) { console.error(e); }
    finally { saving.value = false; }
}

async function deleteRole() {
    if (!confirm('Delete this role? This cannot be undone.')) return;
    try {
        await rolesApi.destroy(route.params.id);
        router.push('/roles');
    } catch (e) { console.error(e); }
}

async function removeUserRole(user) {
    if (!confirm(`Remove "${user.name}" from this role?`)) return;
    try {
        const remainingRoles = user.roles.filter(r => r.id !== role.value.id).map(r => r.name);
        await rolesApi.assignToUser({ user_id: user.id, roles: remainingRoles });
        users.value = users.value.filter(u => u.id !== user.id);
    } catch (e) { console.error(e); }
}

onMounted(async () => {
    await loadPermissions();
    if (!isNew.value) await loadRole();
    loading.value = false;
});
</script>
