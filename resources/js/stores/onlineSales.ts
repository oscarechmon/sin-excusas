import { defineStore } from 'pinia'
import { ref } from 'vue'
import { onlineOrdersApi, storeSettingsApi } from '@/api/onlineSales.api'
import { usePaginatedList } from '@/composables/usePaginatedList'

export interface ShippingRate {
  enabled: boolean
  fee: number
}

/** Costos de envío: delivery en Lima y envío a provincia (Shalom). */
export interface StoreSettings {
  delivery: ShippingRate
  province: ShippingRate
  /** Con el sistema conectado se cambian allá; aquí solo se consultan. */
  managed_by_erp?: boolean
}

export const useOnlineSalesStore = defineStore('onlineSales', () => {
  const list = usePaginatedList<any>((params) => onlineOrdersApi.list(params))
  const settings = ref<StoreSettings | null>(null)

  const getOrder = async (id: number) => {
    const response = await onlineOrdersApi.get(id)
    return response.data
  }

  /** Recarga el listado: el estado cambió y la tabla debe reflejarlo. */
  const changeStatus = async (id: number, payload: { status: string; note?: string | null }) => {
    const response = await onlineOrdersApi.changeStatus(id, payload)
    await list.reload()
    return response
  }

  const loadSettings = async () => {
    const response = await storeSettingsApi.get()
    settings.value = response.data
  }

  const saveSettings = async (payload: StoreSettings) => {
    const response = await storeSettingsApi.update(payload)
    settings.value = response.data
    return response
  }

  return { ...list, settings, getOrder, changeStatus, loadSettings, saveSettings }
})
