<template>
    <div>
        <router-link to="/orders" class="text-sm text-indigo-600 hover:text-indigo-800 font-semibold mb-6 inline-block">
            &larr; Back to Orders
        </router-link>

        <div v-if="loading" class="flex justify-center py-12">
            <div class="w-8 h-8 border-4 border-indigo-500 border-t-transparent rounded-full animate-spin"></div>
        </div>

        <div v-else-if="order" class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 max-w-2xl">
            <div class="flex justify-between items-start mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">{{ order.reference }}</h1>
                    <p class="text-xs text-gray-400 mt-1">Placed {{ order.created_at?.slice(0, 10) }}</p>
                </div>
                <span class="text-xs font-bold px-3 py-1.5 rounded-full uppercase" :class="statusClass(order.status)">
                    {{ order.status }}
                </span>
            </div>

            <div class="grid grid-cols-2 gap-6 mb-6">
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Origin</p>
                    <p class="text-sm font-medium text-gray-800">{{ order.origin }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Destination</p>
                    <p class="text-sm font-medium text-gray-800">{{ order.destination }}</p>
                </div>
                <div v-if="order.pickup_date">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Pickup Date</p>
                    <p class="text-sm font-medium text-gray-800">{{ order.pickup_date }}</p>
                </div>
                <div v-if="order.price">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Price</p>
                    <p class="text-sm font-bold text-gray-800">{{ Number(order.price).toLocaleString() }} FRW</p>
                </div>
            </div>

            <div v-if="order.notes" class="mb-6">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Notes</p>
                <p class="text-sm text-gray-600 bg-gray-50 p-3 rounded-lg">{{ order.notes }}</p>
            </div>

            <div v-if="order.updated_at !== order.created_at" class="text-xs text-gray-400">
                Last updated {{ order.updated_at?.slice(0, 10) }}
            </div>
        </div>

        <div v-else class="bg-white p-12 text-center rounded-xl border border-dashed border-gray-300">
            <p class="text-gray-400 font-medium">Order not found.</p>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { fetchOrder } from '../api/orders'

const props = defineProps({ id: [String, Number] })
const order = ref(null)
const loading = ref(true)

onMounted(async () => {
    try {
        order.value = await fetchOrder(props.id)
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
