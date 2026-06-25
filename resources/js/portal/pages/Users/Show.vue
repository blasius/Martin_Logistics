<template>
    <div class="p-6 max-w-4xl mx-auto">
        <div class="flex items-center gap-2 text-sm text-slate-500 mb-4">
            <a @click="$router.push('/users')" class="hover:text-blue-600 cursor-pointer">Users</a>
            <span>/</span>
            <span class="text-slate-800 font-medium">{{ isNew ? 'New User' : user?.name }}</span>
        </div>

        <div v-if="loading" class="text-center py-12 text-slate-400">Loading user...</div>

        <template v-else>
            <div class="bg-white rounded-xl border border-slate-200 p-6 mb-6">
                <h2 class="text-lg font-bold mb-4">{{ isNew ? 'Create User' : 'Edit User' }}</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Name</label>
                        <input v-model="form.name" type="text" required
                               class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                        <input v-model="form.email" type="email" required
                               class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        Password {{ isNew ? '' : '(leave blank to keep current)' }}
                    </label>
                    <input v-model="form.password" type="password"
                           class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none"
                           :required="isNew">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Roles</label>
                    <div class="flex flex-wrap gap-2">
                        <label v-for="role in allRoles" :key="role"
                               class="flex items-center gap-2 text-sm px-3 py-1.5 border border-slate-200 rounded-lg cursor-pointer hover:bg-slate-50"
                               :class="{ 'border-blue-300 bg-blue-50': selectedRoles.includes(role) }">
                            <input type="checkbox" :value="role" v-model="selectedRoles"
                                   class="rounded border-slate-300">
                            {{ role }}
                        </label>
                    </div>
                    <p v-if="!allRoles.length" class="text-xs text-slate-400 mt-1">No roles available. Create roles first.</p>
                </div>

                <div v-if="!isNew && selectedRoles.length" class="mb-4 bg-slate-50 rounded-lg p-3">
                    <p class="text-xs font-semibold text-slate-600 mb-1">Effective Permissions Preview</p>
                    <div class="text-xs text-slate-500">
                        <span v-for="(perms, group) in effectivePermissions" :key="group" class="block mb-1">
                            <span class="font-medium capitalize">{{ group }}:</span>
                            {{ perms.join(', ') || 'none' }}
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button @click="save" :disabled="saving"
                            class="bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white px-6 py-2 rounded-lg text-sm">
                        {{ saving ? 'Saving...' : (isNew ? 'Create User' : 'Save Changes') }}
                    </button>
                    <button @click="$router.push('/users')"
                            class="border border-slate-200 text-slate-600 hover:bg-slate-50 px-6 py-2 rounded-lg text-sm">
                        Cancel
                    </button>
                    <button v-if="!isNew" @click="deleteUser"
                            class="ml-auto text-red-500 hover:text-red-700 text-sm">Delete User</button>
                </div>
            </div>
        </template>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { usersApi } from '../../api/users';
import { rolesApi } from '../../api/roles';

const route = useRoute();
const router = useRouter();

const isNew = computed(() => route.path.endsWith('/create'));
const loading = ref(true);
const saving = ref(false);
const user = ref(null);
const allRoles = ref([]);
const allPermissions = ref([]);
const selectedRoles = ref([]);

const form = ref({ name: '', email: '', password: '' });

const effectivePermissions = computed(() => {
    const result = {};
    const selectedRoleNames = selectedRoles.value;
    const rolePerms = allPermissions.value.filter(p =>
        selectedRoleNames.includes(p.roles?.[0]?.name)
    );
    return allPermissions.value.reduce((acc, p) => {
        const group = p.group || 'general';
        if (!acc[group]) acc[group] = [];
        return acc;
    }, {});
});

watch(selectedRoles, async () => {
    if (isNew.value) return;
    if (!selectedRoles.value.length) return;
    try {
        const res = await rolesApi.permissionsGrouped();
        allPermissions.value = Object.values(res.data).flat();
    } catch (e) { /* ignore */ }
});

async function loadUser() {
    try {
        const [userRes, rolesRes] = await Promise.all([
            usersApi.get(route.params.id),
            usersApi.rolesList(),
        ]);
        user.value = userRes.data;
        allRoles.value = rolesRes.data;
        form.value.name = userRes.data.name;
        form.value.email = userRes.data.email;
        selectedRoles.value = userRes.data.roles?.map(r => r.name) || [];
    } catch (e) { console.error(e); }
}

async function loadForm() {
    try {
        const rolesRes = await usersApi.rolesList();
        allRoles.value = rolesRes.data;
    } catch (e) { console.error(e); }
}

async function save() {
    saving.value = true;
    try {
        const data = { ...form.value, roles: selectedRoles.value };
        if (!data.password) delete data.password;

        if (isNew.value) {
            await usersApi.create(data);
        } else {
            await usersApi.update(route.params.id, data);
        }
        router.push('/users');
    } catch (e) { console.error(e); }
    finally { saving.value = false; }
}

async function deleteUser() {
    if (!confirm('Delete this user? This cannot be undone.')) return;
    try {
        await usersApi.destroy(route.params.id);
        router.push('/users');
    } catch (e) { console.error(e); }
}

onMounted(async () => {
    loading.value = true;
    if (isNew.value) {
        await loadForm();
    } else {
        await loadUser();
    }
    loading.value = false;
});
</script>
