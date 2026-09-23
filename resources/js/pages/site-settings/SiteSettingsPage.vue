<template>
  <div class="site-settings-page">
    <div class="page-header">
      <div>
        <h1 class="page-header__title">Datos de la web</h1>
        <p class="page-header__subtitle">
          WhatsApp, correo, redes y etiquetas de Google. Solo afectan a la web pública: el panel nunca carga estos scripts.
        </p>
      </div>
      <div class="page-header__actions">
        <Button label="Ver la web" icon="pi pi-external-link" outlined severity="secondary" @click="openSite" />
        <Button label="Guardar cambios" icon="pi pi-check" :loading="saving" @click="save" />
      </div>
    </div>

    <Message v-if="store.error" severity="error" :closable="false">{{ store.error }}</Message>

    <div v-if="store.loading" class="loading"><ProgressSpinner /></div>

    <div v-else class="groups">
      <Card v-for="group in store.groups" :key="group.key">
        <template #title>{{ group.label }}</template>
        <template #subtitle>{{ group.hint }}</template>
        <template #content>
          <div class="fields" :class="{ 'fields--wide': group.key === 'scripts' }">
            <div
              v-for="field in group.fields"
              :key="field.key"
              class="form-field"
              :class="{ 'form-field--full': field.type === 'hours' || field.type === 'code' }"
            >
              <label :for="field.key">{{ field.label }}</label>

              <OpeningHoursField
                v-if="field.type === 'hours'"
                :model-value="store.values[field.key] as OpeningHours"
                :days="store.days"
                @update:model-value="(hours: OpeningHours) => (store.values[field.key] = hours)"
              />
              <Textarea
                v-else-if="field.type === 'code'"
                :id="field.key"
                v-model="store.values[field.key] as string"
                class="code"
                rows="6"
                spellcheck="false"
                placeholder="<!-- Pega aquí la etiqueta tal como te la dio Google -->"
                :invalid="!!errors[field.key]"
              />
              <InputText
                v-else
                :id="field.key"
                v-model="store.values[field.key] as string"
                :type="field.type === 'tel' ? 'text' : field.type"
                :placeholder="field.placeholder ?? ''"
                :invalid="!!errors[field.key]"
              />

              <small v-if="errors[field.key]" class="field-error">{{ errors[field.key] }}</small>
              <small v-else-if="field.hint" class="field-hint">{{ field.hint }}</small>
            </div>
          </div>
        </template>
      </Card>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useSiteSettingsStore, type OpeningHours } from '@/stores/siteSettings'
import OpeningHoursField from '@/components/common/OpeningHoursField.vue'
import { extractMessage } from '@/composables/usePaginatedList'
import Button from 'primevue/button'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import ProgressSpinner from 'primevue/progressspinner'
import Textarea from 'primevue/textarea'

const store = useSiteSettingsStore()
const toast = useToast()

const saving = ref(false)
const errors = ref<Record<string, string>>({})

const openSite = () => window.open('/', '_blank', 'noopener')

const save = async () => {
  saving.value = true
  errors.value = {}
  try {
    const response = await store.save()
    toast.add({ severity: 'success', summary: 'Listo', detail: response.message, life: 3000 })
  } catch (err: any) {
    // El backend valida bajo `values.<clave>`; aquí se muestra en su campo.
    const validation = err?.response?.data?.errors ?? {}
    errors.value = Object.fromEntries(
      Object.entries(validation).map(([key, messages]) => [
        key.replace(/^values\./, '').split('.')[0],
        (messages as string[])[0],
      ])
    )
    toast.add({
      severity: 'error',
      summary: 'Revisa los datos',
      detail: extractMessage(err, 'No se pudieron guardar los ajustes.'),
      life: 5000,
    })
  } finally {
    saving.value = false
  }
}

onMounted(store.load)
</script>

<style scoped lang="scss">
.loading {
  display: grid;
  place-items: center;
  padding: 3rem;
}

.groups {
  display: grid;
  gap: 1rem;
  margin-top: 1rem;
}

.fields {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(min(320px, 100%), 1fr));
  gap: 1rem;

  &--wide {
    grid-template-columns: 1fr;
  }
}

.form-field--full {
  grid-column: 1 / -1;
}

.form-field {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.code :deep(textarea),
.code {
  font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
  font-size: 0.8rem;
}

.field-hint {
  color: #6b7280;
}

.field-error {
  color: var(--p-red-500, #ef4444);
}
</style>
