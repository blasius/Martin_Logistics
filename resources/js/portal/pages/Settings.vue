<template>
    <div class="max-w-6xl mx-auto space-y-6">
        <div>
            <h1 class="text-3xl font-black text-slate-800 tracking-tight uppercase italic leading-none">Settings</h1>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-1">Configure your application</p>
        </div>

        <div class="flex gap-6">
            <!-- Sidebar Navigation -->
            <nav class="w-64 shrink-0 space-y-1">
                <button v-for="section in visibleSections" :key="section.id"
                        @click="activeSection = section.id"
                        :class="[
                            'w-full flex items-center gap-3 px-4 py-3 rounded-2xl text-left transition-all border',
                            activeSection === section.id
                                ? 'bg-indigo-50 border-indigo-200 text-indigo-700 shadow-sm'
                                : 'bg-white border-transparent text-slate-600 hover:bg-slate-50 hover:border-slate-200'
                        ]">
                    <component :is="section.icon" class="w-5 h-5 shrink-0" />
                    <div class="min-w-0">
                        <p class="text-xs font-black uppercase tracking-tight">{{ section.label }}</p>
                        <p class="text-[9px] font-bold text-slate-400 mt-0.5 truncate">{{ section.description }}</p>
                    </div>
                    <span v-if="section.comingSoon"
                          class="ml-auto text-[8px] font-black px-1.5 py-0.5 rounded-md bg-amber-100 text-amber-700 uppercase shrink-0">Soon</span>
                </button>
            </nav>

            <!-- Content Area -->
            <div class="flex-1 min-w-0">
                <!-- Security -->
                <div v-if="activeSection === 'security'">
                    <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200 overflow-hidden">
                        <div class="p-6 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                            <div>
                                <h2 class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Two-Factor Authentication</h2>
                                <p class="text-[9px] font-bold text-slate-400 mt-1">Add an extra layer of security to your account</p>
                            </div>
                            <span v-if="twoFactorEnabled"
                                  class="text-[9px] font-black px-2.5 py-1 rounded-md bg-emerald-100 text-emerald-700 uppercase">Active</span>
                            <span v-else
                                  class="text-[9px] font-black px-2.5 py-1 rounded-md bg-slate-100 text-slate-500 uppercase">Off</span>
                        </div>

                        <div class="p-6">
                            <div v-if="!twoFactorEnabled && !showSetup" class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-bold text-slate-700">Protect your account</p>
                                    <p class="text-[10px] text-slate-400 font-medium mt-1">Use an authenticator app like Google Authenticator or Microsoft Authenticator</p>
                                </div>
                                <button @click="enable2FA"
                                        :disabled="loading"
                                        class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-2xl text-xs font-black shadow-lg shadow-blue-200 active:scale-95 transition-all uppercase disabled:opacity-40">
                                    {{ loading ? 'Setting up...' : 'Enable 2FA' }}
                                </button>
                            </div>

                            <div v-if="showSetup" class="space-y-6">
                                <div class="bg-slate-50 rounded-2xl p-6 border border-slate-200">
                                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-4">Step 1: Scan QR Code</p>
                                    <div class="flex items-start gap-8">
                                        <div v-if="qrCode" class="bg-white p-3 rounded-xl shadow-sm border border-slate-200" v-html="qrCode"></div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs font-bold text-slate-600 mb-2">Or enter this secret key manually:</p>
                                            <code class="block bg-slate-800 text-emerald-300 text-xs font-mono p-3 rounded-xl select-all break-all">{{ secret }}</code>
                                            <p class="text-[10px] text-amber-600 font-bold mt-3 flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                                                Save your recovery codes before confirming. You won't see them again.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="newRecoveryCodes.length" class="bg-amber-50 rounded-2xl p-6 border border-amber-200">
                                    <p class="text-[10px] font-black text-amber-700 uppercase tracking-widest mb-3">Recovery Codes (save these!)</p>
                                    <div class="grid grid-cols-2 gap-2">
                                        <code v-for="(rc, i) in newRecoveryCodes" :key="i"
                                              class="bg-white text-amber-900 text-xs font-mono p-2 rounded-lg border border-amber-100 select-all text-center">
                                            {{ rc }}
                                        </code>
                                    </div>
                                </div>

                                <div class="bg-slate-50 rounded-2xl p-6 border border-slate-200">
                                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-4">Step 2: Confirm Setup</p>
                                    <p class="text-xs font-bold text-slate-600 mb-3">Enter the 6-digit code from your authenticator app</p>
                                    <div class="flex gap-3 items-start">
                                        <input v-model="confirmCode"
                                               type="text"
                                               autocomplete="off"
                                               inputmode="numeric"
                                               maxlength="6"
                                               placeholder="000 000"
                                               class="border-slate-200 px-4 py-3.5 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-500 outline-none bg-white w-48 transition-all text-center text-xl tracking-[0.3em] font-mono" />
                                        <button @click="confirmSetup"
                                                :disabled="loading || confirmCode.length < 6"
                                                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3.5 rounded-xl text-xs font-black transition-all active:scale-95 uppercase disabled:opacity-40">
                                            {{ loading ? 'Verifying...' : 'Confirm' }}
                                        </button>
                                    </div>
                                    <p v-if="confirmError" class="text-red-500 text-[10px] font-bold mt-2">{{ confirmError }}</p>
                                </div>

                                <button @click="cancelSetup"
                                        class="text-[10px] font-black text-slate-400 hover:text-slate-600 uppercase transition-colors">
                                    &larr; Cancel
                                </button>
                            </div>

                            <div v-if="twoFactorEnabled && !showSetup" class="space-y-5">
                                <div class="flex items-center gap-2 text-emerald-700 bg-emerald-50 rounded-xl px-4 py-3 border border-emerald-200">
                                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <p class="text-xs font-bold">Two-factor authentication is active on your account.</p>
                                </div>

                                <div class="flex flex-wrap gap-3">
                                    <button @click="showRecoveryCodes = !showRecoveryCodes"
                                            class="px-5 py-2.5 rounded-xl text-xs font-black border border-slate-200 text-slate-600 hover:bg-slate-50 transition-all uppercase">
                                        {{ showRecoveryCodes ? 'Hide Codes' : 'View Recovery Codes' }}
                                    </button>
                                    <button @click="regenerateCodes"
                                            :disabled="loading"
                                            class="px-5 py-2.5 rounded-xl text-xs font-black border border-amber-200 text-amber-700 hover:bg-amber-50 transition-all uppercase disabled:opacity-40">
                                        Regenerate Codes
                                    </button>
                                </div>

                                <div v-if="showRecoveryCodes" class="bg-amber-50 rounded-2xl p-6 border border-amber-200">
                                    <p class="text-[10px] font-black text-amber-700 uppercase tracking-widest mb-3">Recovery Codes</p>
                                    <div v-if="recoveryCodes.length" class="grid grid-cols-2 gap-2">
                                        <code v-for="(rc, i) in recoveryCodes" :key="i"
                                              class="bg-white text-amber-900 text-xs font-mono p-2 rounded-lg border border-amber-100 select-all text-center">
                                            {{ rc }}
                                        </code>
                                    </div>
                                    <p v-else class="text-xs text-amber-600 font-bold">No recovery codes remaining. Regenerate new ones.</p>
                                </div>

                                <div class="border-t border-slate-100 pt-5">
                                    <details class="group">
                                        <summary class="text-[10px] font-black text-red-400 hover:text-red-600 uppercase cursor-pointer transition-colors list-none flex items-center gap-2">
                                            <svg class="w-3.5 h-3.5 group-open:rotate-90 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                                            Disable Two-Factor Authentication
                                        </summary>
                                        <div class="mt-4 flex gap-3 items-start">
                                            <input v-model="disablePassword"
                                                   type="password"
                                                   autocomplete="new-password"
                                                   placeholder="Enter your password to confirm"
                                                   class="border-slate-200 px-4 py-3 rounded-xl text-sm font-bold focus:ring-2 focus:ring-red-500 outline-none bg-slate-50 w-64 transition-all" />
                                            <button @click="disable2FA"
                                                    :disabled="loading || !disablePassword"
                                                    class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-xl text-xs font-black transition-all active:scale-95 uppercase disabled:opacity-40">
                                                {{ loading ? 'Disabling...' : 'Disable' }}
                                            </button>
                                        </div>
                                    </details>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Coming Soon Placeholder -->
                <div v-else class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200 overflow-hidden">
                    <div class="flex flex-col items-center justify-center py-20 px-6">
                        <component :is="currentSection?.icon" class="w-12 h-12 text-slate-200 mb-4" />
                        <h2 class="text-lg font-black text-slate-400 uppercase tracking-tight">{{ currentSection?.label }}</h2>
                        <p class="text-xs font-bold text-slate-300 mt-2">Coming soon</p>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="notification.show" class="fixed bottom-8 right-8 z-[130] flex items-center gap-3 px-6 py-3 bg-slate-900 text-white rounded-xl shadow-2xl border border-slate-700">
            <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            <span class="text-sm font-bold">{{ notification.message }}</span>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { api } from '../../plugins/axios'
