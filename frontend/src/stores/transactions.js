import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { transactions as txApi } from '@/services/api'

export const useTransactionsStore = defineStore('transactions', () => {
  const list       = ref([])
  const summary    = ref(null)
  const breakdown  = ref([])
  const loading    = ref(false)

  const income      = computed(() => summary.value?.income ?? 0)
  const expenses    = computed(() => summary.value?.expenses ?? 0)
  const savings     = computed(() => summary.value?.savings ?? 0)
  const savingsRate = computed(() => summary.value?.savings_rate ?? 0)

  async function fetchList(params = {}) {
    loading.value = true
    try {
      const { data } = await txApi.list(params)
      list.value = data.data ?? []
    } finally {
      loading.value = false
    }
  }

  async function fetchSummary(year, month) {
    const { data } = await txApi.summary({ year, month })
    summary.value = data
    return data
  }

  async function fetchBreakdown(year, month) {
    const { data } = await txApi.breakdown({ year, month })
    breakdown.value = data.breakdown ?? []
    return data
  }

  async function create(txData) {
    const { data } = await txApi.create(txData)
    list.value.unshift(data.transaction)
    return data.transaction
  }

  async function remove(id) {
    await txApi.delete(id)
    list.value = list.value.filter(t => t.id !== id)
  }

  return {
    list, summary, breakdown, loading,
    income, expenses, savings, savingsRate,
    fetchList, fetchSummary, fetchBreakdown, create, remove,
  }
})
