<template>
    <div>
        <router-link to="/orders"
            class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-indigo-600 transition-colors duration-200 mb-6 group">
            <svg class="w-4 h-4 group-hover:-translate-x-0.5 transition-transform duration-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            Back to Orders
        </router-link>

        <div v-if="loading" class="bg-white rounded-2xl border border-slate-100 shadow-sm p-8">
            <div class="animate-pulse space-y-6">
                <div class="flex items-center justify-between">
                    <div class="space-y-2">
                        <div class="h-6 bg-slate-100 rounded w-40"></div>
                        <div class="h-3 bg-slate-50 rounded w-24"></div>
                    </div>
                    <div class="h-6 bg-slate-100 rounded w-20"></div>
                </div>
                <div class="h-px bg-slate-100"></div>
                <div class="grid grid-cols-2 gap-6">
                    <div class="h-4 bg-slate-50 rounded w-32"></div>
                    <div class="h-4 bg-slate-50 rounded w-32"></div>
                </div>
            </div>
        </div>

        <div v-else-if="order" class="max-w-3xl">
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 sm:p-8 mb-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-800">{{ order.reference }}</h1>
                        <p class="text-sm text-slate-400 mt-1">Placed {{ formatDate(order.created_at) }}</p>
                    </div>
                    <span class="inline-flex items-center gap-1.5 text-xs font-bold px-4 py-2 rounded-full self-start" :class="statusClass(order.status)">
                        <span class="w-1.5 h-1.5 rounded-full" :class="statusDotClass(order.status)"></span>
                        {{ formatStatus(order.status) }}
                    </span>
                </div>

                <div class="mb-8">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Shipment Progress</h3>
                        <span class="text-xs text-slate-400">{{ currentStepIndex }} of {{ steps.length }}</span>
                    </div>
                    <div class="relative">
                        <div class="absolute top-4 left-0 right-0 h-0.5 bg-slate-100">
                            <div class="h-full bg-gradient-to-r from-indigo-600 to-violet-500 transition-all duration-700 ease-out" :style="{ width: progressPercent + '%' }"></div>
                        </div>
                        <div class="relative flex justify-between">
                            <div v-for="(step, idx) in steps" :key="step.key" class="flex flex-col items-center">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-all duration-500"
                                    :class="idx <= currentStepIndex ? 'bg-gradient-to-r from-indigo-600 to-violet-500 text-white shadow-md shadow-indigo-200/50' : 'bg-slate-100 text-slate-400'">
                                    <svg v-if="idx < currentStepIndex" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                    <span v-else>{{ idx + 1 }}</span>
                                </div>
                                <p class="text-[10px] font-medium mt-1.5 text-center" :class="idx <= currentStepIndex ? 'text-slate-700' : 'text-slate-400'">{{ step.label }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="h-px bg-slate-100 mb-6"></div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-5">
                    <div>
                        <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Origin</p>
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                            <p class="text-sm font-medium text-slate-800">{{ order.origin }}</p>
                        </div>
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Destination</p>
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                            <p class="text-sm font-medium text-slate-800">{{ order.destination }}</p>
                        </div>
                    </div>
                    <div v-if="order.pickup_date">
                        <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Pickup Date</p>
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                            <p class="text-sm font-medium text-slate-800">{{ order.pickup_date }}</p>
                        </div>
                    </div>
                    <div v-if="order.price">
                        <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Price</p>
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <p class="text-sm font-bold text-slate-800">{{ Number(order.price).toLocaleString() }} FRW</p>
                        </div>
                    </div>
                </div>

                <div v-if="order.notes" class="mt-6 pt-6 border-t border-slate-100">
                    <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Notes</p>
                    <p class="text-sm text-slate-600 bg-slate-50 p-4 rounded-xl leading-relaxed">{{ order.notes }}</p>
                </div>

                <div v-if="order.updated_at && order.updated_at !== order.created_at" class="mt-4 text-xs text-slate-400">
                    Last updated {{ formatDate(order.updated_at) }}
                </div>
            </div>
        </div>

        <div v-else class="bg-white rounded-2xl border border-slate-100 shadow-sm p-12 text-center">
            <div class="w-16 h-16 rounded-2xl bg-amber-50 flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-amber-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
            </div>
            <p class="text-slate-600 font-semibold">Order not found</p>
            <p class="text-slate-400 text-sm mt-1 mb-4">The order you're looking for doesn't exist or has been removed.</p>
            <router-link to="/orders"
                class="inline-flex items-center gap-2 text-sm font-semibold text-indigo-600 hover:text-indigo-700 transition-colors">
                View all orders &rarr;
            </router-link>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { fetchOrder } from '../api/orders'

const props = defineProps({ id: [String, Number] })
const order = ref(null)
const loading = ref(true)

const steps = [
    { key: 'placed', label: 'Placed' },
    { key: 'confirmed', label: 'Confirmed' },
    { key: 'in_transit', label: 'In Transit' },
    { key: 'delivered', label: 'Delivered' },
]

const statusOrder = { draft: -1, placed: 0, confirmed: 1, in_transit: 2, delivered: 3 }

const currentStepIndex = computed(() => {
    if (!order.value) return -1
    const idx = statusOrder[order.value.status]
    return idx !== undefined ? idx : -1
})

const progressPercent = computed(() => {
    if (currentStepIndex.value < 0) return 0
    return (currentStepIndex.value / (steps.length - 1)) * 100
})

onMounted(async () => {
    try { order.value = await fetchOrder(props.id) } catch {} finally { loading.value = false }
})

function formatDate(d) {
    if (!d) return ''
    return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

function formatStatus(s) {
    const map = { draft: 'Draft', placed: 'Placed', confirmed: 'Confirmed', in_transit: 'In Transit', delivered: 'Delivered', cancelled: 'Cancelled' }
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
