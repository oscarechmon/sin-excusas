<template>
  <div class="dashboard-page">
    <div class="page-header">
      <div>
        <h1 class="page-header__title">Dashboard</h1>
        <p class="page-header__subtitle">Resumen operativo de hoy</p>
      </div>
    </div>

    <div class="metrics-grid">
      <Card v-for="metric in metrics" :key="metric.label" class="metric-card">
        <template #content>
          <div class="metric">
            <span class="metric__icon" :class="`metric__icon--${metric.tone}`">
              <i :class="metric.icon" />
            </span>
            <div class="metric__info">
              <p class="metric__label">{{ metric.label }}</p>
              <p class="metric__value">{{ metric.value }}</p>
            </div>
          </div>
        </template>
      </Card>
    </div>

    <section class="quick-actions">
      <h2 class="quick-actions__title">Accesos rápidos</h2>
      <div class="quick-actions__grid">
        <Button
          v-for="action in quickActions"
          :key="action.label"
          :label="action.label"
          :icon="action.icon"
          :disabled="!action.route"
          severity="secondary"
          outlined
          @click="action.route && router.push({ name: action.route })"
        />
      </div>
    </section>

    <Message severity="info" :closable="false" class="dashboard-note">
      Los indicadores se conectarán a datos reales al implementar Ventas, Caja y
      Atenciones (fases 7 a 9 de la especificación).
    </Message>
  </div>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Message from 'primevue/message'

const router = useRouter()

const metrics = [
  { label: 'Clientes', value: '0', icon: 'pi pi-users', tone: 'primary' },
  { label: 'Citas de hoy', value: '0', icon: 'pi pi-calendar', tone: 'info' },
  { label: 'Ventas de hoy', value: 'S/ 0.00', icon: 'pi pi-shopping-cart', tone: 'success' },
  { label: 'Caja', value: 'S/ 0.00', icon: 'pi pi-wallet', tone: 'warning' },
]

const quickActions = [
  { label: 'Nueva cita', icon: 'pi pi-calendar-plus', route: 'appointments' },
  { label: 'Nuevo cliente', icon: 'pi pi-user-plus', route: 'clients' },
  { label: 'Nueva venta', icon: 'pi pi-plus', route: null },
  { label: 'Registrar atención', icon: 'pi pi-check', route: null },
]
</script>
