<template>
  <div class="space-y-5 max-w-6xl">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-bold text-white">Control de gastos e ingresos</h1>
        <p class="text-xs text-gray-500 mt-0.5 capitalize">{{ currentMonthLabel }}</p>
      </div>
      <button @click="showModal = true" class="btn-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Nueva transacción
      </button>
    </div>

    <!-- KPIs -->
    <div class="grid grid-cols-4 gap-3">
      <div class="stat-card">
        <span class="stat-label">Ingresos</span>
        <span class="stat-value text-emerald-400 font-num">{{ fmt(store.income) }}</span>
      </div>
      <div class="stat-card">
        <span class="stat-label">Gastos</span>
        <span class="stat-value text-red-400 font-num">{{ fmt(store.expenses) }}</span>
      </div>
      <div class="stat-card">
        <span class="stat-label">Ahorro neto</span>
        <span class="stat-value font-num" :class="store.savings >= 0 ? 'text-emerald-400' : 'text-red-400'">
          {{ fmt(store.savings) }}
        </span>
      </div>
      <div class="stat-card">
        <span class="stat-label">Tasa de ahorro</span>
        <span class="stat-value font-num" :class="store.savingsRate >= 20 ? 'text-emerald-400' : 'text-yellow-400'">
          {{ store.savingsRate.toFixed(1) }}%
        </span>
        <div class="progress-bar h-1.5 mt-1.5">
          <div class="progress-fill h-1.5 bg-emerald-500" :style="{ width: Math.min(100, store.savingsRate) + '%' }"></div>
        </div>
      </div>
    </div>

    <!-- Gráfico tendencia + Gastos por categoría -->
    <div class="grid grid-cols-5 gap-4">
      <!-- Línea de tendencia -->
      <div class="col-span-3 card">
        <h2 class="text-sm font-semibold text-white mb-4">Tendencia — últimos 6 meses</h2>
        <div class="h-44">
          <LineChart v-if="lineData.labels.length" :labels="lineData.labels" :datasets="lineData.datasets" />
          <div v-else class="skeleton h-full rounded-xl"></div>
        </div>
      </div>

      <!-- Gastos por categoría -->
      <div class="col-span-2 card p-0 overflow-hidden">
        <div class="px-5 py-4 border-b border-white/5">
          <h2 class="text-sm font-semibold text-white">Gastos por categoría</h2>
        </div>
        <div class="divide-y divide-white/5 max-h-56 overflow-y-auto">
          <div v-for="cat in store.breakdown" :key="cat.category"
            class="flex items-center gap-3 px-5 py-2.5 hover:bg-white/[0.02]">
            <span class="text-base flex-shrink-0">{{ catIcon(cat.category) }}</span>
            <div class="flex-1 min-w-0">
              <div class="flex justify-between items-center mb-1">
                <span class="text-xs text-gray-400 capitalize truncate">{{ cat.category.replace(/_/g,' ') }}</span>
                <span class="text-xs font-bold text-white font-num ml-2">{{ fmt(cat.amount) }}</span>
              </div>
              <div class="progress-bar h-1">
                <div class="progress-fill h-1 bg-red-500/60"
                  :style="{ width: (cat.amount / (store.breakdown[0]?.amount || 1) * 100) + '%' }"></div>
              </div>
            </div>
          </div>
          <div v-if="!store.breakdown.length" class="px-5 py-8 text-center text-sm text-gray-600">
            Sin gastos este mes
          </div>
        </div>
      </div>
    </div>

    <!-- Filtros + Tabla -->
    <div class="card p-0 overflow-hidden">
      <div class="px-5 py-3.5 border-b border-white/5 flex items-center gap-3">
        <h2 class="text-sm font-semibold text-white">Historial de transacciones</h2>
        <div class="flex items-center gap-2 ml-auto">
          <!-- Filtro tipo -->
          <div class="flex rounded-lg border border-white/10 overflow-hidden text-xs">
            <button v-for="t in [['', 'Todos'], ['income', 'Ingresos'], ['expense', 'Gastos']]" :key="t[0]"
              @click="filter.type = t[0]"
              class="px-3 py-1.5 font-medium transition-colors"
              :class="filter.type === t[0] ? 'bg-blue-600 text-white' : 'text-gray-500 hover:bg-white/5'">
              {{ t[1] }}
            </button>
          </div>
          <!-- Filtro mes -->
          <select v-model="filter.month" class="input-sm w-32">
            <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
          </select>
        </div>
      </div>

      <div class="divide-y divide-white/5 max-h-96 overflow-y-auto">
        <div v-for="tx in store.list" :key="tx.id"
          class="flex items-center gap-4 px-5 py-3 hover:bg-white/[0.02] transition-colors group">
          <!-- Ícono categoría -->
          <div class="w-9 h-9 rounded-xl flex items-center justify-center text-lg flex-shrink-0"
            :class="tx.type === 'income' ? 'bg-emerald-500/15' : 'bg-red-500/15'">
            {{ catIcon(tx.category) }}
          </div>
          <!-- Info -->
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-200 truncate">{{ tx.description || tx.category.replace(/_/g,' ') }}</p>
            <p class="text-xs text-gray-600 capitalize">{{ tx.category.replace(/_/g,' ') }} · {{ formatDate(tx.date) }}</p>
          </div>
          <!-- Monto -->
          <span class="font-bold font-num text-sm flex-shrink-0"
            :class="tx.type === 'income' ? 'text-emerald-400' : 'text-red-400'">
            {{ tx.type === 'income' ? '+' : '−' }}{{ fmt(tx.amount) }}
          </span>
          <!-- Eliminar -->
          <button @click="store.remove(tx.id)"
            class="opacity-0 group-hover:opacity-100 btn-icon text-gray-700 hover:text-red-400 transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>
        <div v-if="!store.list.length" class="text-center text-gray-600 text-sm py-12">
          No hay transacciones para este período.
        </div>
      </div>
    </div>

    <!-- Modal nueva transacción -->
    <div v-if="showModal" class="modal-backdrop" @click.self="showModal = false">
      <div class="modal">
        <div class="flex items-center justify-between mb-5">
          <h3 class="font-bold text-white">Nueva transacción</h3>
          <button @click="showModal = false" class="btn-icon text-gray-500">✕</button>
        </div>
        <form @submit.prevent="handleCreate" class="space-y-4">
          <!-- Tipo -->
          <div class="grid grid-cols-2 gap-2">
            <button type="button" @click="form.type = 'income'"
              class="py-2.5 rounded-xl text-sm font-semibold border transition-all"
              :class="form.type === 'income'
                ? 'bg-emerald-500/20 border-emerald-500/50 text-emerald-400'
                : 'border-white/5 bg-white/5 text-gray-500 hover:border-white/10'">
              ↑ Ingreso
            </button>
            <button type="button" @click="form.type = 'expense'"
              class="py-2.5 rounded-xl text-sm font-semibold border transition-all"
              :class="form.type === 'expense'
                ? 'bg-red-500/20 border-red-500/50 text-red-400'
                : 'border-white/5 bg-white/5 text-gray-500 hover:border-white/10'">
              ↓ Gasto
            </button>
          </div>
          <div>
            <label class="label">Monto (CLP)</label>
            <input v-model.number="form.amount" type="number" step="any" class="input" placeholder="0" required />
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="label">Categoría</label>
              <select v-model="form.category" class="input" required>
                <option value="" disabled>Seleccionar...</option>
                <option v-for="cat in currentCategories" :key="cat" :value="cat">
                  {{ catIcon(cat) }} {{ cat.replace(/_/g,' ') }}
                </option>
              </select>
            </div>
            <div>
              <label class="label">Fecha</label>
              <input v-model="form.date" type="date" class="input" :max="today" required />
            </div>
          </div>
          <div>
            <label class="label">Descripción (opcional)</label>
            <input v-model="form.description" class="input" placeholder="Ej: Supermercado Jumbo..." />
          </div>
          <div class="flex gap-3 pt-1">
            <button type="button" @click="showModal = false" class="btn-secondary flex-1">Cancelar</button>
            <button type="submit" class="btn-primary flex-1" :disabled="saving">
              {{ saving ? 'Guardando...' : 'Guardar' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useTransactionsStore } from '@/stores/transactions'
import { transactions as txApi } from '@/services/api'
import LineChart from '@/components/charts/LineChart.vue'

const store     = useTransactionsStore()
const showModal = ref(false)
const saving    = ref(false)
const today     = new Date().toISOString().slice(0, 10)
const categories = ref({ income: [], expense: [] })
const history    = ref([])
const filter     = ref({ type: '', month: new Date().getMonth() + 1 })

const form = ref({ type: 'expense', amount: null, category: '', description: '', date: today })

const currentMonthLabel = new Intl.DateTimeFormat('es-CL', { month: 'long', year: 'numeric' }).format(new Date())
const months = Array.from({ length: 12 }, (_, i) => ({
  value: i + 1,
  label: new Date(2024, i).toLocaleString('es-CL', { month: 'long' }),
}))

const currentCategories = computed(() => categories.value[form.value.type] ?? [])

const lineData = computed(() => {
  if (!history.value.length) return { labels: [], datasets: [] }
  return {
    labels: history.value.map(h =>
      new Intl.DateTimeFormat('es-CL', { month: 'short' }).format(new Date(h.year, h.month - 1))
    ),
    datasets: [
      { label: 'Ingresos', data: history.value.map(h => h.income), borderColor: '#10b981', backgroundColor: '#10b98115', tension: 0.4, fill: true, borderWidth: 2, pointRadius: 3 },
      { label: 'Gastos',   data: history.value.map(h => h.expenses), borderColor: '#ef4444', backgroundColor: '#ef444415', tension: 0.4, fill: true, borderWidth: 2, pointRadius: 3 },
      { label: 'Ahorro',   data: history.value.map(h => h.savings), borderColor: '#3b82f6', backgroundColor: 'transparent', tension: 0.4, borderWidth: 2, pointRadius: 3, borderDash: [4,4] },
    ],
  }
})

const fmt = (v) => new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP', maximumFractionDigits: 0 }).format(v ?? 0)
const formatDate = (d) => new Date(d + 'T12:00:00').toLocaleDateString('es-CL', { day: 'numeric', month: 'short' })
const catIcon = (c) => ({ sueldo:'💼', freelance:'💻', inversiones:'📈', arriendo:'🏠', otros_ingresos:'💰', alimentacion:'🍽️', transporte:'🚗', vivienda:'🏠', salud:'⚕️', educacion:'📚', entretenimiento:'🎬', tecnologia:'📱', servicios_basicos:'💡', seguros:'🛡️', ropa:'👕', viajes:'✈️', otros_gastos:'💸' })[c] ?? '💰'

async function loadData() {
  const year = new Date().getFullYear()
  await Promise.all([
    store.fetchList({ month: filter.value.month, year, type: filter.value.type || undefined }),
    store.fetchSummary(year, filter.value.month),
    store.fetchBreakdown(year, filter.value.month),
  ])
}

async function handleCreate() {
  saving.value = true
  try {
    await store.create({ ...form.value, currency: 'CLP' })
    showModal.value = false
    form.value = { type: 'expense', amount: null, category: '', description: '', date: today }
    await loadData()
  } finally {
    saving.value = false
  }
}

watch(() => [filter.value.month, filter.value.type], loadData)

onMounted(async () => {
  const [cats, hist] = await Promise.all([txApi.categories(), txApi.history(6)])
  categories.value = cats.data.categories
  history.value    = hist.data.history
  await loadData()
})
</script>
