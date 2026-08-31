<template>
  <Dialog
    :visible="visible"
    modal
    header="Abrir caja"
    :style="{ width: '420px' }"
    @update:visible="$emit('update:visible', $event)"
  >
    <form class="form-grid" @submit.prevent="submit">
      <div class="form-field">
        <label for="opening">Monto inicial en efectivo <span class="required">*</span></label>
        <InputNumber
          id="opening"
          v-model="form.opening_amount"
          mode="currency"
          currency="PEN"
          locale="es-PE"
          :min="0"
          autofocus
        />
        <small class="form-hint">Dinero con el que empieza el cajón.</small>
      </div>

      <div class="form-field">
        <label for="notes">Observaciones</label>
        <Textarea id="notes" v-model="form.notes" rows="2" auto-resize />
      </div>

      <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>
    </form>

    <template #footer>
      <Button label="Cancelar" text severity="secondary" :disabled="saving" @click="$emit('update:visible', false)" />
      <Button label="Abrir caja" icon="pi pi-unlock" :loading="saving" @click="submit" />
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { useCashStore } from '@/stores/cash'
import { extractMessage } from '@/composables/usePaginatedList'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputNumber from 'primevue/inputnumber'
import Message from 'primevue/message'
import Textarea from 'primevue/textarea'

const props = defineProps<{ visible: boolean }>()
const emit = defineEmits<{ 'update:visible': [boolean]; saved: [string] }>()

const store = useCashStore()

const saving = ref(false)
const error = ref<string | null>(null)
const form = ref({ opening_amount: 0, notes: '' })

watch(
  () => props.visible,
  (open) => {
    if (!open) return
    form.value = { opening_amount: 0, notes: '' }
    error.value = null
  }
)

const submit = async () => {
  saving.value = true
  error.value = null

  try {
    const response = await store.openSession({
      opening_amount: Number(form.value.opening_amount ?? 0),
      notes: form.value.notes || undefined,
    })
    emit('saved', response.message)
  } catch (err) {
    // Incluye el caso de caja ya abierta, que el backend explica con detalle.
    error.value = extractMessage(err, 'No se pudo abrir la caja.')
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
