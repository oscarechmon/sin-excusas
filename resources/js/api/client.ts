import axios, { AxiosInstance } from 'axios'

const api: AxiosInstance = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || '/api',
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
})

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

api.interceptors.response.use(
  (response) => response,
  (error) => {
    // Un 401 al intentar entrar significa "credenciales incorrectas", no
    // "sesión vencida". Recargar la pantalla ahí borraría el aviso antes de
    // que el usuario alcance a leerlo: el mensaje lo muestra el formulario.
    const intentoDeEntrar = (error.config?.url ?? '').includes('/auth/login')

    if (error.response?.status === 401 && !intentoDeEntrar) {
      localStorage.removeItem('auth_token')

      if (window.location.pathname !== '/login') {
        window.location.href = '/login'
      }
    }

    return Promise.reject(error)
  }
)

export { api }
