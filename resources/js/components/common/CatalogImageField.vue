<template>
  <div class="image-field">
    <label class="image-field__label">Foto para la web</label>

    <p v-if="!enabled" class="image-field__hint">Guarda primero para poder subir una foto.</p>

    <div v-else class="image-field__body">
      <div class="image-field__preview">
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
        <small class="image-field__hint">JPG, PNG o WebP. Máximo 4 MB.</small>
      </div>

      <input
        ref="input"
        type="file"
        accept="image/jpeg,image/png,image/webp"
        hidden
        @change="onChange"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import Button from 'primevue/button'

/**
 * Foto de un servicio o producto para la web pública.
 * Solo emite el archivo: quien lo usa decide a qué endpoint subirlo.
 */
defineProps<{ imageUrl: string | null; enabled: boolean; busy?: boolean }>()
const emit = defineEmits<{ upload: [File]; remove: [] }>()

const input = ref<HTMLInputElement | null>(null)

const onChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  if (file) emit('upload', file)
  // Permite volver a elegir el mismo archivo si la subida falló.
  target.value = ''
}
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
    aspect-ratio: 4 / 3;
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
