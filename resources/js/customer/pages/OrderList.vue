<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-800 mb-6">My Orders</h1>

        <div v-if="loading" class="flex justify-center py-12">
            <div class="w-8 h-8 border-4 border-indigo-500 border-t-transparent rounded-full animate-spin"></div>
        </div>

        <div v-else-if="orders.length === 0" class="bg-white p-12 text-center rounded-xl border border-dashed border-gray-300">
            <p class="text-gray-400 font-medium mb-2">No orders found.</p>
            <router-link to="/place-order" class="text-indigo-600 hover:text-indigo-800 font-semibold text-sm">
                Place your first order
            </router-link>
        </div>

        <div v-else class="grid gap-4">
            <div v-for="order in orders" :key="order.id"
                 class="bg-white p-5 rounded-xl shadow-sm border border-gray-200 hover:shadow-md transition-shadow">
                <div class="flex items-start justify-between mb-2">
                    <div>
                        <router-link :to="`/orders/${order.id}`" class="font-bold text-indigo-600 hover:text-indigo-800">
                            {{ order.reference }}
                        </router-link>
                        <p class="text-xs text-gray-400 mt-0.5">{{ order.created_at?.slice(0, 10) }}</p>
                    </div>
                    <span class="text-[10px] font-bold px-2.5 py-1 rounded-full uppercase" :class="statusClass(order.status)">
                        {{ order.status }}
                    </span>
                </div>
                <div class="flex gap-6 text-sm text-gray-600">
                    <span><span class="font-medium">From:</span> {{ order.origin }}</span>
                    <span><span class="font-medium">To:</span> {{ order.destination }}</span>
                </div>
                <div v-if="order.pickup_date" class="text-xs text-gray-400 mt-1">
                    Pickup: {{ order.pickup_date }}
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { fetchOrders } from '../api/orders'

const orders = ref([])
const loading = ref(true)

onMounted(async () => {
    try {
        orders.value = await fetchOrders()
    } catch {} finally {
        loading.value = false
    }
})

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
