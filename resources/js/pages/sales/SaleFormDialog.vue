<template>
  <Dialog
    :visible="visible"
    modal
    header="Nueva venta"
    :style="{ width: '960px' }"
    :breakpoints="{ '1000px': '96vw' }"
    @update:visible="$emit('update:visible', $event)"
  >
    <form class="form-grid" @submit.prevent="submit">
      <div class="form-row">
        <div class="form-field">
          <label for="client">Cliente</label>
          <Select
            id="client"
            v-model="form.client_id"
            :options="clientsStore.clients"
            option-label="full_name"
            option-value="id"
            filter
            placeholder="Cliente ocasional"
            show-clear
            :invalid="!!errors.client_id"
          />
          <small v-if="errors.client_id" class="form-error">{{ errors.client_id }}</small>
          <small v-else class="form-hint">Obligatorio si la venta incluye un paquete.</small>
        </div>
        <div class="form-field">
          <label for="discount">Descuento global</label>
          <InputNumber id="discount" v-model="form.discount" mode="currency" currency="PEN" locale="es-PE" :min="0" />
        </div>
      </div>

      <!-- Conceptos: la venta puede mezclar servicio, producto y paquete (§21) -->
      <section class="lines">
        <div class="lines__header">
          <h3 class="lines__title">Conceptos</h3>
          <div class="lines__add">
            <Select
              v-model="newItemType"
              :options="itemTypes"
              option-label="label"
              option-value="value"
              class="lines__type"
            />
            <Button label="Agregar" icon="pi pi-plus" size="small" outlined @click="addLine" />
          </div>
        </div>

        <Message v-if="errors.items" severity="error" :closable="false">{{ errors.items }}</Message>

        <p v-if="form.items.length === 0" class="lines__empty">
          Agregue al menos un servicio, producto o paquete.
        </p>

        <div v-for="(line, index) in form.items" :key="index" class="line">
          <Select
            v-model="line.id"
            :options="optionsFor(line.type)"
            option-label="name"
            option-value="id"
            filter
            :placeholder="placeholderFor(line.type)"
            class="line__item"
            @change="applyPrice(line)"
          />
          <InputNumber v-model="line.quantity" :min="0.01" :max-fraction-digits="2" class="line__qty" />
          <InputNumber
            v-model="line.unit_price"
            mode="currency"
            currency="PEN"
            locale="es-PE"
            :min="0"
            class="line__price"
          />
          <Select
            v-model="line.employee_id"
            :options="employeesStore.items"
            option-label="name"
            option-value="id"
            placeholder="Especialista"
            show-clear
            class="line__employee"
          />
          <span class="line__subtotal">{{ format.money(lineSubtotal(line)) }}</span>
          <Button icon="pi pi-trash" text rounded severity="danger" @click="form.items.splice(index, 1)" />
        </div>
      </section>

      <!-- Pagos opcionales: la venta puede quedar con saldo pendiente (§22) -->
      <section class="lines">
        <div class="lines__header">
          <h3 class="lines__title">Pagos (opcional)</h3>
          <Button label="Agregar pago" icon="pi pi-plus" size="small" outlined @click="addPayment" />
        </div>

        <div v-for="(payment, index) in form.payments" :key="`p-${index}`" class="line">
          <Select
            v-model="payment.payment_method_id"
            :options="store.paymentMethods"
            option-label="name"
            option-value="id"
            placeholder="Método"
            class="line__item"
            @change="onMethodChange(payment)"
          />
          <InputNumber
            v-model="payment.amount"
            mode="currency"
            currency="PEN"
            locale="es-PE"
            :min="0"
            class="line__price"
          />
          <InputText
            v-model="payment.reference"
            :placeholder="requiresReference(payment) ? 'N.º de operación *' : 'Referencia'"
            class="line__employee"
          />
          <Button icon="pi pi-trash" text rounded severity="danger" @click="form.payments.splice(index, 1)" />
        </div>
      </section>

      <div class="totals">
        <div class="totals__row">
          <span>Subtotal</span><strong>{{ format.money(subtotal) }}</strong>
        </div>
        <div class="totals__row">
          <span>Descuento</span><strong>− {{ format.money(form.discount ?? 0) }}</strong>
        </div>
        <div class="totals__row totals__row--main">
          <span>Total</span><strong>{{ format.money(total) }}</strong>
        </div>
        <div class="totals__row">
          <span>Pagado ahora</span><strong>{{ format.money(paid) }}</strong>
        </div>
        <div class="totals__row" :class="{ 'totals__row--due': balance > 0 }">
          <span>Saldo pendiente</span><strong>{{ format.money(balance) }}</strong>
        </div>
      </div>

      <Message v-if="overpaid" severity="warn" :closable="false">
        Los pagos superan el total de la venta; el sistema los rechazará.
      </Message>
      <Message v-if="generalError" severity="error" :closable="false">{{ generalError }}</Message>
    </form>

    <template #footer>
      <Button label="Cancelar" text severity="secondary" :disabled="saving" @click="$emit('update:visible', false)" />
      <Button
        label="Registrar venta"
        icon="pi pi-check"
        :loading="saving"
        :disabled="form.items.length === 0 || overpaid"
        @click="submit"
      />
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useSalesStore } from '@/stores/sales'
import { useClientsStore } from '@/stores/clients'
import { useEmployeesStore } from '@/stores/employees'
import { useInventoryStore } from '@/stores/inventory'
import { usePackagesStore } from '@/stores/packages'
import { useServicesStore } from '@/stores/services'
import { useFormat } from '@/composables/useFormat'
import { extractMessage } from '@/composables/usePaginatedList'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputNumber from 'primevue/inputnumber'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import Select from 'primevue/select'

