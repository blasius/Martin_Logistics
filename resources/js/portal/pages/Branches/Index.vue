<template>
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-semibold">Branches</h2>
            <button @click="openCreate" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">+ New Branch</button>
        </div>

        <div class="bg-white rounded-lg shadow overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left">Code</th>
                        <th class="px-4 py-3 text-left">Name</th>
                        <th class="px-4 py-3 text-left">Phone</th>
                        <th class="px-4 py-3 text-left">Email</th>
                        <th class="px-4 py-3 text-center">Active</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="branch in branches" :key="branch.id" class="border-t hover:bg-gray-50">
                        <td class="px-4 py-3 font-mono">{{ branch.code }}</td>
                        <td class="px-4 py-3">{{ branch.name }}</td>
                        <td class="px-4 py-3">{{ branch.phone || '-' }}</td>
                        <td class="px-4 py-3">{{ branch.email || '-' }}</td>
                        <td class="px-4 py-3 text-center">
                            <span :class="branch.is_active ? 'text-green-600' : 'text-red-500'">{{ branch.is_active ? 'Yes' : 'No' }}</span>
                        </td>
                        <td class="px-4 py-3 text-right space-x-2">
                            <button @click="assignUsers(branch)" class="text-blue-600 hover:underline text-xs">Users</button>
                            <button @click="openEdit(branch)" class="text-blue-600 hover:underline text-xs">Edit</button>
                            <button @click="confirmDelete(branch)" class="text-red-600 hover:underline text-xs">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 w-full max-w-lg">
                <h3 class="text-lg font-semibold mb-4">{{ editing ? 'Edit' : 'Create' }} Branch</h3>
                <div class="space-y-3">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium">Code *</label>
                            <input v-model="form.code" class="w-full border rounded px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Name *</label>
                            <input v-model="form.name" class="w-full border rounded px-3 py-2 text-sm" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Address</label>
                        <input v-model="form.address" class="w-full border rounded px-3 py-2 text-sm" />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium">Phone</label>
                            <input v-model="form.phone" class="w-full border rounded px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Email</label>
                            <input v-model="form.email" class="w-full border rounded px-3 py-2 text-sm" />
                        </div>
                    </div>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" v-model="form.is_active" class="rounded" />
                        <span class="text-sm">Active</span>
                    </label>
                </div>
                <div class="flex justify-end gap-2 mt-4">
                    <button @click="showModal = false" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded">Cancel</button>
                    <button @click="save" class="px-4 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">Save</button>
                </div>
            </div>
        </div>

        <div v-if="showUsersModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 w-full max-w-lg">
                <h3 class="text-lg font-semibold mb-4">Assign Users to {{ selectedBranch?.name }}</h3>
                <div class="max-h-64 overflow-y-auto space-y-2">
                    <label v-for="user in allUsers" :key="user.id" class="flex items-center gap-2 p-2 hover:bg-gray-50 rounded">
                        <input type="checkbox" :value="user.id" v-model="selectedUserIds" class="rounded" />
                        <span class="text-sm">{{ user.name }} ({{ user.email }})</span>
                    </label>
                </div>
                <div class="flex justify-end gap-2 mt-4">
                    <button @click="showUsersModal = false" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded">Cancel</button>
                    <button @click="saveUsers" class="px-4 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">Assign</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { branchesApi } from '../../api/branches'
import { usersApi } from '../../api/users'

const branches = ref([])
const allUsers = ref([])
const showModal = ref(false)
const showUsersModal = ref(false)
const editing = ref(null)
const selectedBranch = ref(null)
const selectedUserIds = ref([])

const form = ref({ code: '', name: '', address: '', phone: '', email: '', is_active: true })

async function load() {
    const { data } = await branchesApi.index()
    branches.value = data
}

function openCreate() {
    editing.value = null
    form.value = { code: '', name: '', address: '', phone: '', email: '', is_active: true }
    showModal.value = true
}

function openEdit(branch) {
    editing.value = branch
    form.value = { ...branch }
    showModal.value = true
}

async function save() {
    if (editing.value) {
        await branchesApi.update(editing.value.id, form.value)
    } else {
        await branchesApi.store(form.value)
    }
    showModal.value = false
    load()
}

async function confirmDelete(branch) {
    if (confirm('Delete branch ' + branch.name + '?')) {
        await branchesApi.destroy(branch.id)
        load()
    }
}

async function assignUsers(branch) {
    selectedBranch.value = branch
    try {
        const { data } = await usersApi.getAll()
        allUsers.value = data
        const { data: assigned } = await branchesApi.users(branch.id)
        selectedUserIds.value = assigned.map(u => u.id)
    } catch (e) {
        allUsers.value = []
    }
    showUsersModal.value = true
}

async function saveUsers() {
    await branchesApi.assignUsers(selectedBranch.value.id, selectedUserIds.value)
    showUsersModal.value = false
}

onMounted(load)
</script>
