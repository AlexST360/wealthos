import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { portfolio as portfolioApi } from '@/services/api'

export const usePortfolioStore = defineStore('portfolio', () => {
  const summary  = ref(null)
  const assets   = ref([])
  const loading  = ref(false)
  const error    = ref(null)

  const totalCLP    = computed(() => summary.value?.total_clp ?? 0)
  const totalUSD    = computed(() => summary.value?.total_usd ?? 0)
  const byType      = computed(() => summary.value?.by_type ?? [])
  const usdToClp    = computed(() => summary.value?.usd_to_clp ?? 900)

  async function fetchSummary() {
    loading.value = true
    error.value   = null
    try {
      const { data } = await portfolioApi.summary()
      summary.value  = data
      assets.value   = data.assets ?? []
    } catch (e) {
      error.value = e.response?.data?.message ?? 'Error al cargar el portafolio'
    } finally {
      loading.value = false
    }
  }

  async function addAsset(assetData) {
    const { data } = await portfolioApi.create(assetData)
    await fetchSummary()
    return data.asset
  }

  async function updateAsset(id, updates) {
    const { data } = await portfolioApi.update(id, updates)
    await fetchSummary()
    return data.asset
  }

  async function deleteAsset(id) {
    await portfolioApi.delete(id)
    await fetchSummary()
  }

  // Formatea un número CLP: $1.250.000
  function formatCLP(amount) {
    return new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP', maximumFractionDigits: 0 }).format(amount)
  }

  function formatUSD(amount) {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', maximumFractionDigits: 2 }).format(amount)
  }

  return {
    summary, assets, loading, error,
    totalCLP, totalUSD, byType, usdToClp,
    fetchSummary, addAsset, updateAsset, deleteAsset,
    formatCLP, formatUSD,
  }
})
