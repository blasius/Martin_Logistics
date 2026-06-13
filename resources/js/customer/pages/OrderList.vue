<template>
    <div>
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">My Orders</h1>
                <p class="text-slate-500 mt-1">Track and manage all your shipments</p>
            </div>
            <router-link to="/place-order"
                class="hidden sm:inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-violet-500 text-white text-sm font-semibold rounded-xl hover:from-indigo-700 hover:to-violet-600 transition-all duration-200 shadow-md shadow-indigo-200/50 active:scale-[0.98]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                New Order
            </router-link>
        </div>

        <div v-if="loading" class="space-y-4">
            <div v-for="i in 4" :key="i" class="bg-white rounded-2xl p-5 border border-slate-100 animate-pulse">
                <div class="flex items-start justify-between mb-3">
                    <div class="h-4 bg-slate-100 rounded w-32"></div>
                    <div class="h-5 bg-slate-100 rounded w-16"></div>
                </div>
                <div class="h-3 bg-slate-50 rounded w-48 mb-2"></div>
                <div class="h-3 bg-slate-50 rounded w-36"></div>
            </div>
        </div>

        <div v-else-if="orders.length === 0" class="bg-white rounded-2xl border border-slate-100 shadow-sm p-12 text-center">
            <div class="w-20 h-20 rounded-2xl bg-slate-50 flex items-center justify-center mx-auto mb-5">
                <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/></svg>
            </div>
            <p class="text-slate-600 font-semibold mb-1">No orders yet</p>
            <p class="text-slate-400 text-sm mb-6">You haven't placed any orders yet. Create your first shipment.</p>
            <router-link to="/place-order"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-violet-500 text-white text-sm font-semibold rounded-xl hover:from-indigo-700 hover:to-violet-600 transition-all duration-200 shadow-md shadow-indigo-200/50 active:scale-[0.98]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Place Your First Order
            </router-link>
        </div>

        <div v-else class="grid gap-4">
            <div v-for="order in orders" :key="order.id"
                class="group bg-white rounded-2xl border border-slate-100 shadow-sm hover:shadow-md hover:border-slate-200 transition-all duration-200 overflow-hidden">
                <router-link :to="`/orders/${order.id}`" class="block p-5 sm:p-6">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-slate-50 flex items-center justify-center group-hover:bg-indigo-50 transition-colors duration-200">
                                <svg class="w-5 h-5 text-slate-400 group-hover:text-indigo-500 transition-colors duration-200" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/></svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-sm text-slate-800 group-hover:text-indigo-600 transition-colors duration-200">{{ order.reference }}</h3>
                                <p class="text-xs text-slate-400 mt-0.5">{{ formatDate(order.created_at) }}</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center gap-1.5 text-[11px] font-bold px-3 py-1.5 rounded-full shrink-0" :class="statusClass(order.status)">
                            <span class="w-1.5 h-1.5 rounded-full" :class="statusDotClass(order.status)"></span>
                            {{ formatStatus(order.status) }}
                        </span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mt-4 pt-4 border-t border-slate-50">
                        <div class="flex items-center gap-2 text-sm">
                            <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                            <span class="text-slate-500 truncate"><span class="font-medium text-slate-700">From:</span> {{ order.origin }}</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm">
                            <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                            <span class="text-slate-500 truncate"><span class="font-medium text-slate-700">To:</span> {{ order.destination }}</span>
                        </div>
                    </div>
                    <div v-if="order.pickup_date" class="mt-2 flex items-center gap-2 text-xs text-slate-400">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                        Pickup: {{ order.pickup_date }}
                    </div>
                </router-link>
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
    try { orders.value = await fetchOrders() } catch {} finally { loading.value = false }
})

function formatDate(d) {
    if (!d) return ''
    return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

function formatStatus(s) {
    const map = { draft: 'Draft', confirmed: 'Confirmed', in_transit: 'In Transit', delivered: 'Delivered', cancelled: 'Cancelled' }
    return map[s] || s
}

function statusClass(s) {
    const map = {
        draft: 'bg-slate-100 text-slate-600',
        confirmed: 'bg-blue-50 text-blue-700',
        in_transit: 'bg-amber-50 text-amber-700',
        delivered: 'bg-emerald-50 text-emerald-700',
        cancelled: 'bg-red-50 text-red-700',
    }
    return map[s] || 'bg-slate-100 text-slate-600'
}

function statusDotClass(s) {
    const map = {
        draft: 'bg-slate-400',
        confirmed: 'bg-blue-500',
        in_transit: 'bg-amber-500',
        delivered: 'bg-emerald-500',
        cancelled: 'bg-red-500',
    }
    return map[s] || 'bg-slate-400'
}
</script>
