<template>
  <div class="cash-page">
    <div class="page-header">
      <div>
        <h1 class="page-header__title">Caja</h1>
        <p class="page-header__subtitle">
          El saldo se calcula desde los movimientos; no se edita a mano.
        </p>
      </div>
      <div class="page-header__actions">
        <template v-if="store.isOpen">
          <Button label="Registrar egreso" icon="pi pi-minus-circle" outlined severity="secondary" @click="expenseVisible = true" />
          <Button label="Cerrar caja" icon="pi pi-lock" severity="danger" @click="closeVisible = true" />
        </template>
        <Button v-else label="Abrir caja" icon="pi pi-unlock" @click="openVisible = true" />
      </div>
    </div>

    <ProgressSpinner v-if="store.loading && !store.session" class="page-spinner" />

    <template v-else-if="store.isOpen">
      <div class="summary-grid">
        <div class="summary-tile">
          <p class="summary-tile__label">Monto de apertura</p>
          <p class="summary-tile__value">{{ format.money(store.session.opening_amount) }}</p>
        </div>
        <div class="summary-tile summary-tile--success">
          <p class="summary-tile__label">Esperado en efectivo</p>
          <p class="summary-tile__value">{{ format.money(store.session.current_expected) }}</p>
        </div>
        <div class="summary-tile">
          <p class="summary-tile__label">Abierta desde</p>
          <p class="summary-tile__value summary-tile__value--small">
            {{ format.dateTime(store.session.opened_at) }}
          </p>
        </div>
        <div class="summary-tile">
          <p class="summary-tile__label">Responsable</p>
          <p class="summary-tile__value summary-tile__value--small">
            {{ store.session.opened_by ?? '—' }}
          </p>
        </div>
      </div>

      <Card v-if="methodTotals.length" class="methods-card">
        <template #content>
          <h3 class="section-title">Cobrado por método de pago</h3>
          <div class="methods">
            <div v-for="method in methodTotals" :key="method.name" class="method">
              <span class="method__name">{{ method.name }}</span>
              <span class="method__amount">{{ format.money(method.total) }}</span>
            </div>
          </div>
          <small class="form-hint">
            Solo el efectivo se compara contra el arqueo físico; lo cobrado por Yape,
            Plin o POS no está en el cajón.
          </small>
        </template>
      </Card>

      <Card>
        <template #content>
          <h3 class="section-title">Movimientos de la sesión</h3>
          <DataTable :value="store.session.movements" scrollable scroll-height="380px">
            <template #empty>
              <p class="table-empty">Todavía no hay movimientos.</p>
            </template>

            <Column header="Hora" :style="{ width: '150px' }">
              <template #body="{ data }">
                <span class="cell-muted">{{ format.dateTime(data.created_at) }}</span>
              </template>
            </Column>
            <Column header="Tipo" :style="{ width: '130px' }">
              <template #body="{ data }">
                <Tag :value="data.type_label" :severity="data.type_color" />
              </template>
            </Column>
            <Column field="description" header="Descripción" />
            <Column header="Método" :style="{ width: '130px' }">
              <template #body="{ data }">
                <span class="cell-muted">{{ data.payment_method ?? '—' }}</span>
              </template>
            </Column>
            <Column header="Monto" :style="{ width: '120px' }">
              <template #body="{ data }">
                <span class="cell-amount" :class="data.amount >= 0 ? 'delta-in' : 'delta-out'">
                  {{ format.money(data.amount) }}
                </span>
              </template>
            </Column>
          </DataTable>
        </template>
      </Card>
    </template>

    <Card v-else class="closed-card">
      <template #content>
        <div class="closed">
          <i class="pi pi-lock closed__icon" />
          <h2 class="closed__title">No hay una caja abierta</h2>
          <p class="closed__text">
            Abra la caja para registrar cobros y egresos del día. Los pagos se
            registran igual, pero no quedarán asociados a una sesión de caja.
          </p>
          <Button label="Abrir caja" icon="pi pi-unlock" @click="openVisible = true" />
        </div>
      </template>
    </Card>

    <OpenCashDialog v-model:visible="openVisible" @saved="onAction" />
    <CloseCashDialog v-model:visible="closeVisible" :session="store.session" @saved="onAction" />
    <CashExpenseDialog v-model:visible="expenseVisible" @saved="onAction" />
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useCashStore } from '@/stores/cash'
import { useFormat } from '@/composables/useFormat'
import OpenCashDialog from './OpenCashDialog.vue'
import CloseCashDialog from './CloseCashDialog.vue'
import CashExpenseDialog from './CashExpenseDialog.vue'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import ProgressSpinner from 'primevue/progressspinner'
import Tag from 'primevue/tag'

const store = useCashStore()
const toast = useToast()
const format = useFormat()

const openVisible = ref(false)
const closeVisible = ref(false)
const expenseVisible = ref(false)

const methodTotals = computed(() =>
  Object.entries(store.totalsByMethod).map(([name, total]) => ({ name, total }))
)

const onAction = (message: string) => {
  openVisible.value = false
  closeVisible.value = false
  expenseVisible.value = false
  toast.add({ severity: 'success', summary: 'Listo', detail: message, life: 5000 })
}

onMounted(() => store.loadCurrent())
</script>

<style scoped lang="scss">
.page-spinner {
  display: block;
  margin: 3rem auto;
  width: 44px;
  height: 44px;
}

.section-title {
  margin: 0 0 0.75rem;
  font-size: 0.9375rem;
  font-weight: 600;
  color: #374151;
}

.methods-card {
  margin-bottom: 1rem;
}

.methods {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 0.75rem;
  margin-bottom: 0.5rem;
}

.method {
  display: flex;
  justify-content: space-between;
  padding: 0.5rem 0.75rem;
  border-radius: 0.375rem;
  background-color: #f9fafb;
  font-size: 0.875rem;

  &__amount {
    font-weight: 600;
    font-variant-numeric: tabular-nums;
  }
}

.summary-tile__value--small {
  font-size: 0.9375rem;
}

.delta-in {
  color: #047857;
  font-weight: 600;
}

.delta-out {
  color: #b91c1c;
  font-weight: 600;
}

.closed {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
  padding: 2.5rem 1rem;
  text-align: center;

  &__icon {
    font-size: 2rem;
    color: #9ca3af;
  }

  &__title {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: #111827;
  }

  &__text {
    margin: 0;
    max-width: 440px;
    color: #6b7280;
    font-size: 0.875rem;
  }
}

.form-hint {
  font-size: 0.75rem;
  color: #6b7280;
}
</style>
