<template>
  <Dialog
    :visible="visible"
    modal
    header="Registrar pago"
    :style="{ width: '440px' }"
    @update:visible="$emit('update:visible', $event)"
  >
    <div v-if="sale" class="payment-context">
      <span>Venta <strong>{{ sale.code }}</strong></span>
      <span>Saldo pendiente: <strong>{{ format.money(sale.balance) }}</strong></span>
    </div>

    <form class="form-grid" @submit.prevent="submit">
      <div class="form-field">
        <label for="method">Método de pago <span class="required">*</span></label>
        <Select
          id="method"
          v-model="form.payment_method_id"
          :options="store.paymentMethods"
          option-label="name"
          option-value="id"
          placeholder="Seleccione el método"
          :invalid="!!errors.payment_method_id"
        />
        <small v-if="errors.payment_method_id" class="form-error">{{ errors.payment_method_id }}</small>
      </div>

      <div class="form-field">
        <label for="amount">Monto <span class="required">*</span></label>
        <InputNumber
          id="amount"
          v-model="form.amount"
          mode="currency"
          currency="PEN"
          locale="es-PE"
          :min="0"
          :max="maxAmount"
          :invalid="!!errors.amount"
        />
        <small v-if="errors.amount" class="form-error">{{ errors.amount }}</small>
        <small v-else class="form-hint">No puede superar el saldo pendiente.</small>
      </div>

      <div v-if="needsReference" class="form-field">
        <label for="reference">Número de operación <span class="required">*</span></label>
        <InputText id="reference" v-model="form.reference" :invalid="!!errors.reference" />
        <small v-if="errors.reference" class="form-error">{{ errors.reference }}</small>
      </div>

      <Message v-if="generalError" severity="error" :closable="false">{{ generalError }}</Message>
    </form>

    <template #footer>
      <Button label="Cancelar" text severity="secondary" :disabled="saving" @click="$emit('update:visible', false)" />
      <Button label="Registrar pago" icon="pi pi-check" :loading="saving" @click="submit" />
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useSalesStore } from '@/stores/sales'
import { useFormat } from '@/composables/useFormat'
import { extractMessage } from '@/composables/usePaginatedList'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputNumber from 'primevue/inputnumber'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import Select from 'primevue/select'

const props = defineProps<{ visible: boolean; sale: any | null }>()
const emit = defineEmits<{ 'update:visible': [boolean]; saved: [string] }>()

const store = useSalesStore()
const format = useFormat()

const saving = ref(false)
const errors = ref<Record<string, string>>({})
const generalError = ref<string | null>(null)

const form = ref({ payment_method_id: null as number | null, amount: 0, reference: '' })

const maxAmount = computed(() => Number(props.sale?.balance ?? 0))

const needsReference = computed(
  () =>
    store.paymentMethods.find((m: any) => m.id === form.value.payment_method_id)
      ?.requires_reference ?? false
)

watch(
  () => props.visible,
  (open) => {
    if (!open) return

    errors.value = {}
    generalError.value = null
    // Se propone el saldo completo: es el caso habitual al terminar de cobrar.
    form.value = { payment_method_id: null, amount: maxAmount.value, reference: '' }

    if (store.paymentMethods.length === 0) store.loadPaymentMethods()
  }
)

const submit = async () => {
  if (!props.sale) return

  saving.value = true
  errors.value = {}
  generalError.value = null

  try {
    const response = await store.addPayment(props.sale.id, {
      payment_method_id: form.value.payment_method_id,
      amount: form.value.amount,
      reference: form.value.reference || undefined,
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
      generalError.value = extractMessage(err, 'No se pudo registrar el pago.')
    }
  } finally {
    saving.value = false
  }
}
</script>

<style scoped lang="scss">
.payment-context {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.75rem 1rem;
  margin-bottom: 1rem;
  border-radius: 0.5rem;
  background-color: #f3f4f6;
  font-size: 0.875rem;
}

.form-hint {
  font-size: 0.75rem;
  color: #6b7280;
}
</style>
