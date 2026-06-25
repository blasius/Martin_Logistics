<template>
    <div class="p-6 bg-slate-50 min-h-screen space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-black text-slate-800 tracking-tight uppercase">Role Builder</h1>
                <p class="text-xs font-bold text-slate-400">Create &amp; manage roles with granular permissions</p>
            </div>
            <button @click="openCreate" class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl shadow-lg font-black text-xs flex items-center gap-2 uppercase">
                <Plus class="w-4 h-4" /> New Role
            </button>
        </div>

        <div v-if="loading" class="text-center py-20"><p class="text-slate-400 font-bold text-xs uppercase">Loading...</p></div>

        <div v-else>
            <div class="grid gap-4">
                <div v-for="role in roles" :key="role.id" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-5 flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-3">
                                <h3 class="font-black text-slate-800 text-sm uppercase">{{ role.name }}</h3>
                                <span v-if="role.is_super_admin" class="text-[9px] font-black bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full uppercase">Super Admin</span>
                                <span v-if="!role.is_active" class="text-[9px] font-black bg-slate-100 text-slate-500 px-2 py-0.5 rounded-full uppercase">Inactive</span>
                            </div>
                            <p v-if="role.description" class="text-xs text-slate-500 mt-1">{{ role.description }}</p>
                            <div class="flex items-center gap-4 mt-2 text-[10px] font-bold text-slate-400">
                                <span>{{ role.users_count || 0 }} user(s)</span>
                                <span>{{ role.permissions?.length || 0 }} permission(s)</span>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button @click="openEdit(role)" class="p-2 text-slate-400 hover:text-indigo-600" title="Edit">
                                <Pencil class="w-4 h-4" />
                            </button>
                            <button @click="confirmDelete(role)" class="p-2 text-slate-400 hover:text-rose-600" title="Delete">
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                    <div v-if="role.permissions?.length" class="px-5 pb-4 flex flex-wrap gap-1.5">
                        <span v-for="p in role.permissions" :key="p.id" class="text-[9px] font-bold bg-indigo-50 text-indigo-600 px-2 py-0.5 rounded-full">{{ p.name }}</span>
                    </div>
                </div>
            </div>

            <div class="mt-6 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-slate-100">
                    <h3 class="font-black text-slate-800 uppercase text-sm">Assign Role to User</h3>
                </div>
                <div class="p-5">
                    <div class="flex gap-3 items-end">
                        <div class="flex-1">
                            <label class="text-[10px] font-black text-slate-400 uppercase">User</label>
                            <select v-model="assignForm.user_id" class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                                <option value="">Select user</option>
                                <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }} ({{ u.email }})</option>
                            </select>
                        </div>
                        <div class="flex-1">
                            <label class="text-[10px] font-black text-slate-400 uppercase">Role(s)</label>
                            <select v-model="assignForm.role_ids" multiple class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold min-h-[80px]">
                                <option v-for="r in roles" :key="r.id" :value="r.id">{{ r.name }}</option>
                            </select>
                        </div>
                        <button @click="assignRoles" :disabled="!assignForm.user_id" class="px-4 py-2.5 bg-indigo-600 text-white rounded-xl font-black text-xs uppercase disabled:opacity-50 h-fit">Assign</button>
                    </div>
                </div>
            </div>

            <div class="mt-6 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex justify-between items-center">
                    <h3 class="font-black text-slate-800 uppercase text-sm">Audit Log</h3>
                </div>
                <div v-if="auditLogs.length" class="divide-y divide-slate-50">
                    <div v-for="log in auditLogs" :key="log.id" class="p-4 flex items-start gap-3 text-xs">
                        <div class="w-2 h-2 rounded-full bg-indigo-400 mt-1 shrink-0"></div>
                        <div>
                            <p class="text-slate-700"><span class="font-bold">{{ log.admin?.name }}</span> {{ log.action }} on {{ log.target_type }} #{{ log.target_id }}</p>
                            <p class="text-[10px] text-slate-400 mt-0.5">{{ formatDate(log.created_at) }}</p>
                        </div>
                    </div>
                </div>
                <div v-else class="p-5 text-center text-xs text-slate-400">No audit entries yet.</div>
            </div>
        </div>

        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" @click.self="showModal = false">
            <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full border border-slate-200 max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center">
                    <h3 class="font-black text-slate-800 uppercase text-sm">{{ editing ? 'Edit Role' : 'New Role' }}</h3>
                    <button @click="showModal = false" class="text-slate-400 hover:text-slate-600"><X class="w-5 h-5" /></button>
                </div>
                <form @submit.prevent="saveRole" class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase">Role Name *</label>
                            <input v-model="roleForm.name" type="text" required class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase">Slug</label>
                            <input v-model="roleForm.slug" type="text" class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                        </div>
                    </div>
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase">Description</label>
                        <textarea v-model="roleForm.description" class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold"></textarea>
                    </div>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 text-xs font-bold text-slate-600">
                            <input v-model="roleForm.is_super_admin" type="checkbox"> Super Admin (bypasses all checks)
                        </label>
                        <label class="flex items-center gap-2 text-xs font-bold text-slate-600">
                            <input v-model="roleForm.is_active" type="checkbox"> Active
                        </label>
                    </div>
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase">Permissions</label>
                        <div class="mt-2 max-h-64 overflow-y-auto border border-slate-200 rounded-xl p-3 space-y-3">
                            <div v-for="(perms, group) in groupedPermissions" :key="group">
                                <p class="text-[10px] font-black text-slate-500 uppercase mb-1">{{ group }}</p>
                                <div class="flex flex-wrap gap-2">
                                    <label v-for="p in perms" :key="p.name" class="flex items-center gap-1.5 text-xs text-slate-700 cursor-pointer">
                                        <input type="checkbox" :value="p.name" v-model="roleForm.selectedPermissions" class="rounded">
                                        {{ p.name }}
                                    </label>
                                </div>
                            </div>
                            <p v-if="!Object.keys(groupedPermissions).length" class="text-xs text-slate-400">No permissions loaded</p>
                        </div>
                    </div>
                    <button type="submit" class="w-full py-3 bg-indigo-600 text-white rounded-xl font-black text-xs uppercase">
                        {{ editing ? 'Update Role' : 'Create Role' }}
                    </button>
                </form>
            </div>
        </div>

        <div v-if="deleteTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" @click.self="deleteTarget = null">
            <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full border border-slate-200 p-6 text-center">
                <AlertTriangle class="w-10 h-10 mx-auto text-rose-500 mb-4" />
                <h3 class="font-black text-slate-800 uppercase text-sm mb-2">Delete Role?</h3>
                <p class="text-xs text-slate-500 mb-6">{{ deleteTarget.name }}</p>
                <div class="flex gap-3 justify-center">
                    <button @click="deleteTarget = null" class="px-4 py-2 bg-slate-100 rounded-xl text-xs font-black text-slate-600 uppercase">Cancel</button>
                    <button @click="doDelete" class="px-4 py-2 bg-rose-600 text-white rounded-xl text-xs font-black uppercase">Delete</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { rolesApi } from '../../api/roles';

