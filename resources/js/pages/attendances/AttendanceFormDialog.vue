<template>
  <Dialog
    :visible="visible"
    modal
    header="Registrar atención"
    :style="{ width: '680px' }"
    @update:visible="$emit('update:visible', $event)"
  >
    <form class="form-grid" @submit.prevent="submit">
      <div class="form-row">
        <div class="form-field">
          <label for="client">Cliente <span class="required">*</span></label>
          <Select
            id="client"
            v-model="form.client_id"
            :options="clientsStore.clients"
            option-label="full_name"
            option-value="id"
            filter
            placeholder="Seleccione el cliente"
            :invalid="!!errors.client_id"
            @change="onClientChange"
          />
          <small v-if="errors.client_id" class="form-error">{{ errors.client_id }}</small>
        </div>

        <div class="form-field">
          <label for="date">Fecha <span class="required">*</span></label>
          <DatePicker id="date" v-model="form.attended_at" date-format="dd/mm/yy" show-icon />
        </div>
      </div>

      <div class="form-row">
        <div class="form-field">
          <label for="service">Servicio <span class="required">*</span></label>
          <Select
            id="service"
            v-model="form.service_id"
            :options="servicesStore.services"
            option-label="name"
            option-value="id"
            filter
            placeholder="Seleccione el servicio"
            :invalid="!!errors.service_id"
            @change="onServiceChange"
          />
          <small v-if="errors.service_id" class="form-error">{{ errors.service_id }}</small>
        </div>

        <div class="form-field">
          <label for="employee">Especialista <span class="required">*</span></label>
          <Select
            id="employee"
            v-model="form.employee_id"
            :options="employeesStore.items"
            option-label="name"
            option-value="id"
            filter
            placeholder="Seleccione el especialista"
            :invalid="!!errors.employee_id"
          />
          <small v-if="errors.employee_id" class="form-error">{{ errors.employee_id }}</small>
        </div>
      </div>

      <div class="form-field">
        <label for="package">Descontar de un paquete</label>
        <Select
          id="package"
          v-model="form.client_package_id"
          :options="availablePackages"
          option-label="label"
          option-value="id"
          placeholder="No usar paquete (servicio suelto)"
          show-clear
          :disabled="!form.client_id"
          :invalid="!!errors.client_package_id"
        />
        <small v-if="errors.client_package_id" class="form-error">{{ errors.client_package_id }}</small>
        <small v-else-if="!form.client_id" class="form-hint">Elija primero un cliente.</small>
        <small v-else-if="availablePackages.length === 0" class="form-hint">
          Este cliente no tiene paquetes con sesiones disponibles.
        </small>
      </div>

      <!-- Insumos: se precargan del servicio y las cantidades son editables,
           porque lo que se descuenta es lo realmente usado (§20). -->
      <div v-if="store.suggestedSupplies.length > 0" class="supplies">
        <div class="supplies__header">
          <h3 class="supplies__title">Insumos utilizados</h3>
          <small class="form-hint">Ajuste las cantidades a lo realmente consumido.</small>
        </div>

        <div v-for="supply in store.suggestedSupplies" :key="supply.inventory_item_id" class="supply-row">
          <div class="supply-row__info">
            <span class="supply-row__name">{{ supply.name }}</span>
            <span class="supply-row__stock" :class="{ 'supply-row__stock--short': isShort(supply) }">
              Stock: {{ format.quantity(supply.stock, supply.unit) }}
            </span>
          </div>
          <InputNumber
            v-model="supply.quantity"
            :min="0"
            :max-fraction-digits="2"
            :suffix="` ${supply.unit}`"
            class="supply-row__input"
          />
        </div>

        <Message v-if="anyShort" severity="warn" :closable="false">
          Algún insumo no tiene stock suficiente; el sistema rechazará la atención.
        </Message>
      </div>

      <div class="form-field">
        <label for="observations">Observaciones</label>
        <Textarea id="observations" v-model="form.observations" rows="2" auto-resize />
      </div>

      <div class="form-field">
        <label for="measurements">Medidas</label>
        <Textarea id="measurements" v-model="form.measurements" rows="2" auto-resize />
      </div>

      <Message v-if="generalError" severity="error" :closable="false">{{ generalError }}</Message>
    </form>

    <template #footer>
      <Button label="Cancelar" text severity="secondary" :disabled="saving" @click="$emit('update:visible', false)" />
      <Button label="Confirmar atención" icon="pi pi-check" :loading="saving" @click="submit" />
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useAttendancesStore } from '@/stores/attendances'
import { useClientsStore } from '@/stores/clients'
import { useEmployeesStore } from '@/stores/employees'
import { usePackagesStore } from '@/stores/packages'
import { useServicesStore } from '@/stores/services'
import { useFormat } from '@/composables/useFormat'
import { extractMessage } from '@/composables/usePaginatedList'
import Button from 'primevue/button'
import DatePicker from 'primevue/datepicker'
import Dialog from 'primevue/dialog'
import InputNumber from 'primevue/inputnumber'
import Message from 'primevue/message'
import Select from 'primevue/select'
import Textarea from 'primevue/textarea'

