<template>
  <div class="commissions-page">
    <div class="page-header">
      <div>
        <h1 class="page-header__title">Comisiones</h1>
        <p class="page-header__subtitle">
          Se generan al confirmar una atención, según la regla configurada.
        </p>
      </div>
    </div>

    <Tabs v-model:value="activeTab">
      <TabList>
        <Tab value="list">Comisiones generadas</Tab>
        <Tab value="rules">Reglas de comisión</Tab>
      </TabList>

      <TabPanels>
        <TabPanel value="list">
          <div class="summary-grid">
            <div class="summary-tile summary-tile--danger">
              <p class="summary-tile__label">Pendiente de pago</p>
              <p class="summary-tile__value">{{ format.money(store.pendingAmount) }}</p>
            </div>
            <div class="summary-tile summary-tile--success">
              <p class="summary-tile__label">Pagado en el periodo</p>
              <p class="summary-tile__value">{{ format.money(store.paidAmount) }}</p>
            </div>
          </div>

          <Card class="filter-card">
            <template #content>
              <div class="filters">
                <Select
                  v-model="filters.employee_id"
                  :options="employeesStore.items"
                  option-label="name"
                  option-value="id"
                  filter
                  placeholder="Especialista"
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
                <DatePicker v-model="filters.range" selection-mode="range" placeholder="Rango" show-icon @date-select="reload" />
                <Button
                  label="Pagar seleccionadas"
                  icon="pi pi-check"
                  :disabled="payableSelection.length === 0"
                  @click="confirmPay"
                />
              </div>
            </template>
          </Card>

          <Message v-if="store.error" severity="error" :closable="false">{{ store.error }}</Message>

          <Card>
            <template #content>
              <DataTable
                v-model:selection="selection"
                :value="store.items"
                :loading="store.loading"
                data-key="id"
                lazy
                paginator
                :rows="15"
                :total-records="store.total"
                :first="(store.currentPage - 1) * 15"
                @page="onPage"
              >
                <template #empty>
                  <p class="table-empty">No hay comisiones generadas.</p>
                </template>

                <Column selection-mode="multiple" :style="{ width: '48px' }" />
                <Column header="Fecha" :style="{ width: '110px' }">
                  <template #body="{ data }">{{ format.date(data.generated_at) }}</template>
                </Column>
                <Column header="Especialista">
                  <template #body="{ data }">
                    <span class="cell-strong">{{ data.employee?.name ?? '—' }}</span>
                  </template>
                </Column>
                <Column header="Servicio">
                  <template #body="{ data }">{{ data.service?.name ?? '—' }}</template>
                </Column>
                <Column header="Base" :style="{ width: '110px' }">
                  <template #body="{ data }">
                    <span class="cell-amount cell-muted">{{ format.money(data.base_amount) }}</span>
                  </template>
                </Column>
                <Column header="Regla" :style="{ width: '110px' }">
                  <template #body="{ data }">
                    <span class="cell-muted">
                      {{ data.type === 'percentage' ? `${data.value}%` : format.money(data.value) }}
                    </span>
                  </template>
                </Column>
                <Column header="Comisión" :style="{ width: '120px' }">
                  <template #body="{ data }">
                    <span class="cell-amount cell-strong">{{ format.money(data.amount) }}</span>
                  </template>
                </Column>
                <Column header="Estado" :style="{ width: '120px' }">
                  <template #body="{ data }">
                    <Tag :value="data.status_label" :severity="data.status_color" />
                  </template>
                </Column>
              </DataTable>
            </template>
          </Card>
        </TabPanel>

        <TabPanel value="rules">
          <CommissionRulesPanel />
        </TabPanel>
      </TabPanels>
    </Tabs>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'
import { useCommissionsStore } from '@/stores/commissions'
import { useEmployeesStore } from '@/stores/employees'
import { useFormat } from '@/composables/useFormat'
import { extractMessage } from '@/composables/usePaginatedList'
import CommissionRulesPanel from './CommissionRulesPanel.vue'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import DatePicker from 'primevue/datepicker'
import Message from 'primevue/message'
import Select from 'primevue/select'
import Tab from 'primevue/tab'
import TabList from 'primevue/tablist'
import TabPanel from 'primevue/tabpanel'
import TabPanels from 'primevue/tabpanels'
import Tabs from 'primevue/tabs'
import Tag from 'primevue/tag'

const store = useCommissionsStore()
const employeesStore = useEmployeesStore()
const toast = useToast()
const confirm = useConfirm()
const format = useFormat()

const activeTab = ref('list')
const selection = ref<any[]>([])

const statusOptions = [
  { label: 'Pendientes', value: 'pending' },
  { label: 'Pagadas', value: 'paid' },
]

const filters = ref<{ employee_id: number | null; status: string | null; range: Date[] | null }>({
  employee_id: null,
  status: null,
  range: null,
})

// Solo las pendientes pueden liquidarse; marcar una pagada no debe hacer nada.
const payableSelection = computed(() => selection.value.filter((c) => c.status === 'pending'))

const params = (page = 1) => {
  const [from, to] = filters.value.range ?? []
  return {
    page,
    employee_id: filters.value.employee_id ?? undefined,
    status: filters.value.status ?? undefined,
    from: format.toIsoDate(from) ?? undefined,
    to: format.toIsoDate(to) ?? undefined,
  }
}

const reload = () => {
  selection.value = []
  return store.load(params(1))
}

const onPage = (event: { page: number }) => store.load(params(event.page + 1))

const confirmPay = () => {
  const total = payableSelection.value.reduce((sum, c) => sum + Number(c.amount), 0)

  confirm.require({
    header: 'Pagar comisiones',
    message: `Se marcarán ${payableSelection.value.length} comisiones como pagadas por un total de ${format.money(total)}.`,
    icon: 'pi pi-question-circle',
    acceptLabel: 'Confirmar pago',
    rejectLabel: 'Cancelar',
    accept: async () => {
      try {
        const response = await store.payCommissions(payableSelection.value.map((c) => c.id))
        selection.value = []
        toast.add({ severity: 'success', summary: 'Listo', detail: response.message, life: 4000 })
      } catch (err) {
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: extractMessage(err, 'No se pudieron pagar las comisiones.'),
          life: 5000,
        })
      }
    },
  })
}

onMounted(() => {
  employeesStore.load({ active: true })
  reload()
})
</script>
