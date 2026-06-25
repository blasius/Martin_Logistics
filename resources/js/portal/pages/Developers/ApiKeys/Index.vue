<template>
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-semibold">Your API Keys</h2>
            <button @click="showCreate = true" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">+ Generate Key</button>
        </div>

        <div class="bg-white rounded-lg shadow overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left">Name</th>
                        <th class="px-4 py-3 text-left">Created</th>
                        <th class="px-4 py-3 text-left">Last Used</th>
                        <th class="px-4 py-3 text-center">Active</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="key in keys" :key="key.id" class="border-t hover:bg-gray-50">
                        <td class="px-4 py-3">{{ key.name }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ key.created_at?.substring(0, 10) }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ key.last_used_at?.substring(0, 10) || 'Never' }}</td>
                        <td class="px-4 py-3 text-center">
                            <span :class="key.is_active ? 'text-green-600' : 'text-red-500'">{{ key.is_active ? 'Active' : 'Revoked' }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button v-if="key.is_active" @click="revoke(key)" class="text-red-600 hover:underline text-xs">Revoke</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="showCreate" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 w-full max-w-md">
                <h3 class="text-lg font-semibold mb-4">Generate API Key</h3>
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium">Key Name</label>
                        <input v-model="newKeyName" class="w-full border rounded px-3 py-2 text-sm" placeholder="e.g. Production" />
                    </div>
                </div>
                <div v-if="generatedKey" class="mt-3 p-3 bg-yellow-50 border rounded text-sm">
                    <p class="font-medium text-yellow-800">Copy this key now. You won't see it again.</p>
                    <code class="block mt-1 p-2 bg-white border rounded text-xs break-all">{{ generatedKey }}</code>
                </div>
                <div class="flex justify-end gap-2 mt-4">
                    <button @click="showCreate = false; generatedKey = null" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded">Close</button>
                    <button v-if="!generatedKey" @click="generate" class="px-4 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">Generate</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { apiKeysApi } from '../../../api/apiKeys'

const keys = ref([])
const showCreate = ref(false)
const newKeyName = ref('')
const generatedKey = ref(null)

async function load() {
    const { data } = await apiKeysApi.index()
    keys.value = data
}

async function generate() {
    if (!newKeyName.value) return
    const { data } = await apiKeysApi.store({ name: newKeyName.value })
    generatedKey.value = data.plain_text_key
    load()
}

async function revoke(key) {
    if (confirm('Revoke key "' + key.name + '"?')) {
        await apiKeysApi.destroy(key.id)
        load()
    }
}

onMounted(load)
</script>
