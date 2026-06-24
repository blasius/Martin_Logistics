<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { truckRequestsApi } from '../../api/truck-requests'

const router = useRouter()
const loading = ref(false)
const orders = ref<any[]>([])

const form = ref({
    order_id: null as number | null,
    cargo_type: '',
    tonnage: 0,
    pickup_location: '',
    dropoff_location: '',
    expected_pickup_date: '',
    expected_delivery_date: '',
    special_requirements: '',
    agreed_rate: null as number | null,
    client_reference: '',
    notes: '',
})

async function submit() {
    loading.value = true
    try {
        await truckRequestsApi.create(form.value)
        router.push('/truck-requests')
    } catch {} finally { loading.value = false }
}

async function loadOrders() {
    try {
        const res = await (await import('../../api/orders')).ordersApi.getAll({ per_page: 500, status: 'confirmed' })
        orders.value = res.data.data ?? res.data
    } catch {}
}

async function onOrderSelect() {
    const order = orders.value.find((o: any) => o.id === form.value.order_id)
    if (order) {
        form.value.cargo_type = form.value.cargo_type || order.cargo_type || ''
        form.value.tonnage = form.value.tonnage || order.tonnage || 0
        form.value.pickup_location = form.value.pickup_location || order.pickup_location || ''
        form.value.dropoff_location = form.value.dropoff_location || order.dropoff_location || ''
        form.value.client_reference = form.value.client_reference || order.reference || ''
    }
}

onMounted(loadOrders)
</script>

<template>
    <div>
        <div class="mb-6">
            <router-link to="/truck-requests" class="text-sm text-emerald-600 hover:text-emerald-700 mb-2 inline-block">&larr; Back</router-link>
            <h1 class="text-2xl font-bold text-slate-800">Create Truck Request</h1>
        </div>

        <form @submit.prevent="submit" class="max-w-3xl space-y-6">
            <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Order</label>
                    <select v-model="form.order_id" required @change="onOrderSelect"
                        class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500">
                        <option value="">Select confirmed order</option>
                        <option v-for="o in orders" :key="o.id" :value="o.id">{{ o.reference }} — {{ o.client?.name }}</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Cargo Type</label>
                        <input v-model="form.cargo_type" required placeholder="e.g. Cement, Fuel, General"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Tonnage (T)</label>
                        <input v-model="form.tonnage" type="number" step="0.1" min="0" required
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Pickup Location</label>
                        <input v-model="form.pickup_location" required
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Dropoff Location</label>
                        <input v-model="form.dropoff_location" required
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Expected Pickup Date</label>
                        <input v-model="form.expected_pickup_date" type="date" required
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Expected Delivery Date</label>
                        <input v-model="form.expected_delivery_date" type="date" required
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500" />
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Special Requirements</label>
                    <textarea v-model="form.special_requirements" rows="3" placeholder="Reefer, hazardous, fragile, tarpaulin..."
                        class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Agreed Rate</label>
                        <input v-model="form.agreed_rate" type="number" step="0.01" min="0"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Client Reference</label>
                        <input v-model="form.client_reference"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500" />
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Notes</label>
                    <textarea v-model="form.notes" rows="2"
                        class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500"></textarea>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <router-link to="/truck-requests"
                    class="px-4 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50">
                    Cancel
                </router-link>
                <button type="submit" :disabled="loading"
                    class="px-6 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 disabled:opacity-50">
                    {{ loading ? 'Creating...' : 'Submit Request' }}
                </button>
            </div>
        </form>
    </div>
</template>
