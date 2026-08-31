<template>
  <div class="staff-page">
    <div class="page-header">
      <div>
        <h1 class="page-header__title">Personal</h1>
        <p class="page-header__subtitle">
          Especialistas y trabajadores del centro. No necesitan cuenta de usuario para existir.
        </p>
      </div>
      <div class="page-header__actions">
        <Button label="Nuevo trabajador" icon="pi pi-plus" @click="openCreate" />
      </div>
    </div>

    <Card class="filter-card">
      <template #content>
        <div class="filters">
          <IconField>
            <InputIcon class="pi pi-search" />
            <InputText v-model="search" placeholder="Buscar por nombre" @keyup.enter="reload" />
          </IconField>
          <Select
            v-model="activeFilter"
            :options="activeOptions"
            option-label="label"
            option-value="value"
            placeholder="Estado"
            show-clear
            @change="reload"
          />
          <Button icon="pi pi-search" label="Buscar" outlined @click="reload" />
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
            <p class="table-empty">No hay personal registrado.</p>
          </template>

          <Column field="name" header="Nombre" />
          <Column field="position" header="Cargo">
            <template #body="{ data }">{{ data.position ?? '—' }}</template>
          </Column>
          <Column field="phone" header="Celular">
            <template #body="{ data }">{{ data.phone ?? '—' }}</template>
          </Column>
          <Column header="Servicios">
            <template #body="{ data }">
              <span>{{ data.services?.length ?? 0 }}</span>
            </template>
          </Column>
          <Column header="Acceso al sistema">
            <template #body="{ data }">
              <Tag
                :value="data.has_system_access ? 'Con usuario' : 'Sin usuario'"
                :severity="data.has_system_access ? 'info' : 'secondary'"
              />
            </template>
          </Column>
          <Column header="Estado">
            <template #body="{ data }">
              <Tag
                :value="data.active ? 'Activo' : 'Inactivo'"
                :severity="data.active ? 'success' : 'secondary'"
              />
            </template>
          </Column>
          <Column header="Acciones" :style="{ width: '120px' }">
            <template #body="{ data }">
              <div class="row-actions">
                <Button icon="pi pi-pencil" text rounded severity="secondary" @click="openEdit(data)" />
                <Button icon="pi pi-trash" text rounded severity="danger" @click="confirmDelete(data)" />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <StaffFormDialog
      v-model:visible="dialogVisible"
      :employee="editing"
      @saved="onSaved"
    />
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'
import { useEmployeesStore } from '@/stores/employees'
import { extractMessage } from '@/composables/usePaginatedList'
import StaffFormDialog from './StaffFormDialog.vue'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import Select from 'primevue/select'
import Tag from 'primevue/tag'

const store = useEmployeesStore()
const toast = useToast()
const confirm = useConfirm()

const search = ref('')
const activeFilter = ref<boolean | null>(null)
const dialogVisible = ref(false)
const editing = ref<any | null>(null)

const activeOptions = [
  { label: 'Activos', value: true },
  { label: 'Inactivos', value: false },
]

const params = (page = 1) => ({
  page,
  search: search.value || undefined,
  active: activeFilter.value ?? undefined,
})

const reload = () => store.load(params(1))
const onPage = (event: { page: number }) => store.load(params(event.page + 1))

const openCreate = () => {
  editing.value = null
  dialogVisible.value = true
}

const openEdit = (employee: any) => {
  editing.value = employee
  dialogVisible.value = true
}

const onSaved = (message: string) => {
  dialogVisible.value = false
  toast.add({ severity: 'success', summary: 'Listo', detail: message, life: 3000 })
  reload()
}

const confirmDelete = (employee: any) => {
  confirm.require({
    header: 'Eliminar trabajador',
    message: `¿Eliminar a ${employee.name}? Si tiene historial se desactivará en lugar de borrarse.`,
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Eliminar',
    rejectLabel: 'Cancelar',
    acceptProps: { severity: 'danger' },
    accept: async () => {
      try {
        const response = await store.deleteEmployee(employee.id)
        toast.add({ severity: 'success', summary: 'Listo', detail: response.message, life: 4000 })
      } catch (err) {
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: extractMessage(err, 'No se pudo eliminar el trabajador.'),
          life: 5000,
        })
      }
    },
  })
}

onMounted(reload)
</script>
