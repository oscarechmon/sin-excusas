<template>
  <Dialog
    :visible="visible"
    modal
    :header="isEdit ? 'Editar paquete' : 'Nuevo paquete'"
    :style="{ width: '540px' }"
    @update:visible="$emit('update:visible', $event)"
  >
    <form class="form-grid" @submit.prevent="submit">
      <div class="form-field">
        <label for="name">Nombre <span class="required">*</span></label>
        <InputText id="name" v-model="form.name" :invalid="!!errors.name" autofocus />
        <small v-if="errors.name" class="form-error">{{ errors.name }}</small>
      </div>

      <div class="form-field">
        <label for="services">Servicios incluidos <span class="required">*</span></label>
        <MultiSelect
          id="services"
          v-model="form.service_ids"
          :options="servicesStore.services"
          option-label="name"
          option-value="id"
          filter
          display="chip"
          placeholder="Seleccione los servicios"
          :invalid="!!errors.service_ids"
        />
        <small v-if="errors.service_ids" class="form-error">{{ errors.service_ids }}</small>
      </div>

      <div class="form-row">
        <div class="form-field">
          <label for="sessions">Cantidad de sesiones <span class="required">*</span></label>
          <InputNumber id="sessions" v-model="form.total_sessions" :min="1" :invalid="!!errors.total_sessions" />
          <small v-if="errors.total_sessions" class="form-error">{{ errors.total_sessions }}</small>
        </div>
        <div class="form-field">
          <label for="price">Precio <span class="required">*</span></label>
          <InputNumber id="price" v-model="form.price" mode="currency" currency="PEN" locale="es-PE" :min="0" />
        </div>
      </div>

      <div class="form-field">
        <label for="validity">Vigencia en días</label>
        <InputNumber id="validity" v-model="form.validity_days" :min="1" placeholder="Sin vencimiento" />
        <small class="form-hint">
          Déjelo vacío si el paquete no vence. {{ perSessionHint }}
        </small>
      </div>

      <div class="form-field">
        <label for="description">Descripción</label>
        <Textarea id="description" v-model="form.description" rows="2" auto-resize />
      </div>

      <div class="form-field form-field--inline">
        <ToggleSwitch v-model="form.active" input-id="active" />
        <label for="active">Paquete disponible para la venta</label>
      </div>

      <Message v-if="generalError" severity="error" :closable="false">{{ generalError }}</Message>
    </form>

    <template #footer>
      <Button label="Cancelar" text severity="secondary" :disabled="saving" @click="$emit('update:visible', false)" />
      <Button label="Guardar" icon="pi pi-check" :loading="saving" @click="submit" />
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { usePackagesStore } from '@/stores/packages'
import { useServicesStore } from '@/stores/services'
import { useFormat } from '@/composables/useFormat'
import { extractMessage } from '@/composables/usePaginatedList'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputNumber from 'primevue/inputnumber'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import MultiSelect from 'primevue/multiselect'
import Textarea from 'primevue/textarea'
import ToggleSwitch from 'primevue/toggleswitch'

const props = defineProps<{ visible: boolean; packageItem: any | null }>()
const emit = defineEmits<{ 'update:visible': [boolean]; saved: [string] }>()

const store = usePackagesStore()
const servicesStore = useServicesStore()
const format = useFormat()

const saving = ref(false)
const errors = ref<Record<string, string>>({})
const generalError = ref<string | null>(null)

const isEdit = computed(() => props.packageItem !== null)

const emptyForm = () => ({
  name: '',
  description: '',
  price: 0,
  total_sessions: 1,
  validity_days: null as number | null,
  service_ids: [] as number[],
  active: true,
})

const form = ref(emptyForm())

/** El valor por sesión es lo que se usa como base de comisión (§26). */
const perSessionHint = computed(() => {
  const sessions = Number(form.value.total_sessions ?? 0)
  const price = Number(form.value.price ?? 0)
  if (sessions <= 0 || price <= 0) return ''
  return `Valor por sesión: ${format.money(price / sessions)}.`
})

watch(
  () => props.visible,
  (open) => {
    if (!open) return

    errors.value = {}
    generalError.value = null

    if (servicesStore.services.length === 0) servicesStore.loadServices(1)

    form.value = props.packageItem
      ? {
          name: props.packageItem.name,
          description: props.packageItem.description ?? '',
          price: Number(props.packageItem.price),
          total_sessions: props.packageItem.total_sessions,
          validity_days: props.packageItem.validity_days,
          service_ids: (props.packageItem.services ?? []).map((s: any) => s.id),
          active: props.packageItem.active,
        }
      : emptyForm()
  }
)

const submit = async () => {
  saving.value = true
  errors.value = {}
  generalError.value = null

  try {
    const response = props.packageItem
      ? await store.updatePackage(props.packageItem.id, form.value)
      : await store.createPackage(form.value)

    emit('saved', response.message)
  } catch (err: any) {
    const fieldErrors = err?.response?.data?.errors ?? {}
    errors.value = Object.fromEntries(
      Object.entries(fieldErrors)
        .filter(([, messages]) => Array.isArray(messages))
        .map(([key, messages]) => [key, (messages as string[])[0]])
    )
    if (Object.keys(errors.value).length === 0) {
      generalError.value = extractMessage(err, 'No se pudo guardar el paquete.')
    }
  } finally {
    saving.value = false
  }
}
</script>

<style scoped lang="scss">
.form-hint {
  font-size: 0.75rem;
  color: #6b7280;
}
</style>
