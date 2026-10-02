import { ref } from 'vue'
import { erpApi, type ErpStatus } from '@/api/erp.api'

/**
 * ¿La operación se hace en el sistema (ERP)?
 *
 * Se pregunta una sola vez por sesión y lo comparten todas las pantallas y el
 * menú: con el sistema conectado, este panel queda para la web (contenido,
 * datos del sitio, imágenes, descripciones y qué se publica). Mientras no se
 * sabe, se asume que no hay sistema (el backend igual lo impide).
 */
const status = ref<ErpStatus>({ enabled: false, url: null })
let request: Promise<void> | null = null

function load(): Promise<void> {
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

  return request
}

export function useErp() {
  load()

  return { erp: status }
}

/** Para el router: espera a saber si el sistema está conectado. */
export async function ensureErpStatus(): Promise<ErpStatus> {
  await load()

  return status.value
}
