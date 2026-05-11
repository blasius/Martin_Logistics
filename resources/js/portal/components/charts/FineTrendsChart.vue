<template>
    <div class="w-full h-full">
        <div v-if="!data.length" class="text-center text-slate-400 text-sm">
            No fine data available for selected period
        </div>
        <div v-else class="space-y-3">
            <div v-for="item in data" :key="item.date" class="flex items-center gap-4">
                <div class="w-20 text-xs text-slate-600 font-medium">
                    {{ formatDate(item.date) }}
                </div>
                <div class="flex-1 flex items-center gap-2">
                    <div class="flex-1 bg-slate-100 rounded-full h-4 relative overflow-hidden">
                        <div class="bg-rose-600 h-full rounded-full transition-all duration-500" :style="{ width: `${getPercentage(item.total_amount)}%` }"></div>
                    </div>
                    <div class="flex items-center gap-1 text-xs">
                        <span class="text-slate-500">{{ item.count }}</span>
                        <span class="text-rose-600 font-medium">${{ formatCurrency(item.total_amount) }}</span>
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

const maxAmount = computed(() => {
    return Math.max(...props.data.map(item => item.total_amount), 1);
});

const getPercentage = (amount) => {
    return (amount / maxAmount.value) * 100;
};

const formatDate = (date) => {
    return dayjs(date).format('MMM DD');
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(amount || 0);
};
</script>
