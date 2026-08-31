import { api as client } from './client'

export const commissionsApi = {
  async list(params: Record<string, unknown> = {}) {
    const { data } = await client.get('/commissions', { params })
    return data
  },

  /** Liquida varias comisiones a la vez; el pago suele cubrir un periodo. */
  async pay(commissionIds: number[]) {
    const { data } = await client.post('/commissions/pay', { commission_ids: commissionIds })
    return data
  },
}

export const commissionRulesApi = {
  async list() {
    const { data } = await client.get('/commission-rules')
    return data
  },

  async create(payload: Record<string, unknown>) {
    const { data } = await client.post('/commission-rules', payload)
    return data
  },

  async update(id: number, payload: Record<string, unknown>) {
    const { data } = await client.patch(`/commission-rules/${id}`, payload)
    return data
  },

  async remove(id: number) {
    const { data } = await client.delete(`/commission-rules/${id}`)
    return data
  },
}
