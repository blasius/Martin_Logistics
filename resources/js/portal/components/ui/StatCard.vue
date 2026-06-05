<template>
    <div class="bg-slate-50 rounded-xl p-3 border border-slate-100">
        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-wider mb-1">{{ label }}</p>
        <p class="text-lg font-black" :class="'text-' + color + '-600'">{{ value ?? '—' }}</p>
    </div>
</template>

<script setup>
defineProps(['label', 'value', 'color'])
</script>
