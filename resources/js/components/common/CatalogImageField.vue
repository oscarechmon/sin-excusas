<template>
  <div class="image-field">
    <label class="image-field__label">Foto para la web</label>

    <p v-if="!enabled" class="image-field__hint">Guarda primero para poder subir una foto.</p>

    <div v-else class="image-field__body">
      <div class="image-field__preview" :style="{ aspectRatio: String(aspectRatio ?? 4 / 3) }">
        <img v-if="imageUrl" :src="imageUrl" alt="" />
        <i v-else class="pi pi-image" aria-hidden="true" />
      </div>

      <div class="image-field__actions">
        <Button
          :label="imageUrl ? 'Cambiar foto' : 'Subir foto'"
          icon="pi pi-upload"
          size="small"
          outlined
          :loading="busy"
          @click="input?.click()"
        />
        <Button
          v-if="imageUrl"
          label="Quitar"
          icon="pi pi-trash"
          size="small"
          text
          severity="danger"
          :disabled="busy"
          @click="$emit('remove')"
        />
        <small class="image-field__hint">
          JPG, PNG o WebP. Máximo 4 MB. Podrás encuadrarla{{ aspectLabel ? ` (${aspectLabel})` : '' }}.
        </small>
      </div>

      <input
        ref="input"
        type="file"
        accept="image/jpeg,image/png,image/webp"
        hidden
        @change="onChange"
      />
    </div>

    <ImageCropperDialog
      v-model:visible="cropping"
      :file="pendingFile"
      :aspect-ratio="aspectRatio ?? 4 / 3"
      :aspect-label="aspectLabel"
      @cropped="onCropped"
    />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import Button from 'primevue/button'
import ImageCropperDialog from './ImageCropperDialog.vue'

/**
 * Foto de un servicio, producto o bloque de la web.
 *
 * Solo emite el archivo ya recortado: quien lo usa decide a qué endpoint
 * subirlo. `aspectRatio` es la proporción con la que se mostrará en la web.
 */
defineProps<{
  imageUrl: string | null
  enabled: boolean
  busy?: boolean
  aspectRatio?: number
  aspectLabel?: string
}>()

const emit = defineEmits<{ upload: [File]; remove: [] }>()

const input = ref<HTMLInputElement | null>(null)
const pendingFile = ref<File | null>(null)
const cropping = ref(false)

const onChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]

  if (file) {
    pendingFile.value = file
    cropping.value = true
  }

  // Permite volver a elegir el mismo archivo si se canceló el recorte.
  target.value = ''
}

const onCropped = (file: File) => emit('upload', file)
</script>

<style scoped lang="scss">
.image-field {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;

  &__label {
    font-weight: 500;
  }

  &__body {
    display: flex;
    gap: 1rem;
    align-items: center;
  }

  &__preview {
    width: 120px;
    border-radius: 6px;
    overflow: hidden;
    background: #f3f4f6;
    display: grid;
    place-items: center;
    color: #9ca3af;
    font-size: 1.5rem;
    flex-shrink: 0;

    img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
  }

  &__actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    align-items: center;
  }

  &__hint {
    font-size: 0.75rem;
    color: #6b7280;
    width: 100%;
    margin: 0;
  }
}
</style>
