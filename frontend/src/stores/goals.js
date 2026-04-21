import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { goals as goalsApi } from '@/services/api'

export const useGoalsStore = defineStore('goals', () => {
  const list    = ref([])
  const loading = ref(false)

  const active    = computed(() => list.value.filter(g => g.status !== 'completed'))
  const completed = computed(() => list.value.filter(g => g.status === 'completed'))

  async function fetchAll() {
    loading.value = true
    try {
      const { data } = await goalsApi.list()
      list.value = data.goals ?? []
    } finally {
      loading.value = false
    }
  }

  async function create(goalData) {
    const { data } = await goalsApi.create(goalData)
    list.value.push(data.goal)
    return data.goal
  }

  async function update(id, updates) {
    const { data } = await goalsApi.update(id, updates)
    const idx = list.value.findIndex(g => g.id === id)
    if (idx !== -1) list.value[idx] = data.goal
    return data.goal
  }

  async function contribute(id, amount) {
    const { data } = await goalsApi.addContribution(id, amount)
    const idx = list.value.findIndex(g => g.id === id)
    if (idx !== -1) list.value[idx] = data.goal
    return data.goal
  }

  async function remove(id) {
    await goalsApi.delete(id)
    list.value = list.value.filter(g => g.id !== id)
  }

  return { list, loading, active, completed, fetchAll, create, update, contribute, remove }
})
