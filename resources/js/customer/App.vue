<template>
    <div class="min-h-screen bg-gray-50">
        <template v-if="authStore.user && authStore.user.client">
            <nav class="bg-white shadow-sm border-b border-gray-200">
                <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
                    <router-link to="/dashboard" class="text-lg font-bold text-indigo-600">
                        Martin Logistics
                    </router-link>
                    <div class="flex items-center gap-4 text-sm">
                        <router-link to="/dashboard" class="text-gray-600 hover:text-gray-900 font-medium">Dashboard</router-link>
                        <router-link to="/orders" class="text-gray-600 hover:text-gray-900 font-medium">My Orders</router-link>
                        <router-link to="/place-order" class="text-gray-600 hover:text-gray-900 font-medium">Place Order</router-link>
                        <span class="text-gray-400">|</span>
                        <span class="text-gray-500">{{ authStore.user.name }}</span>
                        <button @click="logout" class="text-red-500 hover:text-red-700 font-medium">Logout</button>
                    </div>
                </div>
            </nav>
            <main class="max-w-6xl mx-auto px-4 py-8">
                <router-view />
            </main>
        </template>
        <template v-else-if="authStore.initialized">
            <router-view />
        </template>
        <div v-else class="flex items-center justify-center min-h-screen">
            <div class="w-8 h-8 border-4 border-indigo-500 border-t-transparent rounded-full animate-spin"></div>
        </div>
    </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from './store/authStore'

const authStore = useAuthStore()
const router = useRouter()

onMounted(async () => {
    try {
        await authStore.checkAuth()
    } catch {
        // not authenticated
    }
})

async function logout() {
    await authStore.logout()
    router.push('/login')
}
</script>
