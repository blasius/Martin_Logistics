<template>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="font-semibold mb-3">Select Orders for Consolidation</h3>
                <div class="space-y-2 max-h-96 overflow-y-auto">
                    <label v-for="order in orders" :key="order.id" class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded border">
                        <input type="checkbox" :value="order.id" v-model="selectedOrderIds" class="rounded" />
                        <div class="flex-1 text-sm">
                            <span class="font-medium">{{ order.reference || '#' + order.id }}</span>
                            <span class="text-gray-500 ml-2">{{ order.origin }} → {{ order.destination }}</span>
                        </div>
                        <div class="text-xs text-gray-500">
                            <span v-if="order.weight_kg" class="mr-2">{{ order.weight_kg }} kg</span>
                            <span v-if="order.volume_m3">{{ order.volume_m3 }} m³</span>
                        </div>
                    </label>
                </div>
                <button @click="optimize" :disabled="!selectedOrderIds.length" class="mt-3 px-4 py-2 bg-blue-600 text-white rounded text-sm hover:bg-blue-700 disabled:opacity-50">
                    Optimize Loads
                </button>
            </div>

            <div v-if="result" class="bg-white rounded-lg shadow p-4">
                <h3 class="font-semibold mb-3">Optimization Result</h3>
                <div v-for="assignment in result.assignments" :key="assignment.vehicle.id" class="border rounded p-3 mb-3">
                    <div class="flex justify-between items-center">
                        <div>
                            <span class="font-medium">{{ assignment.vehicle.plate_number }}</span>
                            <span class="text-xs text-gray-500 ml-2">{{ assignment.vehicle.make }} {{ assignment.vehicle.model }}</span>
                        </div>
                        <div class="text-xs text-gray-600">
                            {{ assignment.total_weight || 0 }}kg / {{ assignment.vehicle.max_payload || '∞' }}kg ·
                            {{ assignment.total_volume || 0 }}m³ / {{ assignment.vehicle.volume_capacity || '∞' }}m³
                        </div>
                    </div>
                    <div class="mt-2 text-xs text-gray-500">
                        Orders: <span v-for="o in assignment.orders" :key="o.id" class="inline-block bg-gray-100 rounded px-2 py-0.5 mr-1">{{ o.reference || '#' + o.id }}</span>
                    </div>
                </div>
                <div v-if="result.remaining.length" class="text-sm text-red-600 mt-2">
                    {{ result.remaining.length }} order(s) could not be assigned.
                </div>
            </div>
        </div>

        <div class="space-y-4">
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="font-semibold mb-3">Available Vehicles</h3>
                <div v-for="v in vehicles" :key="v.id" class="border-b py-2 text-sm">
                    <div class="font-medium">{{ v.plate_number }}</div>
                    <div class="text-xs text-gray-500">
                        {{ v.volume_capacity ? v.volume_capacity + 'm³' : '∞' }} /
                        {{ v.max_payload ? v.max_payload + 'kg' : '∞' }}
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="font-semibold mb-3">Check Suitability</h3>
                <div class="space-y-2 text-sm">
                    <select v-model="suitVehicleId" class="w-full border rounded px-2 py-1">
                        <option value="">Select vehicle</option>
                        <option v-for="v in vehicles" :key="v.id" :value="v.id">{{ v.plate_number }}</option>
                    </select>
                    <div class="grid grid-cols-2 gap-2">
                        <input v-model.number="suitWeight" placeholder="Weight (kg)" type="number" class="border rounded px-2 py-1" />
                        <input v-model.number="suitVolume" placeholder="Volume (m³)" type="number" class="border rounded px-2 py-1" />
                    </div>
                    <button @click="checkSuitability" class="w-full px-3 py-1.5 bg-gray-100 rounded text-sm hover:bg-gray-200">Check</button>
                    <div v-if="suitResult !== null" :class="suitResult ? 'text-green-600' : 'text-red-600'" class="text-sm font-medium">
                        {{ suitResult ? 'Suitable' : 'Not suitable' }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { ordersApi } from '../../api/orders'
import { loadOptimizationApi as loadApi } from '../../api/loadOptimization'

const orders = ref([])
const vehicles = ref([])
const selectedOrderIds = ref([])
const result = ref(null)
const suitVehicleId = ref('')
const suitWeight = ref(0)
const suitVolume = ref(0)
const suitResult = ref(null)

async function load() {
    const [o, v] = await Promise.all([
        ordersApi.getAll(),
        loadApi.vehicles({})
    ])
    orders.value = o.data.data || o.data
    vehicles.value = v.data
}

async function optimize() {
    const { data } = await loadApi.optimize({ order_ids: selectedOrderIds.value })
    result.value = data
}

async function checkSuitability() {
    if (!suitVehicleId.value) return
    const { data } = await loadApi.suitability(suitVehicleId.value, suitWeight.value, suitVolume.value)
    suitResult.value = data.suitable
}

onMounted(load)
</script>
