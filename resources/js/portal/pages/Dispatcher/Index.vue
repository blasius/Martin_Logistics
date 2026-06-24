<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { dispatchPrepApi } from '../../api/truck-requests'

const activeTab = ref('needs-prep')
const needsPrep = ref<any[]>([])
const readyTrips = ref<any[]>([])
const myTrips = ref<any[]>([])
const loading = ref(true)

const selectedTrip = ref<any>(null)
const prepForm = ref<any>({})
const saving = ref(false)

async function fetchAll() {
    loading.value = true
    try {
        const [needs, ready, mine] = await Promise.all([
            dispatchPrepApi.needsPreparation(),
            dispatchPrepApi.readyToDepart(),
            dispatchPrepApi.myTrips(),
        ])
        needsPrep.value = needs.data
        readyTrips.value = ready.data
        myTrips.value = mine.data
    } catch {} finally { loading.value = false }
}

async function openChecklist(trip: any) {
    selectedTrip.value = trip
    try {
        const res = await dispatchPrepApi.show(trip.id)
        prepForm.value = {
            fuel_confirmed: res.data.fuel_confirmed ?? false,
            fuel_liters: res.data.fuel_liters ?? null,
            odometer_start: res.data.odometer_start ?? null,
            documents_uploaded: res.data.documents_uploaded ?? false,
            instructions_provided: res.data.instructions_provided ?? false,
            inspection_confirmed: res.data.inspection_confirmed ?? false,
            notes: res.data.notes ?? '',
        }
    } catch { prepForm.value = { fuel_confirmed: false, fuel_liters: null, odometer_start: null, documents_uploaded: false, instructions_provided: false, inspection_confirmed: false, notes: '' } }
}

async function saveChecklist() {
    if (!selectedTrip.value) return
    saving.value = true
    try {
        await dispatchPrepApi.update(selectedTrip.value.id, prepForm.value)
        await fetchAll()
    } catch {} finally { saving.value = false }
}

async function markReady() {
    if (!selectedTrip.value) return
    saving.value = true
    try {
        await dispatchPrepApi.markReady(selectedTrip.value.id)
        selectedTrip.value = null
        await fetchAll()
    } catch {} finally { saving.value = false }
}

const checklistComplete = computed(() => {
    return prepForm.value.fuel_confirmed
        && prepForm.value.documents_uploaded
        && prepForm.value.instructions_provided
        && prepForm.value.inspection_confirmed
})

function statusBadge(status: string) {
    const map: Record<string, string> = {
        pre_departure: 'bg-yellow-100 text-yellow-700',
        assigned: 'bg-blue-100 text-blue-700',
        on_route: 'bg-green-100 text-green-700',
    }
    return map[status] ?? 'bg-gray-100 text-gray-600'
}

onMounted(fetchAll)
</script>

