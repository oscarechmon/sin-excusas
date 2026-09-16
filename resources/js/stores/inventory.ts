import { defineStore } from 'pinia'
import { ref } from 'vue'
import { inventoryApi, inventoryCategoriesApi } from '@/api/inventory.api'
import { extractMessage, usePaginatedList } from '@/composables/usePaginatedList'

export const useInventoryStore = defineStore('inventory', () => {
  const list = usePaginatedList<any>((params) => inventoryApi.list(params))
  const categories = ref<any[]>([])

  const loadCategories = async () => {
    const response = await inventoryCategoriesApi.list()
    categories.value = response.data ?? []
  }

  const createItem = async (payload: Record<string, unknown>) => {
    const response = await inventoryApi.create(payload)
    await list.reload()
    return response
  }

  const updateItem = async (id: number, payload: Record<string, unknown>) => {
    const response = await inventoryApi.update(id, payload)
    await list.reload()
    return response
  }

  const deleteItem = async (id: number) => {
    const response = await inventoryApi.remove(id)
    await list.reload()
    return response
  }

  /**
   * Entrada, salida o ajuste. Recarga el listado porque el saldo cambió y
   * mostrarlo desactualizado induciría a un segundo ajuste equivocado.
   */
  const adjustStock = async (id: number, payload: { type: string; quantity: number; notes?: string }) => {
    const response = await inventoryApi.adjust(id, payload)
    await list.reload()
    return response
  }

  /** Actualiza la fila en sitio: recargar la tabla haría saltar la paginación. */
  const setPublished = async (id: number, isPublished: boolean) => {
    const response = await inventoryApi.setPublished(id, isPublished)
    const row = list.items.value.find((item) => item.id === id)
    if (row) row.is_published = response.data.is_published
    return response
  }

  /** Sube o quita la foto y refleja la nueva URL en la fila del listado. */
  const setImage = async (id: number, file: File | null) => {
    const response = file
      ? await inventoryApi.uploadImage(id, file)
      : await inventoryApi.removeImage(id)
    const row = list.items.value.find((item) => item.id === id)
    if (row) row.image_url = response.data.image_url
    return response
  }

  const movements = ref<any[]>([])
  const movementsLoading = ref(false)

  const loadMovements = async (id: number) => {
    movementsLoading.value = true
    try {
      const response = await inventoryApi.movements(id)
      movements.value = response.data ?? []
    } finally {
      movementsLoading.value = false
    }
  }

  const createCategory = async (payload: Record<string, unknown>) => {
    const response = await inventoryCategoriesApi.create(payload)
    await loadCategories()
    return response
  }

  const updateCategory = async (id: number, payload: Record<string, unknown>) => {
    const response = await inventoryCategoriesApi.update(id, payload)
    await loadCategories()
    return response
  }

  const deleteCategory = async (id: number) => {
    const response = await inventoryCategoriesApi.remove(id)
    await loadCategories()
    return response
  }

  return {
    ...list,
    categories,
    movements,
    movementsLoading,
    loadCategories,
    createItem,
    updateItem,
    deleteItem,
    adjustStock,
    setPublished,
    setImage,
    loadMovements,
    createCategory,
    updateCategory,
    deleteCategory,
    extractMessage,
  }
})
