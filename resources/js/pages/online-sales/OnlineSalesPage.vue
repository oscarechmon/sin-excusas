<template>
  <div class="online-sales-page">
    <div class="page-header">
      <div>
        <h1 class="page-header__title">Ventas online</h1>
        <p class="page-header__subtitle">
          Pedidos de la web pagados con Izipay y el seguimiento de cada entrega.
        </p>
      </div>
    </div>

    <Tabs v-model:value="tab">
      <TabList>
        <Tab value="orders">Pedidos</Tab>
        <Tab value="settings">Envíos</Tab>
      </TabList>

      <TabPanels>
        <TabPanel value="orders">
          <ErpNotice what="Los pedidos online" detail="Su preparación, envío y entrega se mueven allá, igual que los costos de envío; aquí se consultan." :syncable="false" />
          <Card class="filter-card">
            <template #content>
              <div class="filters">
                <IconField>
                  <InputIcon class="pi pi-search" />
                  <InputText v-model="search" placeholder="Código, cliente o correo" @keyup.enter="reload" />
                </IconField>
                <Select
                  v-model="status"
                  :options="statusOptions"
                  option-label="label"
                  option-value="value"
                  placeholder="Estado"
                  show-clear
                  @change="reload"
                />
                <Select
                  v-model="fulfillment"
                  :options="fulfillmentOptions"
                  option-label="label"
                  option-value="value"
                  placeholder="Entrega"
                  show-clear
                  @change="reload"
                />
                <Button icon="pi pi-search" label="Buscar" outlined @click="reload" />
              </div>
            </template>
          </Card>

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
              <p class="table-empty">Aún no hay pedidos de la web.</p>
            </template>

            <Column header="Pedido" :style="{ width: '130px' }">
              <template #body="{ data }">
                <span class="cell-strong">{{ data.code }}</span>
              </template>
            </Column>
            <Column header="Fecha" :style="{ width: '150px' }">
              <template #body="{ data }">
                <span class="cell-muted">{{ format.dateTime(data.created_at) }}</span>
              </template>
            </Column>
            <Column header="Cliente web">
              <template #body="{ data }">
                <span class="cell-strong">{{ data.customer?.name }}</span>
                <span class="cell-muted cell-block">{{ data.customer?.email }}</span>
              </template>
            </Column>
            <Column header="Entrega" :style="{ width: '160px' }">
              <template #body="{ data }">
                <i :class="fulfillmentIcon(data.fulfillment)" class="fulfillment-icon" />
                {{ data.fulfillment_label }}
              </template>
            </Column>
            <Column header="Total" :style="{ width: '120px' }">
              <template #body="{ data }">
                <span class="cell-amount cell-strong">{{ format.money(data.total) }}</span>
              </template>
            </Column>
            <Column header="Estado" :style="{ width: '160px' }">
              <template #body="{ data }">
                <Tag :value="data.status_label" :severity="data.status_color" />
              </template>
            </Column>
            <Column header="" :style="{ width: '70px' }">
              <template #body="{ data }">
                <Button icon="pi pi-eye" text rounded severity="secondary" aria-label="Ver pedido" @click="openDetail(data)" />
              </template>
            </Column>
          </DataTable>
        </TabPanel>

        <TabPanel value="settings">
          <Card class="settings-card">
            <template #title>Costos de envío</template>
            <template #subtitle>
              Se suman al total según cómo el cliente recibe su pedido. Solo aplican a pedidos con productos:
              los servicios se atienden en el centro.
            </template>
            <template #content>
              <div class="settings-form">
                <Message v-if="managedByErp" severity="info" :closable="false">
                  Se cambian en el
                  <a :href="erp.url ?? '#'" target="_blank" rel="noopener">sistema</a>:
                  Pedidos online → Costos de envío. Aquí ves lo que la web cobra hoy.
                </Message>
                <div v-for="zone in zones" :key="zone.key" class="shipping-zone">
                  <div class="form-field form-field--inline">
                    <ToggleSwitch v-model="settingsForm[zone.key].enabled" :input-id="zone.key + '_enabled'" :disabled="!editable" />
                    <label :for="zone.key + '_enabled'">
                      <strong>{{ zone.label }}</strong>
                      <span class="cell-block zone-hint">{{ zone.hint }}</span>
                    </label>
                  </div>
                  <InputNumber
                    v-model="settingsForm[zone.key].fee"
                    :input-id="zone.key + '_fee'"
                    mode="currency"
                    currency="PEN"
                    locale="es-PE"
                    :min="0"
                    :disabled="!editable || !settingsForm[zone.key].enabled"
                  />
                </div>
                <Message v-if="!managedByErp && !canConfigure" severity="info" :closable="false">
                  Solo quien tiene el permiso de configuración puede cambiar los costos.
                </Message>
                <div v-if="!managedByErp">
                  <Button label="Guardar" icon="pi pi-check" :loading="savingSettings" :disabled="!canConfigure" @click="saveSettings" />
                </div>
              </div>
            </template>
          </Card>
        </TabPanel>
      </TabPanels>
    </Tabs>

    <OnlineOrderDetailDialog v-model:visible="detailVisible" :order-id="selectedId" />
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useOnlineSalesStore } from '@/stores/onlineSales'
import { useAuthStore } from '@/stores/auth'
import { useFormat } from '@/composables/useFormat'
import { extractMessage } from '@/composables/usePaginatedList'
import OnlineOrderDetailDialog from './OnlineOrderDetailDialog.vue'
import ErpNotice from '@/components/common/ErpNotice.vue'
import { useErp } from '@/composables/useErp'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import InputNumber from 'primevue/inputnumber'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import Select from 'primevue/select'
import Tab from 'primevue/tab'
import TabList from 'primevue/tablist'
import TabPanel from 'primevue/tabpanel'
import TabPanels from 'primevue/tabpanels'
import Tabs from 'primevue/tabs'
import Tag from 'primevue/tag'
import ToggleSwitch from 'primevue/toggleswitch'

