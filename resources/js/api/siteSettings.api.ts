import { api as client } from './client'
import type { SiteSettingValue } from '@/stores/siteSettings'

/** Contacto, redes y scripts de la web pública. */
export const siteSettingsApi = {
  async load() {
    const { data } = await client.get('/site-settings')
    return data
  },

  async save(values: Record<string, SiteSettingValue>) {
    const { data } = await client.put('/site-settings', { values })
    return data
  },
}
