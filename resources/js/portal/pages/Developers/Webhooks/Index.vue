<template>
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-semibold">Webhook Subscriptions</h2>
            <button @click="openCreate" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">+ New Subscription</button>
        </div>

        <div class="bg-white rounded-lg shadow overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left">URL</th>
                        <th class="px-4 py-3 text-left">Events</th>
                        <th class="px-4 py-3 text-center">Active</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="sub in subscriptions" :key="sub.id" class="border-t hover:bg-gray-50">
                        <td class="px-4 py-3 max-w-xs truncate">{{ sub.url }}</td>
                        <td class="px-4 py-3">
                            <span v-for="ev in (sub.events || [])" :key="ev" class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-0.5 rounded mr-1">{{ ev }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span :class="sub.is_active ? 'text-green-600' : 'text-red-500'">{{ sub.is_active ? 'Active' : 'Inactive' }}</span>
                        </td>
                        <td class="px-4 py-3 text-right space-x-2">
                            <button @click="openEdit(sub)" class="text-blue-600 hover:underline text-xs">Edit</button>
                            <button @click="confirmDelete(sub)" class="text-red-600 hover:underline text-xs">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 w-full max-w-lg">
                <h3 class="text-lg font-semibold mb-4">{{ editing ? 'Edit' : 'Create' }} Subscription</h3>
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium">Webhook URL *</label>
                        <input v-model="form.url" class="w-full border rounded px-3 py-2 text-sm" placeholder="https://example.com/webhook" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Events</label>
                        <div class="space-y-1 max-h-48 overflow-y-auto">
                            <label v-for="ev in availableEvents" :key="ev" class="flex items-center gap-2 text-sm">
                                <input type="checkbox" :value="ev" v-model="form.events" class="rounded" />
                                {{ ev }}
                            </label>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end gap-2 mt-4">
                    <button @click="showModal = false" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded">Cancel</button>
                    <button @click="save" class="px-4 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">Save</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { webhooksApi } from '../../../api/webhooks'

const subscriptions = ref([])
const availableEvents = ref([])
const showModal = ref(false)
const editing = ref(null)

const form = ref({ url: '', events: [] })

async function load() {
    const [subRes, evRes] = await Promise.all([
        webhooksApi.subscriptions(),
        webhooksApi.events().catch(() => ({ data: [] }))
    ])
    subscriptions.value = subRes.data
    availableEvents.value = evRes.data
}

function openCreate() {
    editing.value = null
    form.value = { url: '', events: [] }
    showModal.value = true
}

function openEdit(sub) {
    editing.value = sub
    form.value = { url: sub.url, events: [...(sub.events || [])] }
    showModal.value = true
}

async function save() {
    if (editing.value) {
        await webhooksApi.updateSubscription(editing.value.id, form.value)
    } else {
        await webhooksApi.storeSubscription(form.value)
    }
    showModal.value = false
    load()
}

async function confirmDelete(sub) {
    if (confirm('Delete webhook subscription for ' + sub.url + '?')) {
        await webhooksApi.deleteSubscription(sub.id)
        load()
    }
}

onMounted(load)
</script>
