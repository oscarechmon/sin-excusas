import { api as client } from './client'

export interface ErpStatus {
  enabled: boolean
  url: string | null
}

/**
 * Conexión con el sistema (ERP), dueño del catálogo y del stock cuando está
 * conectado. Aquí solo se pregunta su estado y se pide sincronizar.
 */
export const erpApi = {
  async status(): Promise<ErpStatus> {
    const { data } = await client.get('/erp/status')
    return data.data
  },

  async sync() {
    const { data } = await client.post('/erp/sync', {}, { timeout: 120000 })
    return data
  },
}
