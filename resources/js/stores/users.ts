import { defineStore } from 'pinia'
import { ref } from 'vue'
import { rolesApi, usersApi } from '@/api/users.api'
import { usePaginatedList } from '@/composables/usePaginatedList'

export const useUsersStore = defineStore('users', () => {
  const list = usePaginatedList<any>((params) => usersApi.list(params))

  const roles = ref<any[]>([])
  const permissionGroups = ref<any[]>([])
  /** Rol cuyos permisos no se pueden editar (Administrador). */
  const lockedRole = ref<string | null>(null)
  const rolesLoading = ref(false)

  const loadRoles = async () => {
    rolesLoading.value = true
    try {
      const response = await rolesApi.list()
      roles.value = response.data.roles ?? []
      permissionGroups.value = response.data.permissions ?? []
      lockedRole.value = response.data.locked_role ?? null
    } finally {
      rolesLoading.value = false
    }
  }

  const createUser = async (payload: Record<string, unknown>) => {
    const response = await usersApi.create(payload)
    await list.reload()
    return response
  }

  const updateUser = async (id: number, payload: Record<string, unknown>) => {
    const response = await usersApi.update(id, payload)
    await list.reload()
    return response
  }

  const deleteUser = async (id: number) => {
    const response = await usersApi.remove(id)
    await list.reload()
    return response
  }

  const revokeSessions = async (id: number) => usersApi.revokeSessions(id)

  const syncRolePermissions = async (roleId: number, permissions: string[]) => {
    const response = await rolesApi.syncPermissions(roleId, permissions)
    await loadRoles()
    return response
  }

  return {
    ...list,
    roles,
    permissionGroups,
    lockedRole,
    rolesLoading,
    loadRoles,
    createUser,
    updateUser,
    deleteUser,
    revokeSessions,
    syncRolePermissions,
  }
})
