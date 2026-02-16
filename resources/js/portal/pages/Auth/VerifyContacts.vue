<template>
    <div class="max-w-xl mx-auto py-10">
        <h2 class="text-2xl font-semibold mb-6">Verify Your Contacts</h2>

        <div v-for="step in steps" :key="step.type" class="mb-6 border p-4 rounded-lg">
            <h3 class="font-semibold text-lg mb-3 capitalize">{{ step.label }}</h3>

            <div v-if="step.verified" class="text-green-600">
                ✅ Verified
            </div>

            <div v-else>
                <div class="flex space-x-2">
                    <input v-model="formValues[step.type]" type="text" placeholder="Enter {{ step.label }}" class="border p-2 rounded w-full" />
                    <button @click="addContact(step.type)" class="bg-blue-600 text-white px-3 py-1 rounded">Add</button>
                </div>

                <div v-if="contactFor(step.type)">
                    <button @click="sendCode(contactFor(step.type).id, step.type)" class="mt-2 bg-yellow-500 text-white px-3 py-1 rounded">Send Code</button>

                    <div class="mt-2 flex space-x-2">
                        <input v-model="verificationCode" type="text" placeholder="Enter code" class="border p-2 rounded w-full" />
                        <button @click="verify(contactFor(step.type).id)" class="bg-green-600 text-white px-3 py-1 rounded">Verify</button>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="isFullyVerified" class="bg-green-100 p-4 rounded-lg text-center font-semibold">
            🎉 All contacts verified!
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { api } from '@/plugins/axios.js'

const contacts = ref([])
const verificationCode = ref('')
const formValues = ref({ email: '', phone: '', whatsapp: '' })

const fetchContacts = async () => {
    const res = await api.get('/contacts')
    contacts.value = res.data
}

const contactFor = type => contacts.value.find(c => c.type === type)

const steps = computed(() =>
    ['email', 'phone', 'whatsapp'].map(type => ({
        type,
        label: type,
        verified: !!contactFor(type)?.verified_at
    }))
)

const isFullyVerified = computed(() =>
    steps.value.every(s => s.verified)
)

const addContact = async type => {
    await api.post('/api/contacts', { type, value: formValues.value[type] })
    await fetchContacts()
}

const sendCode = async (id, type) => {
    const endpoint =
        type === 'whatsapp'
            ? `/api/contacts/${id}/whatsapp-send`
            : `/api/contacts/${id}/send-code`

    await api.post(endpoint)
    alert(`Verification code sent via ${type}`)
}

const verify = async id => {
    await api.post(`/api/contacts/${id}/verify`, { code: verificationCode.value })
    verificationCode.value = ''
    await fetchContacts()
}

onMounted(fetchContacts)
</script>
