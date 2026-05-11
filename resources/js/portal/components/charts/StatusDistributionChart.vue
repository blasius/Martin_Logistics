<template>
    <div class="w-full h-full">
        <div v-if="!data.length" class="text-center text-slate-400 text-sm">
            No status data available for selected period
        </div>
        <div v-else class="space-y-3">
            <div v-for="item in data" :key="item.status" class="flex items-center justify-between p-3 bg-slate-50 rounded-lg">
                <div class="flex items-center gap-3">
                    <div class="w-4 h-4 rounded-full" :style="{ backgroundColor: getStatusColor(item.status) }"></div>
                    <span class="text-sm font-medium text-slate-700 capitalize">{{ item.status }}</span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-32 bg-slate-200 rounded-full h-2">
                        <div class="h-full rounded-full transition-all duration-500" :style="{ 
                            width: `${getPercentage(item.count)}%`,
                            backgroundColor: getStatusColor(item.status)
                        }"></div>
                    </div>
                    <span class="text-sm font-black text-slate-800 w-12 text-right">{{ item.count }}</span>
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

const maxCount = computed(() => {
    return Math.max(...props.data.map(item => item.count), 1);
});

const getPercentage = (count) => {
    return (count / maxCount.value) * 100;
};

const getStatusColor = (status) => {
    const colors = {
        'draft': '#64748b',
        'confirmed': '#2563eb',
        'in_transit': '#d97706',
        'delivered': '#16a34a',
        'cancelled': '#dc2626',
        'pending': '#64748b',
        'assigned': '#2563eb',
        'on_route': '#d97706'
    };
    return colors[status] || '#64748b';
};
</script>
