<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { podApi } from '../../api/proofs-of-delivery'
import { ordersApi } from '../../api/orders'

const router = useRouter()

const form = ref({
    order_id: '',
    received_by_name: '',
    received_by_relation: '',
    signature_data: '',
    notes: '',
    gps_lat: '',
    gps_lng: '',
})

const orders = ref<any[]>([])
const photo = ref<File | null>(null)
const submitting = ref(false)

onMounted(async () => {
    try {
        const res = await ordersApi.getAll()
        orders.value = res.data
    } catch {}
})

function onPhotoChange(e: Event) {
    const target = e.target as HTMLInputElement
    if (target.files?.length) {
        photo.value = target.files[0]
    }
}

async function submit() {
    submitting.value = true
    try {
        const fd = new FormData()
        fd.append('order_id', form.value.order_id)
        fd.append('received_by_name', form.value.received_by_name)
        if (form.value.received_by_relation) fd.append('received_by_relation', form.value.received_by_relation)
        if (form.value.signature_data) fd.append('signature_data', form.value.signature_data)
        if (form.value.notes) fd.append('notes', form.value.notes)
        if (form.value.gps_lat) fd.append('gps_lat', form.value.gps_lat)
        if (form.value.gps_lng) fd.append('gps_lng', form.value.gps_lng)
        if (photo.value) fd.append('photo', photo.value)

        const res = await podApi.create(fd)
        router.push(`/proofs-of-delivery/${res.data.pod.id}`)
    } catch {} finally {
        submitting.value = false
    }
}
</script>

<template>
    <div>
        <router-link to="/proofs-of-delivery"
            class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-indigo-600 transition-colors mb-6">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            Back to PODs
        </router-link>

        <div class="max-w-2xl">
            <h1 class="text-2xl font-bold text-slate-800 mb-6">New Proof of Delivery</h1>

            <form @submit.prevent="submit" class="bg-white rounded-xl border border-slate-100 shadow-sm p-6 sm:p-8 space-y-5">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Order *</label>
                    <select v-model="form.order_id" required
                        class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                        <option value="" disabled>Select order</option>
                        <option v-for="o in orders" :key="o.id" :value="o.id">{{ o.reference }} — {{ o.origin }} → {{ o.destination }}</option>
                    </select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Received By *</label>
                        <input v-model="form.received_by_name" required
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Relation</label>
                        <input v-model="form.received_by_relation" placeholder="e.g. Store Manager"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Signature (Base64 SVG)</label>
                    <textarea v-model="form.signature_data" rows="3"
                        class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                        placeholder="Paste base64 signature data or SVG markup"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Delivery Photo</label>
                    <input type="file" accept="image/*" @change="onPhotoChange"
                        class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">GPS Latitude</label>
                        <input v-model="form.gps_lat" type="number" step="any"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">GPS Longitude</label>
                        <input v-model="form.gps_lng" type="number" step="any"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Notes</label>
                    <textarea v-model="form.notes" rows="3"
                        class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"></textarea>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" :disabled="submitting"
                        class="px-6 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition-colors">
                        {{ submitting ? 'Submitting...' : 'Submit POD' }}
                    </button>
                    <router-link to="/proofs-of-delivery"
                        class="text-sm font-semibold text-slate-500 hover:text-slate-700">Cancel</router-link>
                </div>
            </form>
        </div>
    </div>
</template>
