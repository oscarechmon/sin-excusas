<template>
  <div class="rules-panel">
    <Message severity="info" :closable="false" class="rules-note">
      Se aplica la regla más específica: especialista y servicio gana sobre
      especialista, que gana sobre servicio, que gana sobre la regla general.
    </Message>

    <Card class="rule-form-card">
      <template #content>
        <h3 class="section-title">{{ editing ? 'Editar regla' : 'Nueva regla' }}</h3>

        <div class="rule-form">
          <div class="form-field">
            <label for="rule-employee">Especialista</label>
            <Select
              id="rule-employee"
              v-model="form.employee_id"
              :options="employeesStore.items"
              option-label="name"
              option-value="id"
              filter
              placeholder="Todos"
              show-clear
            />
          </div>

          <div class="form-field">
            <label for="rule-service">Servicio</label>
            <Select
              id="rule-service"
              v-model="form.service_id"
              :options="servicesStore.services"
              option-label="name"
              option-value="id"
              filter
              placeholder="Todos"
              show-clear
            />
          </div>

          <div class="form-field">
            <label for="rule-type">Tipo</label>
            <Select
              id="rule-type"
              v-model="form.type"
              :options="typeOptions"
              option-label="label"
              option-value="value"
            />
          </div>

          <div class="form-field">
            <label for="rule-value">{{ form.type === 'percentage' ? 'Porcentaje' : 'Monto fijo' }}</label>
            <InputNumber
              v-if="form.type === 'percentage'"
              id="rule-value"
              v-model="form.value"
              suffix=" %"
              :min="0"
              :max="100"
            />
            <InputNumber
              v-else
              id="rule-value"
              v-model="form.value"
              mode="currency"
              currency="PEN"
              locale="es-PE"
              :min="0"
            />
          </div>

          <div class="rule-form__actions">
            <Button v-if="editing" label="Cancelar" text severity="secondary" @click="resetForm" />
            <Button :label="editing ? 'Actualizar' : 'Agregar'" icon="pi pi-check" :loading="saving" @click="save" />
          </div>
        </div>

        <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>
      </template>
    </Card>

    <Card>
      <template #content>
        <DataTable :value="store.rules">
          <template #empty>
            <p class="table-empty">No hay reglas configuradas.</p>
          </template>

          <Column header="Alcance" :style="{ width: '180px' }">
            <template #body="{ data }">
              <Tag :value="data.scope" :severity="scopeSeverity(data.scope)" />
            </template>
          </Column>
          <Column header="Especialista">
            <template #body="{ data }">{{ data.employee?.name ?? 'Todos' }}</template>
          </Column>
          <Column header="Servicio">
            <template #body="{ data }">{{ data.service?.name ?? 'Todos' }}</template>
          </Column>
          <Column header="Comisión" :style="{ width: '130px' }">
            <template #body="{ data }">
              <span class="cell-amount cell-strong">
                {{ data.type === 'percentage' ? `${data.value}%` : format.money(data.value) }}
              </span>
            </template>
          </Column>
          <Column header="Estado" :style="{ width: '110px' }">
            <template #body="{ data }">
              <Tag
                :value="data.active ? 'Activa' : 'Inactiva'"
                :severity="data.active ? 'success' : 'secondary'"
              />
            </template>
          </Column>
          <Column header="Acciones" :style="{ width: '110px' }">
            <template #body="{ data }">
              <div class="row-actions">
                <Button icon="pi pi-pencil" text rounded severity="secondary" @click="edit(data)" />
                <Button icon="pi pi-trash" text rounded severity="danger" @click="remove(data)" />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useCommissionsStore } from '@/stores/commissions'
import { useEmployeesStore } from '@/stores/employees'
import { useServicesStore } from '@/stores/services'
import { useFormat } from '@/composables/useFormat'
import { extractMessage } from '@/composables/usePaginatedList'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import InputNumber from 'primevue/inputnumber'
import Message from 'primevue/message'
import Select from 'primevue/select'
import Tag from 'primevue/tag'

const store = useCommissionsStore()
const employeesStore = useEmployeesStore()
const servicesStore = useServicesStore()
const toast = useToast()
const format = useFormat()

const saving = ref(false)
const error = ref<string | null>(null)
const editing = ref<any | null>(null)

const typeOptions = [
  { label: 'Porcentaje', value: 'percentage' },
  { label: 'Monto fijo', value: 'fixed' },
]

const emptyForm = () => ({
  employee_id: null as number | null,
  service_id: null as number | null,
  type: 'percentage',
  value: 10,
  active: true,
})

const form = ref(emptyForm())

const scopeSeverity = (scope: string) =>
  ({
    'Especialista y servicio': 'success',
    Especialista: 'info',
    Servicio: 'warn',
    General: 'secondary',
  })[scope] ?? 'secondary'

const resetForm = () => {
  form.value = emptyForm()
  editing.value = null
  error.value = null
}

const edit = (rule: any) => {
  editing.value = rule
  form.value = {
    employee_id: rule.employee_id,
    service_id: rule.service_id,
    type: rule.type,
    value: Number(rule.value),
    active: rule.active,
  }
}

const save = async () => {
  saving.value = true
  error.value = null

  try {
    const response = editing.value
      ? await store.updateRule(editing.value.id, form.value)
      : await store.createRule(form.value)

    toast.add({ severity: 'success', summary: 'Listo', detail: response.message, life: 3000 })
    resetForm()
  } catch (err) {
    // El backend rechaza reglas duplicadas para el mismo par especialista/servicio.
    error.value = extractMessage(err, 'No se pudo guardar la regla.')
  } finally {
    saving.value = false
  }
}

const remove = async (rule: any) => {
  try {
    const response = await store.deleteRule(rule.id)
    toast.add({ severity: 'success', summary: 'Listo', detail: response.message, life: 3000 })
  } catch (err) {
    error.value = extractMessage(err, 'No se pudo eliminar la regla.')
  }
}

onMounted(() => {
  store.loadRules()
  if (employeesStore.items.length === 0) employeesStore.load({ active: true })
  if (servicesStore.services.length === 0) servicesStore.loadServices(1)
})
</script>

<style scoped lang="scss">
.rules-note {
  margin-bottom: 1rem;
}

.rule-form-card {
  margin-bottom: 1rem;
}

.section-title {
  margin: 0 0 0.75rem;
  font-size: 0.9375rem;
  font-weight: 600;
  color: #374151;
}

.rule-form {
  display: flex;
  align-items: flex-end;
  gap: 0.75rem;
  flex-wrap: wrap;

  .form-field {
    flex: 1 1 170px;
    min-width: 0;
  }

  &__actions {
    display: flex;
    gap: 0.5rem;
  }
}
</style>
