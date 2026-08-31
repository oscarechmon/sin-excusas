<template>
  <div class="attendances-page">
    <div class="page-header">
      <div>
        <h1 class="page-header__title">Atenciones</h1>
        <p class="page-header__subtitle">
          Historial de atenciones. Al confirmar se descuenta la sesión del paquete,
          los insumos y se genera la comisión.
        </p>
      </div>
      <div class="page-header__actions">
        <Button label="Registrar atención" icon="pi pi-plus" @click="formVisible = true" />
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
            v-model="filters.employee_id"
            :options="employeesStore.items"
            option-label="name"
            option-value="id"
            filter
            placeholder="Especialista"
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
            <p class="table-empty">No hay atenciones registradas.</p>
          </template>

          <Column header="Fecha" :style="{ width: '110px' }">
            <template #body="{ data }">{{ format.date(data.attended_at) }}</template>
          </Column>
          <Column header="Cliente">
            <template #body="{ data }">
              <span class="cell-strong">{{ data.client?.full_name ?? '—' }}</span>
            </template>
          </Column>
          <Column header="Servicio">
            <template #body="{ data }">{{ data.service?.name ?? '—' }}</template>
          </Column>
          <Column header="Especialista">
            <template #body="{ data }">{{ data.employee?.name ?? '—' }}</template>
          </Column>
          <Column header="Paquete" :style="{ width: '170px' }">
            <template #body="{ data }">
              <span v-if="data.client_package" class="cell-muted">
                {{ data.client_package.package_name }}
                <Tag :value="`Sesión ${data.session_number}`" severity="info" class="session-tag" />
              </span>
              <span v-else class="cell-muted">Servicio suelto</span>
            </template>
          </Column>
          <Column header="Comisión" :style="{ width: '110px' }">
            <template #body="{ data }">
              <span v-if="data.commission" class="cell-amount">
                {{ format.money(data.commission.amount) }}
              </span>
              <span v-else class="cell-muted">—</span>
            </template>
          </Column>
          <Column header="" :style="{ width: '60px' }">
            <template #body="{ data }">
              <Button icon="pi pi-eye" text rounded severity="secondary" @click="openDetail(data)" />
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <AttendanceFormDialog v-model:visible="formVisible" @saved="onSaved" />
    <AttendanceDetailDialog v-model:visible="detailVisible" :attendance="selected" />
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useAttendancesStore } from '@/stores/attendances'
import { useClientsStore } from '@/stores/clients'
import { useEmployeesStore } from '@/stores/employees'
import { useFormat } from '@/composables/useFormat'
import AttendanceFormDialog from './AttendanceFormDialog.vue'
import AttendanceDetailDialog from './AttendanceDetailDialog.vue'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import DatePicker from 'primevue/datepicker'
import Message from 'primevue/message'
import Select from 'primevue/select'
import Tag from 'primevue/tag'

const store = useAttendancesStore()
const clientsStore = useClientsStore()
const employeesStore = useEmployeesStore()
const toast = useToast()
const format = useFormat()

const formVisible = ref(false)
const detailVisible = ref(false)
const selected = ref<any | null>(null)

const filters = ref<{ client_id: number | null; employee_id: number | null; range: Date[] | null }>({
  client_id: null,
  employee_id: null,
  range: null,
})

const params = (page = 1) => {
  const [from, to] = filters.value.range ?? []
  return {
    page,
    client_id: filters.value.client_id ?? undefined,
    employee_id: filters.value.employee_id ?? undefined,
    from: format.toIsoDate(from) ?? undefined,
    to: format.toIsoDate(to) ?? undefined,
  }
}

const reload = () => store.load(params(1))
const onPage = (event: { page: number }) => store.load(params(event.page + 1))

const clearFilters = () => {
  filters.value = { client_id: null, employee_id: null, range: null }
  reload()
}

const openDetail = (attendance: any) => {
  selected.value = attendance
  detailVisible.value = true
}

const onSaved = (message: string) => {
  formVisible.value = false
  toast.add({ severity: 'success', summary: 'Atención registrada', detail: message, life: 4000 })
}

onMounted(() => {
  clientsStore.loadClients()
  employeesStore.load({ active: true })
  reload()
})
</script>

<style scoped lang="scss">
.session-tag {
  margin-left: 0.25rem;
}
</style>
