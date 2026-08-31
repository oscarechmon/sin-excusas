<template>
  <div class="packages-page">
    <div class="page-header">
      <div>
        <h1 class="page-header__title">Paquetes</h1>
        <p class="page-header__subtitle">Catálogo de paquetes y sesiones contratadas por cliente.</p>
      </div>
      <div class="page-header__actions">
        <Button label="Asignar a cliente" icon="pi pi-user-plus" outlined severity="secondary" @click="sellVisible = true" />
        <Button label="Nuevo paquete" icon="pi pi-plus" @click="openCreate" />
      </div>
    </div>

    <Tabs v-model:value="activeTab">
      <TabList>
        <Tab value="catalog">Catálogo</Tab>
        <Tab value="clients">Paquetes de clientes</Tab>
      </TabList>

      <TabPanels>
        <TabPanel value="catalog">
          <Message v-if="store.error" severity="error" :closable="false">{{ store.error }}</Message>

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
              <p class="table-empty">No hay paquetes en el catálogo.</p>
            </template>

            <Column field="name" header="Paquete">
              <template #body="{ data }">
                <span class="cell-strong">{{ data.name }}</span>
              </template>
            </Column>
            <Column header="Servicios incluidos">
              <template #body="{ data }">
                <span class="cell-muted">
                  {{ data.services?.map((s: any) => s.name).join(', ') || '—' }}
                </span>
              </template>
            </Column>
            <Column header="Sesiones" :style="{ width: '100px' }">
              <template #body="{ data }">
                <span class="cell-amount">{{ data.total_sessions }}</span>
              </template>
            </Column>
            <Column header="Precio" :style="{ width: '120px' }">
              <template #body="{ data }">
                <span class="cell-amount">{{ format.money(data.price) }}</span>
              </template>
            </Column>
            <Column header="Vigencia" :style="{ width: '110px' }">
              <template #body="{ data }">
                <span class="cell-muted">
                  {{ data.validity_days ? `${data.validity_days} días` : 'Sin límite' }}
                </span>
              </template>
            </Column>
            <Column header="Estado" :style="{ width: '110px' }">
              <template #body="{ data }">
                <Tag
                  :value="data.active ? 'Activo' : 'Inactivo'"
                  :severity="data.active ? 'success' : 'secondary'"
                />
              </template>
            </Column>
            <Column header="Acciones" :style="{ width: '110px' }">
              <template #body="{ data }">
                <div class="row-actions">
                  <Button icon="pi pi-pencil" text rounded severity="secondary" @click="openEdit(data)" />
                  <Button icon="pi pi-trash" text rounded severity="danger" @click="confirmDelete(data)" />
                </div>
              </template>
            </Column>
          </DataTable>
        </TabPanel>

        <TabPanel value="clients">
          <div class="filters client-filters">
            <Select
              v-model="statusFilter"
              :options="statusOptions"
              option-label="label"
              option-value="value"
              placeholder="Estado del paquete"
              show-clear
              @change="loadClientPackages"
            />
          </div>

          <DataTable :value="store.clientPackages" :loading="store.clientPackagesLoading">
            <template #empty>
              <p class="table-empty">Ningún cliente tiene paquetes contratados.</p>
            </template>

            <Column header="Cliente">
              <template #body="{ data }">
                <span class="cell-strong">{{ data.client?.full_name ?? '—' }}</span>
              </template>
            </Column>
            <Column field="package_name" header="Paquete" />
            <Column header="Sesiones" :style="{ width: '200px' }">
              <template #body="{ data }">
                <div class="sessions">
                  <ProgressBar
                    :value="progress(data)"
                    :show-value="false"
                    class="sessions__bar"
                  />
                  <span class="sessions__text">
                    {{ data.used_sessions }} / {{ data.total_sessions }}
                    <strong>({{ data.remaining_sessions }} libres)</strong>
                  </span>
                </div>
              </template>
            </Column>
            <Column header="Comprado" :style="{ width: '120px' }">
              <template #body="{ data }">
                <span class="cell-muted">{{ format.date(data.purchased_at) }}</span>
              </template>
            </Column>
            <Column header="Vence" :style="{ width: '120px' }">
              <template #body="{ data }">
                <span class="cell-muted" :class="{ 'text-danger': data.is_expired }">
                  {{ data.expires_at ? format.date(data.expires_at) : 'Sin vencimiento' }}
                </span>
              </template>
            </Column>
            <Column header="Estado" :style="{ width: '120px' }">
              <template #body="{ data }">
                <Tag :value="data.status_label" :severity="data.status_color" />
              </template>
            </Column>
          </DataTable>
        </TabPanel>
      </TabPanels>
    </Tabs>

    <PackageFormDialog v-model:visible="formVisible" :package-item="editing" @saved="onSaved" />
    <SellPackageDialog v-model:visible="sellVisible" @saved="onSold" />
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'
import { usePackagesStore } from '@/stores/packages'
import { useFormat } from '@/composables/useFormat'
import { extractMessage } from '@/composables/usePaginatedList'
import PackageFormDialog from './PackageFormDialog.vue'
import SellPackageDialog from './SellPackageDialog.vue'
import Button from 'primevue/button'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Message from 'primevue/message'
import ProgressBar from 'primevue/progressbar'
import Select from 'primevue/select'
import Tab from 'primevue/tab'
import TabList from 'primevue/tablist'
import TabPanel from 'primevue/tabpanel'
import TabPanels from 'primevue/tabpanels'
import Tabs from 'primevue/tabs'
import Tag from 'primevue/tag'

