<template>
    <div class="w-full h-full">
        <div v-if="!data.length" class="text-center text-slate-400 text-sm">
            No trip data available for selected period
        </div>
        <div v-else class="space-y-2">
            <div v-for="item in data" :key="item.date" class="flex items-center gap-4">
                <div class="w-20 text-xs text-slate-600 font-medium">
                    {{ formatDate(item.date) }}
                </div>
                <div class="flex-1 bg-slate-100 rounded-full h-6 relative overflow-hidden">
                    <div class="bg-blue-600 h-full rounded-full flex items-center justify-end pr-2 transition-all duration-500" :style="{ width: `${getPercentage(item.count)}%` }">
                        <span v-if="item.count > 0" class="text-xs text-white font-black">{{ item.count }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import dayjs from 'dayjs';

const props = defineProps({
    data: {
        type: Array,
        default: () => []
    }
});

const maxCount = computed(() => {
    return Math.max(...props.data.map(item => item.count), 1);
});

const getPercentage = (count) => {
    return (count / maxCount.value) * 100;
};

const formatDate = (date) => {
    return dayjs(date).format('MMM DD');
};
</script>
