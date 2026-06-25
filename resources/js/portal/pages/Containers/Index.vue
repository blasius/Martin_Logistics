<template>
  <div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold">Container Registry</h1>
      <div class="flex gap-2">
        <router-link to="/containers/create" class="bg-blue-600 text-white px-4 py-2 rounded text-sm">+ Add Container</router-link>
        <router-link to="/containers/contracts/create" class="bg-green-600 text-white px-4 py-2 rounded text-sm">+ Add Contract</router-link>
      </div>
    </div>

    <div class="bg-white rounded-lg shadow p-4 flex flex-wrap gap-4">
      <input v-model="filters.search" placeholder="Search container ID..." class="border rounded px-3 py-2 text-sm flex-1 min-w-[200px]" @input="debounceLoad" />
      <select v-model="filters.status" class="border rounded px-3 py-2 text-sm" @change="loadContainers">
        <option value="">All Statuses</option>
        <option value="at_port">At Port</option>
        <option value="in_transit">In Transit</option>
        <option value="at_warehouse">At Warehouse</option>
        <option value="at_customer">At Customer</option>
        <option value="empty_returned">Empty Returned</option>
        <option value="damaged">Damaged</option>
        <option value="scrapped">Scrapped</option>
      </select>
      <select v-model="filters.owner_type" class="border rounded px-3 py-2 text-sm" @change="loadContainers">
        <option value="">All Owners</option>
        <option value="private">Private</option>
        <option value="shipping_line">Shipping Line</option>
      </select>
      <select v-model="filters.size" class="border rounded px-3 py-2 text-sm" @change="loadContainers">
        <option value="">All Sizes</option>
        <option value="20ft">20ft</option>
        <option value="40ft">40ft</option>
        <option value="40hc">40hc</option>
      </select>
    </div>

    <div v-if="loading" class="text-center py-8 text-gray-500">Loading...</div>

    <div v-else class="bg-white rounded-lg shadow overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 text-left">
          <tr>
            <th class="px-4 py-2 text-gray-500">Container ID</th>
            <th class="px-4 py-2 text-gray-500">Size</th>
            <th class="px-4 py-2 text-gray-500">Type</th>
            <th class="px-4 py-2 text-gray-500">Owner</th>
            <th class="px-4 py-2 text-gray-500">Shipping Line</th>
            <th class="px-4 py-2 text-gray-500">Status</th>
            <th class="px-4 py-2 text-gray-500">Penalties</th>
            <th class="px-4 py-2 text-gray-500"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="c in containers" :key="c.id" class="border-t hover:bg-gray-50">
            <td class="px-4 py-3 font-mono">{{ c.container_id }}</td>
            <td class="px-4 py-3">{{ c.size }}</td>
            <td class="px-4 py-3 capitalize">{{ c.type.replace(/_/g, ' ') }}</td>
            <td class="px-4 py-3 capitalize">{{ c.owner_type.replace(/_/g, ' ') }}</td>
            <td class="px-4 py-3">{{ c.shipping_line || '-' }}</td>
            <td class="px-4 py-3">
              <span :class="statusBadge(c.current_status)" class="px-2 py-0.5 rounded text-xs">
                {{ c.current_status.replace(/_/g, ' ') }}
              </span>
            </td>
            <td class="px-4 py-3">
              <span v-if="c.has_penalties" class="text-red-600 font-semibold">{{ formatCurrency(c.total_penalty) }}</span>
              <span v-else class="text-gray-400">-</span>
            </td>
            <td class="px-4 py-3">
              <router-link :to="`/containers/${c.id}`" class="text-blue-600 hover:underline text-xs">View</router-link>
            </td>
          </tr>
          <tr v-if="containers.length === 0">
            <td colspan="8" class="px-4 py-8 text-center text-gray-400">No containers found</td>
          </tr>
        </tbody>
      </table>

      <div v-if="pagination" class="px-4 py-3 border-t flex items-center justify-between text-sm text-gray-500">
        <span>Page {{ pagination.current_page }} of {{ pagination.last_page }} ({{ pagination.total }} total)</span>
        <div class="flex gap-2">
          <button :disabled="!pagination.prev_page_url" @click="goToPage(pagination.current_page - 1)" class="px-3 py-1 border rounded disabled:opacity-50">Prev</button>
          <button :disabled="!pagination.next_page_url" @click="goToPage(pagination.current_page + 1)" class="px-3 py-1 border rounded disabled:opacity-50">Next</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { containersApi } from '../../api/containers'

const containers = ref([])
const pagination = ref(null)
const loading = ref(false)
const filters = reactive({ search: '', status: '', owner_type: '', size: '' })
let debounceTimer = null

function statusBadge(s) {
  const map = {
    at_port: 'bg-yellow-100 text-yellow-800',
    in_transit: 'bg-blue-100 text-blue-800',
    at_warehouse: 'bg-gray-100 text-gray-800',
    at_customer: 'bg-purple-100 text-purple-800',
    empty_returned: 'bg-green-100 text-green-800',
    damaged: 'bg-red-100 text-red-800',
    scrapped: 'bg-gray-200 text-gray-500',
  }
  return map[s] || 'bg-gray-100 text-gray-800'
}

function formatCurrency(val) {
  const n = Number(val)
  if (isNaN(n)) return '-'
  return n.toLocaleString('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 0 })
}

function debounceLoad() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(loadContainers, 300)
}

async function goToPage(page) {
  await loadContainers(page)
}

async function loadContainers(page = 1) {
  loading.value = true
  try {
    const params = { page, per_page: 25 }
    if (filters.search) params.search = filters.search
    if (filters.status) params.status = filters.status
    if (filters.owner_type) params.owner_type = filters.owner_type
    if (filters.size) params.size = filters.size
    const res = await containersApi.index(params)
    containers.value = res.data.data || []
    pagination.value = {
      current_page: res.data.current_page,
      last_page: res.data.last_page,
      total: res.data.total,
      prev_page_url: res.data.prev_page_url,
      next_page_url: res.data.next_page_url,
    }
  } catch (e) {
    console.error('Failed to load containers', e)
  } finally {
    loading.value = false
  }
}

onMounted(() => loadContainers())
</script>
