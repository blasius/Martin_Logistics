<template>
    <span v-if="flag && flag !== 'ok' && flag !== 'normal'" class="inline-block px-2 py-0.5 rounded-full text-[9px] font-black uppercase" :class="badgeClass">
        {{ flag }}
    </span>
    <span v-else-if="flag === 'ok' || flag === 'normal'" class="inline-block px-2 py-0.5 rounded-full text-[9px] font-black uppercase text-emerald-700 bg-emerald-100">
        ok
    </span>
    <span v-else class="inline-block px-2 py-0.5 rounded-full text-[9px] font-black uppercase text-slate-400 bg-slate-100">
        —
    </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    flag: { type: String, default: null },
});

const badgeClass = computed(() => {
    const map = {
        material: 'text-rose-700 bg-rose-100',
        excessive: 'text-rose-700 bg-rose-100',
        theft: 'text-rose-700 bg-rose-100',
        caution: 'text-amber-700 bg-amber-100',
        override_heavy: 'text-purple-700 bg-purple-100',
        normal: 'text-emerald-700 bg-emerald-100',
        ok: 'text-emerald-700 bg-emerald-100',
    };
    return map[props.flag] || 'text-slate-600 bg-slate-100';
});
</script>