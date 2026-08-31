<template>
  <Dialog
    :visible="visible"
    modal
    header="Registrar egreso"
    :style="{ width: '420px' }"
    @update:visible="$emit('update:visible', $event)"
  >
    <form class="form-grid" @submit.prevent="submit">
      <div class="form-field">
        <label for="amount">Monto <span class="required">*</span></label>
        <InputNumber
          id="amount"
          v-model="form.amount"
          mode="currency"
          currency="PEN"
          locale="es-PE"
          :min="0"
          autofocus
        />
      </div>

      <div class="form-field">
        <label for="description">Motivo <span class="required">*</span></label>
        <InputText id="description" v-model="form.description" placeholder="Ej. compra de insumos" />
        <small class="form-hint">Queda registrado en el histórico de la caja.</small>
      </div>

      <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>
    </form>

    <template #footer>
      <Button label="Cancelar" text severity="secondary" :disabled="saving" @click="$emit('update:visible', false)" />
      <Button label="Registrar egreso" icon="pi pi-check" :loading="saving" @click="submit" />
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
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'

const props = defineProps<{ visible: boolean }>()
const emit = defineEmits<{ 'update:visible': [boolean]; saved: [string] }>()

const store = useCashStore()

const saving = ref(false)
const error = ref<string | null>(null)
const form = ref({ amount: 0, description: '' })

watch(
  () => props.visible,
  (open) => {
    if (!open) return
    form.value = { amount: 0, description: '' }
    error.value = null
  }
)

const submit = async () => {
  saving.value = true
  error.value = null

  try {
    const response = await store.registerExpense({
      amount: Number(form.value.amount ?? 0),
      description: form.value.description,
    })
    emit('saved', response.message)
  } catch (err) {
    error.value = extractMessage(err, 'No se pudo registrar el egreso.')
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
