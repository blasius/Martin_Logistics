<template>
  <div class="p-6 max-w-2xl mx-auto">
    <div>
      <router-link to="/containers" class="text-sm text-blue-600 hover:underline">&larr; Registry</router-link>
      <h1 class="text-2xl font-bold mt-1">Add Shipping Line Contract</h1>
    </div>

    <form @submit.prevent="submit" class="mt-6 bg-white rounded-lg shadow p-6 space-y-4">
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Shipping Line *</label>
          <input v-model="form.shipping_line" placeholder="e.g. Maersk, MSC" class="w-full border rounded px-3 py-2 text-sm" required />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Contract Name *</label>
          <input v-model="form.name" placeholder="e.g. Mombasa Route 2026" class="w-full border rounded px-3 py-2 text-sm" required />
        </div>
      </div>

      <div class="grid grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Free Demurrage Days *</label>
          <input v-model="form.free_demurrage_days" type="number" min="0" class="w-full border rounded px-3 py-2 text-sm" required />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Free Detention Days *</label>
          <input v-model="form.free_detention_days" type="number" min="0" class="w-full border rounded px-3 py-2 text-sm" required />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Currency *</label>
          <select v-model="form.currency_id" class="w-full border rounded px-3 py-2 text-sm" required>
            <option value="">Select</option>
            <option v-for="c in currencies" :key="c.id" :value="c.id">{{ c.code }}</option>
          </select>
        </div>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Effective From *</label>
          <input v-model="form.effective_from" type="date" class="w-full border rounded px-3 py-2 text-sm" required />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Effective To (optional)</label>
          <input v-model="form.effective_to" type="date" class="w-full border rounded px-3 py-2 text-sm" />
        </div>
      </div>

      <div>
        <div class="flex items-center justify-between mb-2">
          <label class="text-sm font-medium text-gray-700">Demurrage Tiers</label>
          <button type="button" @click="addTier('demurrage')" class="text-xs text-blue-600 hover:underline">+ Add Tier</button>
        </div>
        <div v-for="(t, i) in form.demurrage_tiers" :key="i" class="flex gap-2 mb-2">
          <input v-model="t.days_from" type="number" placeholder="From" class="w-20 border rounded px-2 py-1 text-xs" />
          <input v-model="t.days_to" type="number" placeholder="To" class="w-20 border rounded px-2 py-1 text-xs" />
          <input v-model="t.daily_rate" type="number" step="0.01" placeholder="Rate" class="w-28 border rounded px-2 py-1 text-xs" />
          <button type="button" @click="removeTier('demurrage', i)" class="text-red-500 text-xs">Remove</button>
        </div>
      </div>

      <div>
        <div class="flex items-center justify-between mb-2">
          <label class="text-sm font-medium text-gray-700">Detention Tiers</label>
          <button type="button" @click="addTier('detention')" class="text-xs text-blue-600 hover:underline">+ Add Tier</button>
        </div>
        <div v-for="(t, i) in form.detention_tiers" :key="i" class="flex gap-2 mb-2">
          <input v-model="t.days_from" type="number" placeholder="From" class="w-20 border rounded px-2 py-1 text-xs" />
          <input v-model="t.days_to" type="number" placeholder="To" class="w-20 border rounded px-2 py-1 text-xs" />
          <input v-model="t.daily_rate" type="number" step="0.01" placeholder="Rate" class="w-28 border rounded px-2 py-1 text-xs" />
          <button type="button" @click="removeTier('detention', i)" class="text-red-500 text-xs">Remove</button>
        </div>
      </div>

      <button type="submit" :disabled="submitting" class="w-full bg-green-600 text-white py-2 rounded font-medium hover:bg-green-700 disabled:opacity-50">
        {{ submitting ? 'Creating...' : 'Create Contract' }}
      </button>

      <p v-if="message" class="text-sm text-center" :class="messageType === 'success' ? 'text-green-600' : 'text-red-600'">{{ message }}</p>
    </form>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { containersApi } from '../../api/containers'
import { api } from '../../../plugins/axios'

const router = useRouter()
const currencies = ref([])
const submitting = ref(false)
const message = ref('')
const messageType = ref('')

const form = ref({
  shipping_line: '',
  name: '',
  free_demurrage_days: 0,
  free_detention_days: 0,
  currency_id: '',
  effective_from: '',
  effective_to: '',
  demurrage_tiers: [],
  detention_tiers: [],
})

function addTier(type) {
  form.value[type + '_tiers'].push({ days_from: 0, days_to: 0, daily_rate: 0 })
}

function removeTier(type, index) {
  form.value[type + '_tiers'].splice(index, 1)
}

async function submit() {
  submitting.value = true
  message.value = ''
  try {
    await containersApi.storeContract({
      shipping_line: form.value.shipping_line,
      name: form.value.name,
      free_demurrage_days: Number(form.value.free_demurrage_days),
      free_detention_days: Number(form.value.free_detention_days),
      currency_id: Number(form.value.currency_id),
      effective_from: form.value.effective_from,
      effective_to: form.value.effective_to || null,
      demurrage_tiers: form.value.demurrage_tiers,
      detention_tiers: form.value.detention_tiers,
    })
    message.value = 'Contract created! Redirecting...'
    messageType.value = 'success'
    setTimeout(() => router.push('/containers'), 1000)
  } catch (e) {
    message.value = e.response?.data?.message || 'Failed to create contract.'
    messageType.value = 'error'
  } finally {
    submitting.value = false
  }
}

onMounted(async () => {
  try {
    const res = await api.get('/portal/currencies')
    currencies.value = res.data || []
  } catch { }
})
</script>
