<template>
  <div class="space-y-5 max-w-6xl">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-bold text-white">Finanzas compartidas</h1>
        <p class="text-xs text-gray-500 mt-0.5">Espacio colaborativo para parejas o familia</p>
      </div>
      <button @click="showCreateModal = true" class="btn-primary" v-if="!currentSpace">
        + Crear espacio compartido
      </button>
    </div>

    <!-- Sin espacios -->
    <div v-if="!spaces.length && !loading" class="card text-center py-16">
      <p class="text-5xl mb-4">👫</p>
      <h3 class="font-bold text-white text-lg mb-2">Sin espacios compartidos</h3>
      <p class="text-gray-500 text-sm max-w-sm mx-auto mb-6">
        Crea un espacio compartido con tu pareja o familia para gestionar juntos gastos, metas e ingresos en común.
      </p>
      <button @click="showCreateModal = true" class="btn-primary mx-auto">
        👫 Crear espacio compartido
      </button>
    </div>

    <!-- Selector de espacio (si hay más de uno) -->
    <div v-if="spaces.length > 1" class="flex gap-2 flex-wrap">
      <button v-for="s in spaces" :key="s.id"
        @click="selectSpace(s)"
        class="flex items-center gap-2 px-4 py-2 rounded-xl border text-sm font-medium transition-all"
        :class="currentSpace?.id === s.id
          ? 'border-blue-500/50 bg-blue-500/10 text-blue-400'
          : 'border-white/5 bg-white/5 text-gray-400 hover:border-white/10'">
        <span>{{ s.icon }}</span>
        {{ s.name }}
      </button>
    </div>

    <!-- Espacio activo -->
    <template v-if="currentSpace">
      <!-- Info del espacio + miembros -->
      <div class="card">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center text-2xl">
              {{ currentSpace.icon }}
            </div>
            <div>
              <h2 class="font-bold text-white text-base">{{ currentSpace.name }}</h2>
              <div class="flex items-center gap-2 mt-1">
                <div v-for="m in currentSpace.members" :key="m.id"
                  class="flex items-center gap-1.5 bg-white/5 rounded-full px-2.5 py-1">
                  <div class="w-5 h-5 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-[10px] font-bold text-white">
                    {{ m.name[0].toUpperCase() }}
                  </div>
                  <span class="text-xs text-gray-300">{{ m.name }}</span>
                  <span v-if="m.role === 'admin'" class="text-[10px] text-yellow-500">★</span>
                </div>
              </div>
            </div>
          </div>
          <!-- Acciones del espacio -->
          <div class="flex items-center gap-2">
            <button @click="showInviteModal = true" class="btn-secondary text-xs gap-1.5">
              ✉️ Invitar persona
            </button>
            <button v-if="currentSpace.is_admin" @click="confirmDeleteSpace"
              class="btn-danger text-xs">
              Eliminar espacio
            </button>
            <button v-else @click="leaveSpace" class="btn-secondary text-xs text-red-400">
              Salir
            </button>
          </div>
        </div>
      </div>

      <!-- KPIs del mes -->
      <div class="grid grid-cols-4 gap-3">
        <div class="stat-card">
          <span class="stat-label">Ingresos compartidos</span>
          <span class="stat-value text-emerald-400 font-num">{{ fmt(summary.income) }}</span>
        </div>
        <div class="stat-card">
          <span class="stat-label">Gastos compartidos</span>
          <span class="stat-value text-red-400 font-num">{{ fmt(summary.expenses) }}</span>
        </div>
        <div class="stat-card">
          <span class="stat-label">Ahorro compartido</span>
          <span class="stat-value font-num" :class="summary.savings >= 0 ? 'text-emerald-400' : 'text-red-400'">
            {{ fmt(summary.savings) }}
          </span>
        </div>
        <div class="stat-card">
          <span class="stat-label">Tasa de ahorro</span>
          <span class="stat-value font-num" :class="summary.savings_rate >= 20 ? 'text-emerald-400' : 'text-yellow-400'">
            {{ summary.savings_rate?.toFixed(1) ?? '0.0' }}%
          </span>
        </div>
      </div>

      <!-- Tabs: Transacciones / Metas -->
      <div class="flex border-b border-white/5 gap-1">
        <button v-for="t in ['Transacciones', 'Metas']" :key="t"
          @click="activeTab = t"
          class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors -mb-px"
          :class="activeTab === t
            ? 'border-blue-500 text-blue-400'
            : 'border-transparent text-gray-500 hover:text-gray-300'">
          {{ t }}
        </button>
        <button @click="showTxModal = true" v-if="activeTab === 'Transacciones'" class="ml-auto btn-primary text-xs py-1.5 mb-1">
          + Nueva transacción
        </button>
        <button @click="showGoalModal = true" v-if="activeTab === 'Metas'" class="ml-auto btn-primary text-xs py-1.5 mb-1">
          + Nueva meta
        </button>
      </div>

      <!-- Tab Transacciones -->
      <div v-if="activeTab === 'Transacciones'">
        <div class="card p-0 overflow-hidden">
          <div class="divide-y divide-white/5">
            <div v-for="tx in transactions" :key="tx.id"
              class="flex items-center gap-4 px-5 py-3 hover:bg-white/[0.02] group transition-colors">
              <div class="w-9 h-9 rounded-xl flex items-center justify-center text-lg flex-shrink-0"
                :class="tx.type === 'income' ? 'bg-emerald-500/15' : 'bg-red-500/15'">
                {{ catIcon(tx.category) }}
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-200 truncate">{{ tx.description || tx.category.replace(/_/g,' ') }}</p>
                <p class="text-xs text-gray-600">
                  {{ tx.author?.name }} · {{ catFmt(tx.date) }}
                </p>
              </div>
              <span class="font-bold font-num text-sm"
                :class="tx.type === 'income' ? 'text-emerald-400' : 'text-red-400'">
                {{ tx.type === 'income' ? '+' : '−' }}{{ fmt(tx.amount) }}
              </span>
              <button @click="deleteTransaction(tx)"
                class="opacity-0 group-hover:opacity-100 btn-icon text-gray-700 hover:text-red-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
              </button>
            </div>
            <div v-if="!transactions.length" class="text-center py-12 text-sm text-gray-600">
              Sin transacciones este mes. ¡Registra la primera!
            </div>
          </div>
        </div>
      </div>

      <!-- Tab Metas -->
      <div v-if="activeTab === 'Metas'">
        <div v-if="goals.length" class="grid grid-cols-2 gap-4">
          <div v-for="goal in goals" :key="goal.id" class="card-hover">
            <div class="flex items-center gap-3 mb-4">
              <div class="w-10 h-10 rounded-2xl bg-white/5 flex items-center justify-center text-xl">{{ goal.icon }}</div>
              <div>
                <h3 class="font-bold text-white text-sm">{{ goal.name }}</h3>
                <p class="text-xs text-gray-600">Creada por {{ goal.creator }}</p>
              </div>
            </div>
            <div class="mb-3">
              <div class="flex justify-between text-sm mb-1.5">
                <span class="text-gray-400 font-num">{{ fmt(goal.current_amount) }}</span>
                <span class="font-bold" :class="goal.status === 'on_track' ? 'text-blue-400' : 'text-red-400'">
                  {{ goal.progress_pct.toFixed(0) }}%
                </span>
                <span class="text-gray-400 font-num">{{ fmt(goal.target_amount) }}</span>
              </div>
              <div class="progress-bar h-2">
                <div class="progress-fill h-2"
                  :class="goal.status === 'on_track' ? 'bg-gradient-to-r from-blue-600 to-blue-400' : 'bg-red-500'"
                  :style="{ width: Math.min(100, goal.progress_pct) + '%' }"></div>
              </div>
            </div>
            <div class="grid grid-cols-2 gap-2 text-xs mb-3">
              <div class="bg-white/[0.03] rounded-lg p-2 text-center">
                <p class="text-gray-600">Ahorro/mes</p>
                <p class="font-bold text-white font-num">{{ fmt(goal.monthly_savings_needed) }}</p>
              </div>
              <div class="bg-white/[0.03] rounded-lg p-2 text-center">
                <p class="text-gray-600">Meses rest.</p>
                <p class="font-bold text-white">{{ goal.months_remaining }}</p>
              </div>
            </div>
            <button @click="openContributeGoal(goal)" class="btn-secondary w-full text-xs py-1.5">
              + Registrar aporte
            </button>
          </div>
        </div>
        <div v-else class="card text-center py-12 text-gray-600 text-sm">
          Sin metas compartidas. ¡Crea la primera!
        </div>
      </div>
    </template>

    <!-- Modal: Crear espacio -->
    <div v-if="showCreateModal" class="modal-backdrop" @click.self="showCreateModal = false">
      <div class="modal max-w-sm">
        <div class="flex items-center justify-between mb-5">
          <h3 class="font-bold text-white">Crear espacio compartido</h3>
          <button @click="showCreateModal = false" class="btn-icon text-gray-500">✕</button>
        </div>
        <form @submit.prevent="handleCreate" class="space-y-4">
          <div class="grid grid-cols-3 gap-3">
            <div>
              <label class="label">Ícono</label>
              <input v-model="createForm.icon" class="input text-center text-xl" placeholder="👫" maxlength="4" />
            </div>
            <div class="col-span-2">
              <label class="label">Nombre</label>
              <input v-model="createForm.name" class="input" placeholder="Ej: Finanzas de pareja" required />
            </div>
          </div>
          <div class="flex gap-3 pt-1">
            <button type="button" @click="showCreateModal = false" class="btn-secondary flex-1">Cancelar</button>
            <button type="submit" class="btn-primary flex-1">Crear espacio</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal: Invitar -->
    <div v-if="showInviteModal" class="modal-backdrop" @click.self="showInviteModal = false">
      <div class="modal max-w-sm">
        <div class="flex items-center justify-between mb-5">
          <h3 class="font-bold text-white">Invitar persona</h3>
          <button @click="showInviteModal = false" class="btn-icon text-gray-500">✕</button>
        </div>
        <div class="space-y-4">
          <p class="text-xs text-gray-500">Si la persona ya tiene cuenta en WealthOS, se unirá automáticamente. Si no, podrá unirse al registrarse.</p>
          <div>
            <label class="label">Email</label>
            <input v-model="inviteEmail" type="email" class="input" placeholder="pareja@email.com" />
          </div>
          <div v-if="inviteMsg" class="bg-emerald-500/10 border border-emerald-500/20 rounded-xl px-4 py-3 text-sm text-emerald-400">
            {{ inviteMsg }}
          </div>
          <div class="flex gap-3">
            <button @click="showInviteModal = false" class="btn-secondary flex-1">Cerrar</button>
            <button @click="handleInvite" class="btn-primary flex-1" :disabled="!inviteEmail">Invitar</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal: Nueva transacción compartida -->
    <div v-if="showTxModal" class="modal-backdrop" @click.self="showTxModal = false">
      <div class="modal max-w-sm">
        <div class="flex items-center justify-between mb-5">
          <h3 class="font-bold text-white">Nueva transacción compartida</h3>
          <button @click="showTxModal = false" class="btn-icon text-gray-500">✕</button>
        </div>
        <form @submit.prevent="handleAddTransaction" class="space-y-4">
          <div class="grid grid-cols-2 gap-2">
            <button type="button" @click="txForm.type = 'income'"
              class="py-2.5 rounded-xl text-sm font-semibold border transition-all"
              :class="txForm.type === 'income' ? 'bg-emerald-500/20 border-emerald-500/50 text-emerald-400' : 'border-white/5 bg-white/5 text-gray-500'">
              ↑ Ingreso
            </button>
            <button type="button" @click="txForm.type = 'expense'"
              class="py-2.5 rounded-xl text-sm font-semibold border transition-all"
              :class="txForm.type === 'expense' ? 'bg-red-500/20 border-red-500/50 text-red-400' : 'border-white/5 bg-white/5 text-gray-500'">
              ↓ Gasto
            </button>
          </div>
          <div>
            <label class="label">Monto (CLP)</label>
            <input v-model.number="txForm.amount" type="number" class="input" placeholder="0" required />
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="label">Categoría</label>
              <select v-model="txForm.category" class="input" required>
                <option value="" disabled>Seleccionar</option>
                <option v-for="c in txCategories[txForm.type]" :key="c" :value="c">{{ c.replace(/_/g,' ') }}</option>
              </select>
            </div>
            <div>
              <label class="label">Fecha</label>
              <input v-model="txForm.date" type="date" class="input" :max="today" required />
            </div>
          </div>
          <div>
            <label class="label">Descripción</label>
            <input v-model="txForm.description" class="input" placeholder="Opcional..." />
          </div>
          <div class="flex gap-3">
            <button type="button" @click="showTxModal = false" class="btn-secondary flex-1">Cancelar</button>
            <button type="submit" class="btn-primary flex-1">Guardar</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal: Nueva meta compartida -->
    <div v-if="showGoalModal" class="modal-backdrop" @click.self="showGoalModal = false">
      <div class="modal max-w-sm">
        <div class="flex items-center justify-between mb-5">
          <h3 class="font-bold text-white">Nueva meta compartida</h3>
          <button @click="showGoalModal = false" class="btn-icon text-gray-500">✕</button>
        </div>
        <form @submit.prevent="handleAddGoal" class="space-y-4">
          <div class="grid grid-cols-3 gap-3">
            <div>
              <label class="label">Ícono</label>
              <input v-model="goalForm.icon" class="input text-center text-xl" placeholder="🏠" maxlength="4" />
            </div>
            <div class="col-span-2">
              <label class="label">Nombre</label>
              <input v-model="goalForm.name" class="input" placeholder="Depto propio..." required />
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="label">Monto objetivo</label>
              <input v-model.number="goalForm.target_amount" type="number" class="input" required />
            </div>
            <div>
              <label class="label">Ya ahorrado</label>
              <input v-model.number="goalForm.current_amount" type="number" class="input" />
            </div>
          </div>
          <div>
            <label class="label">Fecha objetivo</label>
            <input v-model="goalForm.target_date" type="date" class="input" :min="tomorrow" required />
          </div>
          <div class="flex gap-3">
            <button type="button" @click="showGoalModal = false" class="btn-secondary flex-1">Cancelar</button>
            <button type="submit" class="btn-primary flex-1">Crear meta</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal: Aporte a meta compartida -->
    <div v-if="contributeGoal" class="modal-backdrop" @click.self="contributeGoal = null">
      <div class="modal max-w-sm">
        <h3 class="font-bold text-white mb-4">Aporte a {{ contributeGoal.icon }} {{ contributeGoal.name }}</h3>
        <div class="space-y-3">
          <div>
            <label class="label">Monto (CLP)</label>
            <input v-model.number="contributeAmount" type="number" class="input" placeholder="100.000" />
          </div>
          <div class="flex gap-2 flex-wrap">
            <button v-for="a in [50000,100000,200000,500000]" :key="a"
              @click="contributeAmount = a" class="btn-secondary text-xs py-1.5 px-2.5">
              {{ fmt(a) }}
            </button>
          </div>
          <div class="flex gap-3">
            <button @click="contributeGoal = null" class="btn-secondary flex-1">Cancelar</button>
            <button @click="handleContribute" class="btn-primary flex-1">Registrar</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { shared as sharedApi, transactions as txApi } from '@/services/api'

