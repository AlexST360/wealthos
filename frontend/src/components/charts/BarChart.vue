<template>
  <canvas ref="canvas"></canvas>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue'
import {
  Chart, BarController, BarElement,
  LinearScale, CategoryScale, Tooltip, Legend,
} from 'chart.js'

Chart.register(BarController, BarElement, LinearScale, CategoryScale, Tooltip, Legend)

const props = defineProps({
  labels:   { type: Array, default: () => [] },
  datasets: { type: Array, default: () => [] },
})

const canvas = ref(null)
let chart = null

function build() {
  if (!canvas.value) return
  if (chart) chart.destroy()

  chart = new Chart(canvas.value, {
    type: 'bar',
    data: { labels: props.labels, datasets: props.datasets },
    options: {
      responsive:          true,
      maintainAspectRatio: false,
      interaction:         { mode: 'index', intersect: false },
      plugins: {
        legend: { labels: { color: '#9ca3af', font: { size: 11 }, boxWidth: 12, padding: 16 } },
        tooltip: {
          callbacks: {
            label: (ctx) => {
              const v = new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP', maximumFractionDigits: 0 }).format(ctx.raw)
              return ` ${ctx.dataset.label}: ${v}`
            },
          },
        },
      },
      scales: {
        x: { ticks: { color: '#6b7280', font: { size: 11 } }, grid: { display: false } },
        y: {
          ticks: {
            color: '#6b7280',
            font: { size: 11 },
            callback: (v) => new Intl.NumberFormat('es-CL', { notation: 'compact', style: 'currency', currency: 'CLP' }).format(v),
          },
          grid: { color: '#1f2937' },
        },
      },
    },
  })
}

watch(() => [props.labels, props.datasets], build, { deep: true })
onMounted(build)
onUnmounted(() => chart?.destroy())
</script>
