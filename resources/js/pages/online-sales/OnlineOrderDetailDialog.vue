<template>
  <Dialog
    :visible="visible"
    modal
    :header="order ? `Pedido ${order.code}` : 'Pedido'"
    :style="{ width: '780px' }"
    :breakpoints="{ '820px': '95vw' }"
    @update:visible="$emit('update:visible', $event)"
  >
    <div v-if="loading" class="detail-loading"><ProgressSpinner /></div>

    <Message v-else-if="loadError" severity="error" :closable="false">{{ loadError }}</Message>

    <div v-else-if="order" class="order-detail">
      <div class="order-detail__head">
        <Tag :value="order.status_label" :severity="order.status_color" />
        <span class="cell-muted">Creado el {{ format.dateTime(order.created_at) }}</span>
      </div>

      <div class="order-detail__grid">
        <section class="order-detail__box">
          <h3>Cliente web</h3>
          <p class="cell-strong">{{ order.customer?.name }}</p>
          <p class="cell-muted">{{ order.customer?.email }}</p>
          <p class="cell-muted">{{ order.customer?.phone }}</p>
          <p v-if="order.client_code" class="cell-muted">Ficha ERP: {{ order.client_code }}</p>
        </section>

        <section class="order-detail__box">
          <h3>Entrega · {{ order.fulfillment_label }}</h3>
          <p class="cell-strong">{{ order.recipient_name }} · {{ order.phone }}</p>
          <p v-if="order.address">{{ order.address }}, {{ order.district }}</p>
          <p v-if="order.reference" class="cell-muted">Ref.: {{ order.reference }}</p>
          <p v-if="order.notes" class="cell-muted">Nota del cliente: {{ order.notes }}</p>
        </section>
      </div>

      <DataTable :value="order.items" size="small">
        <Column header="Artículo">
          <template #body="{ data }">
            {{ data.name }}
            <Tag :value="data.type === 'product' ? 'Producto' : 'Servicio'" severity="secondary" class="item-tag" />
          </template>
        </Column>
        <Column field="quantity" header="Cant." :style="{ width: '70px' }" />
        <Column header="P. unit." :style="{ width: '110px' }">
          <template #body="{ data }">{{ format.money(data.unit_price) }}</template>
        </Column>
        <Column header="Subtotal" :style="{ width: '110px' }">
          <template #body="{ data }"><span class="cell-amount">{{ format.money(data.subtotal) }}</span></template>
        </Column>
      </DataTable>

      <div class="order-detail__totals">
        <span>Subtotal</span><span>{{ format.money(order.subtotal) }}</span>
        <span>Delivery</span><span>{{ format.money(order.delivery_fee) }}</span>
        <strong>Total</strong><strong>{{ format.money(order.total) }}</strong>
      </div>

      <section class="order-detail__box">
        <h3>Pago</h3>
        <p v-if="order.paid_at">
          Pagado el {{ format.dateTime(order.paid_at) }} · Ref. <code>{{ order.payment_reference }}</code>
        </p>
        <p v-else class="cell-muted">Sin pago confirmado.</p>
        <ul v-if="order.payments?.length" class="payments">
          <li v-for="payment in order.payments" :key="payment.id">
            <Tag :value="payment.status === 'paid' ? 'Aprobado' : 'Rechazado'" :severity="payment.status === 'paid' ? 'success' : 'danger'" />
            {{ payment.gateway }} · {{ format.money(payment.amount) }} · {{ format.dateTime(payment.created_at) }}
          </li>
        </ul>
      </section>

      <section class="order-detail__box">
        <h3>Seguimiento</h3>
        <ul class="timeline">
          <li v-for="history in order.histories" :key="history.id">
            <div class="timeline__title">
              <strong>{{ history.status_label }}</strong>
              <Tag v-if="history.internal" value="Interna" severity="warn" />
            </div>
            <span class="cell-muted">{{ format.dateTime(history.created_at) }}<template v-if="history.user"> · {{ history.user }}</template></span>
            <p v-if="history.note">{{ history.note }}</p>
          </li>
        </ul>
      </section>

      <section v-if="canManage && order.next_statuses.length" class="order-detail__box status-form">
        <h3>Actualizar estado</h3>
        <Select
          v-model="nextStatus"
          :options="order.next_statuses"
          option-label="label"
          option-value="value"
          placeholder="Nuevo estado"
        />
        <Textarea v-model="note" rows="2" auto-resize placeholder="Nota para el cliente (opcional). Ej.: sale con el motorizado a las 4 p. m." />
        <Message v-if="nextStatus === 'cancelled' && order.paid_at" severity="warn" :closable="false">
          Se devolverá el stock de los productos. El reembolso del dinero se hace desde el panel de Izipay.
        </Message>
        <div>
          <Button label="Actualizar estado" icon="pi pi-check" :disabled="!nextStatus" :loading="saving" @click="submitStatus" />
        </div>
      </section>
    </div>
  </Dialog>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useOnlineSalesStore } from '@/stores/onlineSales'
