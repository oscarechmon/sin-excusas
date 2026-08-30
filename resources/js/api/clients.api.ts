import { api } from './client'

export const clientsApi = {
  list: (page = 1, search = '', active = null) => {
    let url = `/clients?page=${page}`
    if (search) url += `&search=${search}`
    if (active !== null) url += `&active=${active ? 1 : 0}`
    return api.get(url)
  },

  get: (id: number) => api.get(`/clients/${id}`),

  create: (data: any) => api.post('/clients', data),

  update: (id: number, data: any) => api.put(`/clients/${id}`, data),

  delete: (id: number) => api.delete(`/clients/${id}`),
}
