import { api as client } from './client'

export const salesApi = {
  async list(params: Record<string, unknown> = {}) {
    const { data } = await client.get('/sales', { params })
    return data
  },

  async get(id: number) {
    const { data } = await client.get(`/sales/${id}`)
    return data
  },

  async create(payload: Record<string, unknown>) {
    const { data } = await client.post('/sales', payload)
    return data
  },

  /** Pago adicional sobre una venta con saldo pendiente. */
  async addPayment(saleId: number, payload: Record<string, unknown>) {
    const { data } = await client.post(`/sales/${saleId}/payments`, payload)
    return data
  },

  async cancel(saleId: number) {
    const { data } = await client.post(`/sales/${saleId}/cancel`)
    return data
  },
}

export const paymentMethodsApi = {
  async list(params: Record<string, unknown> = {}) {
    const { data } = await client.get('/payment-methods', { params })
    return data
  },

  async create(payload: Record<string, unknown>) {
    const { data } = await client.post('/payment-methods', payload)
    return data
  },

  async update(id: number, payload: Record<string, unknown>) {
    const { data } = await client.patch(`/payment-methods/${id}`, payload)
    return data
  },

  async remove(id: number) {
    const { data } = await client.delete(`/payment-methods/${id}`)
    return data
  },
}
