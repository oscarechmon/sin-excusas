<template>
  <Dialog :visible="visible" :header="client ? 'Editar Cliente' : 'Nuevo Cliente'" modal @hide="$emit('close')">
    <div class="form">
      <div class="form-row">
        <div class="form-group">
          <label>Nombre *</label>
          <InputText v-model="form.full_name" :class="{ 'p-invalid': errors.full_name }" />
          <small class="p-error" v-if="errors.full_name">{{ errors.full_name }}</small>
        </div>
        <div class="form-group">
          <label>Documento</label>
          <InputText v-model="form.document_number" :class="{ 'p-invalid': errors.document_number }" />
          <small class="p-error" v-if="errors.document_number">{{ errors.document_number }}</small>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Teléfono</label>
          <InputText v-model="form.phone" />
        </div>
        <div class="form-group">
          <label>WhatsApp</label>
          <InputText v-model="form.whatsapp" />
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Email</label>
          <InputText v-model="form.email" type="email" />
        </div>
        <div class="form-group">
          <label>Distrito</label>
          <InputText v-model="form.district" />
        </div>
      </div>

      <div class="form-group">
        <label>Dirección</label>
        <Textarea v-model="form.address" rows="2" />
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Fecha de Nacimiento</label>
          <DatePicker v-model="form.birth_date" date-format="dd/mm/yy" />
        </div>
        <div class="form-group">
          <label>Género</label>
          <Select v-model="form.gender" :options="genderOptions" option-label="label" option-value="value" />
        </div>
      </div>

      <div class="form-group">
        <label>Alergias</label>
        <Textarea v-model="form.allergies" rows="2" />
      </div>

      <div class="form-group">
        <label>Restricciones</label>
        <Textarea v-model="form.restrictions" rows="2" />
      </div>

      <div class="form-group">
        <label>
          <Checkbox v-model="form.active" />
          Cliente Activo
        </label>
      </div>

    </div>

    <template #footer>
      <Button label="Cancelar" severity="secondary" @click="$emit('close')" />
      <Button label="Guardar" :loading="submitting" @click="handleSubmit" />
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useClientsStore } from '@/stores/clients'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import DatePicker from 'primevue/datepicker'
import Select from 'primevue/select'
import Checkbox from 'primevue/checkbox'
import Button from 'primevue/button'

const props = defineProps({
  visible: Boolean,
  client: Object,
})

const emit = defineEmits(['close', 'save'])

const clientsStore = useClientsStore()
const submitting = ref(false)

const genderOptions = [
  { label: 'Masculino', value: 'M' },
  { label: 'Femenino', value: 'F' },
  { label: 'Otro', value: 'O' },
]

const form = ref({
  full_name: '',
  document_number: '',
  birth_date: null,
  gender: null,
  phone: '',
  whatsapp: '',
  email: '',
  district: '',
  address: '',
  how_knew: '',
  observations: '',
  allergies: '',
  restrictions: '',
  contraindications: '',
  medications: '',
  relevant_info: '',
  active: true,
})

const errors = ref<Record<string, string>>({})

watch(
  () => props.client,
  (newClient) => {
    if (newClient) {
      form.value = { ...newClient }
    } else {
      resetForm()
    }
    errors.value = {}
  }
)

const resetForm = () => {
  form.value = {
    full_name: '',
    document_number: '',
    birth_date: null,
    gender: null,
    phone: '',
    whatsapp: '',
    email: '',
    district: '',
    address: '',
    how_knew: '',
    observations: '',
    allergies: '',
    restrictions: '',
    contraindications: '',
    medications: '',
    relevant_info: '',
    active: true,
  }
}

const handleSubmit = async () => {
  errors.value = {}
  submitting.value = true

  try {
    if (props.client) {
      await clientsStore.updateClient(props.client.id, form.value)
    } else {
      await clientsStore.createClient(form.value)
    }
    emit('save')
  } catch (error: any) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    }
  } finally {
    submitting.value = false
  }
}
</script>

<style scoped lang="scss">
.form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.form-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;

  label {
    font-weight: 500;
    font-size: 0.9rem;
  }
}

:deep(.p-dialog-footer) {
  display: flex;
  gap: 0.5rem;
  justify-content: flex-end;
}
</style>
