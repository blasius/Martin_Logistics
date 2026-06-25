<template>
    <div class="w-full h-full">
        <canvas ref="chartRef"></canvas>
    </div>
</template>

<script setup>
import { ref, onMounted, watch, onUnmounted } from 'vue'
import Chart from 'chart.js/auto'

const props = defineProps({
    labels: { type: Array, default: () => [] },
    series: { type: Array, default: () => [] },
    colors: { type: Array, default: () => [] },
    options: { type: Object, default: () => ({}) },
})

const chartRef = ref(null)
let chart = null

const render = () => {
    if (!chartRef.value || !props.labels.length) return
    if (chart) chart.destroy()
    chart = new Chart(chartRef.value, {
        type: 'doughnut',
        data: {
            labels: props.labels,
            datasets: [{
                data: props.series,
                backgroundColor: props.colors.length ? props.colors : ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#94a3b8'],
                borderWidth: 0,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'right', labels: { font: { size: 9 }, boxWidth: 10 } } },
            ...props.options,
        }
    })
}

watch(() => [props.labels, props.series], render, { deep: true })
onMounted(render)
onUnmounted(() => { if (chart) chart.destroy() })
</script>
