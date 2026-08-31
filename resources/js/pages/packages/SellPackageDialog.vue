<template>
  <Dialog
    :visible="visible"
    modal
    header="Asignar paquete a un cliente"
    :style="{ width: '480px' }"
    @update:visible="$emit('update:visible', $event)"
  >
    <Message severity="info" :closable="false" class="dialog-note">
      Úselo para cargar paquetes ya vendidos. Para una venta nueva con cobro,
      registre la venta desde el módulo de Ventas.
    </Message>

    <form class="form-grid" @submit.prevent="submit">
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
        />
        <small v-if="errors.client_id" class="form-error">{{ errors.client_id }}</small>
      </div>

      <div class="form-field">
        <label for="package">Paquete <span class="required">*</span></label>
        <Select
          id="package"
          v-model="form.package_id"
          :options="store.items"
          option-label="name"
          option-value="id"
          filter
          placeholder="Seleccione el paquete"
          :invalid="!!errors.package_id"
          @change="applyListPrice"
        />
        <small v-if="selectedPackage" class="form-hint">
          {{ selectedPackage.total_sessions }} sesiones ·
          precio de lista {{ format.money(selectedPackage.price) }}
        </small>
      </div>

      <div class="form-row">
        <div class="form-field">
          <label for="price">Precio pactado</label>
          <InputNumber id="price" v-model="form.price" mode="currency" currency="PEN" locale="es-PE" :min="0" />
        </div>
        <div class="form-field">
          <label for="purchased">Fecha de compra</label>
          <DatePicker id="purchased" v-model="form.purchased_at" date-format="dd/mm/yy" show-icon />
        </div>
      </div>

      <Message v-if="generalError" severity="error" :closable="false">{{ generalError }}</Message>
    </form>

    <template #footer>
      <Button label="Cancelar" text severity="secondary" :disabled="saving" @click="$emit('update:visible', false)" />
      <Button label="Asignar" icon="pi pi-check" :loading="saving" @click="submit" />
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { usePackagesStore } from '@/stores/packages'
import { useClientsStore } from '@/stores/clients'
import { useFormat } from '@/composables/useFormat'
import { extractMessage } from '@/composables/usePaginatedList'
import Button from 'primevue/button'
import DatePicker from 'primevue/datepicker'
import Dialog from 'primevue/dialog'
import InputNumber from 'primevue/inputnumber'
import Message from 'primevue/message'
import Select from 'primevue/select'

const props = defineProps<{ visible: boolean }>()
const emit = defineEmits<{ 'update:visible': [boolean]; saved: [string] }>()

const store = usePackagesStore()
const clientsStore = useClientsStore()
const format = useFormat()

const saving = ref(false)
const errors = ref<Record<string, string>>({})
const generalError = ref<string | null>(null)

const form = ref({
  client_id: null as number | null,
  package_id: null as number | null,
  price: null as number | null,
  purchased_at: new Date(),
})

const selectedPackage = computed(() =>
  store.items.find((p: any) => p.id === form.value.package_id)
)

/** Al elegir paquete se propone su precio de lista, editable si hubo descuento. */
const applyListPrice = () => {
  if (selectedPackage.value) form.value.price = Number(selectedPackage.value.price)
}

watch(
  () => props.visible,
  (open) => {
    if (!open) return

    errors.value = {}
    generalError.value = null
    form.value = { client_id: null, package_id: null, price: null, purchased_at: new Date() }

    if (store.items.length === 0) store.load({ page: 1, active: true })
    if (clientsStore.clients.length === 0) clientsStore.loadClients()
  }
)

const submit = async () => {
  saving.value = true
  errors.value = {}
  generalError.value = null

  try {
    const response = await store.sellPackage({
      client_id: form.value.client_id,
      package_id: form.value.package_id,
      price: form.value.price ?? undefined,
      purchased_at: format.toIsoDate(form.value.purchased_at) ?? undefined,
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
      generalError.value = extractMessage(err, 'No se pudo asignar el paquete.')
    }
  } finally {
    saving.value = false
  }
}
</script>

<style scoped lang="scss">
.dialog-note {
  margin-bottom: 1rem;
}

.form-hint {
  font-size: 0.75rem;
  color: #6b7280;
}
</style>
