import { api as client } from './client'

export const cashApi = {
  async list(params: Record<string, unknown> = {}) {
    const { data } = await client.get('/cash-sessions', { params })
    return data
  },

  /** Caja abierta actual; `data` es null si no hay ninguna. */
  async current() {
    const { data } = await client.get('/cash-sessions/current')
    return data
  },

  async get(id: number) {
    const { data } = await client.get(`/cash-sessions/${id}`)
    return data
  },

  async open(payload: { opening_amount: number; notes?: string }) {
    const { data } = await client.post('/cash-sessions/open', payload)
    return data
  },

  async close(payload: { counted_amount: number; notes?: string }) {
    const { data } = await client.post('/cash-sessions/close', payload)
    return data
  },

  async registerExpense(payload: { amount: number; description: string }) {
    const { data } = await client.post('/cash-sessions/expenses', payload)
    return data
  },
}
