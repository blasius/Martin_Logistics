<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { truckRequestsApi } from '../../api/truck-requests'

const requests = ref<any[]>([])
const vehicles = ref<any[]>([])
const loading = ref(true)
const assignModal = ref(false)
const selectedRequest = ref<any>(null)
const assignForm = ref({ vehicle_id: null as number | null, trailer_id: null as number | null })
const assigning = ref(false)

async function fetchQueue() {
    loading.value = true
    try {
        const res = await truckRequestsApi.queue()
        requests.value = res.data.data ?? res.data
    } catch {} finally { loading.value = false }
}

async function openAssign(r: any) {
    selectedRequest.value = r
    assignForm.value = { vehicle_id: null, trailer_id: null }
    vehicles.value = []
    try {
        const res = await truckRequestsApi.availableVehicles(r.id)
        vehicles.value = res.data
    } catch {}
    assignModal.value = true
}

async function doAssign() {
    if (!assignForm.value.vehicle_id) return
    assigning.value = true
    try {
        await truckRequestsApi.assign(selectedRequest.value.id, assignForm.value)
        assignModal.value = false
        await fetchQueue()
    } catch {} finally { assigning.value = false }
}

function statusClass(status: string) {
    const map: Record<string, string> = {
        submitted: 'bg-blue-100 text-blue-700',
        truck_assigned: 'bg-yellow-100 text-yellow-700',
    }
    return map[status] ?? 'bg-gray-100 text-gray-600'
}

function tonnageMatch(v: any) {
    if (!selectedRequest.value) return true
    const capacity = v.capacity_kg ?? v.max_weight ?? 0
    if (capacity <= 0) return true
    return capacity >= selectedRequest.value.tonnage * 1000
}

onMounted(fetchQueue)
</script>

<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-slate-800">Truck Assignment Queue</h1>
            <button @click="fetchQueue"
                class="px-4 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50">
                Refresh
            </button>
        </div>

        <div v-if="loading" class="p-8 text-center text-slate-400">Loading...</div>

        <div v-else class="space-y-4">
            <div v-for="r in requests" :key="r.id"
                class="bg-white rounded-xl border border-slate-100 shadow-sm p-5">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <span class="text-sm font-semibold text-slate-800">{{ r.reference }}</span>
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium"
                                :class="statusClass(r.status)">{{ r.status.replace('_', ' ') }}</span>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <div>
                                <span class="text-slate-400 text-xs block">Client</span>
                                <span class="font-medium">{{ r.order?.client?.name ?? 'N/A' }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 text-xs block">Cargo</span>
                                <span class="font-medium">{{ r.cargo_type }} — {{ Number(r.tonnage).toLocaleString() }}T</span>
                            </div>
                            <div>
                                <span class="text-slate-400 text-xs block">Route</span>
                                <span>{{ r.pickup_location }} → {{ r.dropoff_location }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 text-xs block">Pickup Date</span>
                                <span>{{ r.expected_pickup_date }}</span>
                            </div>
                        </div>
                        <div v-if="r.special_requirements" class="mt-2">
                            <span class="text-xs text-amber-600 bg-amber-50 px-2 py-1 rounded">{{ r.special_requirements }}</span>
                        </div>
                        <div class="mt-2 text-xs text-slate-400">
                            Sales: {{ r.sales_person?.name }} · Rate: {{ r.agreed_rate ? Number(r.agreed_rate).toLocaleString() : 'TBD' }}
                        </div>
                    </div>
                    <div class="ml-4">
                        <button v-if="r.status === 'submitted'" @click="openAssign(r)"
                            class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700">
                            Assign Truck
                        </button>
                        <div v-else class="text-right text-sm">
                            <div class="text-slate-600">{{ r.assigned_vehicle?.plate_number }}</div>
                            <div class="text-slate-400 text-xs">{{ r.dispatcher?.name ?? 'No dispatcher' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="!requests.length" class="text-center py-12 text-slate-400">
                No pending truck requests in the queue.
            </div>
        </div>

        <!-- Assign Modal -->
        <div v-if="assignModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
            @click.self="assignModal = false">
            <div class="bg-white rounded-xl shadow-xl max-w-lg w-full mx-4">
                <div class="flex items-center justify-between p-6 border-b border-slate-100">
                    <h2 class="text-lg font-semibold text-slate-800">Assign Truck</h2>
                    <button @click="assignModal = false"
                        class="text-slate-400 hover:text-slate-600 text-xl leading-none">&times;</button>
                </div>
                <div class="p-6 space-y-4">
                    <div class="text-sm text-slate-600 mb-2">
                        {{ selectedRequest?.reference }} · {{ selectedRequest?.cargo_type }} · {{ Number(selectedRequest?.tonnage).toLocaleString() }}T
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Select Vehicle</label>
                        <select v-model="assignForm.vehicle_id" required
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500">
                            <option value="">Choose...</option>
                            <option v-for="v in vehicles" :key="v.id" :value="v.id" :disabled="!tonnageMatch(v)">
                                {{ v.plate_number }} — {{ v.make }} {{ v.model }}
                                ({{ v.capacity_kg ? (v.capacity_kg / 1000).toFixed(1) + 'T' : 'N/A' }})
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Trailer (optional)</label>
                        <select v-model="assignForm.trailer_id"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500">
                            <option :value="null">None</option>
                            <option v-for="v in vehicles.filter(x => x.id !== assignForm.vehicle_id)" :key="v.id" :value="v.id">
                                {{ v.plate_number }} — {{ v.make }} {{ v.model }}
                            </option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end gap-3 p-6 border-t border-slate-100">
                    <button @click="assignModal = false"
                        class="px-4 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-200 rounded-lg">
                        Cancel
                    </button>
                    <button @click="doAssign" :disabled="!assignForm.vehicle_id || assigning"
                        class="px-6 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 disabled:opacity-50">
                        {{ assigning ? 'Assigning...' : 'Assign & Create Trip' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
