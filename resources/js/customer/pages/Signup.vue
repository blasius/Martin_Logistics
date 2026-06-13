<template>
    <div class="min-h-screen flex">
        <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-indigo-600 via-indigo-700 to-violet-800 relative overflow-hidden">
            <div class="absolute inset-0">
                <div class="absolute -top-20 -left-20 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-20 -right-20 w-[30rem] h-[30rem] bg-violet-300/10 rounded-full blur-3xl"></div>
                <div class="absolute top-1/2 left-1/3 w-56 h-56 bg-blue-300/5 rounded-full blur-2xl"></div>
            </div>
            <div class="relative z-10 flex flex-col justify-between px-16 py-16 w-full">
                <div>
                    <div class="w-14 h-14 rounded-2xl bg-white/15 backdrop-blur-sm flex items-center justify-center mb-10 border border-white/10">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                    </div>
                    <h1 class="text-4xl font-bold text-white mb-4 leading-[1.15] tracking-tight">Join the<br><span class="text-indigo-200">Logistics Network</span></h1>
                    <ul class="space-y-5 text-indigo-100/70">
                        <li class="flex items-center gap-3.5">
                            <div class="w-6 h-6 rounded-full bg-indigo-400/20 flex items-center justify-center shrink-0">
                                <svg class="w-3.5 h-3.5 text-indigo-200" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                            </div>
                            Place and track orders in real time
                        </li>
                        <li class="flex items-center gap-3.5">
                            <div class="w-6 h-6 rounded-full bg-indigo-400/20 flex items-center justify-center shrink-0">
                                <svg class="w-3.5 h-3.5 text-indigo-200" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                            </div>
                            Get instant shipment notifications
                        </li>
                        <li class="flex items-center gap-3.5">
                            <div class="w-6 h-6 rounded-full bg-indigo-400/20 flex items-center justify-center shrink-0">
                                <svg class="w-3.5 h-3.5 text-indigo-200" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                            </div>
                            Dedicated account manager
                        </li>
                    </ul>
                </div>
                <div class="flex items-center gap-4 text-indigo-200/50 text-sm">
                    <div class="flex -space-x-2">
                        <div v-for="a in avatars" :key="a" class="w-9 h-9 rounded-full bg-white/10 border border-white/20 flex items-center justify-center text-[11px] font-bold text-white/70">{{ a }}</div>
                    </div>
                    <span>Join 500+ businesses already on board</span>
                </div>
            </div>
        </div>

        <div class="w-full lg:w-1/2 flex items-start justify-center px-6 py-10 overflow-y-auto">
            <div class="w-full max-w-md">
                <template v-if="showVerification">
                    <div class="text-center mb-8">
                        <div class="w-16 h-16 rounded-2xl bg-amber-50 flex items-center justify-center mx-auto mb-5 border border-amber-100">
                            <svg class="w-8 h-8 text-amber-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
                        </div>
                        <h2 class="text-2xl font-bold text-slate-800">Check your email</h2>
                        <p class="text-sm text-slate-500 mt-2">We sent a 6-digit code to<br><strong class="text-slate-700">{{ form.email }}</strong></p>
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
                    <button @click="resendCode" :disabled="sending" class="w-full text-sm text-indigo-600 hover:text-indigo-700 font-medium text-center mt-5 transition-colors">
                        {{ sending ? 'Sending...' : 'Resend verification code' }}
                    </button>
                </template>

                <template v-else>
                    <div class="lg:hidden flex flex-col items-center mb-6">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-600 to-violet-500 flex items-center justify-center mb-3 shadow-lg shadow-indigo-200/50">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                        </div>
                    </div>

                    <h2 class="text-2xl font-bold text-slate-800 mb-1">Create your account</h2>
                    <p class="text-sm text-slate-500 mb-6">Join thousands of businesses trusting Martin Logistics</p>

                    <form @submit.prevent="submitSignup">
                        <div class="space-y-4">
                            <div>
                                <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5 block">Full Name <span class="text-red-400">*</span></label>
                                <input v-model="form.name" type="text" placeholder="Your full name" :disabled="loading"
                                    class="w-full px-4 py-3.5 rounded-xl border-2 border-slate-200 text-sm font-medium text-slate-800 placeholder:text-slate-400 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 outline-none transition-all" required />
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5 block">Email <span class="text-red-400">*</span></label>
                                <input v-model="form.email" type="email" placeholder="you@example.com" :disabled="loading"
                                    class="w-full px-4 py-3.5 rounded-xl border-2 border-slate-200 text-sm font-medium text-slate-800 placeholder:text-slate-400 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 outline-none transition-all" required />
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5 block">Password <span class="text-red-400">*</span></label>
                                    <input v-model="form.password" type="password" placeholder="Min 8 chars" :disabled="loading"
                                        class="w-full px-4 py-3.5 rounded-xl border-2 border-slate-200 text-sm font-medium text-slate-800 placeholder:text-slate-400 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 outline-none transition-all" required />
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5 block">Confirm <span class="text-red-400">*</span></label>
                                    <input v-model="form.password_confirmation" type="password" placeholder="Repeat" :disabled="loading"
                                        class="w-full px-4 py-3.5 rounded-xl border-2 border-slate-200 text-sm font-medium text-slate-800 placeholder:text-slate-400 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 outline-none transition-all" required />
                                </div>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5 block">Account Type <span class="text-red-400">*</span></label>
                                <div class="grid grid-cols-2 gap-3">
                                    <button type="button" @click="form.type = 'individual'"
                                        class="p-3.5 rounded-xl border-2 text-sm font-medium transition-all duration-200 text-center"
                                        :class="form.type === 'individual' ? 'border-indigo-400 bg-indigo-50 text-indigo-700 shadow-sm' : 'border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-50'">
                                        <svg class="w-5 h-5 mx-auto mb-1.5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                                        Individual
                                    </button>
                                    <button type="button" @click="form.type = 'company'"
                                        class="p-3.5 rounded-xl border-2 text-sm font-medium transition-all duration-200 text-center"
                                        :class="form.type === 'company' ? 'border-indigo-400 bg-indigo-50 text-indigo-700 shadow-sm' : 'border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-50'">
                                        <svg class="w-5 h-5 mx-auto mb-1.5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/></svg>
                                        Company
                                    </button>
                                </div>
                            </div>
                            <div v-if="form.type === 'company'" class="p-4 bg-indigo-50/50 rounded-xl border border-indigo-100">
                                <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5 block">TIN Number <span class="text-red-400">*</span></label>
                                <input v-model="form.tin" type="text" placeholder="Tax Identification Number" :disabled="loading"
                                    class="w-full px-4 py-3 rounded-xl border-2 border-indigo-200 text-sm font-medium text-slate-800 placeholder:text-slate-400 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 outline-none transition-all" />
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5 block">Phone</label>
                                    <input v-model="form.phone" type="text" placeholder="+250 7..." :disabled="loading"
                                        class="w-full px-4 py-3.5 rounded-xl border-2 border-slate-200 text-sm font-medium text-slate-800 placeholder:text-slate-400 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 outline-none transition-all" />
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5 block">Contact</label>
                                    <input v-model="form.contact_person" type="text" placeholder="Contact name" :disabled="loading"
                                        class="w-full px-4 py-3.5 rounded-xl border-2 border-slate-200 text-sm font-medium text-slate-800 placeholder:text-slate-400 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 outline-none transition-all" />
                                </div>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5 block">Address</label>
                                <input v-model="form.address" type="text" placeholder="Street, city, postal code" :disabled="loading"
                                    class="w-full px-4 py-3.5 rounded-xl border-2 border-slate-200 text-sm font-medium text-slate-800 placeholder:text-slate-400 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 outline-none transition-all" />
                            </div>
                        </div>

                        <p v-if="error" class="text-red-500 text-xs font-medium mt-5 flex items-center gap-1.5 bg-red-50 p-3 rounded-lg">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                            {{ error }}
                        </p>

                        <button type="submit" :disabled="loading"
                            class="w-full mt-6 bg-gradient-to-r from-indigo-600 to-violet-500 text-white py-3.5 rounded-xl font-semibold hover:from-indigo-700 hover:to-violet-600 transition-all duration-200 disabled:opacity-50 shadow-lg shadow-indigo-200/50 active:scale-[0.98]">
                            <span v-if="loading" class="flex items-center justify-center gap-2">
                                <div class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                                Creating account...
                            </span>
                            <span v-else>Create Account</span>
                        </button>
                    </form>

                    <p class="text-sm text-slate-500 text-center mt-6">
                        Already have an account?
                        <router-link to="/login" class="text-indigo-600 hover:text-indigo-700 font-semibold ml-1">Sign in</router-link>
                    </p>
                </template>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, nextTick } from 'vue'
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
const digits = ref(['', '', '', '', '', ''])
const code = computed(() => digits.value.join(''))
const verifying = ref(false)
const sending = ref(false)
const digitInputs = ref([])

const avatars = ['TW', 'MK', 'AR', 'SL']

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
    for (let i = 0; i < text.length; i++) digits.value[i] = text[i]
    if (text.length === 6) nextTick(() => submitCode())
}

async function submitSignup() {
    loading.value = true
    error.value = ''
    try {
        await authStore.signup({ ...form })
        showVerification.value = true
        nextTick(() => digitInputs.value[0]?.focus())
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
        error.value = e.response?.data?.message || 'Invalid verification code.'
        digits.value = ['', '', '', '', '', '']
        nextTick(() => digitInputs.value[0]?.focus())
    } finally {
        verifying.value = false
    }
}

async function resendCode() {
    sending.value = true
    try { await authStore.resendVerification() } catch {} finally { sending.value = false }
}
</script>
