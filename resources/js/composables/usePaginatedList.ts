import { ref, type Ref } from 'vue'

export interface ListMeta {
  current_page: number
  last_page: number
  total: number
  [key: string]: unknown
}

interface ListResponse<T> {
  success: boolean
  data: T[]
  meta?: Partial<ListMeta>
}

/**
 * Estado compartido de un listado paginado contra la API.
 *
 * Existe porque los ocho módulos repiten exactamente lo mismo: items,
 * loading, error, página actual y total. Centralizarlo evita ocho copias de
 * la misma lógica y un tratamiento distinto del error en cada una (§9).
 */
export function usePaginatedList<T>(
  fetcher: (params: Record<string, unknown>) => Promise<ListResponse<T>>
) {
  const items = ref([]) as Ref<T[]>
  const loading = ref(false)
  const error = ref<string | null>(null)
  const currentPage = ref(1)
  const lastPage = ref(1)
  const total = ref(0)
  const meta = ref<Partial<ListMeta>>({})

  /** Últimos filtros usados, para poder recargar tras crear o borrar. */
  let lastParams: Record<string, unknown> = {}

  const load = async (params: Record<string, unknown> = {}) => {
    lastParams = params
    loading.value = true
    error.value = null

    try {
      const response = await fetcher(params)
      items.value = response.data ?? []
      meta.value = response.meta ?? {}
      currentPage.value = response.meta?.current_page ?? 1
      lastPage.value = response.meta?.last_page ?? 1
      total.value = response.meta?.total ?? items.value.length
    } catch (err: unknown) {
      error.value = extractMessage(err, 'No se pudo cargar la información.')
      items.value = []
    } finally {
      loading.value = false
    }
  }

  const reload = () => load(lastParams)

  return { items, loading, error, currentPage, lastPage, total, meta, load, reload }
}

/**
 * Mensaje legible de un error de axios.
 *
 * El backend responde `{ success, message, errors }` tanto en errores de
 * validación como de negocio (§10, §40), así que el mensaje del servidor es
 * el que debe verse; el genérico es solo el último recurso.
 */
export function extractMessage(err: unknown, fallback: string): string {
  const response = (err as { response?: { data?: { message?: string } } })?.response
  return response?.data?.message ?? fallback
}
