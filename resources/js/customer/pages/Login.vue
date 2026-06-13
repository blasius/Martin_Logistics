<template>
    <div class="min-h-screen flex">
        <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-indigo-600 via-indigo-700 to-violet-800 relative overflow-hidden">
            <div class="absolute inset-0">
                <div class="absolute -top-20 -left-20 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-20 -right-20 w-[30rem] h-[30rem] bg-violet-300/10 rounded-full blur-3xl"></div>
                <div class="absolute top-1/3 right-1/4 w-48 h-48 bg-blue-300/5 rounded-full blur-2xl"></div>
            </div>
            <div class="relative z-10 flex flex-col justify-between px-16 py-16 w-full">
                <div>
                    <div class="w-14 h-14 rounded-2xl bg-white/15 backdrop-blur-sm flex items-center justify-center mb-10 border border-white/10">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                    </div>
                    <h1 class="text-4xl font-bold text-white mb-4 leading-[1.15] tracking-tight">Logistics Made<br><span class="text-indigo-200">Simple &amp; Reliable</span></h1>
                    <p class="text-indigo-100/70 text-lg leading-relaxed max-w-md">
                        Track shipments in real-time, manage orders, and stay connected with your dedicated logistics team.
                    </p>
                </div>
                <div class="space-y-6">
                    <div class="flex items-center gap-4 text-indigo-200/50 text-sm">
                        <div class="flex -space-x-2">
                            <div v-for="a in avatars" :key="a" class="w-9 h-9 rounded-full bg-white/10 border border-white/20 flex items-center justify-center text-[11px] font-bold text-white/70">{{ a }}</div>
                        </div>
                        <span>Trusted by 500+ businesses</span>
                    </div>
                    <div class="grid grid-cols-3 gap-4">
                        <div v-for="s in stats" :key="s.label" class="bg-white/5 rounded-xl p-4 border border-white/10">
                            <p class="text-2xl font-bold text-white">{{ s.value }}</p>
                            <p class="text-xs text-indigo-200/60 mt-1">{{ s.label }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="w-full lg:w-1/2 flex items-center justify-center px-6 py-12">
            <div class="w-full max-w-sm">
                <template v-if="needsVerification">
                    <div class="text-center mb-8">
                        <div class="w-16 h-16 rounded-2xl bg-amber-50 flex items-center justify-center mx-auto mb-5 border border-amber-100">
                            <svg class="w-8 h-8 text-amber-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
                        </div>
                        <h2 class="text-2xl font-bold text-slate-800">Verify your email</h2>
                        <p class="text-sm text-slate-500 mt-2">Enter the 6-digit code sent to<br><strong class="text-slate-700">{{ verifyingEmail }}</strong></p>
                    </div>
                    <div class="bg-amber-50/50 border border-amber-200 rounded-xl p-3.5 mb-6">
                        <p class="text-xs text-amber-700 flex items-center gap-2">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                            Dev mode — code logged to <code class="bg-amber-100 px-1.5 py-0.5 rounded text-amber-800 font-mono text-[11px]">storage/logs/laravel.log</code>
                        </p>
                    </div>
                    <form @submit.prevent="submitCode">
                        <div class="flex gap-2 justify-center mb-6">
                            <input v-for="i in 6" :key="i" ref="digitInputs" :id="'code-' + i" v-model="digits[i-1]" type="text" maxlength="1" autocomplete="off" inputmode="numeric" pattern="[0-9]"
                                @input="onDigitInput(i - 1)" @keydown.backspace="onDigitBackspace(i - 1)" @paste.prevent="onPaste"
                                class="w-11 h-14 text-center text-2xl font-bold rounded-xl border-2 border-slate-200 text-slate-800 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 outline-none transition-all" />
                        </div>
                        <p v-if="error" class="text-red-500 text-xs font-medium text-center mb-4">{{ error }}</p>
                        <button type="submit" :disabled="verifying || code.length !== 6"
                            class="w-full bg-gradient-to-r from-indigo-600 to-violet-500 text-white py-3.5 rounded-xl font-semibold hover:from-indigo-700 hover:to-violet-600 transition-all duration-200 disabled:opacity-50 shadow-lg shadow-indigo-200/50 active:scale-[0.98]">
                            {{ verifying ? 'Verifying...' : 'Verify Email' }}
                        </button>
                    </form>
                    <button @click="resend" :disabled="sending" class="w-full text-sm text-indigo-600 hover:text-indigo-700 font-medium text-center mt-5 transition-colors">
                        {{ sending ? 'Sending...' : 'Resend verification code' }}
                    </button>
                </template>

                <template v-else>
                    <div class="lg:hidden flex flex-col items-center mb-8">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-600 to-violet-500 flex items-center justify-center mb-3 shadow-lg shadow-indigo-200/50">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                        </div>
                        <h1 class="text-xl font-bold text-slate-800">Martin Logistics</h1>
                    </div>

                    <h2 class="text-2xl font-bold text-slate-800 mb-1">Welcome back</h2>
                    <p class="text-sm text-slate-500 mb-8">Sign in to access your customer portal</p>

                    <form @submit.prevent="submitLogin">
                        <div class="mb-4">
                            <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5 block">Email Address</label>
                            <div class="relative">
                                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
                                <input v-model="email" type="email" placeholder="you@example.com" :disabled="loading"
                                    class="w-full pl-11 pr-4 py-3.5 rounded-xl border-2 border-slate-200 text-sm font-medium text-slate-800 placeholder:text-slate-400 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 outline-none transition-all"
                                    required autocomplete="email" />
                            </div>
                        </div>

                        <div class="mb-6">
                            <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5 block">Password</label>
                            <div class="relative">
                                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                                <input v-model="password" type="password" placeholder="Enter your password" :disabled="loading"
                                    class="w-full pl-11 pr-4 py-3.5 rounded-xl border-2 border-slate-200 text-sm font-medium text-slate-800 placeholder:text-slate-400 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 outline-none transition-all"
                                    required autocomplete="current-password" />
                            </div>
                        </div>

                        <p v-if="error" class="text-red-500 text-xs font-medium mb-4 flex items-center gap-1.5 bg-red-50 p-3 rounded-lg">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                            {{ error }}
                        </p>

                        <button type="submit" :disabled="loading"
                            class="w-full bg-gradient-to-r from-indigo-600 to-violet-500 text-white py-3.5 rounded-xl font-semibold hover:from-indigo-700 hover:to-violet-600 transition-all duration-200 disabled:opacity-50 shadow-lg shadow-indigo-200/50 active:scale-[0.98]">
                            <span v-if="loading" class="flex items-center justify-center gap-2">
                                <div class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                                Signing in...
                            </span>
                            <span v-else>Sign In</span>
                        </button>
                    </form>

                    <div class="mt-8 pt-6 border-t border-slate-100 text-center">
                        <p class="text-sm text-slate-500">
                            Don't have an account?
                            <router-link to="/signup" class="text-indigo-600 hover:text-indigo-700 font-semibold ml-1">Create an account</router-link>
                        </p>
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, nextTick } from 'vue'
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
const digits = ref(['', '', '', '', '', ''])
const code = computed(() => digits.value.join(''))
const verifying = ref(false)
const sending = ref(false)
const digitInputs = ref([])

