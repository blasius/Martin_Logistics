<template>
  <div class="p-6 max-w-2xl mx-auto space-y-6">
    <div>
      <router-link to="/performance" class="text-sm text-blue-600 hover:underline">&larr; Leaderboard</router-link>
      <h1 class="text-2xl font-bold mt-1">Rate {{ isDispatcher ? 'Dispatcher' : 'Driver' }}</h1>
    </div>

    <div class="bg-white rounded-lg shadow p-6 space-y-4">
      <div v-if="!isDispatcher">
        <label class="block text-sm font-medium text-gray-700 mb-1">Driver</label>
        <select v-model="form.rateable_id" class="w-full border rounded px-3 py-2 text-sm">
          <option value="" disabled>Select a driver...</option>
          <option v-for="d in drivers" :key="d.id" :value="d.id">{{ d.name }} ({{ d.phone || 'No phone' }})</option>
        </select>
      </div>

      <div v-else>
        <label class="block text-sm font-medium text-gray-700 mb-1">Dispatcher</label>
        <select v-model="form.rateable_id" class="w-full border rounded px-3 py-2 text-sm">
          <option value="" disabled>Select a dispatcher...</option>
          <option v-for="d in dispatchers" :key="d.id" :value="d.id">{{ d.name }} ({{ d.email }})</option>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
        <select v-model="form.category" class="w-full border rounded px-3 py-2 text-sm">
          <option value="fuel_efficiency">Fuel Efficiency</option>
          <option value="on_time_delivery">On-Time Delivery</option>
          <option value="route_compliance">Route Compliance</option>
          <option value="expense_management">Expense Management</option>
          <option value="safety">Safety</option>
          <option value="overall">Overall</option>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Rating</label>
        <div class="flex gap-2">
          <button
            v-for="n in 5"
            :key="n"
            @click="form.rating = n"
            class="w-12 h-12 rounded-lg text-xl transition-all"
            :class="form.rating >= n ? 'bg-yellow-400 text-white' : 'bg-gray-100 text-gray-400 hover:bg-gray-200'"
          >
            {{ n }}
          </button>
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Comment (optional)</label>
        <textarea v-model="form.comment" rows="3" class="w-full border rounded px-3 py-2 text-sm" placeholder="Add a comment..."></textarea>
      </div>

      <button
        @click="submit"
        :disabled="submitting || !form.rateable_id || !form.rating"
        class="w-full bg-blue-600 text-white py-2 rounded font-medium hover:bg-blue-700 disabled:opacity-50"
      >
        {{ submitting ? 'Submitting...' : 'Submit Rating' }}
      </button>

      <p v-if="message" class="text-sm text-center" :class="messageType === 'success' ? 'text-green-600' : 'text-red-600'">
        {{ message }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { ratingsApi } from '../../api/ratings'

const route = useRoute()
const drivers = ref([])
const dispatchers = ref([])
const submitting = ref(false)
const message = ref('')
const messageType = ref('')

const isDispatcher = computed(() => route.path.includes('/dispatcher'))

const form = ref({
  rateable_type: 'App\\Models\\Driver',
  rateable_id: route.params.id || '',
  rating: 0,
  category: 'overall',
  comment: '',
})

if (isDispatcher.value) {
  form.value.rateable_type = 'App\\Models\\User'
}

async function submit() {
  if (!form.value.rateable_id || !form.value.rating) return
  submitting.value = true
  message.value = ''
  try {
    await ratingsApi.submitRating({
      rateable_type: form.value.rateable_type,
      rateable_id: form.value.rateable_id,
      rating: form.value.rating,
      category: form.value.category,
      comment: form.value.comment,
    })
    message.value = 'Rating submitted successfully!'
    messageType.value = 'success'
    form.value.rating = 0
    form.value.comment = ''
  } catch (e) {
    message.value = 'Failed to submit rating.'
    messageType.value = 'error'
  } finally {
    submitting.value = false
  }
}

onMounted(async () => {
  try {
    const [dRes, dispRes] = await Promise.all([
      ratingsApi.availableDrivers(),
      ratingsApi.availableDispatchers(),
    ])
    drivers.value = dRes.data.data || []
    dispatchers.value = dispRes.data.data || []
  } catch (e) {
    console.error('Failed to load available subjects', e)
  }
})
</script>
