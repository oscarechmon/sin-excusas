import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { api } from '@/api/client'

interface User {
  id: number
  name: string
  email: string
}

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const token = ref<string | null>(localStorage.getItem('auth_token'))
  const roles = ref<string[]>([])
  const permissions = ref<string[]>([])

  const isAuthenticated = computed(() => !!token.value && !!user.value)

  const checkAuth = async () => {
    if (!token.value) return

    try {
      const response = await api.get('/auth/me')
      if (response.data.success) {
        user.value = response.data.data.user
        roles.value = response.data.data.roles
        permissions.value = response.data.data.permissions
      }
    } catch (error) {
      logout()
    }
  }

  /**
   * Restaura la sesión desde el token guardado, una sola vez por carga.
   * El guard del router debe esperarla: si no, en un F5 el usuario todavía
   * no está resuelto y la navegación rebota al login.
   */
  let sessionPromise: Promise<void> | null = null

  const ensureSession = () => {
    if (!sessionPromise) sessionPromise = checkAuth()
    return sessionPromise
  }

  const login = async (email: string, password: string) => {
    try {
      const response = await api.post('/auth/login', { email, password })
      if (response.data.success) {
        user.value = response.data.data.user
        token.value = response.data.data.token
        roles.value = response.data.data.roles
        permissions.value = response.data.data.permissions ?? []
        localStorage.setItem('auth_token', token.value)
        // La sesión ya está completa: ensureSession no debe volver a pedirla.
        sessionPromise = Promise.resolve()
        return true
      }
      return false
    } catch (error) {
      return false
    }
  }

  const logout = async () => {
    try {
      await api.post('/auth/logout')
    } catch (error) {
      console.error(error)
    }
    user.value = null
    token.value = null
    roles.value = []
    permissions.value = []
    sessionPromise = null
    localStorage.removeItem('auth_token')
  }

  const hasRole = (role: string): boolean => roles.value.includes(role)

  const hasPermission = (permission: string): boolean =>
    permissions.value.includes(permission)

  return {
    user,
    token,
    roles,
    permissions,
    isAuthenticated,
    checkAuth,
    ensureSession,
    login,
    logout,
    hasRole,
    hasPermission,
  }
})
