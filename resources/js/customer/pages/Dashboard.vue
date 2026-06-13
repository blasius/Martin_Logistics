<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Welcome, {{ authStore.user?.name }}</h1>
        <p class="text-gray-500 mb-8">Here's your account overview.</p>

        <div v-if="loading" class="flex justify-center py-12">
            <div class="w-8 h-8 border-4 border-indigo-500 border-t-transparent rounded-full animate-spin"></div>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Total Orders</p>
                <p class="text-3xl font-black text-gray-800 mt-1">{{ orders.length }}</p>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">In Transit</p>
                <p class="text-3xl font-black text-indigo-600 mt-1">{{ inTransit }}</p>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Delivered</p>
                <p class="text-3xl font-black text-green-600 mt-1">{{ delivered }}</p>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-5 border-b border-gray-100 flex justify-between items-center">
                <h2 class="text-sm font-bold text-gray-700 uppercase tracking-wide">Recent Orders</h2>
                <router-link to="/orders" class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold">View All</router-link>
            </div>
            <div v-if="orders.length === 0" class="p-8 text-center">
                <p class="text-gray-400 font-medium">No orders yet.</p>
                <router-link to="/place-order" class="text-indigo-600 hover:text-indigo-800 text-sm font-semibold mt-2 inline-block">
                    Place your first order
                </router-link>
            </div>
            <table v-else class="w-full">
                <thead>
                    <tr class="text-[10px] font-bold text-gray-400 uppercase border-b border-gray-100">
                        <th class="text-left p-4 pl-6">Reference</th>
                        <th class="text-left p-4">Origin</th>
                        <th class="text-left p-4">Destination</th>
                        <th class="text-center p-4">Status</th>
                        <th class="text-right p-4 pr-6">Date</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="order in orders.slice(0, 5)" :key="order.id"
                        class="border-b border-gray-50 hover:bg-gray-50/50 transition-colors">
                        <td class="p-4 pl-6">
                            <router-link :to="`/orders/${order.id}`" class="font-bold text-sm text-indigo-600 hover:text-indigo-800">
                                {{ order.reference }}
                            </router-link>
                        </td>
                        <td class="p-4 text-sm text-gray-700">{{ order.origin }}</td>
                        <td class="p-4 text-sm text-gray-700">{{ order.destination }}</td>
                        <td class="p-4 text-center">
                            <span class="text-[10px] font-bold px-2 py-1 rounded-full uppercase" :class="statusClass(order.status)">
                                {{ order.status }}
                            </span>
                        </td>
                        <td class="p-4 pr-6 text-right text-xs text-gray-500">{{ order.created_at?.slice(0, 10) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '../store/authStore'
import { fetchOrders } from '../api/orders'

const authStore = useAuthStore()
const orders = ref([])
const loading = ref(true)

onMounted(async () => {
    try {
        orders.value = await fetchOrders()
    } catch {} finally {
        loading.value = false
    }
})

const inTransit = computed(() => orders.value.filter(o => o.status === 'in_transit').length)
const delivered = computed(() => orders.value.filter(o => o.status === 'delivered').length)

function statusClass(s) {
    const map = {
        draft: 'bg-gray-100 text-gray-600',
        confirmed: 'bg-blue-100 text-blue-700',
        in_transit: 'bg-indigo-100 text-indigo-700',
        delivered: 'bg-green-100 text-green-700',
        cancelled: 'bg-red-100 text-red-700',
    }
    return map[s] || 'bg-gray-100 text-gray-600'
}
</script>
