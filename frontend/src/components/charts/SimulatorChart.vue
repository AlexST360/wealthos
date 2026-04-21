<template>
  <canvas ref="canvas"></canvas>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue'
import {
  Chart,
  LineController, LineElement, PointElement,
  LinearScale, CategoryScale,
  Filler, Tooltip, Legend,
} from 'chart.js'

Chart.register(LineController, LineElement, PointElement, LinearScale, CategoryScale, Filler, Tooltip, Legend)

const props = defineProps({ data: Object })
const canvas = ref(null)
let chart = null

function buildChart() {
  if (!props.data || !canvas.value) return

  const datasets = []
  const labels   = []
  let first      = true

  Object.entries(props.data).forEach(([key, series]) => {
    if (first) {
      series.points.forEach(p => labels.push(`Año ${p.year ?? Math.round(p.month / 12)}`))
      first = false
    }
    datasets.push({
      label:           series.label,
      data:            series.points.map(p => p.balance),
      borderColor:     series.color,
      backgroundColor: series.color + '15',
      borderWidth:     2,
      pointRadius:     3,
      fill:            false,
      tension:         0.4,
    })
  })

  if (chart) chart.destroy()

  chart = new Chart(canvas.value, {
    type: 'line',
    data: { labels, datasets },
    options: {
      responsive:          true,
      maintainAspectRatio: false,
      interaction:         { mode: 'index', intersect: false },
      plugins: {
        legend: {
          labels: { color: '#9ca3af', font: { size: 11 } },
        },
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
        x: {
          ticks:  { color: '#6b7280', font: { size: 10 } },
          grid:   { color: '#1f2937' },
        },
        y: {
          ticks: {
            color: '#6b7280',
            font:  { size: 10 },
            callback: (v) => new Intl.NumberFormat('es-CL', { notation: 'compact', style: 'currency', currency: 'CLP' }).format(v),
          },
          grid: { color: '#1f2937' },
        },
      },
    },
  })
}

watch(() => props.data, buildChart, { deep: true })
onMounted(buildChart)
onUnmounted(() => chart?.destroy())
</script>