import { useAuthStore } from '@/stores/auth'
import { useFormat } from '@/composables/useFormat'
import { extractMessage } from '@/composables/usePaginatedList'
import Button from 'primevue/button'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Dialog from 'primevue/dialog'
import Message from 'primevue/message'
import ProgressSpinner from 'primevue/progressspinner'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import Textarea from 'primevue/textarea'

const props = defineProps<{ visible: boolean; orderId: number | null }>()
defineEmits<{ 'update:visible': [boolean] }>()

const store = useOnlineSalesStore()
const auth = useAuthStore()
const toast = useToast()
const format = useFormat()

const order = ref<any | null>(null)
const loading = ref(false)
const loadError = ref<string | null>(null)
const nextStatus = ref<string | null>(null)
const note = ref('')
const saving = ref(false)

const canManage = computed(() => auth.hasPermission('online_sales.manage'))

const load = async () => {
  if (!props.orderId) return
  loading.value = true
  loadError.value = null
  try {
    order.value = await store.getOrder(props.orderId)
  } catch (err) {
    loadError.value = extractMessage(err, 'No se pudo cargar el pedido.')
  } finally {
    loading.value = false
  }
}

watch(
  () => props.visible,
  (open) => {
    if (!open) return
    order.value = null
    nextStatus.value = null
    note.value = ''
    load()
  }
)

const submitStatus = async () => {
  if (!order.value || !nextStatus.value) return
  saving.value = true
  try {
    const response = await store.changeStatus(order.value.id, { status: nextStatus.value, note: note.value || null })
    order.value = response.data
    nextStatus.value = null
    note.value = ''
    toast.add({ severity: 'success', summary: 'Listo', detail: response.message, life: 3000 })
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Error', detail: extractMessage(err, 'No se pudo actualizar el estado.'), life: 5000 })
  } finally {
    saving.value = false
  }
}
</script>

<style scoped lang="scss">
.detail-loading {
  display: grid;
  place-items: center;
  padding: 3rem;
}

.order-detail {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;

  &__head {
    display: flex;
    align-items: center;
    gap: 0.75rem;
  }

  &__grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 1rem;
  }

  &__box {
    h3 {
      margin: 0 0 0.5rem;
      font-size: 0.8rem;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      color: #6b7280;
    }

    p {
      margin: 0.15rem 0;
    }
  }

  &__totals {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 0.35rem 1.5rem;
    margin-left: auto;
    min-width: 240px;
  }
}

.item-tag {
  margin-left: 0.5rem;
}

.payments {
  list-style: none;
  padding: 0;
  margin: 0.5rem 0 0;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  font-size: 0.85rem;
}

.timeline {
  list-style: none;
  margin: 0;
  padding: 0;

  li {
    position: relative;
    padding: 0 0 0.9rem 1.1rem;
    border-left: 2px solid #e5e7eb;

    &::before {
      content: '';
      position: absolute;
      left: -5px;
      top: 4px;
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: #b08d4b;
    }

    p {
      margin: 0.25rem 0 0;
    }
  }

  &__title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }
}

.status-form {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  padding-top: 1rem;
  border-top: 1px solid #e5e7eb;
}
</style>
