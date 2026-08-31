<template>
  <div class="dashboard-page">
    <div class="page-header">
      <div>
        <h1 class="page-header__title">Dashboard</h1>
        <p class="page-header__subtitle">Resumen operativo de hoy</p>
      </div>
      <div class="page-header__actions">
        <Button icon="pi pi-refresh" label="Actualizar" outlined severity="secondary" :loading="loading" @click="load" />
      </div>
    </div>

    <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>

    <div class="metrics-grid">
      <Card v-for="metric in metrics" :key="metric.label" class="metric-card">
        <template #content>
          <div class="metric">
            <span class="metric__icon" :class="`metric__icon--${metric.tone}`">
              <i :class="metric.icon" />
            </span>
            <div class="metric__info">
              <p class="metric__label">{{ metric.label }}</p>
              <p class="metric__value">
                <Skeleton v-if="loading" width="4rem" height="1.5rem" />
                <template v-else>{{ metric.value }}</template>
              </p>
              <p v-if="metric.hint" class="metric__hint">{{ metric.hint }}</p>
            </div>
          </div>
        </template>
      </Card>
    </div>

    <div class="dashboard-columns">
      <Card class="alerts-card">
        <template #content>
          <h2 class="section-title">Requiere atención</h2>

          <ul v-if="alerts.length" class="alerts">
            <li v-for="alert in alerts" :key="alert.label" class="alert">
              <i :class="alert.icon" class="alert__icon" />
              <span class="alert__text">{{ alert.label }}</span>
              <Button
                v-if="alert.route"
                label="Ver"
                size="small"
                text
                @click="router.push({ name: alert.route })"
              />
            </li>
          </ul>

          <p v-else class="alerts-empty">Nada pendiente. Todo en orden.</p>
        </template>
      </Card>

      <Card class="actions-card">
        <template #content>
          <h2 class="section-title">Accesos rápidos</h2>
          <div class="quick-actions">
            <Button
              v-for="action in quickActions"
              :key="action.label"
              :label="action.label"
              :icon="action.icon"
              severity="secondary"
              outlined
              @click="router.push({ name: action.route })"
            />
          </div>
        </template>
      </Card>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { dashboardApi } from '@/api/reports.api'
import { useAuthStore } from '@/stores/auth'
import { useFormat } from '@/composables/useFormat'
import { extractMessage } from '@/composables/usePaginatedList'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Message from 'primevue/message'
import Skeleton from 'primevue/skeleton'

const router = useRouter()
const authStore = useAuthStore()
const format = useFormat()

const loading = ref(true)
const error = ref<string | null>(null)
const data = ref<any>({})

const metrics = computed(() => [
  {
    label: 'Ventas de hoy',
    value: format.money(data.value.sales_today ?? 0),
    icon: 'pi pi-shopping-cart',
    tone: 'success',
  },
  {
    label: 'Citas de hoy',
    value: String(data.value.appointments_today ?? 0),
    hint: `${data.value.pending_appointments ?? 0} sin atender`,
    icon: 'pi pi-calendar',
    tone: 'info',
  },
  {
    label: 'Caja',
    value: data.value.cash?.is_open
      ? format.money(data.value.cash.expected)
      : 'Cerrada',
    hint: data.value.cash?.is_open ? 'Esperado en efectivo' : 'Sin caja abierta',
    icon: 'pi pi-wallet',
    tone: 'warning',
  },
  {
    label: 'Sesiones pendientes',
    value: String(data.value.pending_package_sessions ?? 0),
    hint: 'De paquetes vigentes',
    icon: 'pi pi-box',
    tone: 'primary',
  },
])

/**
 * Solo se muestran alertas accionables, y únicamente si el usuario tiene
 * permiso sobre el módulo al que llevan: enlazar a una pantalla prohibida
 * sería un callejón sin salida.
 */
const alerts = computed(() => {
  const items: { label: string; icon: string; route?: string }[] = []

  if ((data.value.low_stock_items ?? 0) > 0 && authStore.hasPermission('inventory.view')) {
    items.push({
      label: `${data.value.low_stock_items} producto(s) en stock mínimo o por debajo`,
      icon: 'pi pi-exclamation-triangle',
      route: 'inventory',
    })
  }

  if ((data.value.pending_appointments ?? 0) > 0 && authStore.hasPermission('appointments.view')) {
    items.push({
      label: `${data.value.pending_appointments} cita(s) de hoy sin atender`,
      icon: 'pi pi-clock',
      route: 'appointments',
    })
  }

  if ((data.value.pending_commissions ?? 0) > 0 && authStore.hasPermission('commissions.view')) {
    items.push({
      label: `${format.money(data.value.pending_commissions)} en comisiones por pagar`,
      icon: 'pi pi-percentage',
      route: 'commissions',
    })
  }

  if (data.value.cash && !data.value.cash.is_open && authStore.hasPermission('cash.open')) {
    items.push({ label: 'La caja del día no está abierta', icon: 'pi pi-lock', route: 'cash' })
  }

  return items
})

const allQuickActions = [
  { label: 'Nueva cita', icon: 'pi pi-calendar-plus', route: 'appointments', permission: 'appointments.create' },
  { label: 'Nuevo cliente', icon: 'pi pi-user-plus', route: 'clients', permission: 'clients.create' },
  { label: 'Nueva venta', icon: 'pi pi-shopping-cart', route: 'sales', permission: 'sales.create' },
  { label: 'Registrar atención', icon: 'pi pi-check', route: 'attendances', permission: 'attendances.create' },
]

const quickActions = computed(() =>
  allQuickActions.filter((action) => authStore.hasPermission(action.permission))
)

const load = async () => {
  loading.value = true
  error.value = null

  try {
    const response = await dashboardApi.metrics()
    data.value = response.data
  } catch (err) {
    error.value = extractMessage(err, 'No se pudieron cargar los indicadores.')
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>
