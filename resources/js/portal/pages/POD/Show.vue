<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { podApi } from '../../api/proofs-of-delivery'

const props = defineProps<{ id: string | number }>()
const router = useRouter()

const pod = ref<any>(null)
const loading = ref(true)

onMounted(async () => {
    try {
        const res = await podApi.show(props.id)
        pod.value = res.data
    } catch {} finally {
        loading.value = false
    }
})

function formatDate(d: string | null) {
    if (!d) return '-'
    return new Date(d).toLocaleDateString('en-US', {
        month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit',
    })
}

function statusClass(s: string) {
    const map: Record<string, string> = {
        draft: 'bg-slate-100 text-slate-600',
        submitted: 'bg-amber-50 text-amber-700',
        confirmed: 'bg-emerald-50 text-emerald-700',
    }
    return map[s] || 'bg-slate-100 text-slate-600'
}

async function confirmPod() {
    if (!confirm('Confirm this proof of delivery?')) return
    try {
        await podApi.confirm(props.id)
        pod.value.status = 'confirmed'
    } catch {}
}

async function downloadPdf() {
    try {
        const res = await podApi.downloadPdf(props.id)
        const url = URL.createObjectURL(new Blob([res.data], { type: 'application/pdf' }))
        const a = document.createElement('a')
        a.href = url
        a.download = `delivery-receipt-${pod.value.order?.reference || props.id}.pdf`
        a.click()
        URL.revokeObjectURL(url)
    } catch {}
}

function openGps(lat: number, lng: number) {
    window.open(`https://www.google.com/maps?q=${lat},${lng}`, '_blank')
}
</script>

<template>
    <div>
        <router-link to="/proofs-of-delivery"
            class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-indigo-600 transition-colors mb-6">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            Back to PODs
        </router-link>

        <div v-if="loading" class="bg-white rounded-xl border border-slate-100 shadow-sm p-8">
            <div class="animate-pulse space-y-4">
                <div class="h-6 bg-slate-100 rounded w-1/3"></div>
                <div class="h-4 bg-slate-50 rounded w-1/2"></div>
            </div>
        </div>

        <div v-else-if="pod" class="max-w-3xl">
            <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6 sm:p-8 mb-6">
                <div class="flex flex-col sm:flex-row items-start justify-between gap-4 mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-800">Proof of Delivery</h1>
                        <p class="text-sm text-slate-400 mt-1">{{ pod.order?.reference || 'Unknown Order' }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex text-xs font-bold px-4 py-2 rounded-full" :class="statusClass(pod.status)">
                            {{ pod.status }}
                        </span>
                        <button @click="downloadPdf"
                            class="px-3 py-2 bg-indigo-600 text-white text-xs font-semibold rounded-lg hover:bg-indigo-700 transition-colors">
                            PDF
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Received By</p>
                        <p class="text-sm font-semibold text-slate-800">{{ pod.received_by_name }}</p>
                        <p v-if="pod.received_by_relation" class="text-xs text-slate-400">{{ pod.received_by_relation }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Delivered At</p>
                        <p class="text-sm font-semibold text-slate-800">{{ formatDate(pod.delivered_at) }}</p>
                    </div>
                    <div v-if="pod.gps_lat && pod.gps_lng">
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">GPS Location</p>
                        <button @click="openGps(pod.gps_lat, pod.gps_lng)"
                            class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">
                            {{ pod.gps_lat.toFixed(6) }}, {{ pod.gps_lng.toFixed(6) }}
                        </button>
                    </div>
                    <div v-if="pod.submitter">
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Submitted By</p>
                        <p class="text-sm font-semibold text-slate-800">{{ pod.submitter.name }}</p>
                    </div>
                </div>

                <div v-if="pod.notes" class="mt-6 pt-6 border-t border-slate-100">
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Notes</p>
                    <p class="text-sm text-slate-600 bg-slate-50 p-4 rounded-xl leading-relaxed">{{ pod.notes }}</p>
                </div>

                <div v-if="pod.order" class="mt-6 pt-6 border-t border-slate-100">
                    <h3 class="text-sm font-bold text-slate-700 mb-3">Order Details</h3>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div><span class="text-slate-400">Origin:</span> <span class="font-medium">{{ pod.order.origin }}</span></div>
                        <div><span class="text-slate-400">Destination:</span> <span class="font-medium">{{ pod.order.destination }}</span></div>
                        <div><span class="text-slate-400">Client:</span> <span class="font-medium">{{ pod.order.client?.user?.name || '-' }}</span></div>
                    </div>
                </div>

                <div v-if="pod.trip" class="mt-6 pt-6 border-t border-slate-100">
                    <h3 class="text-sm font-bold text-slate-700 mb-3">Trip Details</h3>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div><span class="text-slate-400">Vehicle:</span> <span class="font-medium">{{ pod.trip.vehicle?.plate || '-' }}</span></div>
                        <div><span class="text-slate-400">Driver:</span> <span class="font-medium">{{ pod.trip.driver?.user?.name || '-' }}</span></div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div v-if="pod.signature_data" class="bg-white rounded-xl border border-slate-100 shadow-sm p-6">
                    <h3 class="text-sm font-bold text-slate-700 mb-3">Signature</h3>
                    <div class="bg-slate-50 rounded-lg p-4 flex items-center justify-center min-h-[100px]">
                        <img v-if="pod.signature_data.startsWith('data:')" :src="pod.signature_data" class="max-h-24" alt="Signature">
                        <img v-else-if="!pod.signature_data.startsWith('<svg')" :src="'data:image/png;base64,' + pod.signature_data" class="max-h-24" alt="Signature">
                        <span v-else v-html="pod.signature_data" class="[&>svg]:max-h-24"></span>
                    </div>
                </div>

                <div v-if="pod.photo_path" class="bg-white rounded-xl border border-slate-100 shadow-sm p-6">
                    <h3 class="text-sm font-bold text-slate-700 mb-3">Delivery Photo</h3>
                    <div class="bg-slate-50 rounded-lg p-2 flex items-center justify-center">
                        <img :src="'/storage/' + pod.photo_path" class="max-w-full max-h-64 rounded object-contain" alt="Delivery Photo">
                    </div>
                </div>
            </div>

            <div v-if="pod.status === 'submitted'" class="mt-6">
                <button @click="confirmPod"
                    class="px-6 py-3 bg-emerald-600 text-white text-sm font-semibold rounded-lg hover:bg-emerald-700 transition-colors">
                    Confirm Delivery
                </button>
            </div>
        </div>

        <div v-else class="bg-white rounded-xl border border-slate-100 shadow-sm p-12 text-center">
            <p class="text-slate-600 font-semibold">Proof of delivery not found</p>
        </div>
    </div>
</template>
