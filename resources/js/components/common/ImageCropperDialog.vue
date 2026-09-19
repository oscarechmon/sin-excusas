<template>
  <Dialog
    :visible="visible"
    modal
    header="Encuadrar la foto"
    :style="{ width: '760px' }"
    :breakpoints="{ '800px': '96vw' }"
    @update:visible="close"
  >
    <p class="cropper-hint">
      Arrastra la foto para moverla y usa el zoom para acercarla. Lo que quede dentro del recuadro
      es lo que se verá en la web{{ aspectLabel ? ` (proporción ${aspectLabel})` : '' }}.
    </p>

    <div class="cropper-stage">
      <img ref="image" :src="source" alt="" />
    </div>

    <div class="cropper-tools">
      <Button icon="pi pi-search-minus" text rounded aria-label="Alejar" @click="zoom(-0.1)" />
      <Button icon="pi pi-search-plus" text rounded aria-label="Acercar" @click="zoom(0.1)" />
      <Button icon="pi pi-replay" text rounded aria-label="Girar a la izquierda" @click="rotate(-90)" />
      <Button icon="pi pi-refresh" text rounded aria-label="Girar a la derecha" @click="rotate(90)" />
      <Button label="Restablecer" text size="small" @click="reset" />
    </div>

    <template #footer>
      <Button label="Cancelar" text severity="secondary" @click="close(false)" />
      <Button label="Usar esta foto" icon="pi pi-check" :loading="working" @click="confirm" />
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import { nextTick, ref, watch } from 'vue'
import Cropper from 'cropperjs'
import 'cropperjs/dist/cropper.css'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'

/**
 * Recorte y zoom antes de subir una foto.
 *
 * Cada destino de la web tiene su proporción (una tarjeta no es lo mismo que
 * la foto amplia de Nosotros); recortando aquí la imagen llega ya encuadrada
 * y no se deforma ni se corta sola al mostrarla.
 */
const props = defineProps<{
  visible: boolean
  file: File | null
  aspectRatio?: number
  aspectLabel?: string
  /** Lado mayor de la foto exportada, en píxeles. */
  maxSize?: number
}>()

const emit = defineEmits<{ 'update:visible': [boolean]; cropped: [File] }>()

const image = ref<HTMLImageElement | null>(null)
const source = ref('')
const working = ref(false)
let cropper: Cropper | null = null

const destroy = () => {
  cropper?.destroy()
  cropper = null
  if (source.value) {
    URL.revokeObjectURL(source.value)
    source.value = ''
  }
}

watch(
  () => props.visible,
  async (open) => {
    if (!open || !props.file) {
      destroy()
      return
    }

    source.value = URL.createObjectURL(props.file)
    await nextTick()
    if (!image.value) return

    cropper = new Cropper(image.value, {
      aspectRatio: props.aspectRatio,
      viewMode: 1,
      autoCropArea: 1,
      dragMode: 'move',
      background: false,
      responsive: true,
      // El recuadro queda fijo y se mueve la foto: es más claro para encuadrar.
      cropBoxMovable: false,
      cropBoxResizable: false,
      toggleDragModeOnDblclick: false,
    })
  }
)

const zoom = (step: number) => cropper?.zoom(step)
const rotate = (degrees: number) => cropper?.rotate(degrees)
const reset = () => cropper?.reset()

const close = (open: boolean) => {
  if (!open) destroy()
  emit('update:visible', open)
}

const confirm = () => {
  if (!cropper || !props.file) return
  working.value = true

  // Una tarjeta se muestra a ~360 px: 1200 px cubre pantallas 2x y pesa un
  // tercio que 2000 px. Las fotos a lo ancho (portada) piden 2000.
  const size = props.maxSize ?? 1200
  const canvas = cropper.getCroppedCanvas({
    maxWidth: size,
    maxHeight: size,
    fillColor: '#ffffff',
    imageSmoothingQuality: 'high',
  })

  canvas.toBlob(
    (blob) => {
      working.value = false
      if (!blob) return

      const name = props.file!.name.replace(/\.[^.]+$/, '') || 'foto'
      emit('cropped', new File([blob], `${name}.jpg`, { type: 'image/jpeg' }))
      close(false)
    },
    'image/jpeg',
    0.85
  )
}
</script>

<style scoped lang="scss">
.cropper-hint {
  margin: 0 0 1rem;
  font-size: 0.875rem;
  color: #6b7280;
}

.cropper-stage {
  max-height: 60vh;
  background: #f3f4f6;

  img {
    display: block;
    max-width: 100%;
  }
}

.cropper-tools {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  margin-top: 0.75rem;
}
</style>
