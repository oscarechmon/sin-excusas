import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { commissionRulesApi, commissionsApi } from '@/api/commissions.api'
import { usePaginatedList } from '@/composables/usePaginatedList'

export const useCommissionsStore = defineStore('commissions', () => {
  const list = usePaginatedList<any>((params) => commissionsApi.list(params))
  const rules = ref<any[]>([])

  // El backend calcula los totales sobre el filtro completo, no solo la página.
  const pendingAmount = computed(() => Number(list.meta.value.pending_amount ?? 0))
  const paidAmount = computed(() => Number(list.meta.value.paid_amount ?? 0))

  const loadRules = async () => {
    const response = await commissionRulesApi.list()
    rules.value = response.data ?? []
  }

  const payCommissions = async (ids: number[]) => {
    const response = await commissionsApi.pay(ids)
    await list.reload()
    return response
  }

  const createRule = async (payload: Record<string, unknown>) => {
    const response = await commissionRulesApi.create(payload)
    await loadRules()
    return response
  }

  const updateRule = async (id: number, payload: Record<string, unknown>) => {
    const response = await commissionRulesApi.update(id, payload)
    await loadRules()
    return response
  }

  const deleteRule = async (id: number) => {
    const response = await commissionRulesApi.remove(id)
    await loadRules()
    return response
  }

  return {
    ...list,
    rules,
    pendingAmount,
    paidAmount,
    loadRules,
    payCommissions,
    createRule,
    updateRule,
    deleteRule,
  }
})
