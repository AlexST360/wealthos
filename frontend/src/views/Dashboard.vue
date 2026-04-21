<template>
  <div class="space-y-6 max-w-7xl">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">{{ today }}</p>
        <h1 class="text-2xl font-bold text-white">Buenos días, {{ auth.user?.name }} 👋</h1>
      </div>
      <button @click="refreshAll" :disabled="loading"
        class="btn-secondary gap-2 text-xs">
        <svg class="w-3.5 h-3.5" :class="{ 'animate-spin': loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
        </svg>
        Actualizar precios
      </button>
    </div>

    <!-- KPIs principales -->
    <div class="grid grid-cols-4 gap-4">
      <div class="stat-card glow-blue">
        <span class="stat-label">💼 Patrimonio total</span>
        <span class="stat-value text-blue-400 font-num">
          <template v-if="loading"><span class="skeleton h-7 w-36 block"></span></template>
          <template v-else>{{ fmt(portfolioStore.totalCLP) }}</template>
        </span>
        <span class="stat-sub font-num">≈ {{ fmtUSD(portfolioStore.totalUSD) }}</span>
      </div>
      <div class="stat-card">
        <span class="stat-label">💰 Ingresos del mes</span>
        <span class="stat-value text-emerald-400 font-num">{{ fmt(txStore.income) }}</span>
        <span class="stat-sub">{{ currentMonthLabel }}</span>
      </div>
      <div class="stat-card">
        <span class="stat-label">💸 Gastos del mes</span>
        <span class="stat-value text-red-400 font-num">{{ fmt(txStore.expenses) }}</span>
        <span class="stat-sub">{{ txStore.breakdown.length }} categorías</span>
      </div>
      <div class="stat-card" :class="txStore.savingsRate >= 20 ? 'glow-green' : ''">
        <span class="stat-label">🏦 Tasa de ahorro</span>
        <span class="stat-value font-num" :class="txStore.savingsRate >= 20 ? 'text-emerald-400' : 'text-yellow-400'">
          {{ txStore.savingsRate.toFixed(1) }}%
        </span>
        <span class="stat-pos font-num">Ahorro: {{ fmt(txStore.savings) }}</span>
      </div>
    </div>

    <!-- Fila 2: Donut portafolio + Barras ingresos/gastos -->
    <div class="grid grid-cols-5 gap-4">
      <!-- Donut distribución -->
      <div class="col-span-2 card">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-sm font-semibold text-white">Distribución del portafolio</h2>
          <RouterLink to="/portfolio" class="text-xs text-blue-400 hover:text-blue-300">Ver todo →</RouterLink>
        </div>
        <div v-if="portfolioStore.byType.length" class="flex gap-6 items-center">
          <div class="w-36 flex-shrink-0">
            <DonutChart
              :data="portfolioStore.byType.map(t => t.pct)"
              :labels="portfolioStore.byType.map(t => typeLabel(t.type))"
              :colors="portfolioStore.byType.map(t => typeColor(t.type))"
              :size="140"
              center-label="Total"
              :center-value="fmtCompact(portfolioStore.totalCLP)"
            />
          </div>
          <div class="flex-1 space-y-2.5">
            <div v-for="item in portfolioStore.byType" :key="item.type" class="flex items-center gap-2">
              <span class="w-2 h-2 rounded-full flex-shrink-0" :style="{ background: typeColor(item.type) }"></span>
              <div class="flex-1 min-w-0">
                <div class="flex justify-between items-center">
                  <span class="text-xs text-gray-400 truncate">{{ typeLabel(item.type) }}</span>
                  <span class="text-xs font-semibold text-gray-200 font-num ml-2">{{ item.pct.toFixed(1) }}%</span>
                </div>
                <div class="progress-bar h-1 mt-1">
                  <div class="progress-fill h-1" :style="{ width: item.pct + '%', background: typeColor(item.type) }"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div v-else class="flex flex-col items-center justify-center py-10 text-center">
          <div class="w-12 h-12 bg-white/5 rounded-2xl flex items-center justify-center text-2xl mb-3">💼</div>
          <p class="text-sm text-gray-500">Sin activos registrados</p>
          <RouterLink to="/portfolio" class="btn-primary mt-3 text-xs py-2">+ Agregar activo</RouterLink>
        </div>
      </div>

      <!-- Barras ingresos vs gastos últimos 6 meses -->
      <div class="col-span-3 card">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-sm font-semibold text-white">Ingresos vs Gastos — últimos 6 meses</h2>
        </div>
        <div class="h-44">
          <BarChart v-if="barData.labels.length" :labels="barData.labels" :datasets="barData.datasets" />
          <div v-else class="flex items-center justify-center h-full">
            <div class="skeleton h-36 w-full rounded-xl"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Fila 3: Top activos + Metas + Acceso IA -->
    <div class="grid grid-cols-3 gap-4">
      <!-- Top activos -->
      <div class="card p-0 overflow-hidden">
        <div class="px-5 py-4 border-b border-white/5 flex items-center justify-between">
          <h2 class="text-sm font-semibold text-white">Top activos</h2>
          <RouterLink to="/portfolio" class="text-xs text-blue-400 hover:text-blue-300">Ver todo →</RouterLink>
        </div>
        <div class="divide-y divide-white/5">
          <div v-if="portfolioStore.assets.length === 0" class="px-5 py-8 text-center text-sm text-gray-600">
            Sin activos
          </div>
          <div v-for="asset in portfolioStore.assets.slice(0,5)" :key="asset.id"
            class="px-5 py-3 flex items-center justify-between hover:bg-white/[0.02] transition-colors">
            <div class="flex items-center gap-2.5 min-w-0">
              <span class="text-base flex-shrink-0">{{ typeIcon(asset.type) }}</span>
              <div class="min-w-0">
                <p class="text-sm font-semibold text-white truncate">{{ asset.ticker }}</p>
                <p class="text-xs text-gray-600 truncate">{{ asset.name }}</p>
              </div>
            </div>
            <div class="text-right flex-shrink-0 ml-2">
              <p class="text-sm font-bold text-white font-num">{{ fmt(asset.value_clp) }}</p>
              <p class="text-xs font-num" :class="asset.change_24h >= 0 ? 'text-emerald-400' : 'text-red-400'">
                {{ asset.change_24h >= 0 ? '▲' : '▼' }} {{ Math.abs(asset.change_24h).toFixed(2) }}%
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Metas -->
      <div class="card p-0 overflow-hidden">
        <div class="px-5 py-4 border-b border-white/5 flex items-center justify-between">
          <h2 class="text-sm font-semibold text-white">Metas financieras</h2>
          <RouterLink to="/goals" class="text-xs text-blue-400 hover:text-blue-300">Ver todo →</RouterLink>
        </div>
        <div class="divide-y divide-white/5">
          <div v-if="!goalsStore.active.length" class="px-5 py-8 text-center text-sm text-gray-600">
            Sin metas activas
          </div>
          <div v-for="goal in goalsStore.active.slice(0,4)" :key="goal.id" class="px-5 py-3">
            <div class="flex items-center justify-between mb-2">
              <span class="text-sm font-medium text-gray-200 flex items-center gap-1.5 truncate">
                {{ goal.icon }} {{ goal.name }}
              </span>
              <span class="text-xs font-bold ml-2 flex-shrink-0"
                :class="goal.status === 'on_track' ? 'text-emerald-400' : 'text-red-400'">
                {{ goal.progress_pct.toFixed(0) }}%
              </span>
            </div>
            <div class="progress-bar h-1.5">
              <div class="progress-fill h-1.5"
                :style="{ width: Math.min(100, goal.progress_pct) + '%' }"
                :class="goal.status === 'on_track' ? 'bg-blue-500' : 'bg-red-500'"></div>
            </div>
            <p class="text-xs text-gray-600 mt-1 font-num">
              {{ fmt(goal.current_amount) }} / {{ fmt(goal.target_amount) }}
            </p>
          </div>
        </div>
      </div>

      <!-- Asesor IA + Gastos por categoría -->
      <div class="space-y-4">
        <!-- IA card -->
        <div class="card bg-gradient-to-br from-blue-900/30 to-indigo-900/20 border-blue-500/20 p-5">
          <div class="flex items-start gap-3">
            <div class="w-10 h-10 bg-blue-500/20 rounded-xl flex items-center justify-center text-xl flex-shrink-0">🤖</div>
            <div class="flex-1 min-w-0">
              <h3 class="text-sm font-bold text-white">Asesor IA</h3>
              <p class="text-xs text-gray-400 mt-0.5 mb-3">Consejos financieros personalizados basados en tus datos reales</p>
              <RouterLink to="/advisor" class="btn-primary text-xs py-2 w-full justify-center">
                💬 Abrir chat
              </RouterLink>
            </div>
          </div>
        </div>

        <!-- Top gastos -->
        <div class="card p-0 overflow-hidden">
          <div class="px-5 py-4 border-b border-white/5">
            <h2 class="text-sm font-semibold text-white">Top gastos del mes</h2>
          </div>
          <div class="divide-y divide-white/5">
            <div v-for="cat in txStore.breakdown.slice(0,4)" :key="cat.category"
              class="px-5 py-2.5 flex items-center justify-between">
              <div class="flex items-center gap-2">
                <span class="text-base">{{ catIcon(cat.category) }}</span>
                <span class="text-xs text-gray-400 capitalize">{{ cat.category.replace(/_/g,' ') }}</span>
              </div>
              <span class="text-xs font-semibold text-white font-num">{{ fmt(cat.amount) }}</span>
            </div>
            <div v-if="!txStore.breakdown.length" class="px-5 py-6 text-center text-xs text-gray-600">
              Sin gastos este mes
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { usePortfolioStore } from '@/stores/portfolio'
import { useTransactionsStore } from '@/stores/transactions'
import { useGoalsStore } from '@/stores/goals'
import { transactions as txApi } from '@/services/api'
import DonutChart from '@/components/charts/DonutChart.vue'
import BarChart from '@/components/charts/BarChart.vue'

