import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { api } from '@/api/client'
import { extractMessage } from '@/composables/usePaginatedList'
import { extractMessage } from '@/composables/usePaginatedList'

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
      // Un 401 significa que el token ya no sirve y toca salir. Un fallo de
      // red o un tiempo agotado solo significan que el servidor no contestó
      // ahora: cerrar la sesión por eso obliga a volver a entrar sin motivo.
      if ((error as { response?: { status?: number } })?.response?.status === 401) {
        logout()
      }

      throw error
    }
  }

  /**
   * Restaura la sesión desde el token guardado, una sola vez por carga.
   * El guard del router debe esperarla: si no, en un F5 el usuario todavía
   * no está resuelto y la navegación rebota al login.
   */
  let sessionPromise: Promise<void> | null = null

  const ensureSession = () => {
    if (!sessionPromise) {
      // Si la comprobación falla (servidor que no contesta), se descarta la
      // promesa: así el siguiente intento vuelve a preguntar en lugar de
      // quedarse con un resultado fallido para el resto de la sesión.
      sessionPromise = checkAuth().catch(() => {
        sessionPromise = null
      })
    }

    return sessionPromise
  }

  /** Motivo del último intento fallido, tal como lo explica el backend. */
  const loginError = ref<string | null>(null)

  const login = async (email: string, password: string) => {
    loginError.value = null

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
      loginError.value = response.data.message ?? null

      return false
    } catch (error) {
      // "El usuario está inactivo" y "Las credenciales son inválidas" llegan
      // igual: distinguirlas le ahorra al usuario adivinar qué pasó.
      loginError.value = extractMessage(error, 'Las credenciales son inválidas.')

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
    loginError,
    logout,
    hasRole,
    hasPermission,
  }
})
