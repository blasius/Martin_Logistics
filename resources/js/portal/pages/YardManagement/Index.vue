<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { yardApi } from '../../api/yard'

const loading = ref(true)
const dashboard = ref<any>(null)
const dockDoors = ref<any[]>([])
const activeTab = ref('offload')
const showDockDoorModal = ref(false)
const dockDoorForm = ref({ code: '', name: '', service_type: 'loading_dock', warehouse_id: '', notes: '' })
const showCheckInModal = ref(false)
const checkInForm = ref({ vehicle_id: '', purpose: 'loading', driver_id: '' })

const serviceTypes = [
    { key: 'offload', label: 'Offload' },
    { key: 'wash', label: 'Wash' },
    { key: 'workshop', label: 'Workshop' },
    { key: 'fuel', label: 'Fuel' },
    { key: 'loading_dock', label: 'Loading Dock' },
    { key: 'unload_dock', label: 'Unload Dock' },
    { key: 'fueling_bay', label: 'Fueling Bay' },
    { key: 'car_wash', label: 'Car Wash' },
]

onMounted(async () => {
    try {
        const [dashRes, doorsRes] = await Promise.all([
            yardApi.dashboard(),
            yardApi.dockDoors(),
        ])
        dashboard.value = dashRes.data
        dockDoors.value = doorsRes.data
    } catch {} finally {
        loading.value = false
    }
})

const activeQueue = computed(() => {
    if (!dashboard.value) return null
    return dashboard.value.queues[activeTab.value] || null
})

const doorsForActiveTab = computed(() => {
    return dockDoors.value.filter(d => d.service_type === activeTab.value)
})

function formatDate(d: string | null) {
    if (!d) return '-'
    return new Date(d).toLocaleDateString('en-US', {
        month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit',
    })
}

function elapsed(d: string | null) {
    if (!d) return '-'
    const mins = Math.floor((Date.now() - new Date(d).getTime()) / 60000)
    if (mins < 60) return `${mins}m`
    return `${Math.floor(mins / 60)}h ${mins % 60}m`
}

function typeBadge(t: string) {
    const map: Record<string, string> = {
        offload: 'bg-orange-50 text-orange-700',
        wash: 'bg-cyan-50 text-cyan-700',
        workshop: 'bg-violet-50 text-violet-700',
        fuel: 'bg-yellow-50 text-yellow-700',
        loading_dock: 'bg-blue-50 text-blue-700',
        unload_dock: 'bg-rose-50 text-rose-700',
        fueling_bay: 'bg-amber-50 text-amber-700',
        car_wash: 'bg-teal-50 text-teal-700',
    }
    return map[t] || 'bg-slate-100 text-slate-600'
}

async function saveDockDoor() {
    try {
        await yardApi.storeDockDoor(dockDoorForm.value)
        showDockDoorModal.value = false
        dockDoorForm.value = { code: '', name: '', service_type: 'loading_dock', warehouse_id: '', notes: '' }
        const res = await yardApi.dockDoors()
        dockDoors.value = res.data
    } catch {}
}

async function releaseDoor(door: any) {
    try {
        const res = await yardApi.releaseDockDoor(door.id)
        const [dashRes, doorsRes] = await Promise.all([
            yardApi.dashboard(),
            yardApi.dockDoors(),
        ])
        dashboard.value = dashRes.data
        dockDoors.value = doorsRes.data
    } catch {}
}

async function assignDoor(door: any, queueId: number) {
    try {
        await yardApi.assignDockDoor(queueId, door.id)
        const [dashRes, doorsRes] = await Promise.all([
            yardApi.dashboard(),
            yardApi.dockDoors(),
        ])
        dashboard.value = dashRes.data
        dockDoors.value = doorsRes.data
    } catch {}
}

async function doCheckIn() {
    try {
        await yardApi.checkIn(checkInForm.value)
        showCheckInModal.value = false
        checkInForm.value = { vehicle_id: '', purpose: 'loading', driver_id: '' }
        const dashRes = await yardApi.dashboard()
        dashboard.value = dashRes.data
    } catch {}
}
</script>

