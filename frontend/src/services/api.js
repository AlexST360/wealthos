import axios from 'axios'

const api = axios.create({
  baseURL: '/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  withCredentials: true,
})

// Inyectar token Sanctum en cada request
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('wealthos_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// Manejar errores globales de autenticación
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('wealthos_token')
      localStorage.removeItem('wealthos_user')
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

// ── Auth ──────────────────────────────────────────────────────────────────
export const auth = {
  login:         (data) => api.post('/auth/login', data),
  register:      (data) => api.post('/auth/register', data),
  logout:        ()     => api.post('/auth/logout'),
  me:            ()     => api.get('/auth/me'),
  updateProfile: (data) => api.put('/auth/profile', data),
}

// ── Portafolio ────────────────────────────────────────────────────────────
export const portfolio = {
  summary:      ()           => api.get('/portfolio/summary'),
  history:      (months=6)   => api.get(`/portfolio/history?months=${months}`),
  list:         ()           => api.get('/portfolio'),
  create:       (data)       => api.post('/portfolio', data),
  update:       (id, data)   => api.put(`/portfolio/${id}`, data),
  delete:       (id)         => api.delete(`/portfolio/${id}`),
  refreshPrice: (id)         => api.post(`/portfolio/${id}/refresh`),
}

// ── Transacciones ─────────────────────────────────────────────────────────
export const transactions = {
  list:       (params={})   => api.get('/transactions', { params }),
  create:     (data)        => api.post('/transactions', data),
  update:     (id, data)    => api.put(`/transactions/${id}`, data),
  delete:     (id)          => api.delete(`/transactions/${id}`),
  summary:    (params={})   => api.get('/transactions/summary', { params }),
  breakdown:  (params={})   => api.get('/transactions/breakdown', { params }),
  history:    (months=6)    => api.get(`/transactions/history?months=${months}`),
  categories: ()            => api.get('/transactions/categories'),
}

// ── Metas ─────────────────────────────────────────────────────────────────
export const goals = {
  list:            ()         => api.get('/goals'),
  create:          (data)     => api.post('/goals', data),
  update:          (id, data) => api.put(`/goals/${id}`, data),
  delete:          (id)       => api.delete(`/goals/${id}`),
  addContribution: (id, amt)  => api.post(`/goals/${id}/contribution`, { amount: amt }),
}

// ── Simulador ─────────────────────────────────────────────────────────────
export const simulator = {
  instruments: ()     => api.get('/simulator/instruments'),
  simulate:    (data) => api.post('/simulator/simulate', data),
  compare:     (data) => api.post('/simulator/compare', data),
}

// ── Asesor IA ─────────────────────────────────────────────────────────────
export const advisor = {
  sessions:      ()           => api.get('/advisor/sessions'),
  createSession: ()           => api.post('/advisor/sessions'),
  quickStart:    ()           => api.post('/advisor/sessions/quick-start'),
  getSession:    (id)         => api.get(`/advisor/sessions/${id}`),
  sendMessage:   (id, msg)    => api.post(`/advisor/sessions/${id}/message`, { message: msg }),
  deleteSession: (id)         => api.delete(`/advisor/sessions/${id}`),
}

// ── Espacio Compartido ────────────────────────────────────────────────
export const shared = {
  list:              ()                    => api.get('/shared'),
  create:            (data)               => api.post('/shared', data),
  get:               (id)                 => api.get(`/shared/${id}`),
  destroy:           (id)                 => api.delete(`/shared/${id}`),
  invite:            (id, email)          => api.post(`/shared/${id}/invite`, { email }),
  leave:             (id)                 => api.post(`/shared/${id}/leave`),
  acceptInvitation:  (token)             => api.post('/shared/accept-invitation', { token }),
  // Transacciones
  getTransactions:   (id, params={})     => api.get(`/shared/${id}/transactions`, { params }),
  addTransaction:    (id, data)          => api.post(`/shared/${id}/transactions`, data),
  deleteTransaction: (id, txId)          => api.delete(`/shared/${id}/transactions/${txId}`),
  // Metas
  getGoals:          (id)               => api.get(`/shared/${id}/goals`),
  addGoal:           (id, data)         => api.post(`/shared/${id}/goals`, data),
  contributeGoal:    (id, goalId, amt)  => api.post(`/shared/${id}/goals/${goalId}/contribution`, { amount: amt }),
}

export default api
