import { defineStore } from 'pinia'
import { ref } from 'vue'
import { attendancesApi } from '@/api/attendances.api'
import { usePaginatedList } from '@/composables/usePaginatedList'

export const useAttendancesStore = defineStore('attendances', () => {
  const list = usePaginatedList<any>((params) => attendancesApi.list(params))

  /** Insumos sugeridos del servicio elegido, editables antes de confirmar. */
  const suggestedSupplies = ref<any[]>([])

  const loadSuppliesForService = async (serviceId: number) => {
    const response = await attendancesApi.suppliesForService(serviceId)
    suggestedSupplies.value = (response.data ?? []).map((supply: any) => ({
      ...supply,
      quantity: supply.default_quantity,
    }))
    return suggestedSupplies.value
  }

  const clearSupplies = () => {
    suggestedSupplies.value = []
  }

  const confirmAttendance = async (payload: Record<string, unknown>) => {
    const response = await attendancesApi.create(payload)
    await list.reload()
    return response
  }

  return { ...list, suggestedSupplies, loadSuppliesForService, clearSupplies, confirmAttendance }
})
