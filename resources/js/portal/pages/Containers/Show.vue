<template>
  <div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <router-link to="/containers" class="text-sm text-blue-600 hover:underline">&larr; Registry</router-link>
        <h1 class="text-2xl font-bold mt-1 font-mono">{{ container?.container_id || 'Container Detail' }}</h1>
      </div>
      <div class="flex gap-2">
        <button @click="calcPenalties" class="bg-green-600 text-white px-4 py-2 rounded text-sm">Recalculate Penalties</button>
        <button @click="showMovementForm = true" class="bg-blue-600 text-white px-4 py-2 rounded text-sm">Record Movement</button>
      </div>
    </div>

    <div v-if="loading" class="text-center py-8 text-gray-500">Loading...</div>

    <template v-else-if="container">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="bg-white rounded-lg shadow p-4">
          <h3 class="font-semibold text-gray-700 mb-2">Details</h3>
          <dl class="space-y-1 text-sm">
            <div class="flex justify-between"><span class="text-gray-500">Size</span><span>{{ container.size }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Type</span><span class="capitalize">{{ container.type }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Owner</span><span class="capitalize">{{ container.owner_type }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Shipping Line</span><span>{{ container.shipping_line || '-' }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Status</span>
              <span :class="statusBadge(container.current_status)" class="px-2 py-0.5 rounded text-xs">{{ container.current_status.replace(/_/g, ' ') }}</span>
            </div>
            <div class="flex justify-between"><span class="text-gray-500">Location</span><span>{{ container.current_location?.name || '-' }}</span></div>
            <div v-if="container.purchase_value" class="flex justify-between"><span class="text-gray-500">Value</span><span>{{ formatCurrency(container.purchase_value) }}</span></div>
          </dl>
        </div>

        <div class="bg-white rounded-lg shadow p-4">
          <h3 class="font-semibold text-gray-700 mb-2">Free Days</h3>
          <dl class="space-y-1 text-sm">
            <div class="flex justify-between"><span class="text-gray-500">Demurrage</span><span>{{ container.free_demurrage_days ?? '-' }} days</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Detention</span><span>{{ container.free_detention_days ?? '-' }} days</span></div>
          </dl>
          <div v-if="container.demurrage_tiers" class="mt-2">
            <p class="text-xs text-gray-500 mb-1">Demurrage Tiers</p>
            <div v-for="(t, i) in container.demurrage_tiers" :key="i" class="text-xs text-gray-600">
              Days {{ t.days_from }}-{{ t.days_to }}: {{ formatCurrency(t.daily_rate) }}/day
            </div>
          </div>
          <div v-if="container.detention_tiers" class="mt-2">
            <p class="text-xs text-gray-500 mb-1">Detention Tiers</p>
            <div v-for="(t, i) in container.detention_tiers" :key="i" class="text-xs text-gray-600">
              Days {{ t.days_from }}-{{ t.days_to }}: {{ formatCurrency(t.daily_rate) }}/day
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow p-4">
          <h3 class="font-semibold text-gray-700 mb-2">Penalties</h3>
          <div v-if="container.penalties?.length">
            <div v-for="p in container.penalties" :key="p.id" class="mb-2 p-2 rounded" :class="p.type === 'demurrage' ? 'bg-yellow-50' : 'bg-red-50'">
              <div class="text-sm font-semibold capitalize">{{ p.type }}</div>
              <div class="text-xs text-gray-600">{{ p.days_overdue }} days overdue @ {{ formatCurrency(p.daily_rate) }}/day</div>
              <div class="text-sm font-bold text-red-600">{{ formatCurrency(p.total_amount) }}</div>
              <div class="text-xs text-gray-400">Calculated: {{ formatDate(p.calculated_at) }}</div>
            </div>
          </div>
          <div v-else class="text-sm text-gray-400">No penalties</div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow">
        <div class="px-4 py-3 border-b font-semibold text-gray-700">Movement History</div>
        <table class="w-full text-sm">
          <thead class="bg-gray-50 text-left">
            <tr>
              <th class="px-4 py-2 text-gray-500">Type</th>
              <th class="px-4 py-2 text-gray-500">Vehicle</th>
              <th class="px-4 py-2 text-gray-500">Driver</th>
              <th class="px-4 py-2 text-gray-500">Trip</th>
              <th class="px-4 py-2 text-gray-500">Seal</th>
              <th class="px-4 py-2 text-gray-500">Departed</th>
              <th class="px-4 py-2 text-gray-500">Arrived</th>
              <th class="px-4 py-2 text-gray-500">Notes</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="m in container.movements" :key="m.id" class="border-t">
              <td class="px-4 py-3 capitalize">{{ m.movement_type.replace(/_/g, ' ') }}</td>
              <td class="px-4 py-3">{{ m.vehicle || '-' }}</td>
              <td class="px-4 py-3">{{ m.driver || '-' }}</td>
              <td class="px-4 py-3 font-mono text-xs">{{ m.trip_reference || '-' }}</td>
              <td class="px-4 py-3">{{ m.seal_number || '-' }}</td>
              <td class="px-4 py-3 text-xs">{{ formatDate(m.departed_at) }}</td>
              <td class="px-4 py-3 text-xs">{{ formatDate(m.arrived_at) }}</td>
              <td class="px-4 py-3 text-xs text-gray-500 max-w-[200px] truncate">{{ m.notes || '-' }}</td>
            </tr>
            <tr v-if="container.movements.length === 0">
              <td colspan="8" class="px-4 py-8 text-center text-gray-400">No movements recorded</td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>
  </div>

  <div v-if="showMovementForm" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="showMovementForm = false">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-lg max-h-[90vh] overflow-y-auto">
      <h2 class="text-lg font-bold mb-4">Record Movement</h2>
      <div class="space-y-3">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Movement Type</label>
          <select v-model="movementForm.movement_type" class="w-full border rounded px-3 py-2 text-sm">
            <option value="port_pickup">Port Pickup</option>
            <option value="delivery_to_customer">Delivery to Customer</option>
            <option value="return_to_depot">Return to Depot</option>
            <option value="reposition">Reposition</option>
            <option value="transfer">Transfer</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Destination</label>
          <div class="flex gap-2">
            <select v-model="locationType" class="border rounded px-3 py-2 text-sm w-1/3">
              <option value="App\Models\Place">Place</option>
              <option value="App\Models\Warehouse">Warehouse</option>
            </select>
            <input v-model="locationQuery" placeholder="Search..." class="border rounded px-3 py-2 text-sm flex-1" @input="searchLocs" />
          </div>
          <div v-if="locationResults.length" class="border rounded mt-1 max-h-32 overflow-y-auto">
            <div v-for="r in locationResults" :key="r.id + r.type" @click="selectLocation(r)" class="px-3 py-2 text-sm hover:bg-gray-100 cursor-pointer">
              {{ r.label }}
            </div>
          </div>
          <div v-if="selectedLocation" class="mt-1 text-xs text-green-600">
            Selected: {{ selectedLocation.label }}
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Vehicle (optional)</label>
          <input v-model="movementForm.vehicle_id" type="number" placeholder="Vehicle ID" class="w-full border rounded px-3 py-2 text-sm" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Seal Number (optional)</label>
          <input v-model="movementForm.seal_number" class="w-full border rounded px-3 py-2 text-sm" />
        </div>
        <div class="grid grid-cols-2 gap-2">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Departed At</label>
            <input v-model="movementForm.departed_at" type="datetime-local" class="w-full border rounded px-3 py-2 text-sm" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Arrived At</label>
            <input v-model="movementForm.arrived_at" type="datetime-local" class="w-full border rounded px-3 py-2 text-sm" />
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
          <textarea v-model="movementForm.notes" rows="2" class="w-full border rounded px-3 py-2 text-sm"></textarea>
        </div>
        <div class="flex gap-2 pt-2">
          <button @click="submitMovement" :disabled="submitting" class="flex-1 bg-blue-600 text-white py-2 rounded text-sm hover:bg-blue-700 disabled:opacity-50">
            {{ submitting ? 'Recording...' : 'Record Movement' }}
          </button>
          <button @click="showMovementForm = false" class="px-4 py-2 border rounded text-sm">Cancel</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { containersApi } from '../../api/containers'

const route = useRoute()
const container = ref(null)
const loading = ref(false)
const showMovementForm = ref(false)
const submitting = ref(false)
const locationType = ref('App\\Models\\Place')
const locationQuery = ref('')
const locationResults = ref([])
const selectedLocation = ref(null)

const movementForm = ref({
  container_id: route.params.id,
  movement_type: 'port_pickup',
  to_location_type: '',
  to_location_id: null,
  vehicle_id: '',
  seal_number: '',
  departed_at: '',
  arrived_at: '',
  notes: '',
})

function statusBadge(s) {
  const map = { at_port: 'bg-yellow-100 text-yellow-800', in_transit: 'bg-blue-100 text-blue-800', at_warehouse: 'bg-gray-100 text-gray-800', at_customer: 'bg-purple-100 text-purple-800', empty_returned: 'bg-green-100 text-green-800', damaged: 'bg-red-100 text-red-800', scrapped: 'bg-gray-200 text-gray-500' }
  return map[s] || 'bg-gray-100 text-gray-800'
}

function formatCurrency(val) {
  const n = Number(val)
  if (isNaN(n)) return '-'
  return n.toLocaleString('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 0 })
}

function formatDate(d) {
  if (!d) return '-'
  return new Date(d).toLocaleString()
}

async function loadContainer() {
  loading.value = true
  try {
    const res = await containersApi.show(route.params.id)
    container.value = res.data.data
    movementForm.value.container_id = route.params.id
  } catch (e) {
    console.error('Failed to load container', e)
  } finally {
    loading.value = false
  }
}

async function calcPenalties() {
  try {
    await containersApi.calculatePenalties(route.params.id)
    await loadContainer()
  } catch (e) {
    console.error('Failed to calculate penalties', e)
  }
}

async function searchLocs() {
  if (locationQuery.value.length < 2) { locationResults.value = []; return }
  try {
    const res = await containersApi.searchLocations(locationQuery.value)
    locationResults.value = res.data.data.filter(r => r.type === locationType.value)
  } catch { locationResults.value = [] }
}

function selectLocation(r) {
  selectedLocation.value = r
  movementForm.value.to_location_type = r.type
  movementForm.value.to_location_id = r.id
  locationResults.value = []
  locationQuery.value = r.label
}

async function submitMovement() {
  submitting.value = true
  try {
    await containersApi.recordMovement({
      container_id: Number(route.params.id),
      movement_type: movementForm.value.movement_type,
      to_location_type: movementForm.value.to_location_type,
      to_location_id: movementForm.value.to_location_id,
      vehicle_id: movementForm.value.vehicle_id ? Number(movementForm.value.vehicle_id) : null,
      seal_number: movementForm.value.seal_number || null,
      departed_at: movementForm.value.departed_at || null,
      arrived_at: movementForm.value.arrived_at || null,
      notes: movementForm.value.notes || null,
    })
    showMovementForm.value = false
    await loadContainer()
  } catch (e) {
    console.error('Failed to record movement', e)
  } finally {
    submitting.value = false
  }
}

onMounted(loadContainer)
</script>
