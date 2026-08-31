<template>
  <div class="inventory-page">
    <div class="page-header">
      <div>
        <h1 class="page-header__title">Inventario</h1>
        <p class="page-header__subtitle">
          El stock nunca se edita a mano: cada cambio queda registrado como movimiento.
        </p>
      </div>
      <div class="page-header__actions">
        <Button
          label="Categorías"
          icon="pi pi-tags"
          outlined
          severity="secondary"
          @click="categoriesVisible = true"
        />
        <Button label="Nuevo producto" icon="pi pi-plus" @click="openCreate" />
      </div>
    </div>

    <Message v-if="lowStockCount > 0" severity="warn" :closable="false" class="stock-alert">
      {{ lowStockCount }} producto(s) están en el stock mínimo o por debajo.
      <a href="#" @click.prevent="showOnlyLowStock">Ver solo esos</a>
    </Message>

    <Card class="filter-card">
      <template #content>
        <div class="filters">
          <IconField>
            <InputIcon class="pi pi-search" />
            <InputText v-model="search" placeholder="Buscar producto o insumo" @keyup.enter="reload" />
          </IconField>
          <Select
            v-model="categoryId"
            :options="store.categories"
            option-label="name"
            option-value="id"
            placeholder="Categoría"
            show-clear
            @change="reload"
          />
          <ToggleButton
            v-model="onlyLowStock"
            on-label="Solo stock bajo"
            off-label="Todo el stock"
            on-icon="pi pi-exclamation-triangle"
            off-icon="pi pi-box"
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
            <p class="table-empty">No hay productos registrados.</p>
          </template>

          <Column field="name" header="Producto">
            <template #body="{ data }">
              <span class="cell-strong">{{ data.name }}</span>
              <Tag v-if="data.is_sellable" value="Se vende" severity="info" class="item-tag" />
            </template>
          </Column>

          <Column header="Categoría">
            <template #body="{ data }">
              <span class="cell-muted">{{ data.category?.name ?? '—' }}</span>
            </template>
          </Column>

          <Column header="Stock" :style="{ width: '140px' }">
            <template #body="{ data }">
              <span class="cell-amount" :class="{ 'stock-low': data.is_low_stock }">
                {{ format.quantity(data.stock, data.unit) }}
              </span>
            </template>
          </Column>

          <Column header="Mínimo" :style="{ width: '110px' }">
            <template #body="{ data }">
              <span class="cell-amount cell-muted">{{ format.quantity(data.min_stock) }}</span>
            </template>
          </Column>

          <Column header="Costo" :style="{ width: '110px' }">
            <template #body="{ data }">
              <span class="cell-amount">{{ format.money(data.cost) }}</span>
            </template>
          </Column>

          <Column header="Precio venta" :style="{ width: '130px' }">
            <template #body="{ data }">
              <span class="cell-amount">{{ data.sale_price ? format.money(data.sale_price) : '—' }}</span>
            </template>
          </Column>

          <Column header="Acciones" :style="{ width: '170px' }">
            <template #body="{ data }">
              <div class="row-actions">
                <Button
                  icon="pi pi-arrow-right-arrow-left"
                  text
                  rounded
                  severity="secondary"
                  aria-label="Registrar movimiento"
                  @click="openAdjust(data)"
                />
                <Button
                  icon="pi pi-history"
                  text
                  rounded
                  severity="secondary"
                  aria-label="Ver movimientos"
                  @click="openMovements(data)"
                />
                <Button icon="pi pi-pencil" text rounded severity="secondary" @click="openEdit(data)" />
                <Button icon="pi pi-trash" text rounded severity="danger" @click="confirmDelete(data)" />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <InventoryItemDialog v-model:visible="formVisible" :item="editing" @saved="onSaved" />
    <StockMovementDialog v-model:visible="adjustVisible" :item="selected" @saved="onSaved" />
    <StockHistoryDialog v-model:visible="historyVisible" :item="selected" />
    <InventoryCategoriesDialog v-model:visible="categoriesVisible" />
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'
import { useInventoryStore } from '@/stores/inventory'
import { useFormat } from '@/composables/useFormat'
import { extractMessage } from '@/composables/usePaginatedList'
import InventoryItemDialog from './InventoryItemDialog.vue'
import InventoryCategoriesDialog from './InventoryCategoriesDialog.vue'
import StockHistoryDialog from './StockHistoryDialog.vue'
import StockMovementDialog from './StockMovementDialog.vue'
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
import ToggleButton from 'primevue/togglebutton'

const store = useInventoryStore()
const toast = useToast()
const confirm = useConfirm()
const format = useFormat()

const search = ref('')
const categoryId = ref<number | null>(null)
const onlyLowStock = ref(false)

const formVisible = ref(false)
const adjustVisible = ref(false)
const historyVisible = ref(false)
const categoriesVisible = ref(false)
const editing = ref<any | null>(null)
const selected = ref<any | null>(null)

const lowStockCount = computed(
  () => store.items.filter((item: any) => item.is_low_stock).length
)

const params = (page = 1) => ({
  page,
  search: search.value || undefined,
  category_id: categoryId.value ?? undefined,
  low_stock: onlyLowStock.value || undefined,
})

const reload = () => store.load(params(1))
const onPage = (event: { page: number }) => store.load(params(event.page + 1))

const showOnlyLowStock = () => {
  onlyLowStock.value = true
  reload()
}

const openCreate = () => {
  editing.value = null
  formVisible.value = true
}

const openEdit = (item: any) => {
  editing.value = item
  formVisible.value = true
}

const openAdjust = (item: any) => {
  selected.value = item
  adjustVisible.value = true
}

const openMovements = (item: any) => {
  selected.value = item
  historyVisible.value = true
}

const onSaved = (message: string) => {
  formVisible.value = false
  adjustVisible.value = false
  toast.add({ severity: 'success', summary: 'Listo', detail: message, life: 3000 })
}

const confirmDelete = (item: any) => {
  confirm.require({
    header: 'Eliminar producto',
    message: `¿Eliminar "${item.name}"? Si tiene movimientos se desactivará para conservar la trazabilidad.`,
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Eliminar',
    rejectLabel: 'Cancelar',
    acceptProps: { severity: 'danger' },
    accept: async () => {
      try {
        const response = await store.deleteItem(item.id)
        toast.add({ severity: 'success', summary: 'Listo', detail: response.message, life: 4000 })
      } catch (err) {
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: extractMessage(err, 'No se pudo eliminar el producto.'),
          life: 5000,
        })
      }
    },
  })
}

onMounted(() => {
  store.loadCategories()
  reload()
})
</script>

<style scoped lang="scss">
.stock-alert {
  margin-bottom: 1rem;

  a {
    margin-left: 0.5rem;
    text-decoration: underline;
  }
}

// Ámbar y no rojo: el stock bajo es un aviso para reponer, no un error.
.stock-low {
  color: #b45309;
  font-weight: 600;
}

.item-tag {
  margin-left: 0.5rem;
}
</style>
