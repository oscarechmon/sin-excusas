import { api as client } from './client'

type DateRange = { from?: string; to?: string; group_by?: string }

export const reportsApi = {
  async sales(params: DateRange = {}) {
    const { data } = await client.get('/reports/sales', { params })
    return data
  },

  async cash(params: DateRange = {}) {
    const { data } = await client.get('/reports/cash', { params })
    return data
  },

  async commissions(params: DateRange = {}) {
    const { data } = await client.get('/reports/commissions', { params })
    return data
  },

  async stock(params: { only_low?: boolean } = {}) {
    const { data } = await client.get('/reports/stock', { params })
    return data
  },
}

export const dashboardApi = {
  async metrics(params: { date?: string } = {}) {
    const { data } = await client.get('/dashboard', { params })
    return data
  },
}
