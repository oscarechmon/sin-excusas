<template>
  <div class="clients-page">
    <div class="page-header">
      <h1>Clientes</h1>
      <Button icon="pi pi-plus" label="Nuevo Cliente" @click="openCreateDialog" />
    </div>

    <Card class="filter-card">
      <template #content>
        <div class="filters">
          <InputText
            v-model="searchInput"
            placeholder="Buscar por nombre, DNI, celular o código..."
            @keyup.enter="performSearch"
          />
          <MultiSelect
            v-model="selectedFilter"
            :options="filterOptions"
            option-label="label"
            option-value="value"
            placeholder="Filtrar por estado"
            @change="applyFilter"
          />
          <Button icon="pi pi-search" @click="performSearch" text rounded />
        </div>
      </template>
    </Card>

    <Card class="table-card">
      <template #content>
        <DataTable
          :value="clients"
          :loading="loading"
          :paginator="true"
          :rows="15"
          :total-records="total"
          :lazy="true"
          paginator-template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
          current-page-report-template="Mostrando {first} a {last} de {totalRecords}"
          @page="onPageChange"
        >
          <template #empty>
            <div class="empty-message">
              <i class="pi pi-inbox"></i>
              <p>No hay clientes registrados</p>
            </div>
          </template>

          <Column field="code" header="Código" style="width: 100px" />
          <Column field="full_name" header="Nombre" style="width: 250px" />
          <Column field="document_number" header="Documento" style="width: 150px" />
          <Column field="phone" header="Teléfono" style="width: 150px" />
          <Column field="email" header="Email" style="width: 200px" />
          <Column field="district" header="Distrito" style="width: 150px" />
          <Column header="Estado" style="width: 120px">
            <template #body="{ data }">
              <Tag :value="data.active ? 'Activo' : 'Inactivo'" :severity="data.active ? 'success' : 'danger'" />
            </template>
          </Column>
          <Column header="Acciones" style="width: 150px">
            <template #body="{ data }">
              <Button
                icon="pi pi-eye"
                text
                rounded
                class="p-button-sm"
                @click="viewClient(data.id)"
                v-tooltip="'Ver'"
              />
              <Button
                icon="pi pi-pencil"
                text
                rounded
                class="p-button-sm"
                @click="editClient(data)"
                v-tooltip="'Editar'"
              />
              <Button
                icon="pi pi-trash"
                text
                rounded
                class="p-button-sm p-button-danger"
                @click="confirmDelete(data.id)"
                v-tooltip="'Eliminar'"
              />
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <ClientFormDialog
      :visible="showFormDialog"
      :client="editingClient"
      @close="closeFormDialog"
      @save="handleSave"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, ref, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import { useRouter } from 'vue-router'
import { useClientsStore } from '@/stores/clients'
import ClientFormDialog from './ClientFormDialog.vue'
import Card from 'primevue/card'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import MultiSelect from 'primevue/multiselect'
import Tag from 'primevue/tag'

const toast = useToast()
const confirm = useConfirm()
const router = useRouter()
const clientsStore = useClientsStore()

const showFormDialog = ref(false)
const editingClient = ref(null)
const searchInput = ref('')
const selectedFilter = ref(null)

const filterOptions = [
  { label: 'Activos', value: true },
  { label: 'Inactivos', value: false },
]

const clients = computed(() => clientsStore.clients)
const loading = computed(() => clientsStore.loading)
const total = computed(() => clientsStore.total)

onMounted(() => {
  clientsStore.loadClients()
})

const openCreateDialog = () => {
  editingClient.value = null
  showFormDialog.value = true
}

const editClient = (client: any) => {
  editingClient.value = client
  showFormDialog.value = true
}

const viewClient = (id: number) => {
  router.push({ name: 'client-detail', params: { id } })
}

const closeFormDialog = () => {
  showFormDialog.value = false
  editingClient.value = null
}

const handleSave = async () => {
  closeFormDialog()
  toast.add({
    severity: 'success',
    summary: 'Éxito',
    detail: 'Cliente guardado correctamente',
    life: 3000,
  })
}

const performSearch = () => {
  clientsStore.search(searchInput.value)
}

const applyFilter = () => {
  clientsStore.filter(selectedFilter.value)
}

const confirmDelete = (id: number) => {
  confirm.require({
    message: '¿Estás seguro de que deseas eliminar este cliente?',
    header: 'Confirmar eliminación',
    icon: 'pi pi-exclamation-triangle',
    accept: async () => {
      try {
        await clientsStore.deleteClient(id)
        toast.add({
          severity: 'success',
          summary: 'Éxito',
          detail: 'Cliente eliminado correctamente',
          life: 3000,
        })
      } catch (error) {
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'No se pudo eliminar el cliente',
          life: 3000,
        })
      }
    },
  })
}

const onPageChange = (event: any) => {
  clientsStore.loadClients(event.page + 1, searchInput.value, selectedFilter.value)
}
</script>

<style scoped lang="scss">
.clients-page {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;

  h1 {
    margin: 0;
  }
}

.filter-card {
  .filters {
    display: flex;
    gap: 0.75rem;
    align-items: center;
    flex-wrap: wrap;

    // El buscador ocupa el espacio libre; el filtro de estado tiene un ancho
    // acotado para que no se coma la fila.
    :deep(.p-inputtext) {
      flex: 1 1 260px;
      min-width: 0;
    }

    :deep(.p-multiselect) {
      flex: 0 1 220px;
    }
  }
}

.table-card {
  .empty-message {
    text-align: center;
    padding: 2rem;
    color: #999;

    i {
      font-size: 2rem;
      display: block;
      margin-bottom: 0.5rem;
    }
  }
}
</style>
