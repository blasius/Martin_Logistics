<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-100 px-4">
        <div class="bg-white p-8 rounded-xl shadow-md w-full max-w-md">
            <template v-if="needsVerification">
                <h2 class="text-2xl font-bold mb-2 text-center text-gray-800">Verify Your Email</h2>
                <p class="text-sm text-gray-500 text-center mb-6">
                    Enter the 6-digit code sent to <strong>{{ verifyingEmail }}</strong>
                </p>
                <p class="text-xs text-amber-600 text-center mb-4 bg-amber-50 p-2 rounded">
                    Dev mode: check <code>storage/logs/laravel.log</code> for the code
                </p>
                <form @submit.prevent="submitCode">
                    <input v-model="code" type="text" maxlength="6" placeholder="000000"
                           class="border w-full p-3 rounded-lg text-center text-2xl tracking-[0.3em] font-mono focus:ring-2 focus:ring-indigo-500 outline-none mb-4"
                           required />
                    <p v-if="error" class="text-red-500 text-xs font-medium mb-3">{{ error }}</p>
                    <button type="submit" :disabled="verifying"
                            class="w-full bg-indigo-600 text-white p-3 rounded-lg font-semibold hover:bg-indigo-700 transition disabled:bg-indigo-300 mb-3">
                        {{ verifying ? 'Verifying...' : 'Verify Email' }}
                    </button>
                </form>
                <button @click="resend" :disabled="sending"
                        class="w-full text-sm text-indigo-600 hover:text-indigo-800 font-medium text-center">
                    Resend Code
                </button>
            </template>

            <template v-else>
                <h2 class="text-2xl font-bold mb-2 text-center text-gray-800">Customer Login</h2>
                <p class="text-sm text-gray-500 text-center mb-6">Welcome back to Martin Logistics</p>

                <form @submit.prevent="submitLogin">
                    <div class="mb-4">
                        <input v-model="email" type="email" placeholder="Email" :disabled="loading"
                               class="border w-full p-3 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none" required />
                    </div>
                    <div class="mb-6">
                        <input v-model="password" type="password" placeholder="Password" :disabled="loading"
                               class="border w-full p-3 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none" required />
                    </div>
                    <p v-if="error" class="text-red-500 text-xs font-medium mb-3">{{ error }}</p>
                    <button type="submit" :disabled="loading"
                            class="w-full bg-indigo-600 text-white p-3 rounded-lg font-semibold hover:bg-indigo-700 transition disabled:bg-indigo-300">
                        {{ loading ? 'Logging in...' : 'Log In' }}
                    </button>
                </form>

                <p class="text-sm text-gray-500 text-center mt-6">
                    Don't have an account?
                    <router-link to="/signup" class="text-indigo-600 hover:text-indigo-800 font-semibold">Sign up</router-link>
                </p>
            </template>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../store/authStore'

const router = useRouter()
const authStore = useAuthStore()

const email = ref('')
const password = ref('')
const loading = ref(false)
const error = ref('')

const needsVerification = ref(false)
const verifyingEmail = ref('')
const code = ref('')
const verifying = ref(false)
const sending = ref(false)

async function submitLogin() {
    loading.value = true
    error.value = ''
    try {
        const data = await authStore.login(email.value, password.value)
        if (data.requires_email_verification) {
            needsVerification.value = true
            verifyingEmail.value = data.email
            await authStore.resendVerification()
        } else {
            router.push('/dashboard')
        }
    } catch (e) {
        error.value = e.response?.data?.errors?.email?.[0] || e.response?.data?.message || 'Login failed. Please try again.'
    } finally {
        loading.value = false
    }
}

async function submitCode() {
    verifying.value = true
    error.value = ''
    try {
        await authStore.verifyEmail(code.value)
        await authStore.checkAuth()
        router.push('/dashboard')
    } catch (e) {
        error.value = e.response?.data?.message || 'Invalid code. Please try again.'
    } finally {
        verifying.value = false
    }
}

async function resend() {
    sending.value = true
    try {
        await authStore.resendVerification()
    } catch {} finally {
        sending.value = false
    }
}
</script>
