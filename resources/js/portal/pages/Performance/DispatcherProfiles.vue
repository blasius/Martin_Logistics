<template>
  <div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold">Dispatcher Profiles</h1>
      <div class="flex items-center gap-4">
        <div>
          <label class="block text-xs text-gray-500 mb-1">Period Start</label>
          <input v-model="periodStart" type="date" class="border rounded px-3 py-2 text-sm" />
        </div>
        <div>
          <label class="block text-xs text-gray-500 mb-1">Period End</label>
          <input v-model="periodEnd" type="date" class="border rounded px-3 py-2 text-sm" />
        </div>
        <button @click="loadDispatchers" class="bg-blue-600 text-white px-4 py-2 rounded text-sm">Refresh</button>
      </div>
    </div>

    <div v-if="loading" class="text-center py-8 text-gray-500">Loading...</div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-for="dp in dispatchers" :key="dp.id" class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center justify-between mb-3">
          <div>
            <div class="font-semibold">{{ dp.name }}</div>
            <div class="text-xs text-gray-500">{{ dp.email }}</div>
          </div>
          <div v-if="dp.score" class="text-2xl font-bold" :class="scoreColor(dp.score.overall_score)">
            {{ dp.score.overall_score }}
          </div>
          <div v-else class="text-gray-400 text-xs">No score</div>
        </div>

        <div v-if="dp.score" class="space-y-2 text-sm">
          <div class="flex justify-between">
            <span class="text-gray-500">Automated</span>
            <span>{{ dp.score.automated_score }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-500">Human Rating</span>
            <span>{{ dp.score.human_rating_avg ? dp.score.human_rating_avg + '/5' : '-' }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-500">Ratings</span>
            <span>{{ dp.score.human_rating_count || 0 }}</span>
          </div>
        </div>

        <div v-if="dp.stats" class="mt-3 pt-3 border-t text-sm space-y-1">
          <div class="flex justify-between">
            <span class="text-gray-500">Trips Managed</span>
            <span>{{ dp.stats.trips_managed }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-500">Completion Rate</span>
            <span>{{ dp.stats.completion_rate }}%</span>
          </div>
        </div>

        <div class="mt-3 flex gap-2">
          <button
            @click="calculateDispatcher(dp.id)"
            class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded hover:bg-green-200"
          >
            Calculate
          </button>
          <router-link
            :to="`/performance/rate/dispatcher/${dp.id}`"
            class="text-xs bg-purple-100 text-purple-700 px-2 py-1 rounded hover:bg-purple-200"
          >
            Rate
          </router-link>
        </div>
      </div>

      <div v-if="dispatchers.length === 0" class="col-span-full text-center py-8 text-gray-400">
        No dispatchers found.
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { ratingsApi } from '../../api/ratings'

const dispatchers = ref([])
const loading = ref(false)
const periodStart = ref(new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0])
const periodEnd = ref(new Date().toISOString().split('T')[0])

function scoreColor(val) {
  if (val >= 80) return 'text-green-600'
  if (val >= 60) return 'text-yellow-600'
  return 'text-red-600'
}

async function loadDispatchers() {
  loading.value = true
  try {
    const res = await ratingsApi.availableDispatchers()
    const users = res.data.data || []
    const profiles = await Promise.all(
      users.map(async (u) => {
        try {
          const p = await ratingsApi.dispatcherProfile(u.id, periodStart.value, periodEnd.value)
          return p.data.data || { dispatcher: u, score: null, stats: null, ratings: [] }
        } catch {
          return { dispatcher: u, score: null, stats: null, ratings: [] }
        }
      })
    )
    dispatchers.value = profiles.map((p) => ({
      id: p.dispatcher.id,
      name: p.dispatcher.name,
      email: p.dispatcher.email,
      score: p.score,
      stats: p.stats,
    }))
  } catch (e) {
    console.error('Failed to load dispatchers', e)
  } finally {
    loading.value = false
  }
}

async function calculateDispatcher(userId) {
  try {
    await ratingsApi.calculateDispatcherScore(userId, periodStart.value, periodEnd.value)
    await loadDispatchers()
  } catch (e) {
    console.error('Failed to calculate', e)
  }
}

onMounted(loadDispatchers)
</script>
