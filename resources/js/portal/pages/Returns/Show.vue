<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import returnsApi from '../../api/returns'

const route = useRoute()
const router = useRouter()

const returnData = ref<any>(null)
const loading = ref(true)
const actionLoading = ref(false)

// Modals
const showRejectModal = ref(false)
const rejectReason = ref('')
const showPickupModal = ref(false)
const pickupForm = ref({ pickup_address: '', pickup_date: '', pickup_trip_id: '' })
const showReceiveModal = ref(false)
const receiveCondition = ref('')
const showCompleteModal = ref(false)
const completeForm = ref({ disposition: 'restock', credit_note_id: '' })

async function fetch() {
    loading.value = true
    try {
        const res = await returnsApi.show(route.params.id)
        returnData.value = res.data
    } catch {} finally { loading.value = false }
}

function statusClass(status: string) {
    const map: Record<string, string> = {
        pending: 'bg-amber-100 text-amber-700',
        approved: 'bg-blue-100 text-blue-700',
        rejected: 'bg-red-100 text-red-700',
        pickup_scheduled: 'bg-purple-100 text-purple-700',
        received: 'bg-cyan-100 text-cyan-700',
        completed: 'bg-emerald-100 text-emerald-700',
        cancelled: 'bg-gray-100 text-gray-500',
    }
    return map[status] ?? 'bg-gray-100 text-gray-600'
}

const canApprove = computed(() => returnData.value?.status === 'pending')
const canReject = computed(() => returnData.value?.status === 'pending')
const canSchedulePickup = computed(() => returnData.value?.status === 'approved')
const canReceive = computed(() => returnData.value?.status === 'pickup_scheduled')
const canComplete = computed(() => returnData.value?.status === 'received')
const canCancel = computed(() => ['pending', 'approved', 'pickup_scheduled'].includes(returnData.value?.status))

async function doApprove() {
    actionLoading.value = true
    try { await returnsApi.approve(route.params.id); await fetch() } catch {} finally { actionLoading.value = false }
}

async function doReject() {
    actionLoading.value = true
    try { await returnsApi.reject(route.params.id, rejectReason.value); showRejectModal.value = false; await fetch() } catch {} finally { actionLoading.value = false }
}

async function doSchedulePickup() {
    actionLoading.value = true
    try { await returnsApi.schedulePickup(route.params.id, pickupForm.value); showPickupModal.value = false; await fetch() } catch {} finally { actionLoading.value = false }
}

async function doReceive() {
    actionLoading.value = true
    try { await returnsApi.receive(route.params.id, { condition: receiveCondition.value }); showReceiveModal.value = false; await fetch() } catch {} finally { actionLoading.value = false }
}

async function doComplete() {
    actionLoading.value = true
    try { await returnsApi.complete(route.params.id, completeForm.value); showCompleteModal.value = false; await fetch() } catch {} finally { actionLoading.value = false }
}

async function doCancel() {
    if (!confirm('Cancel this return request?')) return
    actionLoading.value = true
    try { await returnsApi.cancel(route.params.id); await fetch() } catch {} finally { actionLoading.value = false }
}

onMounted(fetch)
</script>

