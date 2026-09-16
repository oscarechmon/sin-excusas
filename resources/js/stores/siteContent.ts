import { defineStore } from 'pinia'
import { ref } from 'vue'
import { siteContentApi } from '@/api/siteContent.api'
import { extractMessage } from '@/composables/usePaginatedList'

export interface SiteContentSlot {
  key: string
  label: string
  group: string
  hint: string | null
  fields: string[]
  aspect: number
  title: string | null
  text: string | null
  image_url: string | null
}

export const useSiteContentStore = defineStore('siteContent', () => {
  const slots = ref<SiteContentSlot[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  const load = async () => {
    loading.value = true
    error.value = null
    try {
      const response = await siteContentApi.list()
      slots.value = response.data ?? []
    } catch (err) {
      error.value = extractMessage(err, 'No se pudo cargar el contenido web.')
    } finally {
      loading.value = false
    }
  }

  /** Refleja en la lista el espacio que devolvió el backend. */
  const apply = (updated: SiteContentSlot) => {
    const index = slots.value.findIndex((slot) => slot.key === updated.key)
    if (index !== -1) slots.value[index] = { ...slots.value[index], ...updated }
  }

  const saveTexts = async (key: string, payload: { title?: string | null; text?: string | null }) => {
    const response = await siteContentApi.update(key, payload)
    apply(response.data)
    return response
  }

  const setImage = async (key: string, file: File | null) => {
    const response = file
      ? await siteContentApi.uploadImage(key, file)
      : await siteContentApi.removeImage(key)
    apply(response.data)
    return response
  }

  return { slots, loading, error, load, saveTexts, setImage }
})
