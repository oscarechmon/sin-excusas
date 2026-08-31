<template>
  <Dialog
    :visible="visible"
    modal
    header="Cerrar caja"
    :style="{ width: '460px' }"
    @update:visible="$emit('update:visible', $event)"
  >
    <div v-if="session" class="expected">
      <span class="expected__label">Esperado en efectivo</span>
      <span class="expected__value">{{ format.money(session.current_expected) }}</span>
    </div>

    <form class="form-grid" @submit.prevent="submit">
      <div class="form-field">
        <label for="counted">Monto contado <span class="required">*</span></label>
        <InputNumber
          id="counted"
          v-model="form.counted_amount"
          mode="currency"
          currency="PEN"
          locale="es-PE"
          :min="0"
          autofocus
        />
        <small class="form-hint">Cuente el dinero físico del cajón.</small>
      </div>

      <!-- La diferencia se muestra antes de confirmar: cerrar sin verla
           llevaría a descubrir el descuadre cuando ya no se puede revisar. -->
      <div v-if="difference !== null" class="difference" :class="differenceClass">
        <span>{{ differenceLabel }}</span>
        <strong>{{ format.money(Math.abs(difference)) }}</strong>
      </div>

      <div class="form-field">
        <label for="notes">Observaciones del cierre</label>
        <Textarea id="notes" v-model="form.notes" rows="2" auto-resize />
      </div>

      <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>
    </form>

    <template #footer>
      <Button label="Cancelar" text severity="secondary" :disabled="saving" @click="$emit('update:visible', false)" />
      <Button label="Cerrar caja" icon="pi pi-lock" severity="danger" :loading="saving" @click="submit" />
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useCashStore } from '@/stores/cash'
import { useFormat } from '@/composables/useFormat'
import { extractMessage } from '@/composables/usePaginatedList'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputNumber from 'primevue/inputnumber'
import Message from 'primevue/message'
import Textarea from 'primevue/textarea'

const props = defineProps<{ visible: boolean; session: any | null }>()
const emit = defineEmits<{ 'update:visible': [boolean]; saved: [string] }>()

const store = useCashStore()
const format = useFormat()

const saving = ref(false)
const error = ref<string | null>(null)
const form = ref({ counted_amount: 0, notes: '' })

const difference = computed<number | null>(() => {
  if (!props.session) return null
  return Number(form.value.counted_amount ?? 0) - Number(props.session.current_expected ?? 0)
})

const differenceClass = computed(() => {
  if (difference.value === null || Math.abs(difference.value) < 0.01) return 'difference--ok'
  return difference.value > 0 ? 'difference--over' : 'difference--short'
})

const differenceLabel = computed(() => {
  if (difference.value === null || Math.abs(difference.value) < 0.01) return 'La caja cuadra'
  return difference.value > 0 ? 'Sobrante' : 'Faltante'
})

watch(
  () => props.visible,
  (open) => {
    if (!open) return
    // Se propone el esperado: si cuadra, cerrar es un solo clic.
    form.value = { counted_amount: Number(props.session?.current_expected ?? 0), notes: '' }
    error.value = null
  }
)

const submit = async () => {
  saving.value = true
  error.value = null

  try {
    const response = await store.closeSession({
      counted_amount: Number(form.value.counted_amount ?? 0),
      notes: form.value.notes || undefined,
    })
    emit('saved', response.message)
  } catch (err) {
    error.value = extractMessage(err, 'No se pudo cerrar la caja.')
  } finally {
    saving.value = false
  }
}
</script>

<style scoped lang="scss">
.expected {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  padding: 0.75rem 1rem;
  margin-bottom: 1rem;
  border-radius: 0.5rem;
  background-color: #f3f4f6;

  &__label {
    font-size: 0.875rem;
    color: #4b5563;
  }

  &__value {
    font-size: 1.125rem;
    font-weight: 600;
    font-variant-numeric: tabular-nums;
  }
}

.difference {
  display: flex;
  justify-content: space-between;
  padding: 0.625rem 1rem;
  border-radius: 0.5rem;
  font-size: 0.875rem;

  strong {
    font-variant-numeric: tabular-nums;
  }

  &--ok {
    background-color: #ecfdf5;
    color: #047857;
  }

  &--over {
    background-color: #eff6ff;
    color: #1d4ed8;
  }

  &--short {
    background-color: #fef2f2;
    color: #b91c1c;
  }
}

.form-hint {
  font-size: 0.75rem;
  color: #6b7280;
}
</style>
