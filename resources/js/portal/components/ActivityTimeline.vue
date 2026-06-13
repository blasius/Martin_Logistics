<template>
  <div v-if="logs.length" class="space-y-4">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Activity Timeline</h3>

    <div class="relative">
      <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200 dark:bg-gray-600" />

      <div v-for="(log, index) in logs" :key="log.id" class="relative pl-10 pb-6 last:pb-0">
        <div
          class="absolute left-2.5 top-1.5 w-3 h-3 rounded-full border-2"
          :class="dotClass(log.action)"
        />

        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-3">
          <div class="flex items-center justify-between mb-1">
            <span class="text-sm font-medium text-gray-900 dark:text-white">
              {{ formatAction(log.action) }}
            </span>
            <span class="text-xs text-gray-500 dark:text-gray-400">
              {{ formatDate(log.created_at) }}
            </span>
          </div>

          <p class="text-sm text-gray-600 dark:text-gray-300 mb-1">
            {{ log.description }}
          </p>

          <p v-if="log.user" class="text-xs text-gray-400">
            by {{ log.user.name }}
            <span v-if="log.ip_address" class="ml-2">({{ log.ip_address }})</span>
          </p>

          <div v-if="log.old_values || log.new_values" class="mt-2 text-xs">
            <table class="w-full">
              <thead>
                <tr class="text-gray-400 dark:text-gray-500">
                  <th class="text-left pr-2">Field</th>
                  <th class="text-left pr-2">Old</th>
                  <th class="text-left">New</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(value, field) in log.new_values" :key="field" class="border-t border-gray-100 dark:border-gray-700">
                  <td class="py-1 pr-2 font-mono text-gray-500 dark:text-gray-400">{{ field }}</td>
                  <td class="py-1 pr-2 text-red-600 dark:text-red-400">
                    <template v-if="log.old_values && log.old_values[field] !== undefined">
                      {{ displayValue(log.old_values[field]) }}
                    </template>
                    <span v-else class="text-gray-300 dark:text-gray-600">&mdash;</span>
                  </td>
                  <td class="py-1 text-green-600 dark:text-green-400">
                    {{ displayValue(value) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div v-else-if="loaded" class="text-sm text-gray-400 text-center py-8">
    No activity recorded yet.
  </div>
  <div v-else class="text-sm text-gray-400 text-center py-8">
    Loading...
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { format } from 'date-fns'

const props = defineProps({
  auditableType: { type: String, required: true },
  auditableId: { type: [String, Number], required: true },
})

const logs = ref([])
const loaded = ref(false)

function dotClass(action) {
  const map = {
    created: 'bg-green-400 border-green-500',
    updated: 'bg-blue-400 border-blue-500',
    deleted: 'bg-red-400 border-red-500',
    approved: 'bg-emerald-400 border-emerald-500',
    rejected: 'bg-orange-400 border-orange-500',
  }
  return map[action] || 'bg-gray-400 border-gray-500'
}

function formatAction(action) {
  return action.charAt(0).toUpperCase() + action.slice(1).replace(/_/g, ' ')
}

function formatDate(date) {
  return format(new Date(date), 'MMM d, yyyy HH:mm')
}

function displayValue(value) {
  if (value === null || value === undefined) return '—'
  if (typeof value === 'boolean') return value ? 'Yes' : 'No'
  if (typeof value === 'object') return JSON.stringify(value)
  return String(value)
}

async function fetchLogs() {
  if (!props.auditableType || !props.auditableId) return
  loaded.value = false
  try {
    const res = await fetch(`/api/portal/audit-logs/timeline/${props.auditableType}/${props.auditableId}`)
    logs.value = await res.json()
  } catch (e) {
    console.error('Failed to load audit logs:', e)
  } finally {
    loaded.value = true
  }
}

watch(() => [props.auditableType, props.auditableId], fetchLogs, { immediate: true })
</script>
