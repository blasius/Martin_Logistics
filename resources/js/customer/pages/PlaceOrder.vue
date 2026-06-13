<template>
    <div>
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-slate-800">Place a New Order</h1>
            <p class="text-slate-500 mt-1">Fill in the details below to create a shipment order</p>
        </div>

        <div class="max-w-2xl">
            <div v-if="success" class="bg-emerald-50/80 border border-emerald-200 rounded-2xl p-8 text-center">
                <div class="w-16 h-16 rounded-full bg-emerald-100 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h2 class="text-xl font-bold text-slate-800 mb-2">Order Placed!</h2>
                <p class="text-slate-600 mb-6">{{ success }}</p>
                <div class="flex items-center justify-center gap-3">
                    <router-link :to="`/orders/${lastOrderId}`"
                        class="px-5 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 transition-colors duration-200 shadow-md shadow-indigo-200/50">
                        View Order
                    </router-link>
                    <button @click="resetForm"
                        class="px-5 py-2.5 border-2 border-slate-200 text-slate-600 text-sm font-semibold rounded-xl hover:border-slate-300 transition-colors duration-200">
                        Place Another
                    </button>
                </div>
            </div>

            <div v-else class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 sm:p-8">
                <form @submit.prevent="submitOrder">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-6">
                        <div>
                            <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5 block">Origin <span class="text-red-400">*</span></label>
                            <div class="relative">
                                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                                <input v-model="form.origin" type="text" placeholder="Pickup location" :disabled="saving"
                                    class="w-full pl-11 pr-4 py-3.5 rounded-xl border-2 border-slate-200 text-sm font-medium text-slate-800 placeholder:text-slate-400 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 outline-none transition-all"
                                    required />
                            </div>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5 block">Destination <span class="text-red-400">*</span></label>
                            <div class="relative">
                                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                                <input v-model="form.destination" type="text" placeholder="Dropoff location" :disabled="saving"
                                    class="w-full pl-11 pr-4 py-3.5 rounded-xl border-2 border-slate-200 text-sm font-medium text-slate-800 placeholder:text-slate-400 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 outline-none transition-all"
                                    required />
                            </div>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5 block">Pickup Date</label>
                            <div class="relative">
                                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                                <input v-model="form.pickup_date" type="date" :disabled="saving"
                                    class="w-full pl-11 pr-4 py-3.5 rounded-xl border-2 border-slate-200 text-sm font-medium text-slate-800 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 outline-none transition-all" />
                            </div>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5 block">Reference <span class="text-slate-300">(optional)</span></label>
                            <div class="relative">
                                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 100 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186l9.566-5.314m-9.566 7.5l9.566 5.314m0 0a2.25 2.25 0 103.935 2.186 2.25 2.25 0 00-3.935-2.186zm0-12.814a2.25 2.25 0 103.933-2.185 2.25 2.25 0 00-3.933 2.185z"/></svg>
                                <input v-model="form.reference" type="text" placeholder="Your ref code" :disabled="saving"
                                    class="w-full pl-11 pr-4 py-3.5 rounded-xl border-2 border-slate-200 text-sm font-medium text-slate-800 placeholder:text-slate-400 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 outline-none transition-all" />
                            </div>
                        </div>
                    </div>

                    <div class="mb-6">
                        <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5 block">Notes</label>
                        <textarea v-model="form.notes" rows="3" placeholder="Special instructions, cargo details, or any additional information..."
                            class="w-full px-4 py-3.5 rounded-xl border-2 border-slate-200 text-sm font-medium text-slate-800 placeholder:text-slate-400 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 outline-none transition-all resize-none"></textarea>
                    </div>

                    <p v-if="error" class="text-red-500 text-xs font-medium mb-4 flex items-center gap-1.5 bg-red-50 p-3 rounded-lg">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                        {{ error }}
                    </p>

                    <button type="submit" :disabled="saving"
                        class="w-full bg-gradient-to-r from-indigo-600 to-violet-500 text-white py-3.5 rounded-xl font-semibold hover:from-indigo-700 hover:to-violet-600 transition-all duration-200 disabled:opacity-50 shadow-lg shadow-indigo-200/50 active:scale-[0.98]">
                        <span v-if="saving" class="flex items-center justify-center gap-2">
                            <div class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                            Submitting Order...
                        </span>
                        <span v-else class="flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                            Place Order
                        </span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { createOrder } from '../api/orders'

const router = useRouter()
const form = reactive({ origin: '', destination: '', pickup_date: '', notes: '', reference: '' })
const saving = ref(false)
const error = ref('')
const success = ref('')
const lastOrderId = ref(null)

async function submitOrder() {
    saving.value = true
    error.value = ''
    success.value = ''
    try {
        const clean = { ...form }
        if (!clean.reference) delete clean.reference
        if (!clean.pickup_date) delete clean.pickup_date
        if (!clean.notes) delete clean.notes
        const data = await createOrder(clean)
        lastOrderId.value = data.order?.id || data.id
        success.value = `Order ${data.order?.reference || data.reference} has been placed successfully!`
    } catch (e) {
        const msg = e.response?.data?.message || 'Failed to place order.'
        const errs = e.response?.data?.errors
        if (errs) error.value = Object.values(errs).flat()[0]
        else error.value = msg
    } finally {
        saving.value = false
    }
}

function resetForm() {
    form.origin = ''
    form.destination = ''
    form.pickup_date = ''
    form.notes = ''
    form.reference = ''
    success.value = ''
    error.value = ''
}
</script>
