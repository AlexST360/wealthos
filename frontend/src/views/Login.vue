<template>
  <div class="min-h-screen flex bg-[#0a0f1e]">
    <!-- Panel izquierdo decorativo -->
    <div class="hidden lg:flex lg:w-1/2 flex-col justify-between p-12 relative overflow-hidden border-r border-white/5"
      style="background: linear-gradient(160deg, #0d1424 0%, #0a0f1e 100%)">
      <!-- Grid sutil -->
      <div class="absolute inset-0 opacity-[0.025]"
        style="background-image: linear-gradient(#fff 1px, transparent 1px), linear-gradient(90deg, #fff 1px, transparent 1px); background-size: 48px 48px;"></div>
      <!-- Glow pequeño en la esquina -->
      <div class="absolute bottom-0 left-0 w-64 h-64 bg-blue-600/10 rounded-full blur-3xl pointer-events-none"></div>

      <!-- Logo y tagline -->
      <div class="relative z-10">
        <div class="flex items-center gap-3 mb-10">
          <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-base font-black text-white shadow-lg shadow-blue-500/30">W</div>
          <span class="font-bold text-white text-lg">WealthOS</span>
        </div>
        <h1 class="text-3xl font-bold text-white leading-snug mb-3">Tu plataforma<br>financiera personal</h1>
        <p class="text-gray-500 text-sm">Portafolio · Gastos · Metas · Simulador · Asesor IA</p>
      </div>

      <!-- Stats -->
      <div class="relative z-10 grid grid-cols-2 gap-3">
        <div v-for="stat in leftStats" :key="stat.label"
          class="rounded-2xl border border-white/5 p-4"
          style="background: rgba(255,255,255,0.03)">
          <p class="text-2xl font-bold font-num" :class="stat.color">{{ stat.value }}</p>
          <p class="text-xs text-gray-600 mt-1">{{ stat.label }}</p>
        </div>
      </div>
    </div>

    <!-- Panel derecho: formulario -->
    <div class="w-full lg:w-1/2 flex items-center justify-center p-8">
      <div class="w-full max-w-sm">
        <!-- Logo mobile -->
        <div class="lg:hidden text-center mb-8">
          <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-2xl font-black text-white mx-auto mb-3">W</div>
          <h1 class="text-2xl font-bold text-white">WealthOS</h1>
        </div>

        <div class="mb-8">
          <h2 class="text-2xl font-bold text-white">Bienvenido de nuevo</h2>
          <p class="text-gray-500 text-sm mt-1">Ingresa tus credenciales para continuar</p>
        </div>

        <form @submit.prevent="handleLogin" class="space-y-4">
          <div>
            <label class="label">Correo electrónico</label>
            <input v-model="form.email" type="email" class="input" placeholder="alex@email.com" required autocomplete="email" />
          </div>
          <div>
            <label class="label">Contraseña</label>
            <input v-model="form.password" type="password" class="input" placeholder="••••••••" required autocomplete="current-password" />
          </div>

          <div v-if="error" class="bg-red-500/10 border border-red-500/20 rounded-xl px-4 py-3 text-sm text-red-400">
            {{ error }}
          </div>

          <button type="submit" class="btn-primary w-full py-3" :disabled="loading">
            <svg v-if="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            {{ loading ? 'Iniciando sesión...' : 'Iniciar sesión' }}
          </button>
        </form>

        <p class="text-center text-sm text-gray-600 mt-6">
          ¿No tienes cuenta?
          <RouterLink to="/register" class="text-blue-400 hover:text-blue-300 font-medium">Regístrate gratis</RouterLink>
        </p>

        <!-- Demo -->
        <div class="mt-6 border border-white/5 rounded-xl p-4 bg-white/[0.02]">
          <p class="text-xs text-gray-500 mb-2 font-semibold uppercase tracking-wide">Cuenta demo</p>
          <button @click="loginDemo" class="btn-secondary w-full text-xs py-2" :disabled="loading">
            Entrar como Alex (demo)
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth   = useAuthStore()
const router = useRouter()

const form    = ref({ email: '', password: '' })
const loading = ref(false)
const error   = ref('')

const leftStats = [
  { value: '60.1%', label: 'Tasa de ahorro',       color: 'text-blue-400' },
  { value: '$28M+', label: 'Patrimonio gestionado', color: 'text-emerald-400' },
  { value: '6',     label: 'Módulos integrados',    color: 'text-purple-400' },
  { value: 'IA',    label: 'Asesor powered by Groq',color: 'text-yellow-400' },
]

async function handleLogin() {
  loading.value = true
  error.value   = ''
  try {
    await auth.login(form.value.email, form.value.password)
    router.push('/')
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Credenciales incorrectas'
  } finally {
    loading.value = false
  }
}

async function loginDemo() {
  form.value = { email: 'alex@wealthos.cl', password: 'password' }
  await handleLogin()
}
</script>
