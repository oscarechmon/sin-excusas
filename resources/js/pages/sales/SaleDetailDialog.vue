<template>
  <Dialog
    :visible="visible"
    modal
    :header="sale ? `Venta ${sale.code}` : 'Detalle de la venta'"
    :style="{ width: '640px' }"
    @update:visible="$emit('update:visible', $event)"
  >
    <ProgressSpinner v-if="loading" class="detail-spinner" />

    <div v-else-if="sale" class="detail">
      <div class="detail__meta">
        <span>{{ format.dateTime(sale.created_at) }}</span>
        <span>{{ sale.client?.full_name ?? 'Cliente ocasional' }}</span>
        <Tag :value="sale.status_label" :severity="sale.status_color" />
      </div>

      <section>
        <h3 class="detail__title">Conceptos</h3>
        <DataTable :value="sale.items" size="small">
          <Column field="description" header="Concepto" />
          <Column header="Tipo" :style="{ width: '100px' }">
            <template #body="{ data }">
              <span class="cell-muted">{{ typeLabel(data.type) }}</span>
            </template>
          </Column>
          <Column header="Cant." :style="{ width: '70px' }">
            <template #body="{ data }">
              <span class="cell-amount">{{ data.quantity }}</span>
            </template>
          </Column>
          <Column header="P. unit." :style="{ width: '100px' }">
            <template #body="{ data }">
              <span class="cell-amount">{{ format.money(data.unit_price) }}</span>
            </template>
          </Column>
          <Column header="Subtotal" :style="{ width: '110px' }">
            <template #body="{ data }">
              <span class="cell-amount cell-strong">{{ format.money(data.subtotal) }}</span>
            </template>
          </Column>
        </DataTable>
      </section>

      <section v-if="sale.payments?.length">
        <h3 class="detail__title">Pagos</h3>
        <DataTable :value="sale.payments" size="small">
          <Column header="Método">
            <template #body="{ data }">{{ data.payment_method?.name ?? '—' }}</template>
          </Column>
          <Column header="Referencia">
            <template #body="{ data }">
              <span class="cell-muted">{{ data.reference ?? '—' }}</span>
            </template>
          </Column>
          <Column header="Fecha" :style="{ width: '150px' }">
            <template #body="{ data }">
              <span class="cell-muted">{{ format.dateTime(data.paid_at) }}</span>
            </template>
          </Column>
          <Column header="Monto" :style="{ width: '110px' }">
            <template #body="{ data }">
              <span class="cell-amount cell-strong">{{ format.money(data.amount) }}</span>
            </template>
          </Column>
        </DataTable>
      </section>

      <div class="totals">
        <div class="totals__row"><span>Subtotal</span><strong>{{ format.money(sale.subtotal) }}</strong></div>
        <div class="totals__row"><span>Descuento</span><strong>− {{ format.money(sale.discount) }}</strong></div>
        <div class="totals__row totals__row--main"><span>Total</span><strong>{{ format.money(sale.total) }}</strong></div>
        <div class="totals__row"><span>Pagado</span><strong>{{ format.money(sale.paid_amount) }}</strong></div>
        <div class="totals__row"><span>Saldo</span><strong>{{ format.money(sale.balance) }}</strong></div>
      </div>
    </div>

    <template #footer>
      <Button label="Cerrar" text severity="secondary" @click="$emit('update:visible', false)" />
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { useSalesStore } from '@/stores/sales'
import { useFormat } from '@/composables/useFormat'
import Button from 'primevue/button'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Dialog from 'primevue/dialog'
import ProgressSpinner from 'primevue/progressspinner'
import Tag from 'primevue/tag'

const props = defineProps<{ visible: boolean; saleId: number | null }>()
defineEmits<{ 'update:visible': [boolean] }>()

const store = useSalesStore()
const format = useFormat()

const sale = ref<any | null>(null)
const loading = ref(false)

const typeLabel = (type: string) =>
  ({ service: 'Servicio', product: 'Producto', package: 'Paquete' })[type] ?? type

// El detalle completo (ítems y pagos) no viene en el listado: se pide al abrir.
watch(
  () => props.visible,
  async (open) => {
    if (!open || !props.saleId) return

    loading.value = true
    sale.value = null
    try {
      sale.value = await store.getSale(props.saleId)
    } finally {
      loading.value = false
    }
  }
)
</script>

<style scoped lang="scss">
.detail-spinner {
  display: block;
  margin: 2rem auto;
  width: 40px;
  height: 40px;
}

.detail {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;

  &__meta {
    display: flex;
    align-items: center;
    gap: 1rem;
    font-size: 0.875rem;
    color: #6b7280;
  }

  &__title {
    margin: 0 0 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
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
  }
}
</style>
