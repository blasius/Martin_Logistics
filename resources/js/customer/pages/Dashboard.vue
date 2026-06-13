<template>
    <div>
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-slate-800">Welcome back, {{ authStore.user?.name }}</h1>
            <p class="text-slate-500 mt-1">{{ formatDate(new Date()) }}</p>
        </div>

        <div v-if="loading" class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">
            <div v-for="i in 3" :key="i" class="bg-white rounded-2xl p-6 border border-slate-100 animate-pulse">
                <div class="h-3 bg-slate-100 rounded w-20 mb-3"></div>
                <div class="h-8 bg-slate-100 rounded w-16"></div>
            </div>
        </div>

        <template v-else>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">
                <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm hover:shadow-md transition-shadow duration-200">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Orders</span>
                        <div class="w-9 h-9 rounded-xl bg-indigo-50 flex items-center justify-center">
                            <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/></svg>
                        </div>
                    </div>
                    <p class="text-3xl font-black text-slate-800">{{ orders.length }}</p>
                    <p class="text-xs text-slate-400 mt-1.5">All time order count</p>
                </div>
                <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm hover:shadow-md transition-shadow duration-200">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">In Transit</span>
                        <div class="w-9 h-9 rounded-xl bg-amber-50 flex items-center justify-center">
                            <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/></svg>
                        </div>
                    </div>
                    <p class="text-3xl font-black text-amber-600">{{ inTransit }}</p>
                    <p class="text-xs text-slate-400 mt-1.5">Orders currently in transit</p>
                </div>
                <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm hover:shadow-md transition-shadow duration-200">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Delivered</span>
                        <div class="w-9 h-9 rounded-xl bg-emerald-50 flex items-center justify-center">
                            <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z"/></svg>
                        </div>
                    </div>
                    <p class="text-3xl font-black text-emerald-600">{{ delivered }}</p>
                    <p class="text-xs text-slate-400 mt-1.5">Successfully delivered orders</p>
                </div>
            </div>
        </template>

        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.75h16.5"/></svg>
                    </div>
                    <h2 class="text-sm font-bold text-slate-700">Recent Orders</h2>
                </div>
                <router-link to="/orders" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700 transition-colors">
                    View All &rarr;
                </router-link>
            </div>

            <div v-if="loading" class="p-6 space-y-4">
                <div v-for="i in 3" :key="i" class="h-12 bg-slate-50 rounded-xl animate-pulse"></div>
            </div>

            <div v-else-if="orders.length === 0" class="px-6 py-12 text-center">
                <div class="w-16 h-16 rounded-2xl bg-slate-50 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/></svg>
                </div>
                <p class="text-slate-500 font-medium mb-2">No orders yet</p>
                <p class="text-slate-400 text-sm mb-4">Place your first order to get started</p>
                <router-link to="/place-order"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-violet-500 text-white text-sm font-semibold rounded-xl hover:from-indigo-700 hover:to-violet-600 transition-all duration-200 shadow-md shadow-indigo-200/50 active:scale-[0.98]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Place Your First Order
                </router-link>
            </div>

            <table v-else class="w-full">
                <thead>
                    <tr class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider border-b border-slate-50">
                        <th class="text-left px-6 py-3.5">Reference</th>
                        <th class="text-left px-6 py-3.5 hidden sm:table-cell">Origin</th>
                        <th class="text-left px-6 py-3.5 hidden md:table-cell">Destination</th>
                        <th class="text-center px-6 py-3.5">Status</th>
                        <th class="text-right px-6 py-3.5 hidden sm:table-cell">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <tr v-for="order in orders.slice(0, 5)" :key="order.id"
                        class="hover:bg-slate-50/50 transition-colors duration-150">
                        <td class="px-6 py-4">
                            <router-link :to="`/orders/${order.id}`"
                                class="font-semibold text-sm text-indigo-600 hover:text-indigo-700 transition-colors">
                                {{ order.reference }}
                            </router-link>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-600 hidden sm:table-cell truncate max-w-[120px]">{{ order.origin }}</td>
                        <td class="px-6 py-4 text-sm text-slate-600 hidden md:table-cell truncate max-w-[120px]">{{ order.destination }}</td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center gap-1.5 text-[11px] font-bold px-3 py-1.5 rounded-full" :class="statusClass(order.status)">
                                <span class="w-1.5 h-1.5 rounded-full" :class="statusDotClass(order.status)"></span>
                                {{ formatStatus(order.status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right text-xs text-slate-400 hidden sm:table-cell">{{ order.created_at?.slice(0, 10) }}</td>
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
    try { orders.value = await fetchOrders() } catch {} finally { loading.value = false }
})

const inTransit = computed(() => orders.value.filter(o => o.status === 'in_transit').length)
const delivered = computed(() => orders.value.filter(o => o.status === 'delivered').length)

function formatDate(date) {
    return date.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' })
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
