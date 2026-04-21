<template>
  <div class="space-y-5 max-w-5xl">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-bold text-white">Metas financieras</h1>
        <p class="text-xs text-gray-500 mt-0.5">{{ store.active.length }} activa{{ store.active.length !== 1 ? 's' : '' }} · {{ store.completed.length }} completada{{ store.completed.length !== 1 ? 's' : '' }}</p>
      </div>
      <button @click="showModal = true" class="btn-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Nueva meta
      </button>
    </div>

    <!-- Metas activas -->
    <div v-if="store.active.length" class="grid grid-cols-2 gap-4">
      <div v-for="goal in store.active" :key="goal.id" class="card-hover group">
        <!-- Header de la meta -->
        <div class="flex items-start justify-between mb-4">
          <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-2xl flex items-center justify-center text-2xl flex-shrink-0"
              :class="goal.status === 'on_track' ? 'bg-blue-500/15' : 'bg-red-500/15'">
              {{ goal.icon }}
            </div>
            <div>
              <h3 class="font-bold text-white text-sm">{{ goal.name }}</h3>
              <span class="badge text-[10px] mt-0.5"
                :class="goal.status === 'on_track' ? 'badge-green' : 'badge-red'">
                {{ goal.status === 'on_track' ? '✓ En camino' : '⚠ Atrasada' }}
              </span>
            </div>
          </div>
          <button @click="store.remove(goal.id)"
            class="btn-icon text-gray-700 hover:text-red-400 opacity-0 group-hover:opacity-100 transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
          </button>
        </div>

        <!-- Montos y progreso -->
        <div class="mb-3">
          <div class="flex items-end justify-between mb-2">
            <div>
              <p class="text-[10px] text-gray-600 uppercase tracking-wider mb-0.5">Acumulado</p>
              <p class="text-lg font-bold text-white font-num">{{ fmt(goal.current_amount) }}</p>
            </div>
            <div class="text-right">
              <p class="text-[10px] text-gray-600 uppercase tracking-wider mb-0.5">Objetivo</p>
              <p class="text-sm font-semibold text-gray-400 font-num">{{ fmt(goal.target_amount) }}</p>
            </div>
          </div>
          <!-- Barra de progreso -->
          <div class="progress-bar h-2.5 rounded-full">
            <div class="progress-fill h-2.5 rounded-full relative"
              :class="goal.status === 'on_track' ? 'bg-gradient-to-r from-blue-600 to-blue-400' : 'bg-gradient-to-r from-red-600 to-red-400'"
              :style="{ width: Math.min(100, goal.progress_pct) + '%' }">
              <!-- Indicador de punta -->
              <div class="absolute right-0 top-1/2 -translate-y-1/2 w-3.5 h-3.5 rounded-full bg-white shadow-md border-2"
                :class="goal.status === 'on_track' ? 'border-blue-500' : 'border-red-500'"></div>
            </div>
          </div>
          <div class="flex justify-between text-[10px] text-gray-600 mt-1.5">
            <span>0%</span>
            <span class="font-bold" :class="goal.status === 'on_track' ? 'text-blue-400' : 'text-red-400'">
              {{ goal.progress_pct.toFixed(1) }}%
            </span>
            <span>100%</span>
          </div>
        </div>

        <!-- Stats grid -->
        <div class="grid grid-cols-3 gap-2 mt-3">
          <div class="bg-white/[0.03] rounded-xl p-2.5 text-center border border-white/5">
            <p class="text-[10px] text-gray-600 mb-0.5">Faltan</p>
            <p class="text-xs font-bold text-white font-num">{{ fmt(goal.remaining_amount) }}</p>
          </div>
          <div class="bg-white/[0.03] rounded-xl p-2.5 text-center border border-white/5">
            <p class="text-[10px] text-gray-600 mb-0.5">Ahorro/mes</p>
            <p class="text-xs font-bold text-white font-num">{{ fmt(goal.monthly_savings_needed) }}</p>
          </div>
          <div class="bg-white/[0.03] rounded-xl p-2.5 text-center border border-white/5">
            <p class="text-[10px] text-gray-600 mb-0.5">Meses rest.</p>
            <p class="text-xs font-bold text-white">{{ goal.months_remaining }}</p>
          </div>
        </div>

        <!-- Fecha y botón aporte -->
        <div class="flex items-center justify-between mt-4 pt-4 border-t border-white/5">
          <p class="text-xs text-gray-600">
            📅 {{ formatDate(goal.target_date) }}
          </p>
          <button @click="openContribute(goal)" class="btn-secondary text-xs py-1.5 px-3">
            + Registrar aporte
          </button>
        </div>
      </div>
    </div>

    <!-- Estado vacío -->
    <div v-else-if="!store.loading" class="card text-center py-16">
      <p class="text-5xl mb-4">🎯</p>
      <h3 class="font-semibold text-white mb-1">Sin metas activas</h3>
      <p class="text-sm text-gray-500 mb-5">Crea tu primera meta financiera y comienza a ahorrar con propósito</p>
      <button @click="showModal = true" class="btn-primary mx-auto">Crear mi primera meta</button>
    </div>

    <!-- Metas completadas -->
    <div v-if="store.completed.length">
      <h2 class="text-xs font-semibold text-gray-600 uppercase tracking-wider mb-3">Completadas ✓</h2>
      <div class="grid grid-cols-3 gap-3">
        <div v-for="goal in store.completed" :key="goal.id"
          class="card-sm flex items-center gap-3 opacity-50">
          <span class="text-2xl flex-shrink-0">{{ goal.icon }}</span>
          <div class="min-w-0">
            <p class="text-sm font-semibold text-white truncate">{{ goal.name }}</p>
            <p class="text-xs text-emerald-400">✓ {{ fmt(goal.target_amount) }} — completada</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal nueva meta -->
    <div v-if="showModal" class="modal-backdrop" @click.self="showModal = false">
      <div class="modal">
        <div class="flex items-center justify-between mb-5">
          <h3 class="font-bold text-white">Nueva meta financiera</h3>
          <button @click="showModal = false" class="btn-icon text-gray-500">✕</button>
        </div>
        <form @submit.prevent="handleCreate" class="space-y-4">
          <div class="grid grid-cols-3 gap-3">
            <div>
              <label class="label">Ícono</label>
              <input v-model="form.icon" class="input text-center text-xl" placeholder="🎯" maxlength="4" />
            </div>
            <div class="col-span-2">
              <label class="label">Nombre de la meta</label>
              <input v-model="form.name" class="input" placeholder="Ej: Casa propia, Fondo de emergencia..." required />
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="label">Monto objetivo (CLP)</label>
              <input v-model.number="form.target_amount" type="number" class="input" placeholder="5.000.000" required />
            </div>
            <div>
              <label class="label">Ya tengo ahorrado (CLP)</label>
              <input v-model.number="form.current_amount" type="number" class="input" placeholder="0" />
            </div>
          </div>
          <div>
            <label class="label">Fecha objetivo</label>
            <input v-model="form.target_date" type="date" class="input" :min="tomorrow" required />
          </div>
          <!-- Preview del ahorro necesario -->
          <div v-if="previewSavings > 0" class="bg-blue-500/10 border border-blue-500/20 rounded-xl p-3 text-sm">
            <p class="text-gray-400">Necesitarás ahorrar aproximadamente:</p>
            <p class="text-blue-400 font-bold font-num text-base mt-0.5">{{ fmt(previewSavings) }} / mes</p>
          </div>
          <div class="flex gap-3 pt-1">
            <button type="button" @click="showModal = false" class="btn-secondary flex-1">Cancelar</button>
            <button type="submit" class="btn-primary flex-1" :disabled="saving">
              {{ saving ? 'Creando...' : 'Crear meta' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal aporte -->
    <div v-if="contributeGoal" class="modal-backdrop" @click.self="contributeGoal = null">
      <div class="modal max-w-sm">
        <div class="flex items-center justify-between mb-5">
          <h3 class="font-bold text-white">
            Aporte a {{ contributeGoal.icon }} {{ contributeGoal.name }}
          </h3>
          <button @click="contributeGoal = null" class="btn-icon text-gray-500">✕</button>
        </div>
        <div class="mb-4">
          <div class="progress-bar h-2 mb-1">
            <div class="progress-fill h-2 bg-blue-500"
              :style="{ width: contributeGoal.progress_pct + '%' }"></div>
          </div>
          <p class="text-xs text-gray-500 font-num">
            {{ fmt(contributeGoal.current_amount) }} / {{ fmt(contributeGoal.target_amount) }}
          </p>
        </div>
        <div class="space-y-3">
          <div>
            <label class="label">Monto del aporte (CLP)</label>
            <input v-model.number="contributeAmount" type="number" class="input" placeholder="100.000" autofocus />
          </div>
          <!-- Sugerencias rápidas -->
          <div class="flex gap-2 flex-wrap">
            <button v-for="amt in [50000, 100000, 200000, 500000]" :key="amt"
              type="button"
              @click="contributeAmount = amt"
              class="btn-secondary text-xs py-1.5 px-2.5">
              {{ fmt(amt) }}
            </button>
          </div>
          <div class="flex gap-3">
            <button @click="contributeGoal = null" class="btn-secondary flex-1">Cancelar</button>
            <button @click="handleContribute" class="btn-primary flex-1" :disabled="!contributeAmount">
              Registrar aporte
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useGoalsStore } from '@/stores/goals'

const store    = useGoalsStore()
const showModal = ref(false)
const saving    = ref(false)
const tomorrow  = new Date(Date.now() + 86400000).toISOString().slice(0, 10)

const form = ref({ icon: '🎯', name: '', target_amount: null, current_amount: 0, target_date: '', currency: 'CLP' })

const contributeGoal   = ref(null)
const contributeAmount = ref(null)

const fmt = (v) => new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP', maximumFractionDigits: 0 }).format(v ?? 0)
const formatDate = (d) => new Date(d).toLocaleDateString('es-CL', { year: 'numeric', month: 'long', day: 'numeric' })

const previewSavings = computed(() => {
  if (!form.value.target_amount || !form.value.target_date) return 0
  const months = Math.max(1, Math.round((new Date(form.value.target_date) - new Date()) / (1000 * 60 * 60 * 24 * 30)))
  const remaining = (form.value.target_amount || 0) - (form.value.current_amount || 0)
  return remaining > 0 ? remaining / months : 0
})

async function handleCreate() {
  saving.value = true
  try {
    await store.create(form.value)
    showModal.value = false
    form.value = { icon: '🎯', name: '', target_amount: null, current_amount: 0, target_date: '', currency: 'CLP' }
  } finally {
    saving.value = false
  }
}

function openContribute(goal) {
  contributeGoal.value   = goal
  contributeAmount.value = null
}

async function handleContribute() {
  if (!contributeAmount.value) return
  await store.contribute(contributeGoal.value.id, contributeAmount.value)
  contributeGoal.value = null
}

onMounted(() => store.fetchAll())
</script>
