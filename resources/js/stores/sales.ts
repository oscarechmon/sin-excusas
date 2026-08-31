import { defineStore } from 'pinia'
import { ref } from 'vue'
import { paymentMethodsApi, salesApi } from '@/api/sales.api'
import { usePaginatedList } from '@/composables/usePaginatedList'

export const useSalesStore = defineStore('sales', () => {
  const list = usePaginatedList<any>((params) => salesApi.list(params))
  const paymentMethods = ref<any[]>([])

  const loadPaymentMethods = async () => {
    const response = await paymentMethodsApi.list()
    paymentMethods.value = response.data ?? []
  }

  const createSale = async (payload: Record<string, unknown>) => {
    const response = await salesApi.create(payload)
    await list.reload()
    return response
  }

  const addPayment = async (saleId: number, payload: Record<string, unknown>) => {
    const response = await salesApi.addPayment(saleId, payload)
    await list.reload()
    return response
  }

  const cancelSale = async (saleId: number) => {
    const response = await salesApi.cancel(saleId)
    await list.reload()
    return response
  }

  const getSale = async (saleId: number) => {
    const response = await salesApi.get(saleId)
    return response.data
  }

  return { ...list, paymentMethods, loadPaymentMethods, createSale, addPayment, cancelSale, getSale }
})
