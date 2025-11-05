<template>
    <div class="max-w-3xl mx-auto mt-10 bg-white p-6 rounded-xl shadow">
        <h1 class="text-2xl font-bold mb-4">Verify Your Contacts</h1>

        <!-- Progress Bar -->
        <div class="flex items-center justify-between mb-6">
            <div v-for="step in steps" :key="step.type" class="flex flex-col items-center">
                <div
                    :class="[
            'w-8 h-8 rounded-full flex items-center justify-center text-white',
            step.verified ? 'bg-green-500' : 'bg-gray-400'
          ]"
                >
                    <span v-if="step.verified">✔</span>
                    <span v-else>{{ step.index }}</span>
                </div>
                <span class="text-sm mt-1">{{ step.label }}</span>
            </div>
        </div>

        <!-- Contact List -->
        <div v-for="contact in contacts" :key="contact.id" class="border-b py-3">
            <p class="font-semibold">{{ contact.type }}: {{ contact.value }}</p>
            <p class="text-sm text-gray-500">
                Status: <span :class="contact.verified_at ? 'text-green-600' : 'text-red-600'">
          {{ contact.verified_at ? 'Verified' : 'Unverified' }}
        </span>
            </p>

            <div v-if="!contact.verified_at" class="mt-2 flex gap-2">
                <button
                    @click="sendCode(contact.id)"
                    class="bg-blue-500 text-white px-3 py-1 rounded"
                >Send Code</button>

                <input
                    v-model="verificationCode"
                    placeholder="Enter code"
                    class="border p-1 rounded"
                />

                <button
                    @click="verify(contact.id)"
                    class="bg-green-500 text-white px-3 py-1 rounded"
                >Verify</button>
            </div>
        </div>

        <!-- Add new contact -->
        <div class="mt-6">
            <h3 class="text-lg font-semibold mb-2">Add New Contact</h3>
            <div class="flex gap-2">
                <select v-model="newType" class="border rounded p-2">
                    <option disabled value="">Select type</option>
                    <option value="email">Email</option>
                    <option value="phone">Phone</option>
                    <option value="whatsapp">WhatsApp</option>
                </select>
                <input
                    v-model="newValue"
                    placeholder="Enter contact"
                    class="border rounded p-2 flex-grow"
                />
                <button
                    @click="addContact"
                    class="bg-blue-600 text-white px-4 py-2 rounded"
                >Add</button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { api } from '@/plugins/axios.js' // ✅ your pre-configured axios instance (with auth & baseURL)
import { useAuthStore } from '../store'

// reactive state
const contacts = ref([])
const verificationCode = ref('')
const newType = ref('')
const newValue = ref('')
const error = ref(null)

// Computed breadcrumb-style progress steps
const steps = computed(() =>
    ['email', 'phone', 'whatsapp'].map((type, i) => {
        const c = contacts.value.find(ct => ct.type === type)
        return {
            type,
            index: i + 1,
            label: type.charAt(0).toUpperCase() + type.slice(1),
            verified: !!c?.verified_at,
        }
    })
)

// ✅ Load all user contacts
async function fetchContacts() {
    try {
        const { data } = await api.get('/api/contacts')
        contacts.value = data
    } catch (err) {
        console.error('Fetch contacts failed:', err)
        error.value = err.response?.data?.message || 'Failed to load contacts'
    }
}

// ✅ Add a new contact (email / phone / WhatsApp)
async function addContact() {
    if (!newType.value || !newValue.value) {
        alert('Please fill in both fields.')
        return
    }

    try {
        await api.post('/api/contacts', {
            type: newType.value,
            value: newValue.value,
        })
        newType.value = ''
        newValue.value = ''
        await fetchContacts()
    } catch (err) {
        console.error('Add contact failed:', err)
        error.value = err.response?.data?.message || 'Failed to add contact'
    }
}

// ✅ Send verification code to selected contact
async function sendCode(id) {
    try {
        await api.post(`/api/contacts/${id}/send-code`)
        alert('Verification code sent!')
    } catch (err) {
        console.error('Send code failed:', err)
        error.value = err.response?.data?.message || 'Failed to send verification code'
    }
}

// ✅ Verify contact using code
async function verify(id) {
    if (!verificationCode.value) {
        alert('Enter your verification code first.')
        return
    }

    try {
        await api.post(`/api/contacts/${id}/verify`, {
            code: verificationCode.value,
        })
        alert('Contact verified successfully!')
        verificationCode.value = ''
        await fetchContacts()
    } catch (err) {
        console.error('Verify contact failed:', err)
        error.value = err.response?.data?.message || 'Verification failed'
    }
}

onMounted(fetchContacts)
</script>
