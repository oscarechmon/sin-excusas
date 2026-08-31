import { api as client } from './client'

export const usersApi = {
  async list(params: Record<string, unknown> = {}) {
    const { data } = await client.get('/users', { params })
    return data
  },

  async get(id: number) {
    const { data } = await client.get(`/users/${id}`)
    return data
  },

  async create(payload: Record<string, unknown>) {
    const { data } = await client.post('/users', payload)
    return data
  },

  async update(id: number, payload: Record<string, unknown>) {
    const { data } = await client.patch(`/users/${id}`, payload)
    return data
  },

  async remove(id: number) {
    const { data } = await client.delete(`/users/${id}`)
    return data
  },

  /** Cierra todas las sesiones abiertas del usuario. */
  async revokeSessions(id: number) {
    const { data } = await client.post(`/users/${id}/revoke-sessions`)
    return data
  },
}

export const rolesApi = {
  /** Devuelve los roles, el catálogo de permisos agrupado y el rol bloqueado. */
  async list() {
    const { data } = await client.get('/roles')
    return data
  },

  async syncPermissions(roleId: number, permissions: string[]) {
    const { data } = await client.put(`/roles/${roleId}/permissions`, { permissions })
    return data
  },
}