<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-slate-800">Yard Management</h1>
            <div class="flex items-center gap-2">
                <button @click="showCheckInModal = true"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white text-sm font-semibold rounded-lg hover:bg-emerald-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                    Check In
                </button>
                <button @click="showDockDoorModal = true"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Add Dock Door
                </button>
            </div>
        </div>

        <div v-if="loading" class="bg-white rounded-xl border border-slate-100 shadow-sm p-8">
            <div class="animate-pulse space-y-4">
                <div class="h-4 bg-slate-100 rounded w-1/4"></div>
                <div class="grid grid-cols-4 gap-4">
                    <div class="h-20 bg-slate-50 rounded-xl"></div>
                    <div class="h-20 bg-slate-50 rounded-xl"></div>
                    <div class="h-20 bg-slate-50 rounded-xl"></div>
                    <div class="h-20 bg-slate-50 rounded-xl"></div>
                </div>
            </div>
        </div>

        <div v-else-if="dashboard">
            <!-- Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-5">
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Active in Yard</p>
                    <p class="text-2xl font-bold text-slate-800 mt-1">{{ dashboard.active_entries }}</p>
                </div>
                <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-5">
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Dock Doors</p>
                    <p class="text-2xl font-bold text-slate-800 mt-1">{{ dashboard.dock_doors.occupied }} <span class="text-sm font-normal text-slate-400">/ {{ dashboard.dock_doors.total }} occupied</span></p>
                    <p v-if="dashboard.dock_doors.available > 0" class="text-xs text-emerald-600 font-semibold mt-1">{{ dashboard.dock_doors.available }} available</p>
                </div>
                <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-5">
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Queued</p>
                    <p class="text-2xl font-bold text-slate-800 mt-1">{{ dashboard.stats.total_queued }}</p>
                </div>
                <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-5">
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Completed Today</p>
                    <p class="text-2xl font-bold text-slate-800 mt-1">{{ dashboard.stats.completed_today }}</p>
                </div>
            </div>

            <!-- Service Type Tabs -->
            <div class="bg-white rounded-xl border border-slate-100 shadow-sm mb-6 overflow-hidden">
                <div class="border-b border-slate-100">
                    <nav class="flex overflow-x-auto">
                        <button v-for="st in serviceTypes" :key="st.key"
                            @click="activeTab = st.key"
                            class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 transition-colors"
                            :class="activeTab === st.key ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700'">
                            {{ st.label }}
                            <span v-if="dashboard.queues[st.key]?.queued" class="ml-1.5 inline-flex items-center justify-center w-5 h-5 text-xs font-bold bg-indigo-50 text-indigo-600 rounded-full">{{ dashboard.queues[st.key].queued }}</span>
                        </button>
                    </nav>
                </div>

                <div class="p-6">
                    <div v-if="activeQueue">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                            <div class="bg-slate-50 rounded-lg p-4">
                                <p class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Queued</p>
                                <p class="text-lg font-bold text-slate-800">{{ activeQueue.queued }}</p>
                            </div>
                            <div class="bg-slate-50 rounded-lg p-4">
                                <p class="text-xs text-slate-400 font-semibold uppercase tracking-wider">In Progress</p>
                                <p class="text-lg font-bold text-slate-800">{{ activeQueue.in_progress }}</p>
                            </div>
                            <div class="bg-slate-50 rounded-lg p-4">
                                <p class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Est. Wait</p>
                                <p class="text-lg font-bold text-slate-800">{{ activeQueue.wait_time.estimated_wait_minutes }} <span class="text-sm font-normal text-slate-400">min</span></p>
                                <p class="text-xs text-slate-400">{{ activeQueue.wait_time.vehicles_ahead }} vehicles ahead, ~{{ activeQueue.wait_time.average_service_minutes }} min avg</p>
                            </div>
                        </div>

                        <!-- Dock Doors for this service type -->
                        <div v-if="doorsForActiveTab.length > 0" class="mb-6">
                            <h3 class="text-sm font-bold text-slate-700 mb-3">Dock Doors / Bays</h3>
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                                <div v-for="door in doorsForActiveTab" :key="door.id"
                                    class="rounded-lg border p-3 transition-colors"
                                    :class="door.is_occupied ? 'bg-amber-50 border-amber-200' : 'bg-emerald-50 border-emerald-200'">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-sm font-bold" :class="door.is_occupied ? 'text-amber-700' : 'text-emerald-700'">{{ door.code }}</span>
                                        <span class="text-xs font-semibold" :class="door.is_occupied ? 'text-amber-600' : 'text-emerald-600'">{{ door.is_occupied ? 'Occupied' : 'Free' }}</span>
                                    </div>
                                    <p v-if="door.is_occupied && door.current_vehicle" class="text-xs text-slate-500">{{ door.current_vehicle.plate_number }}</p>
                                    <p v-if="door.is_occupied" class="text-xs text-slate-400">{{ elapsed(door.occupied_since) }}</p>
                                    <button v-if="door.is_occupied" @click="releaseDoor(door)"
                                        class="mt-2 text-xs font-semibold text-red-600 hover:text-red-700">Release</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-8 text-slate-400">
                        No data for this service type.
                    </div>
                </div>
            </div>

            <!-- Recent Yard Entries -->
            <div class="bg-white rounded-xl border border-slate-100 shadow-sm">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h2 class="text-sm font-bold text-slate-700">Recent Yard Entries</h2>
                </div>
                <div v-if="dashboard.recent_entries?.length" class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider border-b border-slate-100">
                                <th class="px-6 py-3">Vehicle</th>
                                <th class="px-6 py-3">Purpose</th>
                                <th class="px-6 py-3">Check In</th>
                                <th class="px-6 py-3">Dock Door</th>
                                <th class="px-6 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="entry in dashboard.recent_entries" :key="entry.id" class="hover:bg-slate-50">
                                <td class="px-6 py-3 text-sm font-semibold text-slate-700">{{ entry.vehicle?.plate_number || '-' }}</td>
                                <td class="px-6 py-3">
                                    <span class="inline-flex text-xs font-bold px-2.5 py-1 rounded-full" :class="typeBadge(entry.purpose)">{{ entry.purpose }}</span>
                                </td>
                                <td class="px-6 py-3 text-sm text-slate-600">{{ formatDate(entry.check_in_at) }}</td>
                                <td class="px-6 py-3 text-sm text-slate-600">{{ entry.dock_door?.code || '-' }}</td>
                                <td class="px-6 py-3">
                                    <span v-if="!entry.check_out_at" class="text-xs font-semibold text-amber-600">Active</span>
                                    <span v-else class="text-xs font-semibold text-slate-400">Departed</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="p-12 text-center text-slate-400 text-sm">
                    No yard entries yet.
                </div>
            </div>
        </div>

        <!-- Add Dock Door Modal -->
        <div v-if="showDockDoorModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/30" @click.self="showDockDoorModal = false">
            <div class="bg-white rounded-2xl shadow-xl p-6 w-full max-w-md mx-4">
                <h2 class="text-lg font-bold text-slate-800 mb-4">Add Dock Door</h2>
                <form @submit.prevent="saveDockDoor" class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Code *</label>
                        <input v-model="dockDoorForm.code" required
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Name</label>
                        <input v-model="dockDoorForm.name"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Service Type *</label>
                        <select v-model="dockDoorForm.service_type" required
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                            <option value="loading_dock">Loading Dock</option>
                            <option value="unload_dock">Unload Dock</option>
                            <option value="fueling_bay">Fueling Bay</option>
                            <option value="car_wash">Car Wash</option>
                            <option value="workshop">Workshop</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Notes</label>
                        <textarea v-model="dockDoorForm.notes" rows="2"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none"></textarea>
                    </div>
                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit"
                            class="px-5 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition-colors">Save</button>
                        <button type="button" @click="showDockDoorModal = false"
                            class="text-sm font-semibold text-slate-500 hover:text-slate-700">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Check In Modal -->
        <div v-if="showCheckInModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/30" @click.self="showCheckInModal = false">
            <div class="bg-white rounded-2xl shadow-xl p-6 w-full max-w-md mx-4">
                <h2 class="text-lg font-bold text-slate-800 mb-4">Vehicle Check In</h2>
                <form @submit.prevent="doCheckIn" class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Vehicle ID *</label>
                        <input v-model="checkInForm.vehicle_id" type="number" required
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Purpose *</label>
                        <select v-model="checkInForm.purpose" required
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                            <option value="loading">Loading</option>
                            <option value="unloading">Unloading</option>
                            <option value="fueling">Fueling</option>
                            <option value="washing">Washing</option>
                            <option value="workshop">Workshop</option>
                            <option value="parking">Parking</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Driver ID</label>
                        <input v-model="checkInForm.driver_id" type="number"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit"
                            class="px-5 py-2 bg-emerald-600 text-white text-sm font-semibold rounded-lg hover:bg-emerald-700 transition-colors">Check In</button>
                        <button type="button" @click="showCheckInModal = false"
                            class="text-sm font-semibold text-slate-500 hover:text-slate-700">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
