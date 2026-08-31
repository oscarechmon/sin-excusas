import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { cashApi } from '@/api/cash.api'
import { usePaginatedList } from '@/composables/usePaginatedList'

export const useCashStore = defineStore('cash', () => {
  const history = usePaginatedList<any>((params) => cashApi.list(params))

  const session = ref<any | null>(null)
  const totalsByMethod = ref<Record<string, number>>({})
  const loading = ref(false)

  const isOpen = computed(() => session.value !== null)

  const loadCurrent = async () => {
    loading.value = true
    try {
      const response = await cashApi.current()
      session.value = response.data ?? null
      totalsByMethod.value = response.totals_by_method ?? {}
    } finally {
      loading.value = false
    }
  }

  const openSession = async (payload: { opening_amount: number; notes?: string }) => {
    const response = await cashApi.open(payload)
    await loadCurrent()
    return response
  }

  const closeSession = async (payload: { counted_amount: number; notes?: string }) => {
    const response = await cashApi.close(payload)
    // Tras cerrar ya no hay caja abierta: se refresca para reflejarlo.
    await loadCurrent()
    await history.reload()
    return response
  }

  const registerExpense = async (payload: { amount: number; description: string }) => {
    const response = await cashApi.registerExpense(payload)
    await loadCurrent()
    return response
  }

  return {
    session,
    totalsByMethod,
    loading,
    isOpen,
    history,
    loadCurrent,
    openSession,
    closeSession,
    registerExpense,
  }
})