import { useAuthStore } from '../store/authStore'
import {
    ShieldCheck, Bell, User, Puzzle, Settings2,
} from 'lucide-vue-next'

const authStore = useAuthStore()
const activeSection = ref('security')

const sections = [
    {
        id: 'security',
        label: 'Security',
        description: 'Two-factor authentication & password',
        icon: ShieldCheck,
        roles: null,
    },
    {
        id: 'notifications',
        label: 'Notifications',
        description: 'Alert preferences & channels',
        icon: Bell,
        roles: null,
        comingSoon: true,
    },
    {
        id: 'profile',
        label: 'Profile',
        description: 'Personal information & contacts',
        icon: User,
        roles: null,
        comingSoon: true,
    },
    {
        id: 'integrations',
        label: 'Integrations',
        description: 'API keys & third-party services',
        icon: Puzzle,
        roles: ['super_admin', 'admin'],
        comingSoon: true,
    },
    {
        id: 'system',
        label: 'System',
        description: 'App configuration & maintenance',
        icon: Settings2,
        roles: ['super_admin'],
        comingSoon: true,
    },
]

const visibleSections = computed(() => {
    const userRoles = authStore.user?.roles_list || []
    return sections.filter(s => {
        if (!s.roles) return true
        return s.roles.some(r => userRoles.includes(r))
    })
})

