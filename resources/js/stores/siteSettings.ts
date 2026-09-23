import { defineStore } from 'pinia'
import { ref } from 'vue'
import { siteSettingsApi } from '@/api/siteSettings.api'
import { extractMessage } from '@/composables/usePaginatedList'

export interface DayOption {
  key: string
  label: string
  short: string
}

export type OpeningHours = Record<string, { open: boolean; from: string; to: string }>

export type SiteSettingValue = string | OpeningHours

export interface SiteSettingField {
  key: string
  label: string
  type: 'text' | 'tel' | 'email' | 'url' | 'textarea' | 'code' | 'hours'
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
  const values = ref<Record<string, SiteSettingValue>>({})
  const days = ref<DayOption[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  const load = async () => {
    loading.value = true
    error.value = null
    try {
      const response = await siteSettingsApi.load()
      groups.value = response.data.groups ?? []
      days.value = response.data.days ?? []
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

  return { groups, values, days, loading, error, load, save }
})
