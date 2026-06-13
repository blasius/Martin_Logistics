<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Place a New Order</h1>

        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 max-w-xl">
            <form @submit.prevent="submitOrder">
                <div class="mb-4">
                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Origin *</label>
                    <input v-model="form.origin" type="text" placeholder="Pickup location" :disabled="saving"
                           class="border w-full p-3 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none mt-1" required />
                </div>
                <div class="mb-4">
                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Destination *</label>
                    <input v-model="form.destination" type="text" placeholder="Dropoff location" :disabled="saving"
                           class="border w-full p-3 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none mt-1" required />
                </div>
                <div class="mb-4">
                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Pickup Date</label>
                    <input v-model="form.pickup_date" type="date" :disabled="saving"
                           class="border w-full p-3 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none mt-1" />
                </div>
                <div class="mb-6">
                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Notes</label>
                    <textarea v-model="form.notes" rows="3" placeholder="Special instructions..." :disabled="saving"
                              class="border w-full p-3 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none mt-1 resize-none"></textarea>
                </div>

                <p v-if="error" class="text-red-500 text-xs font-medium mb-3">{{ error }}</p>
                <p v-if="success" class="text-green-600 text-xs font-medium mb-3">{{ success }}</p>

                <button type="submit" :disabled="saving"
                        class="w-full bg-indigo-600 text-white p-3 rounded-lg font-semibold hover:bg-indigo-700 transition disabled:bg-indigo-300">
                    {{ saving ? 'Placing Order...' : 'Place Order' }}
                </button>
            </form>
        </div>
    </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { createOrder } from '../api/orders'

const router = useRouter()
const form = reactive({ origin: '', destination: '', pickup_date: '', notes: '' })
const saving = ref(false)
const error = ref('')
const success = ref('')

async function submitOrder() {
    saving.value = true
    error.value = ''
    success.value = ''
    try {
        const data = await createOrder({ ...form })
        success.value = `Order ${data.order.reference} placed successfully!`
        setTimeout(() => router.push(`/orders/${data.order.id}`), 1500)
    } catch (e) {
        const msg = e.response?.data?.message || 'Failed to place order.'
        const errs = e.response?.data?.errors
        if (errs) {
            error.value = Object.values(errs).flat()[0]
        } else {
            error.value = msg
        }
    } finally {
        saving.value = false
    }
}
</script>
