<template>
  <Dialog
    :visible="visible"
    modal
    :header="isEdit ? 'Editar producto' : 'Nuevo producto'"
    :style="{ width: '580px' }"
    @update:visible="$emit('update:visible', $event)"
  >
    <form class="form-grid" @submit.prevent="submit">
      <div class="form-field">
        <label for="name">Nombre <span class="required">*</span></label>
        <InputText id="name" v-model="form.name" :invalid="!!errors.name" autofocus />
        <small v-if="errors.name" class="form-error">{{ errors.name }}</small>
      </div>

      <div class="form-row">
        <div class="form-field">
          <label for="category">Categoría</label>
          <Select
            id="category"
            v-model="form.category_id"
            :options="store.categories"
            option-label="name"
            option-value="id"
            placeholder="Sin categoría"
            show-clear
          />
        </div>
        <div class="form-field">
          <label for="unit">Unidad de medida <span class="required">*</span></label>
          <Select
            id="unit"
            v-model="form.unit"
            :options="units"
            editable
            placeholder="unidad"
            :invalid="!!errors.unit"
          />
        </div>
      </div>

      <div class="form-row">
        <div class="form-field">
          <label for="stock">Stock inicial</label>
          <InputNumber
            id="stock"
            v-model="form.stock"
            :min="0"
            :max-fraction-digits="2"
            :disabled="isEdit"
          />
          <small class="form-hint">
            {{
              isEdit
                ? 'El stock se modifica con movimientos, no editando el producto.'
                : 'Se registrará como movimiento de compra inicial.'
            }}
          </small>
        </div>
        <div class="form-field">
          <label for="min_stock">Stock mínimo <span class="required">*</span></label>
          <InputNumber id="min_stock" v-model="form.min_stock" :min="0" :max-fraction-digits="2" />
          <small class="form-hint">Por debajo de este valor aparece la alerta.</small>
        </div>
      </div>

      <div class="form-row">
        <div class="form-field">
          <label for="cost">Costo <span class="required">*</span></label>
          <InputNumber id="cost" v-model="form.cost" mode="currency" currency="PEN" locale="es-PE" :min="0" />
        </div>
        <div class="form-field">
          <label for="sale_price">Precio de venta</label>
          <InputNumber
            id="sale_price"
            v-model="form.sale_price"
            mode="currency"
            currency="PEN"
            locale="es-PE"
            :min="0"
            :disabled="!form.is_sellable"
          />
        </div>
      </div>

      <div class="form-field">
        <label for="supplier">Proveedor</label>
        <InputText id="supplier" v-model="form.supplier" />
      </div>

      <div class="form-field form-field--inline">
        <ToggleSwitch v-model="form.is_sellable" input-id="is_sellable" />
        <label for="is_sellable">Se vende al cliente (no es solo insumo interno)</label>
      </div>

      <div class="form-field form-field--inline">
        <ToggleSwitch v-model="form.active" input-id="active" />
        <label for="active">Producto activo</label>
      </div>

      <Message v-if="generalError" severity="error" :closable="false">{{ generalError }}</Message>
    </form>

    <template #footer>
      <Button
        label="Cancelar"
        text
        severity="secondary"
        :disabled="saving"
        @click="$emit('update:visible', false)"
      />
      <Button label="Guardar" icon="pi pi-check" :loading="saving" @click="submit" />
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useInventoryStore } from '@/stores/inventory'
import { extractMessage } from '@/composables/usePaginatedList'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputNumber from 'primevue/inputnumber'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import Select from 'primevue/select'
import ToggleSwitch from 'primevue/toggleswitch'

const props = defineProps<{ visible: boolean; item: any | null }>()
const emit = defineEmits<{ 'update:visible': [boolean]; saved: [string] }>()

const store = useInventoryStore()

const saving = ref(false)
const errors = ref<Record<string, string>>({})
const generalError = ref<string | null>(null)

const isEdit = computed(() => props.item !== null)
const units = ['unidad', 'par', 'caja', 'ml', 'gr', 'litro']

const emptyForm = () => ({
  name: '',
  category_id: null as number | null,
  unit: 'unidad',
  stock: 0,
  min_stock: 0,
  cost: 0,
  sale_price: null as number | null,
  supplier: '',
  is_sellable: false,
  active: true,
})

const form = ref(emptyForm())

watch(
  () => props.visible,
  (open) => {
    if (!open) return

    errors.value = {}
    generalError.value = null

    form.value = props.item
      ? {
          name: props.item.name,
          category_id: props.item.category_id,
          unit: props.item.unit,
          stock: Number(props.item.stock),
          min_stock: Number(props.item.min_stock),
          cost: Number(props.item.cost),
          sale_price: props.item.sale_price !== null ? Number(props.item.sale_price) : null,
          supplier: props.item.supplier ?? '',
          is_sellable: props.item.is_sellable,
          active: props.item.active,
        }
      : emptyForm()
  }
)

const submit = async () => {
  saving.value = true
  errors.value = {}
  generalError.value = null

  // El backend rechaza `stock` al editar: el saldo solo cambia por movimientos.
  const payload: Record<string, unknown> = { ...form.value }
  if (isEdit.value) delete payload.stock
  if (!form.value.is_sellable) payload.sale_price = null

  try {
    const response = props.item
      ? await store.updateItem(props.item.id, payload)
      : await store.createItem(payload)

    emit('saved', response.message)
  } catch (err: any) {
    const fieldErrors = err?.response?.data?.errors ?? {}
    errors.value = Object.fromEntries(
      Object.entries(fieldErrors)
        .filter(([, messages]) => Array.isArray(messages))
        .map(([key, messages]) => [key, (messages as string[])[0]])
    )
    if (Object.keys(errors.value).length === 0) {
      generalError.value = extractMessage(err, 'No se pudo guardar el producto.')
    }
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
