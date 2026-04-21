<template>
  <div class="relative">
    <canvas ref="canvas" :height="size"></canvas>
    <div v-if="centerLabel" class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
      <span class="text-xs text-gray-500 font-medium">{{ centerLabel }}</span>
      <span class="text-base font-bold text-white">{{ centerValue }}</span>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue'
import { Chart, DoughnutController, ArcElement, Tooltip, Legend } from 'chart.js'

Chart.register(DoughnutController, ArcElement, Tooltip, Legend)

const props = defineProps({
  data:        { type: Array, default: () => [] },
  labels:      { type: Array, default: () => [] },
  colors:      { type: Array, default: () => [] },
  size:        { type: Number, default: 200 },
  centerLabel: { type: String, default: '' },
  centerValue: { type: String, default: '' },
})

const canvas = ref(null)
let chart = null

const defaultColors = ['#3b82f6','#10b981','#f59e0b','#8b5cf6','#ef4444','#06b6d4','#ec4899']

function build() {
  if (!canvas.value || !props.data.length) return
  if (chart) chart.destroy()

  chart = new Chart(canvas.value, {
    type: 'doughnut',
    data: {
      labels:   props.labels,
      datasets: [{
        data:            props.data,
        backgroundColor: props.colors.length ? props.colors : defaultColors,
        borderColor:     '#111827',
        borderWidth:     3,
        hoverOffset:     6,
      }],
    },
    options: {
      responsive:          true,
      maintainAspectRatio: true,
      cutout:              '72%',
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: (ctx) => ` ${ctx.label}: ${ctx.parsed.toFixed(1)}%`,
          },
        },
      },
    },
  })
}

watch(() => props.data, build, { deep: true })
onMounted(build)
onUnmounted(() => chart?.destroy())
</script>