const spaces       = ref([])
const currentSpace = ref(null)
const transactions = ref([])
const goals        = ref([])
const summary      = ref({ income: 0, expenses: 0, savings: 0, savings_rate: 0 })
const loading      = ref(false)
const activeTab    = ref('Transacciones')

const showCreateModal = ref(false)
const showInviteModal = ref(false)
const showTxModal     = ref(false)
const showGoalModal   = ref(false)
const contributeGoal  = ref(null)
const contributeAmount = ref(null)

const inviteEmail = ref('')
const inviteMsg   = ref('')
const today       = new Date().toISOString().slice(0, 10)
const tomorrow    = new Date(Date.now() + 86400000).toISOString().slice(0, 10)

const createForm = ref({ icon: '👫', name: '', currency: 'CLP' })
const txForm     = ref({ type: 'expense', amount: null, category: '', description: '', date: today })
const goalForm   = ref({ icon: '🎯', name: '', target_amount: null, current_amount: 0, target_date: '' })

const txCategories = {
  income:  ['sueldo', 'freelance', 'inversiones', 'arriendo', 'otros_ingresos'],
  expense: ['alimentacion', 'transporte', 'vivienda', 'salud', 'educacion', 'entretenimiento', 'tecnologia', 'servicios_basicos', 'seguros', 'ropa', 'viajes', 'otros_gastos'],
}

