<template>
  <div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold">Container Dashboard</h1>
      <div class="flex gap-2">
        <button @click="loadDashboard" class="bg-blue-600 text-white px-4 py-2 rounded text-sm">Refresh</button>
        <button @click="batchCalculate" class="bg-green-600 text-white px-4 py-2 rounded text-sm">Calculate Penalties</button>
      </div>
    </div>

    <div v-if="loading" class="text-center py-8 text-gray-500">Loading...</div>

    <template v-else>
      <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="bg-white rounded-lg shadow p-4 text-center">
          <div class="text-3xl font-bold text-blue-600">{{ stats.total_containers }}</div>
          <div class="text-xs text-gray-500 mt-1">Total Containers</div>
        </div>
        <div v-for="(count, status) in stats.by_status" :key="status" class="bg-white rounded-lg shadow p-4 text-center">
          <div class="text-2xl font-bold" :class="statusColor(status)">{{ count }}</div>
          <div class="text-xs text-gray-500 mt-1 capitalize">{{ status.replace(/_/g, ' ') }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center">
          <div class="text-2xl font-bold text-red-600">{{ stats.overdue_containers }}</div>
          <div class="text-xs text-gray-500 mt-1">Overdue</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center">
          <div class="text-2xl font-bold text-orange-600">{{ formatCurrency(stats.total_penalties_amount) }}</div>
          <div class="text-xs text-gray-500 mt-1">Total Penalties</div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow">
        <div class="px-4 py-3 border-b font-semibold text-gray-700 flex items-center justify-between">
          <span>Overdue Containers</span>
          <router-link to="/containers" class="text-sm text-blue-600 hover:underline">View All</router-link>
        </div>
        <table class="w-full text-sm">
          <thead class="bg-gray-50 text-left">
            <tr>
              <th class="px-4 py-2 text-gray-500">Container</th>
              <th class="px-4 py-2 text-gray-500">Size</th>
              <th class="px-4 py-2 text-gray-500">Line</th>
              <th class="px-4 py-2 text-gray-500">Status</th>
              <th class="px-4 py-2 text-gray-500">Penalty</th>
              <th class="px-4 py-2 text-gray-500">Amount</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="c in overdue" :key="c.id" class="border-t hover:bg-gray-50">
              <td class="px-4 py-3">
                <router-link :to="`/containers/${c.id}`" class="text-blue-600 hover:underline font-mono">
                  {{ c.container_id }}
                </router-link>
              </td>
              <td class="px-4 py-3">{{ c.size }}</td>
              <td class="px-4 py-3">{{ c.shipping_line || '-' }}</td>
              <td class="px-4 py-3 capitalize">{{ c.current_status.replace(/_/g, ' ') }}</td>
              <td class="px-4 py-3">
                <span v-for="p in c.penalties" :key="p.type" class="block text-xs">
                  {{ p.type }}: {{ p.days_overdue }} days @ {{ formatCurrency(p.daily_rate) }}/day
                </span>
              </td>
              <td class="px-4 py-3 font-semibold text-red-600">
                {{ formatCurrency(c.penalties.reduce((s, p) => s + p.total_amount, 0)) }}
              </td>
            </tr>
            <tr v-if="overdue.length === 0">
              <td colspan="6" class="px-4 py-8 text-center text-gray-400">No overdue containers</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="bg-white rounded-lg shadow">
        <div class="px-4 py-3 border-b font-semibold text-gray-700">Recent Movements</div>
        <table class="w-full text-sm">
          <thead class="bg-gray-50 text-left">
            <tr>
              <th class="px-4 py-2 text-gray-500">Container</th>
              <th class="px-4 py-2 text-gray-500">Movement</th>
              <th class="px-4 py-2 text-gray-500">Arrived</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="m in stats.recent_movements" :key="m.id" class="border-t">
              <td class="px-4 py-3 font-mono text-xs">{{ m.container_id }}</td>
              <td class="px-4 py-3 capitalize">{{ m.movement_type.replace(/_/g, ' ') }}</td>
              <td class="px-4 py-3 text-xs">{{ formatDate(m.arrived_at) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { containersApi } from '../../api/containers'

const loading = ref(false)
const stats = ref({ total_containers: 0, by_status: {}, overdue_containers: 0, total_penalties_amount: 0, recent_movements: [] })
const overdue = ref([])

function statusColor(s) {
  const map = { at_port: 'text-yellow-600', in_transit: 'text-blue-600', at_warehouse: 'text-gray-600', at_customer: 'text-purple-600', empty_returned: 'text-green-600', damaged: 'text-red-600', scrapped: 'text-gray-400' }
  return map[s] || 'text-gray-600'
}

function formatCurrency(val) {
  const n = Number(val)
  if (isNaN(n)) return '-'
  return n.toLocaleString('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 0 })
}

function formatDate(d) {
  if (!d) return '-'
  return new Date(d).toLocaleDateString()
}

async function loadDashboard() {
  loading.value = true
  try {
    const res = await containersApi.dashboard()
    stats.value = res.data.data.stats
    overdue.value = res.data.data.overdue_containers
  } catch (e) {
    console.error('Failed to load dashboard', e)
  } finally {
    loading.value = false
  }
}

async function batchCalculate() {
  try {
    await containersApi.batchCalculatePenalties()
    await loadDashboard()
  } catch (e) {
    console.error('Failed to calculate penalties', e)
  }
}

onMounted(loadDashboard)
</script>
