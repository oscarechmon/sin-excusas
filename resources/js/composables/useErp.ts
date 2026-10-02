import { ref } from 'vue'
import { erpApi, type ErpStatus } from '@/api/erp.api'

/**
 * ¿El catálogo y el stock se administran en el sistema (ERP)?
 *
 * Se pregunta una sola vez por sesión y lo comparten todas las pantallas:
 * inventario, servicios y ventas ocultan lo que ya no se hace aquí. Mientras
 * no se sabe, se asume que no hay sistema (el backend igual lo impide).
 */
const status = ref<ErpStatus>({ enabled: false, url: null })
let request: Promise<void> | null = null

export function useErp() {
  if (!request) {
    request = erpApi
      .status()
      .then((value) => {
        status.value = value
      })
      .catch(() => {
        request = null // Se vuelve a preguntar en la próxima pantalla.
      })
  }

  return { erp: status }
}
