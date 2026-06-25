<template>
    <div class="space-y-4">
        <div ref="calendarEl" class="bg-white rounded-lg shadow p-4"></div>

        <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 w-full max-w-md">
                <h3 class="text-lg font-semibold mb-4">{{ editingSlot ? 'Edit' : 'Create' }} Time Slot</h3>
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium">Title</label>
                        <input v-model="form.title" class="w-full border rounded px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Type</label>
                        <select v-model="form.type" class="w-full border rounded px-3 py-2 text-sm">
                            <option value="pickup">Pickup</option>
                            <option value="delivery">Delivery</option>
                            <option value="driver_shift">Driver Shift</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="dock_reservation">Dock Reservation</option>
                            <option value="trip">Trip</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium">Start</label>
                            <input type="datetime-local" v-model="form.start_time" class="w-full border rounded px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium">End</label>
                            <input type="datetime-local" v-model="form.end_time" class="w-full border rounded px-3 py-2 text-sm" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Vehicle ID</label>
                        <input type="number" v-model="form.vehicle_id" class="w-full border rounded px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Notes</label>
                        <textarea v-model="form.notes" class="w-full border rounded px-3 py-2 text-sm"></textarea>
                    </div>
                </div>
                <div class="flex justify-end gap-2 mt-4">
                    <button @click="showModal = false" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded">Cancel</button>
                    <button @click="saveSlot" class="px-4 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">Save</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { Calendar } from '@fullcalendar/core'
import dayGridPlugin from '@fullcalendar/daygrid'
import timeGridPlugin from '@fullcalendar/timegrid'
import interactionPlugin from '@fullcalendar/interaction'
import { schedulingApi } from '../../api/scheduling'

const calendarEl = ref(null)
const showModal = ref(false)
const editingSlot = ref(null)
let calendar = null

const form = ref({
    title: '', type: 'pickup', start_time: '', end_time: '', vehicle_id: null, notes: '',
})

function resetForm() {
    form.value = { title: '', type: 'pickup', start_time: '', end_time: '', vehicle_id: null, notes: '' }
    editingSlot.value = null
}

async function fetchEvents(info, successCallback) {
    try {
        const { data } = await schedulingApi.events(info.startStr, info.endStr)
        successCallback(data)
    } catch (e) {
        console.error('Failed to fetch events', e)
    }
}

function handleDateSelect(info) {
    resetForm()
    form.value.start_time = info.startStr.substring(0, 16)
    form.value.end_time = info.endStr.substring(0, 16)
    showModal.value = true
}

function handleEventClick(info) {
    const props = info.event.extendedProps
    editingSlot.value = info.event.id
    form.value = {
        title: info.event.title,
        type: props.type || 'pickup',
        start_time: info.event.startStr.substring(0, 16),
        end_time: info.event.endStr.substring(0, 16),
        vehicle_id: null,
        notes: props.notes || '',
    }
    showModal.value = true
}

async function saveSlot() {
    try {
        if (editingSlot.value) {
            const id = editingSlot.value.replace('slot-', '')
            await schedulingApi.update(id, form.value)
        } else {
            await schedulingApi.store(form.value)
        }
        showModal.value = false
        resetForm()
        calendar.refetchEvents()
    } catch (e) {
        if (e.response?.status === 409) {
            alert('Scheduling conflict: ' + (e.response.data?.message || 'Vehicle already booked'))
        } else {
            alert('Failed to save time slot')
        }
    }
}

onMounted(() => {
    calendar = new Calendar(calendarEl.value, {
        plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
        initialView: 'timeGridWeek',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        editable: false,
        selectable: true,
        selectMirror: true,
        events: fetchEvents,
        select: handleDateSelect,
        eventClick: handleEventClick,
        slotMinTime: '06:00:00',
        slotMaxTime: '22:00:00',
        height: 'auto',
    })
    calendar.render()
})

onBeforeUnmount(() => {
    calendar?.destroy()
})
</script>
