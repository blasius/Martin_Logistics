<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { truckRequestsApi } from '../../api/truck-requests'

const route = useRoute()
const router = useRouter()
const request = ref<any>(null)
const loading = ref(true)

function statusClass(status: string) {
    const map: Record<string, string> = {
        draft: 'bg-gray-100 text-gray-600',
        submitted: 'bg-blue-100 text-blue-700',
        truck_assigned: 'bg-yellow-100 text-yellow-700',
        in_progress: 'bg-green-100 text-green-700',
        completed: 'bg-slate-100 text-slate-600',
        cancelled: 'bg-red-100 text-red-700',
    }
    return map[status] ?? 'bg-gray-100 text-gray-600'
}

async function fetchRequest() {
    loading.value = true
    try {
        const res = await truckRequestsApi.show(route.params.id as string)
        request.value = res.data
    } catch {} finally { loading.value = false }
}

async function remove() {
    if (!confirm('Delete this truck request?')) return
    try {
        await truckRequestsApi.destroy(request.value.id)
        router.push('/truck-requests')
    } catch {}
}

onMounted(fetchRequest)
</script>

<template>
    <div>
        <div class="mb-6">
            <router-link to="/truck-requests" class="text-sm text-emerald-600 hover:text-emerald-700 mb-2 inline-block">&larr; Back</router-link>
            <div v-if="request" class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold text-slate-800">{{ request.reference }}</h1>
                    <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium"
                        :class="statusClass(request.status)">{{ request.status.replace('_', ' ') }}</span>
                </div>
                <button @click="remove"
                    class="px-4 py-2 text-sm font-medium text-red-600 bg-white border border-red-200 rounded-lg hover:bg-red-50">Delete</button>
            </div>
        </div>

        <div v-if="loading" class="p-8 text-center text-slate-400">Loading...</div>

        <template v-else-if="request">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6">
                        <h2 class="text-lg font-semibold text-slate-800 mb-4">Cargo Details</h2>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-slate-400 block text-xs">Cargo Type</span>
                                <span class="font-medium">{{ request.cargo_type }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-xs">Tonnage</span>
                                <span class="font-medium">{{ Number(request.tonnage).toLocaleString() }} tons</span>
                            </div>
                            <div class="col-span-2">
                                <span class="text-slate-400 block text-xs">Special Requirements</span>
                                <span class="font-medium">{{ request.special_requirements || 'None' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6">
                        <h2 class="text-lg font-semibold text-slate-800 mb-4">Route</h2>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-slate-400 block text-xs">Pickup</span>
                                <span class="font-medium">{{ request.pickup_location }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-xs">Dropoff</span>
                                <span class="font-medium">{{ request.dropoff_location }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-xs">Expected Pickup</span>
                                <span class="font-medium">{{ request.expected_pickup_date }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-xs">Expected Delivery</span>
                                <span class="font-medium">{{ request.expected_delivery_date }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6">
                        <h2 class="text-lg font-semibold text-slate-800 mb-4">Commercial</h2>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-slate-400 block text-xs">Agreed Rate</span>
                                <span class="font-medium">{{ request.agreed_rate ? Number(request.agreed_rate).toLocaleString() : 'Not set' }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-xs">Client Reference</span>
                                <span class="font-medium">{{ request.client_reference || 'N/A' }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-xs">Payment Status</span>
                                <span class="font-medium capitalize">{{ request.payment_status }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6">
                        <h2 class="text-lg font-semibold text-slate-800 mb-4">Assignment</h2>
                        <div class="space-y-3 text-sm">
                            <div>
                                <span class="text-slate-400 block text-xs">Sales Person</span>
                                <span class="font-medium">{{ request.sales_person?.name }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-xs">Vehicle</span>
                                <span class="font-medium">{{ request.assigned_vehicle?.plate_number ?? 'Not assigned' }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-xs">Trailer</span>
                                <span class="font-medium">{{ request.assigned_trailer?.plate_number ?? 'None' }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-xs">Dispatcher</span>
                                <span class="font-medium">{{ request.dispatcher?.name ?? 'Not assigned' }}</span>
                            </div>
                            <div v-if="request.trip">
                                <span class="text-slate-400 block text-xs">Trip</span>
                                <router-link :to="`/trips/${request.trip.id}`"
                                    class="font-medium text-emerald-600 hover:text-emerald-700">
                                    {{ request.trip.reference }}
                                </router-link>
                            </div>
                        </div>
                    </div>

                    <div v-if="request.notes" class="bg-white rounded-xl border border-slate-100 shadow-sm p-6">
                        <h2 class="text-lg font-semibold text-slate-800 mb-2">Notes</h2>
                        <p class="text-sm text-slate-600 whitespace-pre-wrap">{{ request.notes }}</p>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>
