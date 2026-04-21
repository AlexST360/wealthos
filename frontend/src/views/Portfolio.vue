<template>
  <div class="space-y-5 max-w-7xl">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-bold text-white">Portafolio de inversiones</h1>
        <p class="text-xs text-gray-500 mt-0.5">Precios actualizados · caché 15 min</p>
      </div>
      <button @click="showAddModal = true" class="btn-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Agregar activo
      </button>
    </div>

    <!-- KPIs -->
    <div class="grid grid-cols-4 gap-3">
      <div class="stat-card">
        <span class="stat-label">Valor total</span>
        <span class="stat-value text-blue-400 font-num">{{ fmt(store.totalCLP) }}</span>
        <span class="stat-sub font-num">{{ fmtUSD(store.totalUSD) }}</span>
      </div>
      <div class="stat-card">
        <span class="stat-label">Ganancia / Pérdida</span>
        <span class="stat-value font-num" :class="totalPL >= 0 ? 'text-emerald-400' : 'text-red-400'">
          {{ totalPL >= 0 ? '+' : '' }}{{ fmt(totalPL) }}
        </span>
        <span class="font-num" :class="totalPLPct >= 0 ? 'stat-pos' : 'stat-neg'">
          {{ totalPLPct >= 0 ? '+' : '' }}{{ totalPLPct.toFixed(2) }}% total
        </span>
      </div>
      <div class="stat-card">
        <span class="stat-label">Activos en cartera</span>
        <span class="stat-value">{{ store.assets.length }}</span>
        <span class="stat-sub">{{ store.byType.length }} tipos distintos</span>
      </div>
      <div class="stat-card">
        <span class="stat-label">Cambio hoy (promedio)</span>
        <span class="stat-value font-num" :class="avgChange24h >= 0 ? 'text-emerald-400' : 'text-red-400'">
          {{ avgChange24h >= 0 ? '+' : '' }}{{ avgChange24h.toFixed(2) }}%
        </span>
        <span class="stat-sub">Variación 24h ponderada</span>
      </div>
    </div>

    <!-- Tabla de activos -->
    <div class="card p-0 overflow-hidden">
      <div class="px-5 py-4 border-b border-white/5 flex items-center gap-3">
        <h2 class="text-sm font-semibold text-white">Posiciones abiertas</h2>
        <div v-if="store.loading" class="flex items-center gap-1.5 text-xs text-gray-600">
          <span class="w-1.5 h-1.5 bg-blue-500 rounded-full animate-pulse"></span>
          Actualizando precios...
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="border-b border-white/5">
            <tr>
              <th class="table-header text-left">Activo</th>
              <th class="table-header">Cantidad</th>
              <th class="table-header">P. Compra</th>
              <th class="table-header">P. Actual</th>
              <th class="table-header">Valor (CLP)</th>
              <th class="table-header">Dist.</th>
              <th class="table-header">G/P</th>
              <th class="table-header">24h</th>
              <th class="table-header"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="asset in store.assets" :key="asset.id" class="table-row">
              <!-- Activo -->
              <td class="table-cell">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-lg flex items-center justify-center text-base flex-shrink-0"
                    :style="{ background: typeColor(asset.type) + '20' }">
                    {{ typeIcon(asset.type) }}
                  </div>
                  <div>
                    <p class="font-bold text-white text-sm">{{ asset.ticker }}</p>
                    <p class="text-xs text-gray-600 truncate max-w-28">{{ asset.name }}</p>
                  </div>
                </div>
              </td>
              <!-- Cantidad -->
              <td class="table-cell text-right font-num text-gray-400">
                {{ formatQty(asset.quantity) }}
              </td>
              <!-- P. Compra -->
              <td class="table-cell text-right font-num text-gray-600 text-xs">
                {{ asset.currency }} {{ fmtNum(asset.avg_buy_price) }}
              </td>
              <!-- P. Actual -->
              <td class="table-cell text-right font-num text-gray-300 text-xs">
                {{ asset.currency }} {{ fmtNum(asset.current_price) }}
              </td>
              <!-- Valor CLP -->
              <td class="table-cell text-right font-bold text-white font-num">
                {{ fmt(asset.value_clp) }}
              </td>
              <!-- Distribución -->
              <td class="table-cell text-right">
                <div class="flex items-center justify-end gap-2">
                  <div class="progress-bar w-12 h-1">
                    <div class="progress-fill h-1" :style="{ width: asset.pct + '%', background: typeColor(asset.type) }"></div>
                  </div>
                  <span class="text-xs text-gray-500 font-num w-8 text-right">{{ asset.pct?.toFixed(1) }}%</span>
                </div>
              </td>
              <!-- G/P -->
              <td class="table-cell text-right">
                <div>
                  <p class="text-xs font-bold font-num" :class="asset.profit_loss >= 0 ? 'text-emerald-400' : 'text-red-400'">
                    {{ asset.profit_loss >= 0 ? '+' : '' }}{{ asset.profit_loss_pct?.toFixed(2) }}%
                  </p>
                  <p class="text-[10px] text-gray-600 font-num">
                    {{ asset.profit_loss >= 0 ? '+' : '' }}{{ fmt(asset.profit_loss) }}
                  </p>
                </div>
              </td>
              <!-- 24h -->
              <td class="table-cell text-right">
                <span class="badge font-num text-xs"
                  :class="asset.change_24h >= 0 ? 'badge-green' : 'badge-red'">
                  {{ asset.change_24h >= 0 ? '▲' : '▼' }}
                  {{ Math.abs(asset.change_24h).toFixed(2) }}%
                </span>
              </td>
              <!-- Acciones -->
              <td class="table-cell">
                <button @click="confirmDelete(asset)" class="btn-icon text-gray-700 hover:text-red-400">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                  </svg>
                </button>
              </td>
            </tr>
            <tr v-if="!store.assets.length">
              <td colspan="9" class="text-center py-16 text-gray-600 text-sm">
                <p class="text-3xl mb-3">💼</p>
                <p>No tienes activos en tu portafolio.</p>
                <button @click="showAddModal = true" class="btn-primary mt-4 text-xs">+ Agregar primer activo</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal agregar activo -->
    <div v-if="showAddModal" class="modal-backdrop" @click.self="showAddModal = false">
      <div class="modal">
        <div class="flex items-center justify-between mb-5">
          <h3 class="font-bold text-white">Agregar activo</h3>
          <button @click="showAddModal = false" class="btn-icon text-gray-500">✕</button>
        </div>
        <form @submit.prevent="handleAddAsset" class="space-y-4">
          <div>
            <label class="label">Tipo de activo</label>
            <div class="grid grid-cols-5 gap-1.5">
              <button v-for="t in assetTypes" :key="t.value" type="button"
                @click="newAsset.type = t.value; newAsset.currency = t.currency"
                class="flex flex-col items-center gap-1 py-2 rounded-xl border text-xs font-medium transition-all"
                :class="newAsset.type === t.value
                  ? 'border-blue-500 bg-blue-500/15 text-blue-400'
                  : 'border-white/5 bg-white/5 text-gray-500 hover:border-white/10'">
                <span class="text-base">{{ t.icon }}</span>
                {{ t.label }}
              </button>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="label">Ticker / Símbolo</label>
              <input v-model="newAsset.ticker" class="input" :placeholder="tickerPlaceholder" />
            </div>
            <div>
              <label class="label">Nombre</label>
              <input v-model="newAsset.name" class="input" :placeholder="namePlaceholder" required />
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="label">Cantidad</label>
              <input v-model.number="newAsset.quantity" type="number" step="any" min="0" class="input" placeholder="0" required />
            </div>
            <div>
              <label class="label">Precio de compra ({{ newAsset.currency }})</label>
              <input v-model.number="newAsset.avg_buy_price" type="number" step="any" min="0" class="input" placeholder="0" required />
            </div>
          </div>
          <p v-if="addError" class="text-red-400 text-sm bg-red-500/10 px-3 py-2 rounded-lg">{{ addError }}</p>
          <div class="flex gap-3 pt-1">
            <button type="button" @click="showAddModal = false" class="btn-secondary flex-1">Cancelar</button>
            <button type="submit" class="btn-primary flex-1" :disabled="adding">
              {{ adding ? 'Agregando...' : 'Agregar al portafolio' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { usePortfolioStore } from '@/stores/portfolio'

const store        = usePortfolioStore()
const showAddModal = ref(false)
const adding       = ref(false)
const addError     = ref('')

const assetTypes = [
  { value: 'stock',  icon: '📈', label: 'Acción',  currency: 'USD' },
  { value: 'crypto', icon: '🪙', label: 'Cripto',  currency: 'USD' },
  { value: 'uf',     icon: '🇨🇱', label: 'UF',     currency: 'CLP' },
  { value: 'fund',   icon: '🏦', label: 'Fondo',   currency: 'CLP' },
  { value: 'cash',   icon: '💵', label: 'Efectivo', currency: 'CLP' },
]

const newAsset = ref({ type: 'stock', ticker: '', name: '', quantity: null, avg_buy_price: null, currency: 'USD' })

const tickerPlaceholder = computed(() => ({ stock:'AAPL', crypto:'BTC', uf:'UF', fund:'FMIV', cash:'CLP' })[newAsset.value.type])
const namePlaceholder   = computed(() => ({ stock:'Apple Inc.', crypto:'Bitcoin', uf:'Unidad de Fomento', fund:'Nombre fondo', cash:'Pesos Chilenos' })[newAsset.value.type])

const totalPL     = computed(() => store.assets.reduce((s, a) => s + (a.profit_loss ?? 0), 0))
const totalCost   = computed(() => store.assets.reduce((s, a) => s + (a.cost_clp ?? 0), 0))
const totalPLPct  = computed(() => totalCost.value > 0 ? (totalPL.value / totalCost.value) * 100 : 0)
const avgChange24h = computed(() => {
  if (!store.assets.length) return 0
  return store.assets.reduce((s, a) => s + (a.change_24h ?? 0), 0) / store.assets.length
})

const fmt    = (v) => new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP', maximumFractionDigits: 0 }).format(v ?? 0)
const fmtUSD = (v) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', maximumFractionDigits: 0 }).format(v ?? 0)
const fmtNum = (v) => (v ?? 0).toLocaleString('es-CL', { maximumFractionDigits: 2 })
const formatQty = (v) => v >= 1 ? v.toLocaleString('es-CL', { maximumFractionDigits: 2 }) : v.toLocaleString('es-CL', { maximumFractionDigits: 6 })

const typeIcon  = (t) => ({ stock:'📈', crypto:'🪙', uf:'🇨🇱', fund:'🏦', cash:'💵' })[t] ?? '💰'
const typeColor = (t) => ({ stock:'#3b82f6', crypto:'#f59e0b', uf:'#8b5cf6', fund:'#10b981', cash:'#6b7280' })[t] ?? '#6b7280'

async function handleAddAsset() {
  adding.value   = true
  addError.value = ''
  try {
    await store.addAsset(newAsset.value)
    showAddModal.value = false
    newAsset.value = { type: 'stock', ticker: '', name: '', quantity: null, avg_buy_price: null, currency: 'USD' }
  } catch (e) {
    addError.value = e.response?.data?.message ?? 'Error al agregar el activo'
  } finally {
    adding.value = false
  }
}

async function confirmDelete(asset) {
  if (!confirm(`¿Eliminar ${asset.name} (${asset.ticker}) del portafolio?`)) return
  await store.deleteAsset(asset.id)
}

onMounted(() => store.fetchSummary())
</script>