const fmt     = (v) => new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP', maximumFractionDigits: 0 }).format(v ?? 0)
const catFmt  = (d) => new Date(d + 'T12:00:00').toLocaleDateString('es-CL', { day: 'numeric', month: 'short' })
const catIcon = (c) => ({ sueldo:'💼',freelance:'💻',inversiones:'📈',alimentacion:'🍽️',transporte:'🚗',vivienda:'🏠',salud:'⚕️',educacion:'📚',entretenimiento:'🎬',tecnologia:'📱',servicios_basicos:'💡',seguros:'🛡️',ropa:'👕',viajes:'✈️' })[c] ?? '💰'

async function loadSpaces() {
  loading.value = true
  const { data } = await sharedApi.list()
  spaces.value = data.spaces
  if (spaces.value.length) await selectSpace(spaces.value[0])
  loading.value = false
}

async function selectSpace(space) {
  currentSpace.value = space
  await Promise.all([loadTransactions(), loadGoals()])
}

async function loadTransactions() {
  const now = new Date()
  const { data } = await sharedApi.getTransactions(currentSpace.value.id, { year: now.getFullYear(), month: now.getMonth() + 1 })
  transactions.value = data.transactions
  summary.value      = data.summary
}

async function loadGoals() {
  const { data } = await sharedApi.getGoals(currentSpace.value.id)
  goals.value = data.goals
}

