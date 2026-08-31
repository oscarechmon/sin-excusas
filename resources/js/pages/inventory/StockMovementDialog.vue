<template>
  <Dialog
    :visible="visible"
    modal
    header="Registrar movimiento de stock"
    :style="{ width: '480px' }"
    @update:visible="$emit('update:visible', $event)"
  >
    <div v-if="item" class="movement-context">
      <span class="movement-context__name">{{ item.name }}</span>
      <span class="movement-context__stock">
        Stock actual: <strong>{{ format.quantity(item.stock, item.unit) }}</strong>
      </span>
    </div>

    <form class="form-grid" @submit.prevent="submit">
      <div class="form-field">
        <label for="type">Tipo de movimiento <span class="required">*</span></label>
        <Select
          id="type"
          v-model="form.type"
          :options="movementTypes"
          option-label="label"
          option-value="value"
          :invalid="!!errors.type"
        />
        <small class="form-hint">{{ typeHint }}</small>
      </div>

      <div class="form-field">
        <label for="quantity">
          {{ isAdjustment ? 'Stock real contado' : 'Cantidad' }} <span class="required">*</span>
        </label>
        <InputNumber
          id="quantity"
          v-model="form.quantity"
          :min="0"
          :max-fraction-digits="2"
          :suffix="item ? ` ${item.unit}` : undefined"
          :invalid="!!errors.quantity"
        />
        <small v-if="errors.quantity" class="form-error">{{ errors.quantity }}</small>
        <small v-else-if="resultingStock !== null" class="form-hint">
          Stock resultante: <strong>{{ format.quantity(resultingStock, item?.unit) }}</strong>
        </small>
      </div>

      <div class="form-field">
        <label for="notes">Motivo</label>
        <Textarea id="notes" v-model="form.notes" rows="2" auto-resize />
      </div>

      <Message v-if="generalError" severity="error" :closable="false">{{ generalError }}</Message>
      <Message v-if="wouldGoNegative" severity="warn" :closable="false">
        Esa salida dejaría el stock en negativo; el sistema la rechazará.
      </Message>
    </form>

    <template #footer>
      <Button
        label="Cancelar"
        text
        severity="secondary"
        :disabled="saving"
        @click="$emit('update:visible', false)"
      />
      <Button label="Registrar" icon="pi pi-check" :loading="saving" @click="submit" />
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useInventoryStore } from '@/stores/inventory'
import { useFormat } from '@/composables/useFormat'
import { extractMessage } from '@/composables/usePaginatedList'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputNumber from 'primevue/inputnumber'
import Message from 'primevue/message'
import Select from 'primevue/select'
import Textarea from 'primevue/textarea'

const props = defineProps<{ visible: boolean; item: any | null }>()
const emit = defineEmits<{ 'update:visible': [boolean]; saved: [string] }>()

const store = useInventoryStore()
const format = useFormat()

const saving = ref(false)
const errors = ref<Record<string, string>>({})
const generalError = ref<string | null>(null)

// Solo los tipos manuales: el consumo por atención y la venta los genera el
// sistema y registrarlos a mano descuadraría el saldo (§24).
const movementTypes = [
  { label: 'Compra (entrada)', value: 'purchase' },
  { label: 'Entrada manual', value: 'manual_in' },
  { label: 'Salida manual', value: 'manual_out' },
  { label: 'Ajuste por conteo', value: 'adjustment' },
]

const form = ref({ type: 'purchase', quantity: 0, notes: '' })

const isAdjustment = computed(() => form.value.type === 'adjustment')

const typeHint = computed(() => {
  switch (form.value.type) {
    case 'adjustment':
      return 'Indique el stock real que contó; el sistema calcula la diferencia.'
    case 'manual_out':
      return 'Salida que no corresponde a una atención ni a una venta (merma, préstamo).'
    case 'manual_in':
      return 'Entrada que no proviene de una compra registrada.'
    default:
      return 'Ingreso de mercadería comprada al proveedor.'
  }
})

/** Previsualiza el saldo para que nadie confirme un movimiento a ciegas. */
const resultingStock = computed<number | null>(() => {
  if (!props.item) return null

  const current = Number(props.item.stock)
  const quantity = Number(form.value.quantity ?? 0)

  if (isAdjustment.value) return quantity
  if (form.value.type === 'manual_out') return current - quantity
  return current + quantity
})

const wouldGoNegative = computed(() => resultingStock.value !== null && resultingStock.value < 0)

watch(
  () => props.visible,
  (open) => {
    if (!open) return
    form.value = { type: 'purchase', quantity: 0, notes: '' }
    errors.value = {}
    generalError.value = null
  }
)

const submit = async () => {
  if (!props.item) return

  saving.value = true
  errors.value = {}
  generalError.value = null

  try {
    const response = await store.adjustStock(props.item.id, {
      type: form.value.type,
      quantity: Number(form.value.quantity ?? 0),
      notes: form.value.notes || undefined,
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
      generalError.value = extractMessage(err, 'No se pudo registrar el movimiento.')
    }
  } finally {
    saving.value = false
  }
}
</script>

<style scoped lang="scss">
.movement-context {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  gap: 1rem;
  padding: 0.75rem 1rem;
  margin-bottom: 1rem;
  border-radius: 0.5rem;
  background-color: #f3f4f6;

  &__name {
    font-weight: 600;
  }

  &__stock {
    font-size: 0.875rem;
    color: #4b5563;
  }
}

.form-hint {
  font-size: 0.75rem;
  color: #6b7280;
}
</style>
