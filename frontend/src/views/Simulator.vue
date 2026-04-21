<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold text-white">📈 Simulador de Escenarios</h1>
      <p class="text-gray-400 text-sm">Proyecta el crecimiento de tu inversión en distintos instrumentos</p>
    </div>

    <!-- Panel de inputs -->
    <div class="grid grid-cols-3 gap-6">
      <div class="col-span-1 card space-y-5">
        <h2 class="font-semibold text-white">Parámetros</h2>

        <!-- Monto inicial -->
        <div>
          <label class="label">Monto inicial (CLP)</label>
          <input v-model.number="params.initialAmount" type="number" class="input" step="100000" />
          <p class="text-xs text-gray-500 mt-1">{{ formatCLP(params.initialAmount) }}</p>
        </div>

        <!-- Aporte mensual -->
        <div>
          <label class="label">Aporte mensual (CLP)</label>
          <input v-model.number="params.monthlyContribution" type="number" class="input" step="10000" />
          <p class="text-xs text-gray-500 mt-1">{{ formatCLP(params.monthlyContribution) }}</p>
        </div>

        <!-- Años -->
        <div>
          <label class="label">Período: {{ params.years }} años</label>
          <input v-model.number="params.years" type="range" min="1" max="40" class="w-full accent-blue-500" />
          <div class="flex justify-between text-xs text-gray-500 mt-1">
            <span>1 año</span>
            <span>40 años</span>
          </div>
        </div>

        <!-- Instrumentos -->
        <div>
          <label class="label">Instrumentos (máx 3)</label>
          <div class="space-y-2">
            <label v-for="inst in instruments" :key="inst.id"
              class="flex items-center gap-3 p-2 rounded-lg cursor-pointer hover:bg-gray-800/50 transition-colors"
              :class="{ 'bg-gray-800 border border-gray-700': selectedInstruments.includes(inst.id) }">
              <input type="checkbox" :value="inst.id" v-model="selectedInstruments"
                class="rounded accent-blue-500"
                :disabled="!selectedInstruments.includes(inst.id) && selectedInstruments.length >= 3" />
              <div class="flex-1">
                <div class="flex items-center justify-between">
                  <span class="text-sm text-white font-medium">{{ inst.name }}</span>
                  <span class="text-xs font-mono" :style="{ color: inst.color }">{{ inst.annual_rate }}% /año</span>
                </div>
                <span class="text-xs text-gray-500">{{ inst.description }}</span>
              </div>
            </label>
          </div>
        </div>

        <button @click="runSimulation" class="btn-primary w-full" :disabled="!selectedInstruments.length || loading">
          {{ loading ? 'Calculando...' : '📊 Simular' }}
        </button>
      </div>

      <!-- Resultados -->
      <div class="col-span-2 space-y-4">
        <div v-if="results">
          <!-- Cards de resultado final -->
          <div class="grid grid-cols-3 gap-3 mb-4">
            <div v-for="(data, key) in results.comparisons" :key="key" class="card-sm">
              <div class="flex items-center gap-2 mb-2">
                <div class="w-3 h-3 rounded-full" :style="{ background: instrumentColor(key) }"></div>
                <span class="text-sm font-medium text-white">{{ instrumentName(key) }}</span>
              </div>
              <p class="text-lg font-bold text-white font-mono">{{ formatCLP(data.final_balance) }}</p>
              <p class="text-xs text-emerald-400 font-mono">+{{ formatCLP(data.total_gain) }} ganancia</p>
              <p class="text-xs text-gray-500">{{ data.annual_rate }}% anual</p>
            </div>
          </div>

          <!-- Gráfico de líneas -->
          <div class="card">
            <h3 class="font-semibold text-white mb-4">Proyección de crecimiento</h3>
            <div class="h-64">
              <SimulatorChart :data="chartData" />
            </div>
          </div>

          <!-- Tabla de hitos -->
          <div class="card p-0 overflow-hidden">
            <div class="p-4 border-b border-gray-800">
              <h3 class="font-semibold text-white">Hitos de crecimiento</h3>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead>
                  <tr class="text-gray-500 text-xs uppercase border-b border-gray-800">
                    <th class="text-left px-4 py-2">Año</th>
                    <th v-for="(_, key) in results.comparisons" :key="key" class="text-right px-4 py-2">
                      {{ instrumentName(key) }}
                    </th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-800">
                  <tr v-for="row in results.milestones" :key="row.year" class="hover:bg-gray-800/50">
                    <td class="px-4 py-2 font-medium text-gray-300">Año {{ row.year }}</td>
                    <td v-for="(_, key) in results.comparisons" :key="key"
                      class="px-4 py-2 text-right font-mono text-white text-xs">
                      {{ row[key] ? formatCLP(row[key]) : '—' }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Estado inicial -->
        <div v-else class="card h-96 flex flex-col items-center justify-center text-gray-500">
          <p class="text-5xl mb-4">📊</p>
          <p>Configura los parámetros y presiona Simular para ver las proyecciones.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { simulator as simulatorApi } from '@/services/api'
import SimulatorChart from '@/components/charts/SimulatorChart.vue'

const params = ref({
  initialAmount:       1000000,
  monthlyContribution: 200000,
  years:               10,
})
const selectedInstruments = ref(['sp500', 'uf'])
const instruments = ref([])
const results     = ref(null)
const loading     = ref(false)

function formatCLP(v) {
  return new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP', maximumFractionDigits: 0 }).format(v ?? 0)
}

function instrumentName(id) {
  return instruments.value.find(i => i.id === id)?.name ?? id
}
function instrumentColor(id) {
  return instruments.value.find(i => i.id === id)?.color ?? '#6b7280'
}

const chartData = computed(() => {
  if (!results.value) return null
  const allPoints = {}
  Object.entries(results.value.comparisons).forEach(([key, data]) => {
    allPoints[key] = {
      color:  instrumentColor(key),
      label:  instrumentName(key),
      points: data.points.filter((_, i) => i % 12 === 0), // Solo puntos anuales
    }
  })
  return allPoints
})

async function runSimulation() {
  loading.value = true
  try {
    const { data } = await simulatorApi.compare({
      initial_amount:       params.value.initialAmount,
      monthly_contribution: params.value.monthlyContribution,
      years:                params.value.years,
      instruments:          selectedInstruments.value,
    })
    results.value = data
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  const { data } = await simulatorApi.instruments()
  instruments.value = data.instruments
})
</script>
