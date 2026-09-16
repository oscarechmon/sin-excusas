import { api as client } from './client'

/**
 * Capa HTTP del módulo de inventario.
 *
 * Toda llamada al backend vive en `api/`: los componentes nunca usan axios
 * directamente (§9).
 */
export const inventoryApi = {
  async list(params: Record<string, unknown> = {}) {
    const { data } = await client.get('/inventory-items', { params })
    return data
  },

  async get(id: number) {
    const { data } = await client.get(`/inventory-items/${id}`)
    return data
  },

  async create(payload: Record<string, unknown>) {
    const { data } = await client.post('/inventory-items', payload)
    return data
  },

  async update(id: number, payload: Record<string, unknown>) {
    const { data } = await client.patch(`/inventory-items/${id}`, payload)
    return data
  },

  async remove(id: number) {
    const { data } = await client.delete(`/inventory-items/${id}`)
    return data
  },

  /** Foto para la web. El header explícito evita que axios convierta el FormData a JSON. */
  async uploadImage(id: number, file: File) {
    const body = new FormData()
    body.append('image', file)
    const { data } = await client.post(`/inventory-items/${id}/image`, body, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return data
  },

  async removeImage(id: number) {
    const { data } = await client.delete(`/inventory-items/${id}/image`)
    return data
  },

  /** Publica o retira el producto del catálogo de la web. Solo aplica a vendibles. */
  async setPublished(id: number, isPublished: boolean) {
    const { data } = await client.patch(`/inventory-items/${id}/publish`, { is_published: isPublished })
    return data
  },

  async movements(id: number, params: Record<string, unknown> = {}) {
    const { data } = await client.get(`/inventory-items/${id}/movements`, { params })
    return data
  },

  /** Entrada, salida o ajuste manual de stock. */
  async adjust(id: number, payload: { type: string; quantity: number; notes?: string }) {
    const { data } = await client.post(`/inventory-items/${id}/adjust`, payload)
    return data
  },
}

export const inventoryCategoriesApi = {
  async list() {
    const { data } = await client.get('/inventory-categories')
    return data
  },

  async create(payload: Record<string, unknown>) {
    const { data } = await client.post('/inventory-categories', payload)
    return data
  },

  async update(id: number, payload: Record<string, unknown>) {
    const { data } = await client.patch(`/inventory-categories/${id}`, payload)
    return data
  },

  async remove(id: number) {
    const { data } = await client.delete(`/inventory-categories/${id}`)
    return data
  },
}
