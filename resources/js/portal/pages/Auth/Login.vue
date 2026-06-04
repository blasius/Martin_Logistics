<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-100 px-4">
        <div class="bg-white p-8 rounded shadow-md w-full max-w-md">
            <template v-if="!store.requiresTwoFactor">
                <h2 class="text-2xl font-semibold mb-6 text-center text-gray-800">Portal Login</h2>

                <form @submit.prevent="submit">
                    <div class="mb-4">
                        <input v-model="identifier"
                               type="text"
                               placeholder="Email or Phone"
                               :disabled="loading"
                               class="border w-full p-3 rounded focus:ring-2 focus:ring-blue-500 outline-none"
                               required />
                    </div>

                    <div class="mb-6">
                        <input v-model="password"
                               type="password"
                               placeholder="Password"
                               :disabled="loading"
                               class="border w-full p-3 rounded focus:ring-2 focus:ring-blue-500 outline-none"
                               required />
                    </div>

                    <button type="submit"
                            :disabled="loading"
                            class="w-full bg-blue-600 text-white p-3 rounded font-medium hover:bg-blue-700 transition disabled:bg-blue-300">
                        <span v-if="loading">Authenticating...</span>
                        <span v-else>Log In</span>
                    </button>
                </form>
            </template>

            <template v-else>
                <h2 class="text-2xl font-semibold mb-2 text-center text-gray-800">Two-Factor Authentication</h2>
                <p class="text-sm text-gray-500 text-center mb-6">Enter the code from your authenticator app</p>

                <form @submit.prevent="submitTwoFactor">
                    <div class="mb-6">
                        <input v-model="twoFactorCode"
                               type="text"
                               inputmode="numeric"
                               autocomplete="one-time-code"
                               placeholder="000 000"
                               maxlength="6"
                               :disabled="loading"
                               class="border w-full p-3 rounded focus:ring-2 focus:ring-blue-500 outline-none text-center text-2xl tracking-[0.5em] font-mono"
                               required />
                    </div>

                    <button type="submit"
                            :disabled="loading"
                            class="w-full bg-blue-600 text-white p-3 rounded font-medium hover:bg-blue-700 transition disabled:bg-blue-300">
                        <span v-if="loading">Verifying...</span>
                        <span v-else>Verify Code</span>
                    </button>
                </form>

                <div class="mt-6 pt-6 border-t border-gray-100">
                    <p class="text-xs text-gray-400 text-center mb-3">Lost access to your authenticator?</p>
                    <button @click="showRecovery = true"
                            class="w-full text-sm text-blue-600 hover:text-blue-800 font-medium text-center">
                        Use a Recovery Code
                    </button>
                </div>

                <div v-if="showRecovery" class="mt-4 pt-4 border-t border-gray-100">
                    <form @submit.prevent="submitRecovery">
                        <div class="mb-4">
                            <input v-model="recoveryCode"
                                   type="text"
                                   placeholder="Recovery code"
                                   :disabled="loading"
                                   class="border w-full p-3 rounded focus:ring-2 focus:ring-blue-500 outline-none text-center text-sm font-mono"
                                   required />
                        </div>
                        <button type="submit"
                                :disabled="loading"
                                class="w-full bg-amber-600 text-white p-3 rounded font-medium hover:bg-amber-700 transition disabled:bg-amber-300">
                            Recover Account
                        </button>
                    </form>
                </div>

                <button @click="goBack" :disabled="loading"
                        class="w-full text-sm text-gray-500 hover:text-gray-700 font-medium text-center mt-4">
                    &larr; Back to Login
                </button>
            </template>

            <p v-if="error" class="text-red-500 mt-4 text-sm text-center font-medium bg-red-50 p-2 rounded">
                {{ error }}
            </p>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { useAuthStore } from '../../store/authStore'
import { useRouter } from 'vue-router'

const identifier = ref('')
const password = ref('')
const twoFactorCode = ref('')
const recoveryCode = ref('')
const error = ref('')
const loading = ref(false)
const showRecovery = ref(false)

const router = useRouter()
const store = useAuthStore()

const submit = async () => {
    try {
        error.value = ''
        loading.value = true
        await store.login(identifier.value, password.value)

        if (store.requiresTwoFactor) return

        if (store.requiresSetup) {
            router.push('/2fa/setup')
            return
        }

        router.push('/dashboard')
    } catch (e) {
        if (e.response) {
            error.value = e.response.data.message || 'Invalid credentials.'
        } else if (e.request) {
            error.value = 'The server did not respond. Check your internet or CORS settings.'
        } else {
            error.value = 'Request setup error: ' + e.message
        }
    } finally {
        loading.value = false
    }
}

const submitTwoFactor = async () => {
    try {
        error.value = ''
        loading.value = true
        await store.verifyTwoFactor(twoFactorCode.value)
        router.push('/dashboard')
    } catch (e) {
        if (e.response) {
            error.value = e.response.data.message || 'Invalid code.'
        } else {
            error.value = 'Verification failed.'
        }
    } finally {
        loading.value = false
    }
}

const submitRecovery = async () => {
    try {
        error.value = ''
        loading.value = true
        await store.verifyRecoveryCode(recoveryCode.value)
        router.push('/dashboard')
    } catch (e) {
        if (e.response) {
            error.value = e.response.data.message || 'Invalid recovery code.'
        } else {
            error.value = 'Recovery failed.'
        }
    } finally {
        loading.value = false
    }
}

const goBack = () => {
    store.cancelTwoFactor()
    twoFactorCode.value = ''
    recoveryCode.value = ''
    showRecovery.value = false
    error.value = ''
}
</script>