async function handleCreate() {
  const { data } = await sharedApi.create(createForm.value)
  spaces.value.push(data.space)
  await selectSpace(data.space)
  showCreateModal.value = false
  createForm.value = { icon: '👫', name: '', currency: 'CLP' }
}

async function handleInvite() {
  const { data } = await sharedApi.invite(currentSpace.value.id, inviteEmail.value)
  inviteMsg.value = data.message
  if (data.auto_accepted) {
    const { data: sd } = await sharedApi.get(currentSpace.value.id)
    currentSpace.value = sd.space
    spaces.value = spaces.value.map(s => s.id === sd.space.id ? sd.space : s)
  }
  inviteEmail.value = ''
}

async function handleAddTransaction() {
  await sharedApi.addTransaction(currentSpace.value.id, { ...txForm.value, currency: 'CLP' })
  showTxModal.value = false
  txForm.value = { type: 'expense', amount: null, category: '', description: '', date: today }
  await loadTransactions()
}

async function deleteTransaction(tx) {
  if (!confirm('¿Eliminar esta transacción?')) return
  await sharedApi.deleteTransaction(currentSpace.value.id, tx.id)
  await loadTransactions()
}

async function handleAddGoal() {
  await sharedApi.addGoal(currentSpace.value.id, goalForm.value)
  showGoalModal.value = false
  goalForm.value = { icon: '🎯', name: '', target_amount: null, current_amount: 0, target_date: '' }
  await loadGoals()
}

function openContributeGoal(goal) {
  contributeGoal.value   = goal
  contributeAmount.value = null
}

async function handleContribute() {
  await sharedApi.contributeGoal(currentSpace.value.id, contributeGoal.value.id, contributeAmount.value)
  contributeGoal.value = null
  await loadGoals()
}

async function leaveSpace() {
  if (!confirm('¿Salir de este espacio compartido?')) return
  await sharedApi.leave(currentSpace.value.id)
  spaces.value = spaces.value.filter(s => s.id !== currentSpace.value.id)
  currentSpace.value = spaces.value[0] ?? null
}

async function confirmDeleteSpace() {
  if (!confirm(`¿Eliminar el espacio "${currentSpace.value.name}"? Se borrarán todas las transacciones y metas.`)) return
  await sharedApi.destroy(currentSpace.value.id)
  spaces.value = spaces.value.filter(s => s.id !== currentSpace.value.id)
  currentSpace.value = spaces.value[0] ?? null
}

onMounted(loadSpaces)
</script>
