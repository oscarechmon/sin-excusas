import { defineStore } from 'pinia'
import { ref } from 'vue'
import { siteSettingsApi } from '@/api/siteSettings.api'
import { extractMessage } from '@/composables/usePaginatedList'

export interface SiteSettingField {
  key: string
  label: string
  type: 'text' | 'tel' | 'email' | 'url' | 'textarea' | 'code'
  hint: string | null
  placeholder: string | null
}

export interface SiteSettingGroup {
  key: string
  label: string
  hint: string | null
  fields: SiteSettingField[]
}

export const useSiteSettingsStore = defineStore('siteSettings', () => {
  const groups = ref<SiteSettingGroup[]>([])
  const values = ref<Record<string, string>>({})
  const loading = ref(false)
  const error = ref<string | null>(null)

  const load = async () => {
    loading.value = true
    error.value = null
    try {
      const response = await siteSettingsApi.load()
      groups.value = response.data.groups ?? []
      values.value = { ...response.data.values }
    } catch (err) {
      error.value = extractMessage(err, 'No se pudieron cargar los ajustes del sitio.')
    } finally {
      loading.value = false
    }
  }

  const save = async () => {
    const response = await siteSettingsApi.save(values.value)
    values.value = { ...response.data.values }
    return response
  }

  return { groups, values, loading, error, load, save }
})
