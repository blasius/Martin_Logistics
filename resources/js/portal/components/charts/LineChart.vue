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
    datasets: { type: Array, default: () => [] },
    options: { type: Object, default: () => ({}) },
})

const chartRef = ref(null)
let chart = null

const render = () => {
    if (!chartRef.value || !props.labels.length) return
    if (chart) chart.destroy()
    chart = new Chart(chartRef.value, {
        type: 'line',
        data: { labels: props.labels, datasets: props.datasets.map(d => ({ ...d, fill: false, tension: 0.3 })) },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'top', labels: { font: { size: 9 }, boxWidth: 10 } } },
            scales: {
                x: { ticks: { font: { size: 8 } } },
                y: { beginAtZero: true, ticks: { font: { size: 8 } } }
            },
            ...props.options,
        }
    })
}

watch(() => [props.labels, props.datasets], render, { deep: true })
onMounted(render)
onUnmounted(() => { if (chart) chart.destroy() })
</script>
