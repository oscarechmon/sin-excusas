<template>
  <Dialog
    :visible="visible"
    modal
    header="Detalle de la atención"
    :style="{ width: '560px' }"
    @update:visible="$emit('update:visible', $event)"
  >
    <div v-if="attendance" class="detail">
      <dl class="detail__grid">
        <div class="detail__item">
          <dt>Fecha</dt>
          <dd>{{ format.date(attendance.attended_at) }}</dd>
        </div>
        <div class="detail__item">
          <dt>Cliente</dt>
          <dd>{{ attendance.client?.full_name ?? '—' }}</dd>
        </div>
        <div class="detail__item">
          <dt>Servicio</dt>
          <dd>{{ attendance.service?.name ?? '—' }}</dd>
        </div>
        <div class="detail__item">
          <dt>Especialista</dt>
          <dd>{{ attendance.employee?.name ?? '—' }}</dd>
        </div>
        <div class="detail__item">
          <dt>Paquete</dt>
          <dd>
            <template v-if="attendance.client_package">
              {{ attendance.client_package.package_name }} (sesión {{ attendance.session_number }})
            </template>
            <template v-else>Servicio suelto</template>
          </dd>
        </div>
        <div class="detail__item">
          <dt>Comisión generada</dt>
          <dd>{{ attendance.commission ? format.money(attendance.commission.amount) : 'Sin comisión' }}</dd>
        </div>
      </dl>

      <section v-if="attendance.supplies?.length" class="detail__section">
        <h3 class="detail__title">Insumos consumidos</h3>
        <ul class="supply-list">
          <li v-for="supply in attendance.supplies" :key="supply.inventory_item_id">
            <span>{{ supply.name }}</span>
            <span class="cell-amount">{{ format.quantity(supply.quantity, supply.unit) }}</span>
          </li>
        </ul>
      </section>

      <section v-if="attendance.observations" class="detail__section">
        <h3 class="detail__title">Observaciones</h3>
        <p class="detail__text">{{ attendance.observations }}</p>
      </section>

      <section v-if="attendance.measurements" class="detail__section">
        <h3 class="detail__title">Medidas</h3>
        <p class="detail__text">{{ attendance.measurements }}</p>
      </section>
    </div>

    <template #footer>
      <Button label="Cerrar" text severity="secondary" @click="$emit('update:visible', false)" />
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import { useFormat } from '@/composables/useFormat'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'

defineProps<{ visible: boolean; attendance: any | null }>()
defineEmits<{ 'update:visible': [boolean] }>()

const format = useFormat()
</script>

<style scoped lang="scss">
.detail {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;

  &__grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 0.75rem 1.5rem;
    margin: 0;
  }

  &__item {
    dt {
      font-size: 0.75rem;
      color: #6b7280;
      margin-bottom: 0.125rem;
    }

    dd {
      margin: 0;
      font-size: 0.9375rem;
      color: #111827;
    }
  }

  &__title {
    margin: 0 0 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
  }

  &__text {
    margin: 0;
    font-size: 0.875rem;
    color: #4b5563;
    white-space: pre-wrap;
  }
}

.supply-list {
  list-style: none;
  margin: 0;
  padding: 0;
  font-size: 0.875rem;

  li {
    display: flex;
    justify-content: space-between;
    padding: 0.375rem 0;
    border-bottom: 1px solid #f3f4f6;

    &:last-child {
      border-bottom: none;
    }
  }
}
</style>
