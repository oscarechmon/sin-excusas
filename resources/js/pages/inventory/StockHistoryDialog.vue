<template>
  <Dialog
    :visible="visible"
    modal
    :header="item ? `Movimientos de ${item.name}` : 'Movimientos'"
    :style="{ width: '760px' }"
    @update:visible="$emit('update:visible', $event)"
  >
    <DataTable :value="store.movements" :loading="store.movementsLoading" scrollable scroll-height="420px">
      <template #empty>
        <p class="table-empty">Este producto todavía no tiene movimientos.</p>
      </template>

      <Column header="Fecha" :style="{ width: '150px' }">
        <template #body="{ data }">
          <span class="cell-muted">{{ format.dateTime(data.created_at) }}</span>
        </template>
      </Column>

      <Column header="Tipo" :style="{ width: '170px' }">
        <template #body="{ data }">
          <Tag :value="data.type_label" :severity="data.type_color" />
        </template>
      </Column>

      <Column header="Cantidad" :style="{ width: '110px' }">
        <template #body="{ data }">
          <span class="cell-amount" :class="data.quantity >= 0 ? 'delta-in' : 'delta-out'">
            {{ data.quantity > 0 ? '+' : '' }}{{ format.quantity(data.quantity) }}
          </span>
        </template>
      </Column>

      <Column header="Saldo" :style="{ width: '100px' }">
        <template #body="{ data }">
          <span class="cell-amount cell-strong">{{ format.quantity(data.stock_after) }}</span>
        </template>
      </Column>

      <Column header="Origen / motivo">
        <template #body="{ data }">
          <span v-if="data.notes">{{ data.notes }}</span>
          <span v-else-if="data.source_type" class="cell-muted">
            {{ sourceLabel(data.source_type) }} #{{ data.source_id }}
          </span>
          <span v-else class="cell-muted">—</span>
        </template>
      </Column>

      <Column header="Usuario" :style="{ width: '140px' }">
        <template #body="{ data }">
          <span class="cell-muted">{{ data.user?.name ?? 'Sistema' }}</span>
        </template>
      </Column>
    </DataTable>

    <template #footer>
      <Button label="Cerrar" text severity="secondary" @click="$emit('update:visible', false)" />
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import { watch } from 'vue'
import { useInventoryStore } from '@/stores/inventory'
import { useFormat } from '@/composables/useFormat'
import Button from 'primevue/button'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Dialog from 'primevue/dialog'
import Tag from 'primevue/tag'

const props = defineProps<{ visible: boolean; item: any | null }>()
defineEmits<{ 'update:visible': [boolean] }>()

const store = useInventoryStore()
const format = useFormat()

/** Traduce la clase del origen polimórfico a algo legible. */
const sourceLabel = (type: string) =>
  ({ Attendance: 'Atención', Sale: 'Venta' })[type] ?? type

watch(
  () => props.visible,
  (open) => {
    if (open && props.item) store.loadMovements(props.item.id)
  }
)
</script>

<style scoped lang="scss">
.delta-in {
  color: #047857;
  font-weight: 600;
}

.delta-out {
  color: #b91c1c;
  font-weight: 600;
}
</style>
