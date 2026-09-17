<template>
  <div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold">Mechanic Profiles</h1>
      <div class="flex items-center gap-4">
        <div>
          <label class="block text-xs text-gray-500 mb-1">Period Start</label>
          <input v-model="periodStart" type="date" class="border rounded px-3 py-2 text-sm" />
        </div>
        <div>
          <label class="block text-xs text-gray-500 mb-1">Period End</label>
          <input v-model="periodEnd" type="date" class="border rounded px-3 py-2 text-sm" />
        </div>
        <button @click="loadMechanics" class="bg-blue-600 text-white px-4 py-2 rounded text-sm">Refresh</button>
      </div>
    </div>

    <div v-if="loading" class="text-center py-8 text-gray-500">Loading...</div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-for="m in mechanics" :key="m.id" class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center justify-between mb-3">
          <div>
            <div class="font-semibold">{{ m.name }}</div>
            <div class="text-xs text-gray-500">{{ m.email }}</div>
          </div>
          <div v-if="m.score" class="text-2xl font-bold" :class="scoreColor(m.score.overall_score)">
            {{ m.score.overall_score }}
          </div>
          <div v-else class="text-gray-400 text-xs">No score</div>
        </div>

        <div v-if="m.score" class="space-y-2 text-sm">
          <div class="flex justify-between">
            <span class="text-gray-500">Automated</span>
            <span>{{ m.score.automated_score }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-500">Human Rating</span>
            <span>{{ m.score.human_rating_avg ? m.score.human_rating_avg + '/5' : '-' }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-500">Ratings</span>
            <span>{{ m.score.human_rating_count || 0 }}</span>
          </div>
        </div>

        <div v-if="m.stats" class="mt-3 pt-3 border-t text-sm space-y-1">
          <div class="flex justify-between">
            <span class="text-gray-500">Jobs Done</span>
            <span>{{ m.stats.assignments_completed }}/{{ m.stats.assignments_total }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-500">Completion Rate</span>
            <span>{{ m.stats.completion_rate }}%</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-500">Avg Turnaround</span>
            <span>{{ m.stats.avg_turnaround_minutes != null ? m.stats.avg_turnaround_minutes + 'm' : '-' }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-500">Unresolved Releases</span>
            <span>{{ m.stats.unresolved_releases }}</span>
          </div>
        </div>

        <div class="mt-3">
          <button
            @click="calculateMechanic(m.id)"
            class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded hover:bg-green-200"
          >
            Calculate
          </button>
        </div>
      </div>

      <div v-if="mechanics.length === 0" class="col-span-full text-center py-8 text-gray-400">
        No mechanics found.
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { ratingsApi } from '../../api/ratings'

const mechanics = ref([])
const loading = ref(false)
const periodStart = ref(new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0])
const periodEnd = ref(new Date().toISOString().split('T')[0])

function scoreColor(val) {
  if (val >= 80) return 'text-green-600'
  if (val >= 60) return 'text-yellow-600'
  return 'text-red-600'
}

async function loadMechanics() {
  loading.value = true
  try {
    const res = await ratingsApi.availableMechanics()
    const users = res.data.data || []
    const profiles = await Promise.all(
      users.map(async (u) => {
        try {
          const p = await ratingsApi.mechanicProfile(u.id, periodStart.value, periodEnd.value)
          return p.data.data || { mechanic: u, score: null, stats: null, ratings: [] }
        } catch {
          return { mechanic: u, score: null, stats: null, ratings: [] }
        }
      })
    )
    mechanics.value = profiles.map((p) => ({
      id: p.mechanic.id,
      name: p.mechanic.name,
      email: p.mechanic.email,
      score: p.score,
      stats: p.stats,
    }))
  } catch (e) {
    console.error('Failed to load mechanics', e)
  } finally {
    loading.value = false
  }
}

async function calculateMechanic(userId) {
  try {
    await ratingsApi.calculateMechanicScore(userId, periodStart.value, periodEnd.value)
    await loadMechanics()
  } catch (e) {
    console.error('Failed to calculate', e)
  }
}

onMounted(loadMechanics)
</script>
