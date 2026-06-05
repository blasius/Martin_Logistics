<template>
    <div class="flex items-center justify-between">
        <span class="text-[10px] font-bold text-slate-500">{{ label }}</span>
        <span class="text-xs font-black" :class="cls">{{ value ?? '—' }}</span>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps(['label', 'value', 'highlight', 'warning'])

const cls = computed(() => {
    if (props.highlight) return 'text-emerald-600'
    if (props.warning) return 'text-red-500'
    return 'text-slate-700'
})
</script>
