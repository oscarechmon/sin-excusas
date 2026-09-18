<template>
  <div class="reports-page">
    <div class="page-header">
      <div>
        <h1 class="page-header__title">Reportes</h1>
        <p class="page-header__subtitle">Resumen del periodo seleccionado.</p>
      </div>
      <div class="page-header__actions">
        <DatePicker v-model="range" selection-mode="range" show-icon placeholder="Rango de fechas" />
        <Button label="Actualizar" icon="pi pi-refresh" :loading="loading" @click="loadAll" />
      </div>
    </div>

    <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>

    <Tabs v-model:value="activeTab">
      <TabList>
        <Tab value="sales">Ventas</Tab>
        <Tab value="cash">Caja</Tab>
        <Tab value="commissions">Comisiones</Tab>
        <Tab value="stock">Stock</Tab>
      </TabList>

      <TabPanels>
        <TabPanel value="sales">
          <div class="report-grid">
            <ReportTable
              title="Ventas por periodo"
              :rows="sales.by_period"
              :columns="[
                { field: 'period', header: 'Periodo' },
                { field: 'sales_count', header: 'Ventas', numeric: true },
                { field: 'total', header: 'Total', money: true },
              ]"
            />
            <ReportTable
              title="Ventas por servicio"
              :rows="sales.by_service"
              :columns="[
                { field: 'description', header: 'Servicio' },
                { field: 'quantity', header: 'Cant.', numeric: true },
                { field: 'total', header: 'Total', money: true },
              ]"
            />
            <ReportTable
              title="Ventas por producto"
              :rows="sales.by_product"
              :columns="[
                { field: 'description', header: 'Producto' },
                { field: 'quantity', header: 'Cant.', numeric: true },
                { field: 'total', header: 'Total', money: true },
              ]"
            />
            <ReportTable
              title="Ventas por especialista"
              :rows="sales.by_employee"
              :columns="[
                { field: 'employee', header: 'Especialista' },
                { field: 'items_count', header: 'Ítems', numeric: true },
                { field: 'total', header: 'Total', money: true },
              ]"
            />
          </div>
        </TabPanel>

        <TabPanel value="cash">
          <ReportTable
            title="Movimientos de caja"
            :rows="cashFlow"
            :columns="[
              { field: 'type_label', header: 'Tipo' },
              { field: 'movements_count', header: 'Movimientos', numeric: true },
              { field: 'total', header: 'Total', money: true },
            ]"
          />
        </TabPanel>

        <TabPanel value="commissions">
          <ReportTable
            title="Comisiones por especialista"
            :rows="commissions"
            :columns="[
              { field: 'employee', header: 'Especialista' },
              { field: 'status_label', header: 'Estado' },
              { field: 'items_count', header: 'Cantidad', numeric: true },
              { field: 'total', header: 'Total', money: true },
            ]"
          />
        </TabPanel>

        <TabPanel value="stock">
          <div class="stock-toolbar">
            <ToggleButton
              v-model="onlyLowStock"
              on-label="Solo stock bajo"
              off-label="Todo el stock"
              @change="loadStock"
            />
          </div>
          <ReportTable
            title="Stock actual"
            :rows="stock"
            :columns="[
              { field: 'name', header: 'Producto' },
              { field: 'category', header: 'Categoría' },
              { field: 'stock', header: 'Stock', numeric: true },
              { field: 'min_stock', header: 'Mínimo', numeric: true },
            ]"
          />
        </TabPanel>
      </TabPanels>
    </Tabs>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { reportsApi } from '@/api/reports.api'
import { useFormat } from '@/composables/useFormat'
import { extractMessage } from '@/composables/usePaginatedList'
import ReportTable from './ReportTable.vue'
import Button from 'primevue/button'
import DatePicker from 'primevue/datepicker'
import Message from 'primevue/message'
import Tab from 'primevue/tab'
import TabList from 'primevue/tablist'
import TabPanel from 'primevue/tabpanel'
import TabPanels from 'primevue/tabpanels'
import Tabs from 'primevue/tabs'
import ToggleButton from 'primevue/togglebutton'

const format = useFormat()

const activeTab = ref('sales')
const loading = ref(false)
const error = ref<string | null>(null)
const onlyLowStock = ref(false)

// Por defecto el mes en curso: es el periodo que se consulta a diario.
const startOfMonth = new Date()
startOfMonth.setDate(1)
const range = ref<Date[]>([startOfMonth, new Date()])

const sales = ref<Record<string, any[]>>({
  by_period: [],
  by_service: [],
  by_product: [],
  by_package: [],
  by_employee: [],
})
const cashFlow = ref<any[]>([])
const commissions = ref<any[]>([])
const stock = ref<any[]>([])

const rangeParams = () => {
  const [from, to] = range.value ?? []
  return {
    from: format.toIsoDate(from) ?? undefined,
    to: format.toIsoDate(to) ?? undefined,
  }
}

const cashTypeLabels: Record<string, string> = {
  opening: 'Apertura',
  sale: 'Venta',
  expense: 'Egreso',
  adjustment: 'Ajuste',
}

const loadAll = async () => {
  loading.value = true
  error.value = null

  try {
    const params = rangeParams()
    const [salesRes, cashRes, commissionsRes] = await Promise.all([
      reportsApi.sales(params),
      reportsApi.cash(params),
      reportsApi.commissions(params),
    ])

    sales.value = salesRes.data
    cashFlow.value = (cashRes.data.flow ?? []).map((row: any) => ({
      ...row,
      type_label: cashTypeLabels[row.type] ?? row.type,
    }))
    commissions.value = commissionsRes.data.commissions ?? []
  } catch (err) {
    error.value = extractMessage(err, 'No se pudieron cargar los reportes.')
  } finally {
    loading.value = false
  }
}

const loadStock = async () => {
  try {
    const response = await reportsApi.stock({ only_low: onlyLowStock.value })
    stock.value = response.data.stock ?? []
  } catch (err) {
    error.value = extractMessage(err, 'No se pudo cargar el stock.')
  }
}

// El stock no depende del rango de fechas, así que se pide solo al abrir su pestaña.
watch(activeTab, (tab) => {
  if (tab === 'stock' && stock.value.length === 0) loadStock()
})

onMounted(loadAll)
</script>

<style scoped lang="scss">
.report-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(min(340px, 100%), 1fr));
  gap: 1rem;
  margin-top: 1rem;
}

.stock-toolbar {
  margin: 1rem 0;
}
</style>