<template>
    <div>
        <div class="flex items-center gap-4 mb-6">
            <router-link to="/returns" class="text-slate-400 hover:text-slate-600">&larr; Returns</router-link>
            <h1 class="text-2xl font-bold text-slate-800" v-if="returnData">{{ returnData.reference }}</h1>
        </div>

        <div v-if="loading" class="text-center py-12 text-slate-400">Loading...</div>

        <template v-if="returnData && !loading">
            <!-- Status Banner + Actions -->
            <div class="flex items-center justify-between mb-6">
                <span class="inline-block px-3 py-1 rounded-full text-sm font-medium" :class="statusClass(returnData.status)">
                    {{ returnData.status.replace('_', ' ') }}
                </span>
                <div class="flex gap-2">
                    <button v-if="canApprove" @click="doApprove" :disabled="actionLoading"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium">
                        Approve
                    </button>
                    <button v-if="canReject" @click="showRejectModal = true" :disabled="actionLoading"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors text-sm font-medium">
                        Reject
                    </button>
                    <button v-if="canSchedulePickup" @click="showPickupModal = true" :disabled="actionLoading"
                        class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors text-sm font-medium">
                        Schedule Pickup
                    </button>
                    <button v-if="canReceive" @click="showReceiveModal = true" :disabled="actionLoading"
                        class="px-4 py-2 bg-cyan-600 text-white rounded-lg hover:bg-cyan-700 transition-colors text-sm font-medium">
                        Mark Received
                    </button>
                    <button v-if="canComplete" @click="showCompleteModal = true" :disabled="actionLoading"
                        class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors text-sm font-medium">
                        Complete
                    </button>
                    <button v-if="canCancel" @click="doCancel" :disabled="actionLoading"
                        class="px-4 py-2 text-sm text-slate-600 border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">
                        Cancel
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-6">
                <!-- Details -->
                <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6 space-y-4">
                    <h2 class="text-lg font-semibold text-slate-700">Details</h2>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div><span class="text-slate-400">Client</span><p class="font-medium text-slate-700">{{ returnData.client?.user?.name ?? '—' }}</p></div>
                        <div><span class="text-slate-400">Order</span><p class="font-medium text-slate-700">{{ returnData.order?.reference ?? '—' }}</p></div>
                        <div><span class="text-slate-400">Reason</span><p class="font-medium text-slate-700">{{ returnData.reason }}</p></div>
                        <div><span class="text-slate-400">Disposition</span><p class="font-medium text-slate-700">{{ returnData.disposition ?? '—' }}</p></div>
                        <div><span class="text-slate-400">Created by</span><p class="font-medium text-slate-700">{{ returnData.creator?.name ?? '—' }}</p></div>
                        <div><span class="text-slate-400">Created at</span><p class="font-medium text-slate-700">{{ returnData.created_at?.slice(0, 10) }}</p></div>
                    </div>
                </div>

                <!-- Pickup Info -->
                <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6 space-y-4">
                    <h2 class="text-lg font-semibold text-slate-700">Pickup</h2>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div><span class="text-slate-400">Address</span><p class="font-medium text-slate-700">{{ returnData.pickup_address ?? '—' }}</p></div>
                        <div><span class="text-slate-400">Date</span><p class="font-medium text-slate-700">{{ returnData.pickup_date ?? '—' }}</p></div>
                        <div><span class="text-slate-400">Trip</span><p class="font-medium text-slate-700">{{ returnData.pickup_trip?.reference ?? '—' }}</p></div>
                        <div><span class="text-slate-400">Received at</span><p class="font-medium text-slate-700">{{ returnData.received_at ?? '—' }}</p></div>
                    </div>
                </div>

                <!-- Items -->
                <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6 space-y-4 col-span-2">
                    <h2 class="text-lg font-semibold text-slate-700">Items</h2>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-100">
                                <th class="text-left px-3 py-2 text-xs font-semibold text-slate-500">Description</th>
                                <th class="text-left px-3 py-2 text-xs font-semibold text-slate-500">Qty</th>
                                <th class="text-left px-3 py-2 text-xs font-semibold text-slate-500">Reason</th>
                                <th class="text-left px-3 py-2 text-xs font-semibold text-slate-500">Condition</th>
                                <th class="text-left px-3 py-2 text-xs font-semibold text-slate-500">Disposition</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in returnData.items" :key="item.id" class="border-b border-slate-50">
                                <td class="px-3 py-2 text-slate-700">{{ item.description }}</td>
                                <td class="px-3 py-2 text-slate-700">{{ item.quantity }}</td>
                                <td class="px-3 py-2 text-slate-500">{{ item.reason ?? '—' }}</td>
                                <td class="px-3 py-2"><span v-if="item.condition" class="inline-block px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-600">{{ item.condition }}</span><span v-else class="text-slate-400">—</span></td>
                                <td class="px-3 py-2"><span v-if="item.disposition" class="inline-block px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-600">{{ item.disposition }}</span><span v-else class="text-slate-400">—</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Credit Note -->
                <div v-if="returnData.credit_note" class="bg-white rounded-xl border border-slate-100 shadow-sm p-6 space-y-4 col-span-2">
                    <h2 class="text-lg font-semibold text-slate-700">Credit Note</h2>
                    <p class="text-sm text-slate-600">Reference: <span class="font-medium text-slate-700">{{ returnData.credit_note.reference }}</span></p>
                    <p class="text-sm text-slate-600">Amount: <span class="font-medium text-slate-700">{{ returnData.credit_note.total }}</span></p>
                </div>

                <!-- Notes -->
                <div v-if="returnData.notes" class="bg-white rounded-xl border border-slate-100 shadow-sm p-6 space-y-4 col-span-2">
                    <h2 class="text-lg font-semibold text-slate-700">Notes</h2>
                    <p class="text-sm text-slate-600 whitespace-pre-wrap">{{ returnData.notes }}</p>
                </div>
            </div>
        </template>

        <!-- Reject Modal -->
        <div v-if="showRejectModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" @click.self="showRejectModal = false">
            <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-md">
                <h3 class="text-lg font-semibold text-slate-700 mb-4">Reject Return</h3>
                <textarea v-model="rejectReason" placeholder="Reason for rejection (optional)" rows="3"
                    class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500 mb-4"></textarea>
                <div class="flex justify-end gap-3">
                    <button @click="showRejectModal = false" class="px-4 py-2 text-sm text-slate-600 border border-slate-200 rounded-lg">Cancel</button>
                    <button @click="doReject" class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-medium">Reject</button>
                </div>
            </div>
        </div>

        <!-- Schedule Pickup Modal -->
        <div v-if="showPickupModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" @click.self="showPickupModal = false">
            <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-md">
                <h3 class="text-lg font-semibold text-slate-700 mb-4">Schedule Pickup</h3>
                <div class="space-y-3">
                    <input v-model="pickupForm.pickup_address" placeholder="Pickup address" required
                        class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500" />
                    <input v-model="pickupForm.pickup_date" type="date" required
                        class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500" />
                    <input v-model="pickupForm.pickup_trip_id" placeholder="Trip ID (optional)" type="number"
                        class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500" />
                </div>
                <div class="flex justify-end gap-3 mt-4">
                    <button @click="showPickupModal = false" class="px-4 py-2 text-sm text-slate-600 border border-slate-200 rounded-lg">Cancel</button>
                    <button @click="doSchedulePickup" class="px-4 py-2 bg-purple-600 text-white rounded-lg text-sm font-medium">Schedule</button>
                </div>
            </div>
        </div>

        <!-- Receive Modal -->
        <div v-if="showReceiveModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" @click.self="showReceiveModal = false">
            <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-md">
                <h3 class="text-lg font-semibold text-slate-700 mb-4">Mark Received</h3>
                <div class="space-y-3">
                    <label class="block text-xs font-medium text-slate-500 mb-1">Overall condition</label>
                    <select v-model="receiveCondition"
                        class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500">
                        <option value="">Select condition...</option>
                        <option value="good">Good</option>
                        <option value="damaged">Damaged</option>
                        <option value="incomplete">Incomplete</option>
                        <option value="wrong_item">Wrong Item</option>
                    </select>
                </div>
                <div class="flex justify-end gap-3 mt-4">
                    <button @click="showReceiveModal = false" class="px-4 py-2 text-sm text-slate-600 border border-slate-200 rounded-lg">Cancel</button>
                    <button @click="doReceive" class="px-4 py-2 bg-cyan-600 text-white rounded-lg text-sm font-medium">Confirm Receipt</button>
                </div>
            </div>
        </div>

        <!-- Complete Modal -->
        <div v-if="showCompleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" @click.self="showCompleteModal = false">
            <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-md">
                <h3 class="text-lg font-semibold text-slate-700 mb-4">Complete Return</h3>
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Disposition</label>
                        <select v-model="completeForm.disposition"
                            class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500">
                            <option value="restock">Restock</option>
                            <option value="repair">Repair</option>
                            <option value="scrap">Scrap</option>
                            <option value="return_to_vendor">Return to Vendor</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Credit Note ID (optional)</label>
                        <input v-model="completeForm.credit_note_id" type="number" placeholder="Invoice ID for credit note"
                            class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500" />
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-4">
                    <button @click="showCompleteModal = false" class="px-4 py-2 text-sm text-slate-600 border border-slate-200 rounded-lg">Cancel</button>
                    <button @click="doComplete" class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-medium">Complete</button>
                </div>
            </div>
        </div>
    </div>
</template>