<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-slate-800">Dispatch Preparation</h1>
            <button @click="fetchAll"
                class="px-4 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50">
                Refresh
            </button>
        </div>

        <div class="flex gap-1 mb-6 bg-slate-100 rounded-lg p-1">
            <button @click="activeTab = 'needs-prep'"
                :class="['px-4 py-2 text-sm font-medium rounded-md transition-colors', activeTab === 'needs-prep' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700']">
                Needs Preparation ({{ needsPrep.length }})
            </button>
            <button @click="activeTab = 'ready'"
                :class="['px-4 py-2 text-sm font-medium rounded-md transition-colors', activeTab === 'ready' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700']">
                Ready to Depart ({{ readyTrips.length }})
            </button>
            <button @click="activeTab = 'my-trips'"
                :class="['px-4 py-2 text-sm font-medium rounded-md transition-colors', activeTab === 'my-trips' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700']">
                My Trips ({{ myTrips.length }})
            </button>
        </div>

        <div v-if="loading" class="p-8 text-center text-slate-400">Loading...</div>

        <!-- Needs Prep Tab -->
        <div v-else-if="activeTab === 'needs-prep'" class="space-y-3">
            <div v-for="trip in needsPrep" :key="trip.id"
                class="bg-white rounded-xl border border-slate-100 shadow-sm p-4 flex items-center justify-between">
                <div>
                    <div class="text-sm font-semibold text-slate-800">{{ trip.reference }}</div>
                    <div class="text-sm text-slate-500">{{ trip.vehicle?.plate_number }} · {{ trip.vehicle?.make }} {{ trip.vehicle?.model }}</div>
                </div>
                <button @click="openChecklist(trip)"
                    class="px-3 py-1.5 text-sm font-medium text-emerald-600 bg-emerald-50 rounded-lg hover:bg-emerald-100">
                    Prepare
                </button>
            </div>
            <div v-if="!needsPrep.length" class="text-center py-8 text-slate-400">All trips prepared</div>
        </div>

        <!-- Ready to Depart Tab -->
        <div v-else-if="activeTab === 'ready'" class="space-y-3">
            <div v-for="trip in readyTrips" :key="trip.id"
                class="bg-white rounded-xl border border-slate-100 shadow-sm p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm font-semibold text-slate-800">{{ trip.reference }}</div>
                        <div class="text-sm text-slate-500">{{ trip.vehicle?.plate_number }} · Ready {{ trip.preparation?.ready_at ? new Date(trip.preparation.ready_at).toLocaleString() : '' }}</div>
                    </div>
                    <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium"
                        :class="statusBadge(trip.status)">{{ trip.status.replace('_', ' ') }}</span>
                </div>
            </div>
            <div v-if="!readyTrips.length" class="text-center py-8 text-slate-400">No trips ready to depart</div>
        </div>

        <!-- My Trips Tab -->
        <div v-else-if="activeTab === 'my-trips'" class="space-y-3">
            <div v-for="trip in myTrips" :key="trip.id"
                class="bg-white rounded-xl border border-slate-100 shadow-sm p-4 flex items-center justify-between">
                <div>
                    <div class="text-sm font-semibold text-slate-800">{{ trip.reference }}</div>
                    <div class="text-sm text-slate-500">{{ trip.vehicle?.plate_number }} · {{ trip.vehicle?.make }} {{ trip.vehicle?.model }}</div>
                    <div class="text-xs text-slate-400 mt-1">{{ trip.truck_request?.reference ?? '' }}</div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium"
                        :class="statusBadge(trip.status)">{{ trip.status.replace('_', ' ') }}</span>
                    <button @click="openChecklist(trip)"
                        class="px-3 py-1.5 text-sm font-medium text-emerald-600 bg-emerald-50 rounded-lg hover:bg-emerald-100">
                        Checklist
                    </button>
                </div>
            </div>
            <div v-if="!myTrips.length" class="text-center py-8 text-slate-400">No trips assigned to you</div>
        </div>

        <!-- Checklist Modal -->
        <div v-if="selectedTrip"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
            @click.self="selectedTrip = null">
            <div class="bg-white rounded-xl shadow-xl max-w-lg w-full mx-4 max-h-[80vh] overflow-y-auto">
                <div class="flex items-center justify-between p-6 border-b border-slate-100">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-800">Pre-Departure Checklist</h2>
                        <p class="text-sm text-slate-500">{{ selectedTrip.reference }} · {{ selectedTrip.vehicle?.plate_number }}</p>
                    </div>
                    <button @click="selectedTrip = null"
                        class="text-slate-400 hover:text-slate-600 text-xl leading-none">&times;</button>
                </div>
                <div class="p-6 space-y-4">
                    <label class="flex items-center gap-3 p-3 rounded-lg border" :class="prepForm.fuel_confirmed ? 'border-green-200 bg-green-50' : 'border-slate-200'">
                        <input type="checkbox" v-model="prepForm.fuel_confirmed"
                            class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" />
                        <div class="flex-1">
                            <span class="text-sm font-medium text-slate-700">Fuel Confirmed</span>
                            <input v-model="prepForm.fuel_liters" type="number" step="0.1" placeholder="Liters"
                                class="mt-1 w-full border border-slate-200 rounded-lg px-2 py-1 text-sm focus:ring-2 focus:ring-emerald-500" />
                        </div>
                    </label>

                    <label class="flex items-center gap-3 p-3 rounded-lg border" :class="prepForm.odometer_start ? 'border-green-200 bg-green-50' : 'border-slate-200'">
                        <input type="checkbox" :checked="!!prepForm.odometer_start" @change="prepForm.odometer_start = $event.target.checked ? 0 : null"
                            class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" />
                        <div class="flex-1">
                            <span class="text-sm font-medium text-slate-700">Odometer Start</span>
                            <input v-model="prepForm.odometer_start" type="number" step="0.1" placeholder="KM"
                                class="mt-1 w-full border border-slate-200 rounded-lg px-2 py-1 text-sm focus:ring-2 focus:ring-emerald-500" />
                        </div>
                    </label>

                    <label class="flex items-center gap-3 p-3 rounded-lg border" :class="prepForm.documents_uploaded ? 'border-green-200 bg-green-50' : 'border-slate-200'">
                        <input type="checkbox" v-model="prepForm.documents_uploaded"
                            class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" />
                        <span class="text-sm font-medium text-slate-700">Documents Uploaded</span>
                    </label>

                    <label class="flex items-center gap-3 p-3 rounded-lg border" :class="prepForm.instructions_provided ? 'border-green-200 bg-green-50' : 'border-slate-200'">
                        <input type="checkbox" v-model="prepForm.instructions_provided"
                            class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" />
                        <span class="text-sm font-medium text-slate-700">Driver Instructions Provided</span>
                    </label>

                    <label class="flex items-center gap-3 p-3 rounded-lg border" :class="prepForm.inspection_confirmed ? 'border-green-200 bg-green-50' : 'border-slate-200'">
                        <input type="checkbox" v-model="prepForm.inspection_confirmed"
                            class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" />
                        <span class="text-sm font-medium text-slate-700">Pre-Trip Inspection Confirmed</span>
                    </label>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Notes</label>
                        <textarea v-model="prepForm.notes" rows="2"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500"></textarea>
                    </div>

                    <div v-if="!checklistComplete" class="text-xs text-amber-600 bg-amber-50 p-2 rounded">
                        Complete all checklist items before marking ready.
                    </div>
                </div>
                <div class="flex justify-between p-6 border-t border-slate-100">
                    <button @click="saveChecklist" :disabled="saving"
                        class="px-4 py-2 text-sm font-medium text-slate-600 bg-slate-100 rounded-lg hover:bg-slate-200 disabled:opacity-50">
                        {{ saving ? 'Saving...' : 'Save' }}
                    </button>
                    <button @click="markReady" :disabled="!checklistComplete || saving"
                        class="px-6 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 disabled:opacity-50">
                        Mark Ready to Depart
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
