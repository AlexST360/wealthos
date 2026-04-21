import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { auth as authApi } from '@/services/api'

export const useAuthStore = defineStore('auth', () => {
  const user  = ref(JSON.parse(localStorage.getItem('wealthos_user') || 'null'))
  const token = ref(localStorage.getItem('wealthos_token') || null)

  const isAuthenticated = computed(() => !!token.value && !!user.value)

  async function login(email, password) {
    const { data } = await authApi.login({ email, password })
    _setSession(data.token, data.user)
    return data.user
  }

  async function register(name, email, password, password_confirmation) {
    const { data } = await authApi.register({ name, email, password, password_confirmation })
    _setSession(data.token, data.user)
    return data.user
  }

  async function logout() {
    try { await authApi.logout() } catch (_) {}
    _clearSession()
  }

  async function fetchMe() {
    const { data } = await authApi.me()
    user.value = data.user
    localStorage.setItem('wealthos_user', JSON.stringify(data.user))
    return data.user
  }

  function _setSession(t, u) {
    token.value = t
    user.value  = u
    localStorage.setItem('wealthos_token', t)
    localStorage.setItem('wealthos_user', JSON.stringify(u))
  }

  function _clearSession() {
    token.value = null
    user.value  = null
    localStorage.removeItem('wealthos_token')
    localStorage.removeItem('wealthos_user')
  }

  return { user, token, isAuthenticated, login, register, logout, fetchMe }
})
