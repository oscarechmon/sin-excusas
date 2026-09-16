import { defineStore } from 'pinia'
import { ref } from 'vue'
import { clientPackagesApi, packagesApi } from '@/api/packages.api'
import { usePaginatedList } from '@/composables/usePaginatedList'

export const usePackagesStore = defineStore('packages', () => {
  const list = usePaginatedList<any>((params) => packagesApi.list(params))

  /** Paquetes ya contratados por clientes, con su saldo de sesiones. */
  const clientPackages = ref<any[]>([])
  const clientPackagesLoading = ref(false)

  const loadClientPackages = async (params: Record<string, unknown> = {}) => {
    clientPackagesLoading.value = true
    try {
      const response = await clientPackagesApi.list(params)
      clientPackages.value = response.data ?? []
    } finally {
      clientPackagesLoading.value = false
    }
  }

  const createPackage = async (payload: Record<string, unknown>) => {
    const response = await packagesApi.create(payload)
    await list.reload()
    return response
  }

  const updatePackage = async (id: number, payload: Record<string, unknown>) => {
    const response = await packagesApi.update(id, payload)
    await list.reload()
    return response
  }

  const deletePackage = async (id: number) => {
    const response = await packagesApi.remove(id)
    await list.reload()
    return response
  }

  /** Actualiza la fila en sitio: recargar la tabla haría saltar la paginación. */
  const setPublished = async (id: number, isPublished: boolean) => {
    const response = await packagesApi.setPublished(id, isPublished)
    const row = list.items.value.find((item) => item.id === id)
    if (row) row.is_published = response.data.is_published
    return response
  }

  const sellPackage = async (payload: Record<string, unknown>) => {
    const response = await packagesApi.sell(payload)
    await loadClientPackages()
    return response
  }

  return {
    ...list,
    clientPackages,
    clientPackagesLoading,
    loadClientPackages,
    createPackage,
    updatePackage,
    deletePackage,
    setPublished,
    sellPackage,
  }
})
