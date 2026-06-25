<template>
  <div class="p-6 max-w-2xl mx-auto">
    <div>
      <router-link to="/containers" class="text-sm text-blue-600 hover:underline">&larr; Registry</router-link>
      <h1 class="text-2xl font-bold mt-1">Add Container</h1>
    </div>

    <form @submit.prevent="submit" class="mt-6 bg-white rounded-lg shadow p-6 space-y-4">
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Container ID *</label>
          <input v-model="form.container_id" placeholder="e.g. MSCU1234567" class="w-full border rounded px-3 py-2 text-sm" required />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Size *</label>
          <select v-model="form.size" class="w-full border rounded px-3 py-2 text-sm" required>
            <option value="">Select...</option>
            <option value="20ft">20ft</option>
            <option value="40ft">40ft</option>
            <option value="40hc">40hc</option>
          </select>
        </div>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Type *</label>
          <select v-model="form.type" class="w-full border rounded px-3 py-2 text-sm" required>
            <option value="">Select...</option>
            <option value="dry">Dry</option>
            <option value="reefer">Reefer</option>
            <option value="open_top">Open Top</option>
            <option value="flat_rack">Flat Rack</option>
            <option value="tank">Tank</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Owner *</label>
          <select v-model="form.owner_type" class="w-full border rounded px-3 py-2 text-sm" required>
            <option value="">Select...</option>
            <option value="private">Private (Company Owned)</option>
            <option value="shipping_line">Shipping Line</option>
          </select>
        </div>
      </div>

      <div v-if="form.owner_type === 'shipping_line'">
        <label class="block text-sm font-medium text-gray-700 mb-1">Shipping Line Contract</label>
        <select v-model="form.shipping_line_contract_id" class="w-full border rounded px-3 py-2 text-sm">
          <option value="">No contract</option>
          <option v-for="c in contracts" :key="c.id" :value="c.id">{{ c.shipping_line }} - {{ c.name }}</option>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
        <select v-model="form.current_status" class="w-full border rounded px-3 py-2 text-sm" required>
          <option value="">Select...</option>
          <option value="at_port">At Port</option>
          <option value="at_warehouse">At Warehouse</option>
          <option value="at_customer">At Customer</option>
          <option value="in_transit">In Transit</option>
          <option value="empty_returned">Empty Returned</option>
          <option value="damaged">Damaged</option>
          <option value="scrapped">Scrapped</option>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
        <div class="flex gap-2">
          <select v-model="locationType" class="border rounded px-3 py-2 text-sm w-1/3">
            <option value="App\Models\Place">Place</option>
            <option value="App\Models\Warehouse">Warehouse</option>
          </select>
          <input v-model="locationQuery" placeholder="Search location..." class="border rounded px-3 py-2 text-sm flex-1" @input="searchLocs" />
        </div>
        <div v-if="locationResults.length" class="border rounded mt-1 max-h-32 overflow-y-auto">
          <div v-for="r in locationResults" :key="r.id + r.type" @click="selectLocation(r)" class="px-3 py-2 text-sm hover:bg-gray-100 cursor-pointer">
            {{ r.label }}
          </div>
        </div>
        <div v-if="selectedLocation" class="mt-1 text-xs text-green-600">Selected: {{ selectedLocation.label }}</div>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Purchase Value (if owned)</label>
          <input v-model="form.purchase_value" type="number" step="0.01" class="w-full border rounded px-3 py-2 text-sm" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Purchase Date</label>
          <input v-model="form.purchase_date" type="date" class="w-full border rounded px-3 py-2 text-sm" />
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">GPS Coordinates (optional)</label>
        <input v-model="form.last_known_gps" placeholder="e.g. -1.9441, 30.0619" class="w-full border rounded px-3 py-2 text-sm" />
      </div>

      <button type="submit" :disabled="submitting" class="w-full bg-blue-600 text-white py-2 rounded font-medium hover:bg-blue-700 disabled:opacity-50">
        {{ submitting ? 'Creating...' : 'Create Container' }}
      </button>

      <p v-if="message" class="text-sm text-center" :class="messageType === 'success' ? 'text-green-600' : 'text-red-600'">{{ message }}</p>
    </form>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { containersApi } from '../../api/containers'

const router = useRouter()
const contracts = ref([])
const submitting = ref(false)
const message = ref('')
const messageType = ref('')
const locationType = ref('App\\Models\\Place')
const locationQuery = ref('')
const locationResults = ref([])
const selectedLocation = ref(null)

const form = ref({
  container_id: '',
  size: '',
  type: '',
  owner_type: '',
  shipping_line_contract_id: '',
  is_owned: true,
  purchase_value: '',
  purchase_date: '',
  current_status: '',
  current_location_type: '',
  current_location_id: '',
  last_known_gps: '',
})

async function searchLocs() {
  if (locationQuery.value.length < 2) { locationResults.value = []; return }
  try {
    const res = await containersApi.searchLocations(locationQuery.value)
    locationResults.value = res.data.data.filter(r => r.type === locationType.value)
  } catch { locationResults.value = [] }
}

function selectLocation(r) {
  selectedLocation.value = r
  form.value.current_location_type = r.type
  form.value.current_location_id = r.id
  locationResults.value = []
  locationQuery.value = r.label
}

async function submit() {
  submitting.value = true
  message.value = ''
  try {
    await containersApi.store({
      container_id: form.value.container_id,
      size: form.value.size,
      type: form.value.type,
      owner_type: form.value.owner_type,
      shipping_line_contract_id: form.value.shipping_line_contract_id || null,
      is_owned: form.value.owner_type === 'private',
      purchase_value: form.value.purchase_value || null,
      purchase_date: form.value.purchase_date || null,
      current_status: form.value.current_status,
      current_location_type: form.value.current_location_type || null,
      current_location_id: form.value.current_location_id || null,
      last_known_gps: form.value.last_known_gps || null,
    })
    message.value = 'Container created! Redirecting...'
    messageType.value = 'success'
    setTimeout(() => router.push('/containers'), 1000)
  } catch (e) {
    message.value = e.response?.data?.message || 'Failed to create container.'
    messageType.value = 'error'
  } finally {
    submitting.value = false
  }
}

onMounted(async () => {
  try {
    const res = await containersApi.contracts()
    contracts.value = res.data.data || []
  } catch { }
})
</script>
