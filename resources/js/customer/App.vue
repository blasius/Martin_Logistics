<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-indigo-50/20">
        <template v-if="authStore.user && authStore.user.client">
            <nav class="bg-white/70 backdrop-blur-xl border-b border-slate-200/50 sticky top-0 z-50">
                <div class="max-w-[80%] mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center justify-between h-16">
                        <router-link to="/dashboard" class="flex items-center gap-2.5 group shrink-0">
                            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-600 to-violet-500 flex items-center justify-center shadow-md shadow-indigo-200/50 group-hover:shadow-lg group-hover:shadow-indigo-200/60 transition-all duration-300">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                            </div>
                            <span class="text-lg font-bold bg-gradient-to-r from-indigo-600 to-violet-500 bg-clip-text text-transparent">Martin Logistics</span>
                        </router-link>

                        <div class="hidden md:flex items-center gap-1">
                            <router-link v-for="item in navItems" :key="item.path" :to="item.path"
                                class="px-4 py-2 text-sm font-medium rounded-lg transition-all duration-200"
                                :class="isActive(item.path) ? 'bg-indigo-50 text-indigo-700 shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'">
                                {{ item.label }}
                            </router-link>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="relative" v-click-outside="() => showDropdown = false">
                                <button @click="showDropdown = !showDropdown"
                                    class="flex items-center gap-2.5 p-1.5 pr-3 rounded-xl hover:bg-slate-100 transition-all duration-200 border border-transparent hover:border-slate-200">
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-violet-400 flex items-center justify-center text-white text-xs font-bold shadow-sm">
                                        {{ (authStore.user.name || 'U')[0]?.toUpperCase() }}
                                    </div>
                                    <span class="text-sm font-medium text-slate-700 hidden sm:block">{{ authStore.user.name }}</span>
                                    <svg class="w-4 h-4 text-slate-400 transition-transform duration-200" :class="showDropdown && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                <transition enter-active-class="transition-all duration-200 ease-out" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100" leave-active-class="transition-all duration-150 ease-in" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
                                    <div v-if="showDropdown" class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-lg border border-slate-200 py-1.5 z-50 origin-top-right">
                                        <div class="px-4 py-2.5 border-b border-slate-100">
                                            <p class="text-sm font-semibold text-slate-800">{{ authStore.user.name }}</p>
                                            <p class="text-xs text-slate-500 mt-0.5">{{ authStore.user.email }}</p>
                                        </div>
                                        <button @click="logout" class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                            Sign Out
                                        </button>
                                    </div>
                                </transition>
                            </div>

                            <button @click="mobileOpen = !mobileOpen" class="md:hidden p-2 rounded-lg text-slate-600 hover:bg-slate-100 transition-colors">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </nav>

            <transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0 -translate-x-4" enter-to-class="opacity-100 translate-x-0" leave-active-class="transition-all duration-200 ease-in" leave-from-class="opacity-100 translate-x-0" leave-to-class="opacity-0 -translate-x-4">
                <div v-if="mobileOpen" class="fixed inset-0 z-40 md:hidden">
                    <div class="fixed inset-0 bg-slate-900/20 backdrop-blur-sm" @click="mobileOpen = false"></div>
                    <div class="relative w-64 h-full bg-white shadow-xl pt-4">
                        <div class="px-4 pb-4 border-b border-slate-100 mb-2">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-500 to-violet-400 flex items-center justify-center text-white font-bold shadow-sm">
                                    {{ (authStore.user.name || 'U')[0]?.toUpperCase() }}
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-slate-800">{{ authStore.user.name }}</p>
                                    <p class="text-xs text-slate-500">{{ authStore.user.email }}</p>
                                </div>
                            </div>
                        </div>
                        <router-link v-for="item in navItems" :key="item.path" :to="item.path" @click="mobileOpen = false"
                            class="flex items-center gap-3 px-4 py-3 text-sm font-medium transition-colors"
                            :class="isActive(item.path) ? 'bg-indigo-50 text-indigo-700 border-r-2 border-indigo-600' : 'text-slate-600 hover:bg-slate-50'">
                            <component :is="item.icon" class="w-5 h-5" />
                            {{ item.label }}
                        </router-link>
                        <div class="absolute bottom-4 left-0 right-0 px-4">
                            <button @click="logout" class="w-full flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-medium text-red-600 bg-red-50 hover:bg-red-100 rounded-xl transition-colors">
                                Sign Out
                            </button>
                        </div>
                    </div>
                </div>
            </transition>

            <main class="max-w-[80%] mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <router-view v-slot="{ Component }">
                    <transition name="page" mode="out-in">
                        <component :is="Component" />
                    </transition>
                </router-view>
            </main>
        </template>

        <template v-else-if="authStore.initialized">
            <router-view v-slot="{ Component }">
                <transition name="page" mode="out-in">
                    <component :is="Component" />
                </transition>
            </router-view>
        </template>

        <div v-else class="flex items-center justify-center min-h-screen">
            <div class="flex flex-col items-center gap-4">
                <div class="w-12 h-12 border-[3px] border-indigo-500 border-t-transparent rounded-full animate-spin"></div>
                <p class="text-sm text-slate-400 font-medium animate-pulse">Loading...</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from './store/authStore'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const showDropdown = ref(false)
const mobileOpen = ref(false)

const navItems = [
    { path: '/dashboard', label: 'Dashboard' },
    { path: '/orders', label: 'My Orders' },
    { path: '/place-order', label: 'Place Order' },
]

function isActive(path) {
    if (path === '/dashboard') return route.path === '/dashboard'
    return route.path.startsWith(path)
}

function handleClickOutside(e) {
    if (showDropdown.value) showDropdown.value = false
}

onMounted(async () => {
    try { await authStore.checkAuth() } catch {}
    document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)
})

async function logout() {
    await authStore.logout()
    window.location.href = '/customer/login'
}
</script>

<style>
.page-enter-active {
    transition: all 0.2s ease-out;
}
.page-leave-active {
    transition: all 0.15s ease-in;
}
.page-enter-from {
    opacity: 0;
    transform: translateY(8px);
}
.page-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}
</style>