const props = defineProps<{ visible: boolean }>()
const emit = defineEmits<{ 'update:visible': [boolean]; saved: [string] }>()

const store = useAttendancesStore()
const clientsStore = useClientsStore()
const employeesStore = useEmployeesStore()
const packagesStore = usePackagesStore()
const servicesStore = useServicesStore()
const format = useFormat()

const saving = ref(false)
const errors = ref<Record<string, string>>({})
const generalError = ref<string | null>(null)

const emptyForm = () => ({
  client_id: null as number | null,
  service_id: null as number | null,
  employee_id: null as number | null,
  client_package_id: null as number | null,
  attended_at: new Date(),
  observations: '',
  measurements: '',
})

const form = ref(emptyForm())

/** Solo paquetes del cliente elegido que aún pueden consumir una sesión. */
const availablePackages = computed(() =>
  packagesStore.clientPackages
    .filter((p: any) => p.client_id === form.value.client_id && p.can_consume)
    .map((p: any) => ({
      id: p.id,
      label: `${p.package_name} — ${p.remaining_sessions} sesión(es) libre(s)`,
    }))
)

const isShort = (supply: any) => Number(supply.quantity ?? 0) > Number(supply.stock ?? 0)
const anyShort = computed(() => store.suggestedSupplies.some(isShort))

const onClientChange = () => {
  form.value.client_package_id = null
  if (form.value.client_id) {
    packagesStore.loadClientPackages({ client_id: form.value.client_id, consumable: true })
  }
}

const onServiceChange = () => {
  if (form.value.service_id) {
    store.loadSuppliesForService(form.value.service_id)
  } else {
    store.clearSupplies()
  }
}

watch(
  () => props.visible,
  (open) => {
    if (!open) {
      store.clearSupplies()
      return
    }

    errors.value = {}
    generalError.value = null
    form.value = emptyForm()
    store.clearSupplies()

    if (clientsStore.clients.length === 0) clientsStore.loadClients()
    if (servicesStore.services.length === 0) servicesStore.loadServices(1)
    if (employeesStore.items.length === 0) employeesStore.load({ active: true })
  }
)

const submit = async () => {
  saving.value = true
  errors.value = {}
  generalError.value = null

  try {
    const response = await store.confirmAttendance({
      client_id: form.value.client_id,
      service_id: form.value.service_id,
      employee_id: form.value.employee_id,
      client_package_id: form.value.client_package_id ?? undefined,
      attended_at: format.toIsoDate(form.value.attended_at),
      observations: form.value.observations || undefined,
      measurements: form.value.measurements || undefined,
      // Solo se envían los insumos con cantidad; los puestos a 0 no se usaron.
      supplies: store.suggestedSupplies
        .filter((s: any) => Number(s.quantity) > 0)
        .map((s: any) => ({ inventory_item_id: s.inventory_item_id, quantity: Number(s.quantity) })),
    })

    emit('saved', response.message)
  } catch (err: any) {
    const fieldErrors = err?.response?.data?.errors ?? {}
    errors.value = Object.fromEntries(
      Object.entries(fieldErrors)
        .filter(([, messages]) => Array.isArray(messages))
        .map(([key, messages]) => [key, (messages as string[])[0]])
    )
    if (Object.keys(errors.value).length === 0) {
      generalError.value = extractMessage(err, 'No se pudo registrar la atención.')
    }
  } finally {
    saving.value = false
  }
}
</script>

<style scoped lang="scss">
.supplies {
  padding: 1rem;
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
  background-color: #f9fafb;

  &__header {
    margin-bottom: 0.75rem;
  }

  &__title {
    margin: 0;
    font-size: 0.9375rem;
    font-weight: 600;
    color: #374151;
  }
}

.supply-row {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.5rem 0;
  border-bottom: 1px solid #e5e7eb;

  &:last-of-type {
    border-bottom: none;
  }

  &__info {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-width: 0;
  }

  &__name {
    font-size: 0.875rem;
    color: #111827;
  }

  &__stock {
    font-size: 0.75rem;
    color: #6b7280;

    &--short {
      color: #b91c1c;
      font-weight: 600;
    }
  }

  &__input {
    width: 150px;
    flex-shrink: 0;
  }
}

.form-hint {
  font-size: 0.75rem;
  color: #6b7280;
}
</style>
