import { api as client } from './client'

/** Contacto, redes y scripts de la web pública. */
export const siteSettingsApi = {
  async load() {
    const { data } = await client.get('/site-settings')
    return data
  },

  async save(values: Record<string, string>) {
    const { data } = await client.put('/site-settings', { values })
    return data
  },
}