const auth           = useAuthStore()
const portfolioStore = usePortfolioStore()
const txStore        = useTransactionsStore()
const goalsStore     = useGoalsStore()
const loading        = ref(false)
const history        = ref([])

const today = new Intl.DateTimeFormat('es-CL', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }).format(new Date())
const currentMonthLabel = new Intl.DateTimeFormat('es-CL', { month: 'long', year: 'numeric' }).format(new Date())

const fmt        = (v) => new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP', maximumFractionDigits: 0 }).format(v ?? 0)
const fmtUSD     = (v) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', maximumFractionDigits: 0 }).format(v ?? 0)
const fmtCompact = (v) => new Intl.NumberFormat('es-CL', { notation: 'compact', style: 'currency', currency: 'CLP', maximumFractionDigits: 1 }).format(v ?? 0)

const typeIcon  = (t) => ({ stock:'📈', crypto:'🪙', uf:'🇨🇱', fund:'🏦', cash:'💵' })[t] ?? '💰'
const typeLabel = (t) => ({ stock:'Acciones', crypto:'Cripto', uf:'UF', fund:'Fondos', cash:'Efectivo' })[t] ?? t
const typeColor = (t) => ({ stock:'#3b82f6', crypto:'#f59e0b', uf:'#8b5cf6', fund:'#10b981', cash:'#6b7280' })[t] ?? '#6b7280'
const catIcon   = (c) => ({ sueldo:'💼', freelance:'💻', inversiones:'📈', alimentacion:'🍽️', transporte:'🚗', vivienda:'🏠', salud:'⚕️', educacion:'📚', entretenimiento:'🎬', tecnologia:'📱', servicios_basicos:'💡', seguros:'🛡️', ropa:'👕', viajes:'✈️' })[c] ?? '💰'

const barData = computed(() => {
  if (!history.value.length) return { labels: [], datasets: [] }
  const monthNames = history.value.map(h =>
    new Intl.DateTimeFormat('es-CL', { month: 'short' }).format(new Date(h.year, h.month - 1))
  )
  return {
    labels: monthNames,
    datasets: [
      {
        label: 'Ingresos',
        data: history.value.map(h => h.income),
        backgroundColor: '#10b98133',
        borderColor: '#10b981',
        borderWidth: 1.5,
        borderRadius: 6,
      },
      {
        label: 'Gastos',
        data: history.value.map(h => h.expenses),
        backgroundColor: '#ef444433',
        borderColor: '#ef4444',
        borderWidth: 1.5,
        borderRadius: 6,
      },
    ],
  }
})

async function refreshAll() {
  loading.value = true
  const now = new Date()
  const [hist] = await Promise.all([
    txApi.history(6),
    portfolioStore.fetchSummary(),
    txStore.fetchSummary(now.getFullYear(), now.getMonth() + 1),
    txStore.fetchBreakdown(now.getFullYear(), now.getMonth() + 1),
    goalsStore.fetchAll(),
  ])
  history.value = hist.data.history
  loading.value = false
}

onMounted(refreshAll)
</script>
