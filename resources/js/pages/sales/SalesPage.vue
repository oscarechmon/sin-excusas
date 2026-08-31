<template>
  <div class="sales-page">
    <div class="page-header">
      <div>
        <h1 class="page-header__title">Ventas</h1>
        <p class="page-header__subtitle">Servicios, productos y paquetes, con pago mixto y saldos.</p>
      </div>
      <div class="page-header__actions">
        <Button label="Nueva venta" icon="pi pi-plus" @click="formVisible = true" />
      </div>
    </div>

    <Card class="filter-card">
      <template #content>
        <div class="filters">
          <Select
            v-model="filters.client_id"
            :options="clientsStore.clients"
            option-label="full_name"
            option-value="id"
            filter
            placeholder="Cliente"
            show-clear
            @change="reload"
          />
          <Select
            v-model="filters.status"
            :options="statusOptions"
            option-label="label"
            option-value="value"
            placeholder="Estado"
            show-clear
            @change="reload"
          />
          <DatePicker v-model="filters.range" selection-mode="range" placeholder="Rango de fechas" show-icon @date-select="reload" />
          <Button icon="pi pi-filter-slash" label="Limpiar" outlined severity="secondary" @click="clearFilters" />
        </div>
      </template>
    </Card>

    <Message v-if="store.error" severity="error" :closable="false">{{ store.error }}</Message>

    <Card>
      <template #content>
        <DataTable
          :value="store.items"
          :loading="store.loading"
          lazy
          paginator
          :rows="15"
          :total-records="store.total"
          :first="(store.currentPage - 1) * 15"
          @page="onPage"
        >
          <template #empty>
            <p class="table-empty">No hay ventas registradas.</p>
          </template>

          <Column field="code" header="Código" :style="{ width: '110px' }">
            <template #body="{ data }">
              <span class="cell-strong">{{ data.code }}</span>
            </template>
          </Column>
          <Column header="Fecha" :style="{ width: '150px' }">
            <template #body="{ data }">
              <span class="cell-muted">{{ format.dateTime(data.created_at) }}</span>
            </template>
          </Column>
          <Column header="Cliente">
            <template #body="{ data }">
              {{ data.client?.full_name ?? 'Cliente ocasional' }}
            </template>
          </Column>
          <Column header="Total" :style="{ width: '120px' }">
            <template #body="{ data }">
              <span class="cell-amount cell-strong">{{ format.money(data.total) }}</span>
            </template>
          </Column>
          <Column header="Pagado" :style="{ width: '120px' }">
            <template #body="{ data }">
              <span class="cell-amount">{{ format.money(data.paid_amount) }}</span>
            </template>
          </Column>
          <Column header="Saldo" :style="{ width: '120px' }">
            <template #body="{ data }">
              <span class="cell-amount" :class="{ 'balance-due': data.balance > 0 }">
                {{ format.money(data.balance) }}
              </span>
            </template>
          </Column>
          <Column header="Estado" :style="{ width: '130px' }">
            <template #body="{ data }">
              <Tag :value="data.status_label" :severity="data.status_color" />
            </template>
          </Column>
          <Column header="Acciones" :style="{ width: '140px' }">
            <template #body="{ data }">
              <div class="row-actions">
                <Button
                  v-if="data.balance > 0 && data.status !== 'cancelled'"
                  icon="pi pi-dollar"
                  text
                  rounded
                  severity="success"
                  aria-label="Registrar pago"
                  @click="openPayment(data)"
                />
                <Button icon="pi pi-eye" text rounded severity="secondary" @click="openDetail(data)" />
                <Button
                  v-if="data.status !== 'cancelled'"
                  icon="pi pi-times"
                  text
                  rounded
                  severity="danger"
                  aria-label="Anular venta"
                  @click="confirmCancel(data)"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <SaleFormDialog v-model:visible="formVisible" @saved="onSaved" />
    <SalePaymentDialog v-model:visible="paymentVisible" :sale="selected" @saved="onSaved" />
    <SaleDetailDialog v-model:visible="detailVisible" :sale-id="selectedId" />
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'
import { useSalesStore } from '@/stores/sales'
import { useClientsStore } from '@/stores/clients'
import { useFormat } from '@/composables/useFormat'
import { extractMessage } from '@/composables/usePaginatedList'
import SaleFormDialog from './SaleFormDialog.vue'
import SalePaymentDialog from './SalePaymentDialog.vue'
import SaleDetailDialog from './SaleDetailDialog.vue'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import DatePicker from 'primevue/datepicker'
import Message from 'primevue/message'
import Select from 'primevue/select'
import Tag from 'primevue/tag'

const store = useSalesStore()
const clientsStore = useClientsStore()
const toast = useToast()
const confirm = useConfirm()
const format = useFormat()

const formVisible = ref(false)
const paymentVisible = ref(false)
const detailVisible = ref(false)
const selected = ref<any | null>(null)
const selectedId = ref<number | null>(null)

const statusOptions = [
  { label: 'Pendiente', value: 'pending' },
  { label: 'Pago parcial', value: 'partial' },
  { label: 'Pagada', value: 'paid' },
  { label: 'Anulada', value: 'cancelled' },
]

const filters = ref<{ client_id: number | null; status: string | null; range: Date[] | null }>({
  client_id: null,
  status: null,
  range: null,
})

const params = (page = 1) => {
  const [from, to] = filters.value.range ?? []
  return {
    page,
    client_id: filters.value.client_id ?? undefined,
    status: filters.value.status ?? undefined,
    from: format.toIsoDate(from) ?? undefined,
    to: format.toIsoDate(to) ?? undefined,
  }
}

const reload = () => store.load(params(1))
const onPage = (event: { page: number }) => store.load(params(event.page + 1))

const clearFilters = () => {
  filters.value = { client_id: null, status: null, range: null }
  reload()
}

const openPayment = (sale: any) => {
  selected.value = sale
  paymentVisible.value = true
}

const openDetail = (sale: any) => {
  selectedId.value = sale.id
  detailVisible.value = true
}

const onSaved = (message: string) => {
  formVisible.value = false
  paymentVisible.value = false
  toast.add({ severity: 'success', summary: 'Listo', detail: message, life: 4000 })
}

const confirmCancel = (sale: any) => {
  confirm.require({
    header: 'Anular venta',
    message: `¿Anular la venta ${sale.code}? La venta no se elimina: queda registrada como anulada.`,
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Anular',
    rejectLabel: 'Cancelar',
    acceptProps: { severity: 'danger' },
    accept: async () => {
      try {
        const response = await store.cancelSale(sale.id)
        toast.add({ severity: 'success', summary: 'Listo', detail: response.message, life: 4000 })
      } catch (err) {
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: extractMessage(err, 'No se pudo anular la venta.'),
          life: 5000,
        })
      }
    },
  })
}

onMounted(() => {
  clientsStore.loadClients()
  reload()
})
</script>

<style scoped lang="scss">
.balance-due {
  color: #b45309;
  font-weight: 600;
}
</style>
