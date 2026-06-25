<template>
  <div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <router-link to="/performance" class="text-sm text-blue-600 hover:underline">&larr; Leaderboard</router-link>
        <h1 class="text-2xl font-bold mt-1">{{ profile?.driver?.name || 'Driver Profile' }}</h1>
      </div>
      <div class="flex items-center gap-4">
        <div>
          <label class="block text-xs text-gray-500 mb-1">Period Start</label>
          <input v-model="periodStart" type="date" class="border rounded px-3 py-2 text-sm" />
        </div>
        <div>
          <label class="block text-xs text-gray-500 mb-1">Period End</label>
          <input v-model="periodEnd" type="date" class="border rounded px-3 py-2 text-sm" />
        </div>
        <button @click="loadProfile" class="bg-blue-600 text-white px-4 py-2 rounded text-sm">Refresh</button>
        <button @click="recalculate" class="bg-green-600 text-white px-4 py-2 rounded text-sm">Recalculate</button>
        <router-link
          :to="`/performance/rate/driver/${$route.params.id}`"
          class="bg-purple-600 text-white px-4 py-2 rounded text-sm"
        >
          Rate Driver
        </router-link>
      </div>
    </div>

    <div v-if="loading" class="text-center py-8 text-gray-500">Loading...</div>

    <template v-else-if="profile">
      <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-4 text-center">
          <div class="text-3xl font-bold" :class="scoreColor(profile.score?.overall_score)">{{ profile.score?.overall_score ?? '-' }}</div>
          <div class="text-xs text-gray-500 mt-1">Overall Score</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center">
          <div class="text-2xl font-bold text-blue-600">{{ profile.score?.automated_score ?? '-' }}</div>
          <div class="text-xs text-gray-500 mt-1">Automated</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center">
          <div class="text-2xl font-bold text-purple-600">{{ profile.score?.human_rating_avg ? profile.score.human_rating_avg + '/5' : '-' }}</div>
          <div class="text-xs text-gray-500 mt-1">Human Rating</div>
          <div class="text-xs text-gray-400">{{ profile.score?.human_rating_count ?? 0 }} submissions</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center">
          <div class="text-sm text-gray-500 mt-1">Phone: {{ profile.driver.phone || '-' }}</div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow">
        <div class="px-4 py-3 border-b font-semibold text-gray-700">Score Breakdown</div>
        <div class="p-4 space-y-4" v-if="profile.score">
          <div v-for="metric in metrics" :key="metric.key" class="flex items-center gap-4">
            <div class="w-40 text-sm text-gray-600">{{ metric.label }}</div>
            <div class="flex-1 bg-gray-200 rounded-full h-3">
              <div
                class="h-3 rounded-full transition-all"
                :class="metric.color"
                :style="{ width: (profile.score[metric.key] ?? 0) + '%' }"
              ></div>
            </div>
            <div class="w-16 text-right text-sm font-mono">{{ profile.score[metric.key] ?? 0 }}</div>
          </div>
        </div>
        <div v-else class="p-4 text-gray-400 text-sm">
          No score calculated for this period yet.
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow">
          <div class="px-4 py-3 border-b font-semibold text-gray-700">Recent Ratings</div>
          <div v-if="profile.ratings?.length" class="divide-y">
            <div v-for="r in profile.ratings" :key="r.id" class="p-3 text-sm">
              <div class="flex items-center justify-between">
                <span class="font-medium">{{ r.category.replace(/_/g, ' ') }}</span>
                <span class="text-yellow-500">{{ '★'.repeat(r.rating) }}{{ '☆'.repeat(5 - r.rating) }}</span>
              </div>
              <p v-if="r.comment" class="text-gray-600 mt-1">{{ r.comment }}</p>
              <p class="text-xs text-gray-400 mt-1">by {{ r.rater_name }} &middot; {{ formatDate(r.created_at) }}</p>
            </div>
          </div>
          <div v-else class="p-4 text-gray-400 text-sm">No ratings yet.</div>
        </div>

        <div class="bg-white rounded-lg shadow">
          <div class="px-4 py-3 border-b font-semibold text-gray-700">Recent Trips</div>
          <div v-if="profile.recent_trips?.length" class="divide-y">
            <div v-for="t in profile.recent_trips" :key="t.id" class="p-3 text-sm flex items-center justify-between">
              <div>
                <span class="font-mono">{{ t.reference }}</span>
                <span v-if="t.is_deviated" class="ml-2 text-red-500 text-xs">Deviated</span>
              </div>
              <span :class="statusColor(t.status)" class="px-2 py-0.5 rounded text-xs">{{ t.status }}</span>
            </div>
          </div>
          <div v-else class="p-4 text-gray-400 text-sm">No trips in this period.</div>
        </div>
      </div>
    </template>

    <div v-else class="text-center py-8 text-gray-500">Driver not found.</div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { ratingsApi } from '../../api/ratings'

const route = useRoute()
const profile = ref(null)
const loading = ref(false)
const periodStart = ref(new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0])
const periodEnd = ref(new Date().toISOString().split('T')[0])

const metrics = [
  { key: 'fuel_efficiency_score', label: 'Fuel Efficiency', color: 'bg-green-500' },
  { key: 'on_time_delivery_score', label: 'On-Time Delivery', color: 'bg-blue-500' },
  { key: 'route_compliance_score', label: 'Route Compliance', color: 'bg-cyan-500' },
  { key: 'expense_management_score', label: 'Expense Management', color: 'bg-yellow-500' },
  { key: 'safety_score', label: 'Safety', color: 'bg-purple-500' },
]

function scoreColor(val) {
  if (!val) return 'text-gray-400'
  if (val >= 80) return 'text-green-600'
  if (val >= 60) return 'text-yellow-600'
  return 'text-red-600'
}

function statusColor(s) {
  const map = {
    completed: 'bg-green-100 text-green-800',
    on_route: 'bg-blue-100 text-blue-800',
    pre_departure: 'bg-gray-100 text-gray-800',
    assigned: 'bg-yellow-100 text-yellow-800',
    cancelled: 'bg-red-100 text-red-800',
  }
  return map[s] || 'bg-gray-100 text-gray-800'
}

function formatDate(d) {
  return new Date(d).toLocaleDateString()
}

async function loadProfile() {
  loading.value = true
  try {
    const res = await ratingsApi.driverProfile(route.params.id, periodStart.value, periodEnd.value)
    profile.value = res.data.data
  } catch (e) {
    console.error('Failed to load profile', e)
    profile.value = null
  } finally {
    loading.value = false
  }
}

async function recalculate() {
  loading.value = true
  try {
    await ratingsApi.calculateDriverScore(route.params.id, periodStart.value, periodEnd.value)
    await loadProfile()
  } catch (e) {
    console.error('Failed to recalculate', e)
  } finally {
    loading.value = false
  }
}

onMounted(loadProfile)
</script>