const avatars = ['TW', 'MK', 'AR', 'SL']
const stats = [
    { value: '12K+', label: 'Shipments' },
    { value: '98%', label: 'On Time' },
    { value: '500+', label: 'Clients' },
]

function onDigitInput(index) {
    if (digits.value[index] && index < 5) {
        const next = digitInputs.value[index + 1]
        if (next) next.focus()
    }
}

function onDigitBackspace(index) {
    if (!digits.value[index] && index > 0) {
        digits.value[index - 1] = ''
        const prev = digitInputs.value[index - 1]
        if (prev) prev.focus()
    }
}

function onPaste(e) {
    const text = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6)
    for (let i = 0; i < text.length; i++) {
        digits.value[i] = text[i]
    }
    if (text.length === 6) {
        nextTick(() => submitCode())
    }
}

async function submitLogin() {
    loading.value = true
    error.value = ''
    try {
        const data = await authStore.login(email.value, password.value)
        if (data.requires_email_verification) {
            needsVerification.value = true
            verifyingEmail.value = data.email
            await authStore.resendVerification()
            nextTick(() => digitInputs.value[0]?.focus())
        } else {
            router.push('/dashboard')
        }
    } catch (e) {
        error.value = e.response?.data?.errors?.email?.[0] || e.response?.data?.message || 'Invalid email or password.'
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
        error.value = e.response?.data?.message || 'Invalid verification code.'
        digits.value = ['', '', '', '', '', '']
        nextTick(() => digitInputs.value[0]?.focus())
    } finally {
        verifying.value = false
    }
}

async function resend() {
    sending.value = true
    try { await authStore.resendVerification() } catch {} finally { sending.value = false }
}
</script>
