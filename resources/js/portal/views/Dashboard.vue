<template>
    <div class="p-6">
        <h1 class="text-2xl font-semibold mb-4">Welcome, {{ user?.name || 'User' }}</h1>
        <p>Your verified contacts:</p>
        <ul>
            <li v-for="c in contacts" :key="c.id">
                {{ c.type }} - {{ c.value }}
                <span v-if="c.verified_at" class="text-green-600">(verified)</span>
                <span v-else class="text-red-500">(pending)</span>
            </li>
        </ul>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from '../api'
import { useAuthStore } from '../store'

const store = useAuthStore()
const user = store.user
const contacts = ref([])

onMounted(async () => {
    const { data } = await axios.get('/contacts')
    contacts.value = data
})
</script>