const props = defineProps<{ visible: boolean }>()
const emit = defineEmits<{ 'update:visible': [boolean]; saved: [string] }>()

const store = useSalesStore()
const clientsStore = useClientsStore()
const employeesStore = useEmployeesStore()
const inventoryStore = useInventoryStore()
const packagesStore = usePackagesStore()
const servicesStore = useServicesStore()
const format = useFormat()

const saving = ref(false)
const errors = ref<Record<string, string>>({})
const generalError = ref<string | null>(null)
const newItemType = ref('service')

const itemTypes = [
  { label: 'Servicio', value: 'service' },
  { label: 'Producto', value: 'product' },
  { label: 'Paquete', value: 'package' },
]

interface SaleLine {
  type: string
  id: number | null
  quantity: number
  unit_price: number
  employee_id: number | null
}

const emptyForm = () => ({
  client_id: null as number | null,
  discount: 0,
  items: [] as SaleLine[],
  payments: [] as { payment_method_id: number | null; amount: number; reference: string }[],
})

const form = ref(emptyForm())

const optionsFor = (type: string) => {
  if (type === 'product') return inventoryStore.items.filter((i: any) => i.is_sellable)
  if (type === 'package') return packagesStore.items
  return servicesStore.services
}

const placeholderFor = (type: string) =>
  ({ product: 'Seleccione el producto', package: 'Seleccione el paquete' })[type] ??
  'Seleccione el servicio'

const addLine = () => {
  form.value.items.push({
    type: newItemType.value,
    id: null,
    quantity: 1,
    unit_price: 0,
    employee_id: null,
  })
}

const addPayment = () => {
  // Se propone el saldo restante: es lo que se cobra casi siempre.
  form.value.payments.push({ payment_method_id: null, amount: Math.max(0, balance.value), reference: '' })
}

/** Al elegir el ítem se trae su precio; sigue siendo editable por si se pacta otro. */
const applyPrice = (line: SaleLine) => {
  const option: any = optionsFor(line.type).find((o: any) => o.id === line.id)
  if (!option) return
  line.unit_price = Number(line.type === 'product' ? (option.sale_price ?? 0) : option.price)
}

const requiresReference = (payment: { payment_method_id: number | null }) =>
  store.paymentMethods.find((m: any) => m.id === payment.payment_method_id)?.requires_reference ?? false

const onMethodChange = (payment: any) => {
  if (!requiresReference(payment)) payment.reference = ''
}

