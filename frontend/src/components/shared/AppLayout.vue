<template>
  <div class="min-h-screen flex bg-[#0a0f1e]">
    <!-- Sidebar -->
    <aside class="w-60 flex flex-col fixed h-full z-20 border-r border-white/5"
      style="background: linear-gradient(180deg, #0d1424 0%, #0a0f1e 100%)">

      <!-- Logo -->
      <div class="px-5 py-5 border-b border-white/5">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-sm font-black text-white shadow-lg shadow-blue-500/30">
            W
          </div>
          <div>
            <p class="font-bold text-white text-sm leading-none">WealthOS</p>
            <p class="text-[10px] text-gray-600 mt-0.5">Finanzas personales</p>
          </div>
        </div>
      </div>

      <!-- Patrimonio rápido -->
      <div class="mx-3 mt-3 px-3 py-3 rounded-xl bg-white/[0.03] border border-white/5">
        <p class="text-[10px] font-semibold text-gray-600 uppercase tracking-wider mb-1">Patrimonio</p>
        <p class="text-base font-bold text-white font-num">{{ fmtCompact(portfolioStore.totalCLP) }}</p>
        <p class="text-[10px] text-gray-600 font-num">≈ {{ fmtUSD(portfolioStore.totalUSD) }}</p>
      </div>

      <!-- Nav -->
      <nav class="flex-1 px-3 mt-4 space-y-0.5">
        <RouterLink v-for="item in navItems" :key="item.to" :to="item.to"
          class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-150 group relative"
          :class="isActive(item.to)
            ? 'bg-blue-600/15 text-blue-400'
            : 'text-gray-500 hover:text-gray-200 hover:bg-white/5'">
          <!-- Active indicator -->
          <div v-if="isActive(item.to)"
            class="absolute left-0 top-1/2 -translate-y-1/2 w-0.5 h-5 bg-blue-500 rounded-r-full"></div>
          <span class="text-base leading-none">{{ item.icon }}</span>
          <span class="text-sm font-medium">{{ item.label }}</span>
          <!-- Badge de alertas (placeholder para futuro) -->
          <span v-if="item.badge" class="ml-auto text-[10px] bg-blue-600 text-white px-1.5 py-0.5 rounded-full font-bold">
            {{ item.badge }}
          </span>
        </RouterLink>
      </nav>

      <!-- Mes actual stats -->
      <div class="mx-3 mb-3 px-3 py-3 rounded-xl bg-white/[0.03] border border-white/5 space-y-2">
        <p class="text-[10px] font-semibold text-gray-600 uppercase tracking-wider">{{ mesActual }}</p>
        <div class="flex justify-between text-xs">
          <span class="text-gray-500">Ingresos</span>
          <span class="text-emerald-400 font-semibold font-num">{{ fmtCompact(txStore.income) }}</span>
        </div>
        <div class="flex justify-between text-xs">
          <span class="text-gray-500">Gastos</span>
          <span class="text-red-400 font-semibold font-num">{{ fmtCompact(txStore.expenses) }}</span>
        </div>
        <div class="progress-bar h-1.5 mt-1">
          <div class="progress-fill h-1.5 bg-gradient-to-r from-emerald-500 to-blue-500"
            :style="{ width: Math.min(100, txStore.savingsRate) + '%' }"></div>
        </div>
        <p class="text-[10px] text-gray-600">Ahorro: <span class="text-gray-400 font-semibold">{{ txStore.savingsRate.toFixed(0) }}%</span></p>
      </div>

      <!-- User + Logout -->
      <div class="px-3 pb-4 border-t border-white/5 pt-3">
        <div class="flex items-center gap-2.5 px-2 py-2">
          <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-blue-600 to-purple-600 flex items-center justify-center text-xs font-bold text-white flex-shrink-0">
            {{ auth.user?.name?.[0]?.toUpperCase() }}
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-xs font-semibold text-gray-300 truncate">{{ auth.user?.name }}</p>
            <p class="text-[10px] text-gray-600 truncate">{{ auth.user?.email }}</p>
          </div>
          <button @click="handleLogout" class="btn-icon text-gray-600 hover:text-red-400" title="Cerrar sesión">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
          </button>
        </div>
      </div>
    </aside>

    <!-- Main content -->
    <main class="flex-1 ml-60 min-h-screen">
      <!-- Top bar -->
      <div class="sticky top-0 z-10 border-b border-white/5 px-8 py-3.5 flex items-center justify-between"
        style="background: rgba(10,15,30,0.85); backdrop-filter: blur(12px)">
        <div class="flex items-center gap-2 text-sm text-gray-500">
          <span>{{ currentPageLabel }}</span>
        </div>
        <div class="flex items-center gap-2 text-xs text-gray-600">
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
          Datos en tiempo real
        </div>
      </div>

      <div class="p-8">
        <RouterView v-slot="{ Component }">
          <Transition name="page" mode="out-in">
            <component :is="Component" />
          </Transition>
        </RouterView>
      </div>
    </main>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { usePortfolioStore } from '@/stores/portfolio'
import { useTransactionsStore } from '@/stores/transactions'

const auth           = useAuthStore()
const portfolioStore = usePortfolioStore()
const txStore        = useTransactionsStore()
const route          = useRoute()
const router         = useRouter()

const mesActual = new Intl.DateTimeFormat('es-CL', { month: 'long', year: 'numeric' }).format(new Date())

const navItems = [
  { to: '/',             icon: '📊', label: 'Dashboard'     },
  { to: '/portfolio',    icon: '💼', label: 'Portafolio'    },
  { to: '/transactions', icon: '💸', label: 'Transacciones' },
  { to: '/goals',        icon: '🎯', label: 'Metas'         },
  { to: '/simulator',    icon: '📈', label: 'Simulador'     },
  { to: '/advisor',      icon: '🤖', label: 'Asesor IA'     },
  { to: '/shared',       icon: '👫', label: 'Compartido'    },
]

const currentPageLabel = computed(() =>
  navItems.find(i => isActive(i.to))?.label ?? ''
)

const fmt        = (v) => new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP', maximumFractionDigits: 0 }).format(v ?? 0)
const fmtCompact = (v) => new Intl.NumberFormat('es-CL', { notation: 'compact', style: 'currency', currency: 'CLP', maximumFractionDigits: 1 }).format(v ?? 0)
const fmtUSD     = (v) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', maximumFractionDigits: 0 }).format(v ?? 0)

function isActive(path) {
  if (path === '/') return route.path === '/'
  return route.path.startsWith(path)
}

async function handleLogout() {
  await auth.logout()
  router.push('/login')
}
</script>
