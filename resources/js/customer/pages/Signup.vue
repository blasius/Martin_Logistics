<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-100 px-4 py-10">
        <div class="bg-white p-8 rounded-xl shadow-md w-full max-w-lg">
            <template v-if="showVerification">
                <h2 class="text-2xl font-bold mb-2 text-center text-gray-800">Verify Your Email</h2>
                <p class="text-sm text-gray-500 text-center mb-2">
                    A 6-digit code was sent to <strong>{{ form.email }}</strong>
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
                <button @click="resendCode" :disabled="sending"
                        class="w-full text-sm text-indigo-600 hover:text-indigo-800 font-medium text-center">
                    Resend Code
                </button>
            </template>

            <template v-else>
                <h2 class="text-2xl font-bold mb-2 text-center text-gray-800">Create Account</h2>
                <p class="text-sm text-gray-500 text-center mb-6">Join Martin Logistics</p>

                <form @submit.prevent="submitSignup">
                    <div class="mb-4">
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Full Name *</label>
                        <input v-model="form.name" type="text" placeholder="Your name" :disabled="loading"
                               class="border w-full p-3 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none mt-1" required />
                    </div>
                    <div class="mb-4">
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Email *</label>
                        <input v-model="form.email" type="email" placeholder="you@example.com" :disabled="loading"
                               class="border w-full p-3 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none mt-1" required />
                    </div>
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Password *</label>
                            <input v-model="form.password" type="password" placeholder="Min 8 chars" :disabled="loading"
                                   class="border w-full p-3 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none mt-1" required />
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Confirm *</label>
                            <input v-model="form.password_confirmation" type="password" placeholder="Repeat password" :disabled="loading"
                                   class="border w-full p-3 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none mt-1" required />
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Account Type *</label>
                        <select v-model="form.type" :disabled="loading"
                                class="border w-full p-3 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none mt-1">
                            <option value="individual">Individual</option>
                            <option value="company">Company</option>
                        </select>
                    </div>
                    <div v-if="form.type === 'company'" class="mb-4">
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">TIN Number *</label>
                        <input v-model="form.tin" type="text" placeholder="Tax Identification Number" :disabled="loading"
                               class="border w-full p-3 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none mt-1" />
                    </div>
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Phone</label>
                            <input v-model="form.phone" type="text" placeholder="+250..." :disabled="loading"
                                   class="border w-full p-3 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none mt-1" />
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Contact Person</label>
                            <input v-model="form.contact_person" type="text" placeholder="Contact name" :disabled="loading"
                                   class="border w-full p-3 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none mt-1" />
                        </div>
                    </div>
                    <div class="mb-6">
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Address</label>
                        <input v-model="form.address" type="text" placeholder="Your address" :disabled="loading"
                               class="border w-full p-3 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none mt-1" />
                    </div>

                    <p v-if="error" class="text-red-500 text-xs font-medium mb-3">{{ error }}</p>

                    <button type="submit" :disabled="loading"
                            class="w-full bg-indigo-600 text-white p-3 rounded-lg font-semibold hover:bg-indigo-700 transition disabled:bg-indigo-300">
                        {{ loading ? 'Creating account...' : 'Create Account' }}
                    </button>
                </form>

                <p class="text-sm text-gray-500 text-center mt-6">
                    Already have an account?
                    <router-link to="/login" class="text-indigo-600 hover:text-indigo-800 font-semibold">Log in</router-link>
                </p>
            </template>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../store/authStore'

const router = useRouter()
const authStore = useAuthStore()

const form = reactive({
    name: '', email: '', password: '', password_confirmation: '',
    type: 'individual', tin: '', phone: '', contact_person: '', address: '',
})
const loading = ref(false)
const error = ref('')

const showVerification = ref(false)
const code = ref('')
const verifying = ref(false)
const sending = ref(false)

async function submitSignup() {
    loading.value = true
    error.value = ''
    try {
        await authStore.signup({ ...form })
        showVerification.value = true
    } catch (e) {
        const data = e.response?.data
        if (data?.errors) {
            const first = Object.values(data.errors)[0]
            error.value = Array.isArray(first) ? first[0] : first
        } else {
            error.value = data?.message || 'Signup failed. Please try again.'
        }
    } finally {
        loading.value = false
    }
}

async function submitCode() {
    verifying.value = true
    error.value = ''
    try {
        await authStore.verifyEmail(code.value)
        router.push('/dashboard')
    } catch (e) {
        error.value = e.response?.data?.message || 'Invalid code. Please try again.'
    } finally {
        verifying.value = false
    }
}

async function resendCode() {
    sending.value = true
    try {
        await authStore.resendVerification()
    } catch {} finally {
        sending.value = false
    }
}
</script>