const lineSubtotal = (line: SaleLine) =>
  Math.max(0, Number(line.unit_price ?? 0) * Number(line.quantity ?? 0))

const subtotal = computed(() =>
  form.value.items.reduce((sum, line) => sum + lineSubtotal(line), 0)
)
const total = computed(() => Math.max(0, subtotal.value - Number(form.value.discount ?? 0)))
const paid = computed(() =>
  form.value.payments.reduce((sum, p) => sum + Number(p.amount ?? 0), 0)
)
const balance = computed(() => total.value - paid.value)
const overpaid = computed(() => paid.value > total.value + 0.001)

watch(
  () => props.visible,
  (open) => {
    if (!open) return

    errors.value = {}
    generalError.value = null
    form.value = emptyForm()

    if (clientsStore.clients.length === 0) clientsStore.loadClients()
    if (servicesStore.services.length === 0) servicesStore.loadServices(1)
    if (employeesStore.items.length === 0) employeesStore.load({ active: true })
    if (inventoryStore.items.length === 0) inventoryStore.load({ sellable: true })
    if (packagesStore.items.length === 0) packagesStore.load({ active: true })
    if (store.paymentMethods.length === 0) store.loadPaymentMethods()
  }
)

const submit = async () => {
  saving.value = true
  errors.value = {}
  generalError.value = null

  try {
    const response = await store.createSale({
      client_id: form.value.client_id ?? undefined,
      discount: form.value.discount || 0,
      items: form.value.items.map((line) => ({
        type: line.type,
        id: line.id,
        quantity: line.quantity,
        unit_price: line.unit_price,
        employee_id: line.employee_id ?? undefined,
      })),
      payments: form.value.payments
        .filter((p) => p.payment_method_id && Number(p.amount) > 0)
        .map((p) => ({
          payment_method_id: p.payment_method_id,
          amount: p.amount,
          reference: p.reference || undefined,
        })),
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
      generalError.value = extractMessage(err, 'No se pudo registrar la venta.')
    }
  } finally {
    saving.value = false
  }
}
</script>

<style scoped lang="scss">
.lines {
  padding: 1rem;
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;

  &__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    margin-bottom: 0.75rem;
  }

  &__title {
    margin: 0;
    font-size: 0.9375rem;
    font-weight: 600;
    color: #374151;
  }

  &__add {
    display: flex;
    gap: 0.5rem;
    align-items: center;
  }

  &__type {
    width: 140px;
  }

  &__empty {
    margin: 0;
    font-size: 0.875rem;
    color: #6b7280;
  }
}

.line {
  display: grid;
  grid-template-columns: minmax(0, 2.2fr) 6rem 8.5rem minmax(0, 1.4fr) 6.5rem 2.5rem;
  align-items: center;
  gap: 0.5rem;
  padding: 0.375rem 0;

  // Los InputNumber y Select de PrimeVue traen ancho propio: se fuerza a que
  // ocupen su celda para que la fila no se desborde.
  :deep(.p-select),
  :deep(.p-inputnumber),
  :deep(.p-inputtext) {
    width: 100%;
    min-width: 0;
  }

  &__subtotal {
    text-align: right;
    font-variant-numeric: tabular-nums;
    font-weight: 600;
  }

  @media (max-width: 860px) {
    grid-template-columns: minmax(0, 1fr) 6rem 8.5rem 2.5rem;

    &__employee {
      grid-column: 1 / 3;
    }

    &__subtotal {
      grid-column: 3 / 5;
    }
  }
}

.totals {
  padding: 1rem;
  border-radius: 0.5rem;
  background-color: #f9fafb;

  &__row {
    display: flex;
    justify-content: space-between;
    padding: 0.25rem 0;
    font-size: 0.875rem;
    color: #4b5563;

    strong {
      font-variant-numeric: tabular-nums;
    }

    &--main {
      font-size: 1.05rem;
      color: #111827;
      border-top: 1px solid #e5e7eb;
      margin-top: 0.25rem;
      padding-top: 0.5rem;
    }

    &--due strong {
      color: #b45309;
    }
  }
}

.form-hint {
  font-size: 0.75rem;
  color: #6b7280;
}
</style>
