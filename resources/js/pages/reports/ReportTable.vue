<template>
  <Card class="report-table">
    <template #content>
      <h3 class="report-table__title">{{ title }}</h3>

      <DataTable :value="rows" size="small">
        <template #empty>
          <p class="table-empty">Sin datos en el periodo seleccionado.</p>
        </template>

        <Column v-for="column in columns" :key="column.field" :header="column.header">
          <template #body="{ data }">
            <span v-if="column.money" class="cell-amount cell-strong">
              {{ format.money(data[column.field]) }}
            </span>
            <span v-else-if="column.numeric" class="cell-amount">
              {{ data[column.field] ?? 0 }}
            </span>
            <span v-else>{{ data[column.field] ?? '—' }}</span>
          </template>
        </Column>
      </DataTable>

      <div v-if="moneyTotal !== null" class="report-table__total">
        <span>Total</span>
        <strong>{{ format.money(moneyTotal) }}</strong>
      </div>
    </template>
  </Card>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useFormat } from '@/composables/useFormat'
import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'

interface ReportColumn {
  field: string
  header: string
  money?: boolean
  numeric?: boolean
}

const props = defineProps<{ title: string; rows: any[]; columns: ReportColumn[] }>()

const format = useFormat()

/**
 * Suma la columna monetaria, si la hay. Un reporte sin total obliga a sumar
 * a mano, que es justo lo que se quiere evitar.
 */
const moneyTotal = computed<number | null>(() => {
  const moneyColumn = props.columns.find((c) => c.money)
  if (!moneyColumn || props.rows.length === 0) return null

  return props.rows.reduce((sum, row) => sum + Number(row[moneyColumn.field] ?? 0), 0)
})
</script>

<style scoped lang="scss">
.report-table {
  &__title {
    margin: 0 0 0.75rem;
    font-size: 0.9375rem;
    font-weight: 600;
    color: #374151;
  }

  &__total {
    display: flex;
    justify-content: space-between;
    padding: 0.75rem 0 0;
    margin-top: 0.5rem;
    border-top: 1px solid #e5e7eb;
    font-size: 0.875rem;
    color: #4b5563;

    strong {
      font-variant-numeric: tabular-nums;
      color: #111827;
    }
  }
}
</style>
