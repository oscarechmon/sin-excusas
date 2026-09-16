import { api as client } from './client'

/** Ventas online: pedidos de la web y configuración del delivery. */
export const onlineOrdersApi = {
  async list(params: Record<string, unknown> = {}) {
    const { data } = await client.get('/online-orders', { params })
    return data
  },

  async get(id: number) {
    const { data } = await client.get(`/online-orders/${id}`)
    return data
  },

  /** Avanza el seguimiento (preparación, envío, entrega) o anula el pedido. */
  async changeStatus(id: number, payload: { status: string; note?: string | null }) {
    const { data } = await client.post(`/online-orders/${id}/status`, payload)
    return data
  },
}

export const storeSettingsApi = {
  async get() {
    const { data } = await client.get('/store-settings')
    return data
  },

  async update(payload: { delivery_enabled: boolean; delivery_fee: number }) {
    const { data } = await client.put('/store-settings', payload)
    return data
  },
}