const currentSection = computed(() => {
    return sections.find(s => s.id === activeSection.value)
})

// 2FA state
const twoFactorEnabled = ref(false)
const loading = ref(false)
const showSetup = ref(false)
const qrCode = ref('')
const secret = ref('')
const newRecoveryCodes = ref([])
const confirmCode = ref('')
const confirmError = ref('')
const recoveryCodes = ref([])
const showRecoveryCodes = ref(false)
const disablePassword = ref('')
const notification = ref({ show: false, message: '' })

const notify = (msg) => {
    notification.value = { show: true, message: msg }
    setTimeout(() => notification.value.show = false, 3000)
}

const checkStatus = async () => {
    try {
        const { data } = await api.get('user')
        twoFactorEnabled.value = !!data.two_factor_confirmed_at
    } catch (e) {
        console.error('Failed to check 2FA status', e)
    }
}

const enable2FA = async () => {
    loading.value = true
    try {
        const { data } = await api.post('portal/2fa/enable')
        qrCode.value = data.qr_code
        secret.value = data.secret
        newRecoveryCodes.value = data.recovery_codes
        showSetup.value = true
    } catch (e) {
        notify(e.response?.data?.message || 'Failed to enable 2FA.')
    } finally {
        loading.value = false
    }
}

const confirmSetup = async () => {
    loading.value = true
    confirmError.value = ''
    try {
        await api.post('portal/2fa/confirm', { code: confirmCode.value })
        twoFactorEnabled.value = true
        showSetup.value = false
        confirmCode.value = ''
        notify('Two-factor authentication is now active.')
    } catch (e) {
        confirmError.value = e.response?.data?.message || 'Invalid code. Try again.'
    } finally {
        loading.value = false
    }
}

const cancelSetup = () => {
    showSetup.value = false
    qrCode.value = ''
    secret.value = ''
    newRecoveryCodes.value = []
    confirmCode.value = ''
    confirmError.value = ''
}

const disable2FA = async () => {
    loading.value = true
    try {
        await api.post('portal/2fa/disable', { password: disablePassword.value })
        twoFactorEnabled.value = false
        disablePassword.value = ''
        recoveryCodes.value = []
        notify('Two-factor authentication disabled.')
    } catch (e) {
        notify(e.response?.data?.message || 'Failed to disable 2FA.')
    } finally {
        loading.value = false
    }
}

const fetchRecoveryCodes = async () => {
    try {
        const { data } = await api.get('portal/2fa/recovery-codes')
        recoveryCodes.value = data.recovery_codes || []
    } catch (e) {
        console.error('Failed to fetch recovery codes', e)
    }
}

const regenerateCodes = async () => {
    loading.value = true
    try {
        const { data } = await api.post('portal/2fa/recovery-codes/regenerate')
        recoveryCodes.value = data.recovery_codes
        notify('Recovery codes regenerated.')
    } catch (e) {
        notify(e.response?.data?.message || 'Failed to regenerate codes.')
    } finally {
        loading.value = false
    }
}

onMounted(() => {
    checkStatus()
})
</script>
