import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { clientsApi } from '@/api/clients.api'

interface Client {
  id?: number
  code: string
  full_name: string
  document_number?: string
  birth_date?: string
  gender?: string
  phone?: string
  whatsapp?: string
  email?: string
  district?: string
  address?: string
  how_knew?: string
  observations?: string
  allergies?: string
  restrictions?: string
  contraindications?: string
  medications?: string
  relevant_info?: string
  active: boolean
  created_at?: string
  updated_at?: string
}

export const useClientsStore = defineStore('clients', () => {
  const clients = ref<Client[]>([])
  const selectedClient = ref<Client | null>(null)
  const loading = ref(false)
  const currentPage = ref(1)
  const lastPage = ref(1)
  const total = ref(0)
  const searchQuery = ref('')
  const filterActive = ref<boolean | null>(null)

  const hasClients = computed(() => clients.value.length > 0)

  const loadClients = async (page = 1, search = '', active: boolean | null = null) => {
    loading.value = true
    try {
      const response = await clientsApi.list(page, search, active)
      if (response.data.success) {
        clients.value = response.data.data
        currentPage.value = response.data.meta.current_page
        lastPage.value = response.data.meta.last_page
        total.value = response.data.meta.total
      }
    } finally {
      loading.value = false
    }
  }

  const getClient = async (id: number) => {
    loading.value = true
    try {
      const response = await clientsApi.get(id)
      if (response.data.success) {
        selectedClient.value = response.data.data
        return response.data.data
      }
    } finally {
      loading.value = false
    }
  }

  const createClient = async (data: Partial<Client>) => {
    try {
      const response = await clientsApi.create(data)
      if (response.data.success) {
        await loadClients(1, searchQuery.value, filterActive.value)
        return response.data.data
      }
    } catch (error) {
      throw error
    }
  }

  const updateClient = async (id: number, data: Partial<Client>) => {
    try {
      const response = await clientsApi.update(id, data)
      if (response.data.success) {
        await loadClients(currentPage.value, searchQuery.value, filterActive.value)
        return response.data.data
      }
    } catch (error) {
      throw error
    }
  }

  const deleteClient = async (id: number) => {
    try {
      const response = await clientsApi.delete(id)
      if (response.data.success) {
        await loadClients(1, searchQuery.value, filterActive.value)
        return true
      }
    } catch (error) {
      throw error
    }
  }

  const search = async (query: string) => {
    searchQuery.value = query
    await loadClients(1, query, filterActive.value)
  }

  const filter = async (active: boolean | null) => {
    filterActive.value = active
    await loadClients(1, searchQuery.value, active)
  }

  return {
    clients,
    selectedClient,
    loading,
    currentPage,
    lastPage,
    total,
    searchQuery,
    filterActive,
    hasClients,
    loadClients,
    getClient,
    createClient,
    updateClient,
    deleteClient,
    search,
    filter,
  }
})
