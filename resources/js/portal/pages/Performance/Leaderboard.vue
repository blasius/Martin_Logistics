<template>
  <div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold">Performance Leaderboard</h1>
      <div class="flex items-center gap-4">
        <div>
          <label class="block text-xs text-gray-500 mb-1">Period Start</label>
          <input v-model="periodStart" type="date" class="border rounded px-3 py-2 text-sm" />
        </div>
        <div>
          <label class="block text-xs text-gray-500 mb-1">Period End</label>
          <input v-model="periodEnd" type="date" class="border rounded px-3 py-2 text-sm" />
        </div>
        <button @click="refresh" class="bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700">
          Refresh
        </button>
        <button @click="triggerCalculate" class="bg-green-600 text-white px-4 py-2 rounded text-sm hover:bg-green-700">
          Calculate Scores
        </button>
      </div>
    </div>

    <div v-if="loading" class="text-center py-8 text-gray-500">Loading...</div>

    <template v-else>
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-lg shadow">
          <div class="px-4 py-3 border-b font-semibold text-gray-700">Top Drivers</div>
          <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left">
              <tr>
                <th class="px-4 py-2 text-gray-500">#</th>
                <th class="px-4 py-2 text-gray-500">Driver</th>
                <th class="px-4 py-2 text-gray-500">Overall</th>
                <th class="px-4 py-2 text-gray-500">Automated</th>
                <th class="px-4 py-2 text-gray-500">Human</th>
                <th class="px-4 py-2 text-gray-500">Fuel</th>
                <th class="px-4 py-2 text-gray-500">Delivery</th>
                <th class="px-4 py-2 text-gray-500">Route</th>
                <th class="px-4 py-2 text-gray-500"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(d, i) in topDrivers" :key="d.id" class="border-t hover:bg-gray-50">
                <td class="px-4 py-3 font-mono">{{ i + 1 }}</td>
                <td class="px-4 py-3">
                  <router-link :to="`/performance/drivers/${d.driver_id}`" class="text-blue-600 hover:underline">
                    {{ d.driver_name }}
                  </router-link>
                </td>
                <td class="px-4 py-3">
                  <span :class="scoreBadge(d.overall_score)" class="px-2 py-0.5 rounded text-xs font-semibold">
                    {{ d.overall_score }}
                  </span>
                </td>
                <td class="px-4 py-3">{{ d.automated_score }}</td>
                <td class="px-4 py-3">
                  {{ d.human_rating_avg ? d.human_rating_avg + '/5' : '-' }}
                  <span v-if="d.human_rating_count" class="text-gray-400 text-xs">({{ d.human_rating_count }})</span>
                </td>
                <td class="px-4 py-3">{{ d.fuel_efficiency_score }}</td>
                <td class="px-4 py-3">{{ d.on_time_delivery_score }}</td>
                <td class="px-4 py-3">{{ d.route_compliance_score }}</td>
                <td class="px-4 py-3">
                  <router-link
                    :to="`/performance/rate/driver/${d.driver_id}`"
                    class="text-xs text-blue-500 hover:underline"
                  >
                    Rate
                  </router-link>
                </td>
              </tr>
              <tr v-if="topDrivers.length === 0">
                <td colspan="9" class="px-4 py-8 text-center text-gray-400">
                  No scores yet. Click "Calculate Scores" to generate.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="space-y-6">
          <div class="bg-white rounded-lg shadow">
            <div class="px-4 py-3 border-b font-semibold text-gray-700">Bottom Drivers</div>
            <table class="w-full text-sm">
              <thead class="bg-gray-50 text-left">
                <tr>
                  <th class="px-4 py-2 text-gray-500">Driver</th>
                  <th class="px-4 py-2 text-gray-500">Score</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(d, i) in bottomDrivers" :key="d.id" class="border-t hover:bg-gray-50">
                  <td class="px-4 py-2">
                    <router-link :to="`/performance/drivers/${d.driver_id}`" class="text-blue-600 hover:underline text-xs">
                      {{ d.driver_name }}
                    </router-link>
                  </td>
                  <td class="px-4 py-2">
                    <span :class="scoreBadge(d.overall_score)" class="px-2 py-0.5 rounded text-xs font-semibold">
                      {{ d.overall_score }}
                    </span>
                  </td>
                </tr>
                <tr v-if="bottomDrivers.length === 0">
                  <td colspan="2" class="px-4 py-8 text-center text-gray-400">No data</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="bg-white rounded-lg shadow p-4">
            <h3 class="font-semibold text-gray-700 mb-2">Quick Actions</h3>
            <div class="space-y-2">
              <router-link
                to="/performance/rate"
                class="block text-sm text-blue-600 hover:underline"
              >
                Rate a Driver
              </router-link>
              <router-link
                to="/performance/dispatchers"
                class="block text-sm text-blue-600 hover:underline"
              >
                Dispatcher Profiles
              </router-link>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { ratingsApi } from '../../api/ratings'

const periodStart = ref(new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0])
const periodEnd = ref(new Date().toISOString().split('T')[0])
const topDrivers = ref([])
const bottomDrivers = ref([])
const loading = ref(false)

function scoreBadge(score) {
  if (score >= 80) return 'bg-green-100 text-green-800'
  if (score >= 60) return 'bg-yellow-100 text-yellow-800'
  return 'bg-red-100 text-red-800'
}

async function refresh() {
  loading.value = true
  try {
    const [topRes, bottomRes] = await Promise.all([
      ratingsApi.topDrivers(periodStart.value, periodEnd.value),
      ratingsApi.bottomDrivers(periodStart.value, periodEnd.value),
    ])
    topDrivers.value = topRes.data.data || []
    bottomDrivers.value = bottomRes.data.data || []
  } catch (e) {
    console.error('Failed to load leaderboard', e)
  } finally {
    loading.value = false
  }
}

async function triggerCalculate() {
  loading.value = true
  try {
    await ratingsApi.calculateScores(periodStart.value, periodEnd.value)
    await refresh()
  } catch (e) {
    console.error('Failed to calculate scores', e)
  } finally {
    loading.value = false
  }
}

onMounted(refresh)
</script>
