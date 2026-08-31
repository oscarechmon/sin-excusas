<template>
  <Dialog
    :visible="visible"
    modal
    :header="isEdit ? 'Editar trabajador' : 'Nuevo trabajador'"
    :style="{ width: '520px' }"
    @update:visible="$emit('update:visible', $event)"
  >
    <form class="form-grid" @submit.prevent="submit">
      <div class="form-field">
        <label for="name">Nombre <span class="required">*</span></label>
        <InputText id="name" v-model="form.name" :invalid="!!errors.name" autofocus />
        <small v-if="errors.name" class="form-error">{{ errors.name }}</small>
      </div>

      <div class="form-field">
        <label for="position">Cargo</label>
        <InputText id="position" v-model="form.position" placeholder="Ej. Especialista facial" />
      </div>

      <div class="form-row">
        <div class="form-field">
          <label for="phone">Celular</label>
          <InputMask id="phone" v-model="form.phone" mask="999999999" placeholder="999999999" />
        </div>
        <div class="form-field">
          <label for="document">Documento</label>
          <InputText id="document" v-model="form.document_number" />
        </div>
      </div>

      <div class="form-field">
        <label for="services">Servicios que puede realizar</label>
        <MultiSelect
          id="services"
          v-model="form.service_ids"
          :options="services"
          option-label="name"
          option-value="id"
          filter
          display="chip"
          placeholder="Seleccione los servicios"
        />
      </div>

      <div class="form-field form-field--inline">
        <ToggleSwitch v-model="form.active" input-id="active" />
        <label for="active">Trabajador activo</label>
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
import { useEmployeesStore } from '@/stores/employees'
import { useServicesStore } from '@/stores/services'
import { extractMessage } from '@/composables/usePaginatedList'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputMask from 'primevue/inputmask'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import MultiSelect from 'primevue/multiselect'
import ToggleSwitch from 'primevue/toggleswitch'

const props = defineProps<{ visible: boolean; employee: any | null }>()
const emit = defineEmits<{ 'update:visible': [boolean]; saved: [string] }>()

const store = useEmployeesStore()
const servicesStore = useServicesStore()

const saving = ref(false)
const errors = ref<Record<string, string>>({})
const generalError = ref<string | null>(null)

const isEdit = computed(() => props.employee !== null)
const services = computed(() => servicesStore.services)

const emptyForm = () => ({
  name: '',
  position: '',
  phone: '',
  document_number: '',
  service_ids: [] as number[],
  active: true,
})

const form = ref(emptyForm())

// Se rellena al abrir, no al montar: el diálogo se reutiliza para crear y editar.
watch(
  () => props.visible,
  (open) => {
    if (!open) return

    errors.value = {}
    generalError.value = null

    if (servicesStore.services.length === 0) {
      servicesStore.loadServices(1)
    }

    form.value = props.employee
      ? {
          name: props.employee.name,
          position: props.employee.position ?? '',
          phone: props.employee.phone ?? '',
          document_number: props.employee.document_number ?? '',
          service_ids: (props.employee.services ?? []).map((s: any) => s.id),
          active: props.employee.active,
        }
      : emptyForm()
  }
)

const submit = async () => {
  saving.value = true
  errors.value = {}
  generalError.value = null

  try {
    const response = props.employee
      ? await store.updateEmployee(props.employee.id, form.value)
      : await store.createEmployee(form.value)

    emit('saved', response.message)
  } catch (err: any) {
    // Los errores de campo se muestran junto al input; el resto arriba (§43).
    const fieldErrors = err?.response?.data?.errors ?? {}
    errors.value = Object.fromEntries(
      Object.entries(fieldErrors).map(([key, messages]) => [key, (messages as string[])[0]])
    )
    if (Object.keys(errors.value).length === 0) {
      generalError.value = extractMessage(err, 'No se pudo guardar el trabajador.')
    }
  } finally {
    saving.value = false
  }
}
</script>
