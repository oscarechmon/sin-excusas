import { api as client } from './client'

export const employeesApi = {
  async list(params: Record<string, unknown> = {}) {
    const { data } = await client.get('/employees', { params })
    return data
  },

  async get(id: number) {
    const { data } = await client.get(`/employees/${id}`)
    return data
  },

  async create(payload: Record<string, unknown>) {
    const { data } = await client.post('/employees', payload)
    return data
  },

  async update(id: number, payload: Record<string, unknown>) {
    const { data } = await client.patch(`/employees/${id}`, payload)
    return data
  },

  async remove(id: number) {
    const { data } = await client.delete(`/employees/${id}`)
    return data
  },
}
