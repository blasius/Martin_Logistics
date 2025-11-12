<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-100">
        <form @submit.prevent="submit" class="bg-white p-8 rounded shadow w-96">
            <h2 class="text-2xl font-semibold mb-4 text-center">Portal Login</h2>

            <input v-model="identifier" type="text" placeholder="Email or Phone"
                   class="border w-full mb-3 p-2 rounded" />
            <input v-model="password" type="password" placeholder="Password"
                   class="border w-full mb-3 p-2 rounded" />

            <button type="submit"
                    class="w-full bg-blue-600 text-white p-2 rounded hover:bg-blue-700">
                Log In
            </button>

            <p v-if="error" class="text-red-500 mt-3 text-center">{{ error }}</p>
        </form>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { useAuthStore } from '../../store.js'
import { useRouter } from 'vue-router'

const identifier = ref('')
const password = ref('')
const error = ref('')
const router = useRouter()
const store = useAuthStore()

const submit = async () => {
    try {
        error.value = ''
        await store.login(identifier.value, password.value)
        router.push('/dashboard')
    } catch (e) {
        error.value = e.response?.data?.message || 'Login failed'
    }
}
</script>