const store = usePackagesStore()
const toast = useToast()
const confirm = useConfirm()
const format = useFormat()

const activeTab = ref('catalog')
const statusFilter = ref<string | null>(null)
const formVisible = ref(false)
const sellVisible = ref(false)
const editing = ref<any | null>(null)

const statusOptions = [
  { label: 'Activos', value: 'active' },
  { label: 'Terminados', value: 'completed' },
  { label: 'Vencidos', value: 'expired' },
]

const progress = (row: any) =>
  row.total_sessions > 0 ? Math.round((row.used_sessions / row.total_sessions) * 100) : 0

const onPage = (event: { page: number }) => store.load({ page: event.page + 1 })

const loadClientPackages = () =>
  store.loadClientPackages({ status: statusFilter.value ?? undefined })

const openCreate = () => {
  editing.value = null
  formVisible.value = true
}

const openEdit = (item: any) => {
  editing.value = item
  formVisible.value = true
}

const onSaved = (message: string) => {
  formVisible.value = false
  toast.add({ severity: 'success', summary: 'Listo', detail: message, life: 3000 })
}

const onSold = (message: string) => {
  sellVisible.value = false
  activeTab.value = 'clients'
  toast.add({ severity: 'success', summary: 'Listo', detail: message, life: 3000 })
}

const confirmDelete = (item: any) => {
  confirm.require({
    header: 'Eliminar paquete',
    message: `¿Eliminar "${item.name}"? Si ya fue vendido se desactivará para conservar el historial.`,
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Eliminar',
    rejectLabel: 'Cancelar',
    acceptProps: { severity: 'danger' },
    accept: async () => {
      try {
        const response = await store.deletePackage(item.id)
        toast.add({ severity: 'success', summary: 'Listo', detail: response.message, life: 4000 })
      } catch (err) {
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: extractMessage(err, 'No se pudo eliminar el paquete.'),
          life: 5000,
        })
      }
    },
  })
}

// Los paquetes de clientes se cargan solo al abrir su pestaña, para no pedir
// datos que quizá no se miren.
watch(activeTab, (tab) => {
  if (tab === 'clients' && store.clientPackages.length === 0) loadClientPackages()
})

onMounted(() => store.load({ page: 1 }))
</script>

<style scoped lang="scss">
.client-filters {
  margin: 1rem 0;
}

.sessions {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;

  &__bar {
    height: 6px;
  }

  &__text {
    font-size: 0.75rem;
    color: #6b7280;
  }
}

.text-danger {
  color: #b91c1c;
}
</style>
