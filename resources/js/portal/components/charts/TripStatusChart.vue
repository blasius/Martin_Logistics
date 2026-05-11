<template>
    <div class="w-full h-full flex items-center justify-center">
        <div v-if="!data.length" class="text-center text-slate-400 text-sm">
            No trip status data available for selected period
        </div>
        <div v-else class="w-full max-w-md">
            <div class="grid grid-cols-2 gap-4">
                <div v-for="item in data" :key="item.status" class="text-center">
                    <div class="relative w-20 h-20 mx-auto mb-2">
                        <svg class="w-20 h-20 transform -rotate-90">
                            <circle
                                cx="40"
                                cy="40"
                                r="36"
                                stroke="#e2e8f0"
                                stroke-width="8"
                                fill="none"
                            />
                            <circle
                                cx="40"
                                cy="40"
                                r="36"
                                :stroke="getStatusColor(item.status)"
                                stroke-width="8"
                                fill="none"
                                :stroke-dasharray="circumference"
                                :stroke-dashoffset="getDashOffset(item.count)"
                                class="transition-all duration-500"
                            />
                        </svg>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <span class="text-lg font-black text-slate-800">{{ item.count }}</span>
                        </div>
                    </div>
                    <p class="text-xs font-medium text-slate-600 capitalize">{{ item.status.replace('_', ' ') }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    data: {
        type: Array,
        default: () => []
    }
});

const circumference = computed(() => {
    return 2 * Math.PI * 36;
});

const totalCount = computed(() => {
    return props.data.reduce((sum, item) => sum + item.count, 0);
});

const getDashOffset = (count) => {
    const percentage = totalCount.value > 0 ? count / totalCount.value : 0;
    return circumference.value - (percentage * circumference.value);
};

const getStatusColor = (status) => {
    const colors = {
        'pending': '#64748b',
        'assigned': '#2563eb',
        'on_route': '#d97706',
        'delivered': '#16a34a',
        'cancelled': '#dc2626'
    };
    return colors[status] || '#64748b';
};
</script>
