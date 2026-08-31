import { api as client } from './client'

export const packagesApi = {
  async list(params: Record<string, unknown> = {}) {
    const { data } = await client.get('/packages', { params })
    return data
  },

  async create(payload: Record<string, unknown>) {
    const { data } = await client.post('/packages', payload)
    return data
  },

  async update(id: number, payload: Record<string, unknown>) {
    const { data } = await client.patch(`/packages/${id}`, payload)
    return data
  },

  async remove(id: number) {
    const { data } = await client.delete(`/packages/${id}`)
    return data
  },

  /** Asigna un paquete a un cliente sin pasar por el módulo de ventas. */
  async sell(payload: Record<string, unknown>) {
    const { data } = await client.post('/packages/sell', payload)
    return data
  },
}

export const clientPackagesApi = {
  async list(params: Record<string, unknown> = {}) {
    const { data } = await client.get('/client-packages', { params })
    return data
  },

  async get(id: number) {
    const { data } = await client.get(`/client-packages/${id}`)
    return data
  },
}
