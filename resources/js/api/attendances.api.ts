import { api as client } from './client'

export const attendancesApi = {
  async list(params: Record<string, unknown> = {}) {
    const { data } = await client.get('/attendances', { params })
    return data
  },

  async get(id: number) {
    const { data } = await client.get(`/attendances/${id}`)
    return data
  },

  async create(payload: Record<string, unknown>) {
    const { data } = await client.post('/attendances', payload)
    return data
  },

  /** Insumos configurados del servicio, con su cantidad referencial (§20). */
  async suppliesForService(serviceId: number) {
    const { data } = await client.get(`/services/${serviceId}/supplies`)
    return data
  },
}