const roles = ref([]);
const users = ref([]);
const loading = ref(true);
const showModal = ref(false);
const editing = ref(null);
const deleteTarget = ref(null);
const auditLogs = ref([]);
const permissionsList = ref([]);
const allPermissionsGrouped = ref({});
const roleForm = ref(emptyForm());
const assignForm = ref({ user_id: '', role_ids: [] });

function emptyForm() {
    return { name: '', slug: '', description: '', is_super_admin: false, is_active: true, selectedPermissions: [] };
}

const groupedPermissions = computed(() => {
    if (Object.keys(allPermissionsGrouped.value).length) {
        return allPermissionsGrouped.value;
    }
    const grouped = {};
    for (const p of permissionsList.value) {
        const group = p.split('_')[0] || 'general';
        if (!grouped[group]) grouped[group] = [];
        grouped[group].push({ name: p });
    }
    return grouped;
});

async function load() {
    loading.value = true;
    try {
        const [rolesRes, permGroupedRes, auditRes, usersRes] = await Promise.all([
            rolesApi.getAll(),
            rolesApi.permissionsGrouped(),
            rolesApi.auditLog(),
            fetchUsers(),
        ]);
        roles.value = rolesRes.data || [];
        allPermissionsGrouped.value = permGroupedRes.data || {};
        auditLogs.value = auditRes.data?.data || auditRes.data || [];
        users.value = usersRes || [];
    } catch (e) { console.error(e); }
    finally { loading.value = false; }
}

async function fetchUsers() {
    try {
        const { api } = await import('../../../plugins/axios');
        const res = await api.get('/portal/users');
        return res.data?.data || res.data || [];
    } catch { return []; }
}

function openCreate() {
    editing.value = null;
    roleForm.value = emptyForm();
    showModal.value = true;
}

function openEdit(role) {
    editing.value = role;
    roleForm.value = {
        name: role.name,
        slug: role.slug || '',
        description: role.description || '',
        is_super_admin: role.is_super_admin || false,
        is_active: role.is_active ?? true,
        selectedPermissions: role.permissions?.map(p => p.name) || [],
    };
    showModal.value = true;
}

async function saveRole() {
    try {
        const data = {
            name: roleForm.value.name,
            slug: roleForm.value.slug || undefined,
            description: roleForm.value.description || undefined,
            is_super_admin: roleForm.value.is_super_admin,
            is_active: roleForm.value.is_active,
            permissions: roleForm.value.selectedPermissions,
        };
        if (editing.value) {
            await rolesApi.update(editing.value.id, data);
        } else {
            await rolesApi.store(data);
        }
        showModal.value = false;
        await load();
    } catch (e) { console.error(e); }
}

function confirmDelete(role) { deleteTarget.value = role; }

async function doDelete() {
    if (!deleteTarget.value) return;
    try {
        await rolesApi.destroy(deleteTarget.value.id);
        deleteTarget.value = null;
        await load();
    } catch (e) { console.error(e); }
}

async function assignRoles() {
    if (!assignForm.value.user_id || !assignForm.value.role_ids.length) return;
    try {
        await rolesApi.assignToUser({
            user_id: assignForm.value.user_id,
            role_ids: assignForm.value.role_ids,
        });
        assignForm.value = { user_id: '', role_ids: [] };
        await load();
    } catch (e) { console.error(e); }
}

function formatDate(d) {
    if (!d) return '';
    return new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

onMounted(load);
</script>
