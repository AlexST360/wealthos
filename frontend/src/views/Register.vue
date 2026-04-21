<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-950 p-4">
    <div class="w-full max-w-sm">
      <div class="text-center mb-8">
        <div class="w-16 h-16 bg-blue-600 rounded-2xl flex items-center justify-center text-3xl font-bold mx-auto mb-4">W</div>
        <h1 class="text-2xl font-bold text-white">WealthOS</h1>
        <p class="text-gray-400 text-sm mt-1">Crea tu cuenta</p>
      </div>

      <div class="card">
        <h2 class="text-lg font-semibold text-white mb-6">Registro</h2>

        <form @submit.prevent="handleRegister" class="space-y-4">
          <div>
            <label class="label">Nombre</label>
            <input v-model="form.name" type="text" class="input" placeholder="Alex" required />
          </div>
          <div>
            <label class="label">Correo electrónico</label>
            <input v-model="form.email" type="email" class="input" placeholder="alex@email.com" required />
          </div>
          <div>
            <label class="label">Contraseña</label>
            <input v-model="form.password" type="password" class="input" placeholder="Mínimo 8 caracteres" required />
          </div>
          <div>
            <label class="label">Confirmar contraseña</label>
            <input v-model="form.password_confirmation" type="password" class="input" required />
          </div>

          <div v-if="errors.length" class="bg-red-900/30 border border-red-700 rounded-lg p-3">
            <p v-for="e in errors" :key="e" class="text-red-400 text-sm">{{ e }}</p>
          </div>

          <button type="submit" class="btn-primary w-full" :disabled="loading">
            {{ loading ? 'Creando cuenta...' : 'Crear cuenta' }}
          </button>
        </form>

        <p class="text-center text-sm text-gray-500 mt-4">
          ¿Ya tienes cuenta?
          <RouterLink to="/login" class="text-blue-400 hover:text-blue-300">Inicia sesión</RouterLink>
        </p>
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

const form    = ref({ name: '', email: '', password: '', password_confirmation: '' })
const loading = ref(false)
const errors  = ref([])

async function handleRegister() {
  loading.value = true
  errors.value  = []
  try {
    await auth.register(form.value.name, form.value.email, form.value.password, form.value.password_confirmation)
    router.push('/')
  } catch (e) {
    const errs = e.response?.data?.errors
    if (errs) {
      errors.value = Object.values(errs).flat()
    } else {
      errors.value = [e.response?.data?.message ?? 'Error al registrarse']
    }
  } finally {
    loading.value = false
  }
}
</script>