const store = useOnlineSalesStore()
const { erp } = useErp()

const fulfillmentIcon = (value: string) =>
  ({ delivery: 'pi pi-truck', province: 'pi pi-send' } as Record<string, string>)[value] ?? 'pi pi-shop'
const auth = useAuthStore()
const toast = useToast()
const format = useFormat()

const tab = ref('orders')
const search = ref('')
const status = ref<string | null>(null)
const fulfillment = ref<string | null>(null)
const detailVisible = ref(false)
const selectedId = ref<number | null>(null)

const canConfigure = computed(() => auth.hasPermission('settings.manage'))

const statusOptions = [
  { label: 'Pendiente de pago', value: 'pending_payment' },
  { label: 'Pago rechazado', value: 'payment_failed' },
  { label: 'Pagado', value: 'paid' },
  { label: 'En preparación', value: 'preparing' },
  { label: 'En camino', value: 'shipped' },
  { label: 'Listo para recoger', value: 'ready_for_pickup' },
  { label: 'Entregado', value: 'delivered' },
  { label: 'Anulado', value: 'cancelled' },
]

const fulfillmentOptions = [
  { label: 'Delivery en Lima', value: 'delivery' },
  { label: 'Envío a provincia', value: 'province' },
  { label: 'Recojo en el centro', value: 'pickup' },
]

const zones = [
  { key: 'delivery' as const, label: 'Delivery en Lima', hint: 'A la dirección del cliente.' },
  { key: 'province' as const, label: 'Envío a provincia (Shalom)', hint: 'Se recoge en la agencia Shalom con DNI o CE.' },
]

const params = (page = 1) => ({
  page,
  search: search.value || undefined,
  status: status.value ?? undefined,
  fulfillment: fulfillment.value ?? undefined,
})

const reload = () => store.load(params(1))
const onPage = (event: { page: number }) => store.load(params(event.page + 1))

const openDetail = (order: any) => {
  selectedId.value = order.id
  detailVisible.value = true
}

// Al cerrar el detalle se recarga: el estado pudo cambiar dentro del diálogo.
watch(detailVisible, (open) => {
  if (!open) store.reload()
})

const settingsForm = ref({
  delivery: { enabled: true, fee: 10 },
  province: { enabled: true, fee: 12 },
})
const managedByErp = computed(() => store.settings?.managed_by_erp ?? false)
const editable = computed(() => canConfigure.value && !managedByErp.value)
const savingSettings = ref(false)

const loadSettings = async () => {
  try {
    await store.loadSettings()
    if (store.settings) {
      settingsForm.value = {
        delivery: { ...store.settings.delivery },
        province: { ...store.settings.province },
      }
    }
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Error', detail: extractMessage(err, 'No se pudo cargar la configuración.'), life: 5000 })
  }
}

const saveSettings = async () => {
  savingSettings.value = true
  try {
    const response = await store.saveSettings({
      delivery: { enabled: settingsForm.value.delivery.enabled, fee: Number(settingsForm.value.delivery.fee ?? 0) },
      province: { enabled: settingsForm.value.province.enabled, fee: Number(settingsForm.value.province.fee ?? 0) },
    })
    toast.add({ severity: 'success', summary: 'Listo', detail: response.message, life: 3000 })
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Error', detail: extractMessage(err, 'No se pudo guardar.'), life: 5000 })
  } finally {
    savingSettings.value = false
  }
}

watch(tab, (value) => {
  if (value === 'settings' && !store.settings) loadSettings()
})

onMounted(reload)
</script>

<style scoped lang="scss">
.cell-block {
  display: block;
  font-size: 0.8rem;
}

.fulfillment-icon {
  margin-right: 0.35rem;
  color: #6b7280;
}

.settings-card {
  max-width: 560px;
  margin-top: 1rem;
}

.settings-form {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.shipping-zone {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.85rem 1rem;
  border: 1px solid var(--p-content-border-color, #e5e7eb);
  border-radius: 10px;
}

.zone-hint {
  color: #6b7280;
}
</style>
