<template>
  <AdminLayout>
    <div class="dashboard-page">
      <h1>Dashboard</h1>

      <div class="metrics-grid">
        <Card class="metric-card">
          <template #content>
            <div class="metric">
              <i class="pi pi-users metric-icon"></i>
              <div class="metric-info">
                <p class="metric-label">Clientes</p>
                <p class="metric-value">0</p>
              </div>
            </div>
          </template>
        </Card>

        <Card class="metric-card">
          <template #content>
            <div class="metric">
              <i class="pi pi-calendar metric-icon"></i>
              <div class="metric-info">
                <p class="metric-label">Citas Hoy</p>
                <p class="metric-value">0</p>
              </div>
            </div>
          </template>
        </Card>

        <Card class="metric-card">
          <template #content>
            <div class="metric">
              <i class="pi pi-shopping-cart metric-icon"></i>
              <div class="metric-info">
                <p class="metric-label">Ventas Hoy</p>
                <p class="metric-value">S/ 0.00</p>
              </div>
            </div>
          </template>
        </Card>

        <Card class="metric-card">
          <template #content>
            <div class="metric">
              <i class="pi pi-wallet metric-icon"></i>
              <div class="metric-info">
                <p class="metric-label">Caja</p>
                <p class="metric-value">S/ 0.00</p>
              </div>
            </div>
          </template>
        </Card>
      </div>

      <div class="quick-actions">
        <h2>Acciones Rápidas</h2>
        <div class="actions-grid">
          <Button label="Nueva Cita" icon="pi pi-calendar-plus" class="p-button-rounded" />
          <Button label="Nuevo Cliente" icon="pi pi-user-plus" class="p-button-rounded" />
          <Button label="Nueva Venta" icon="pi pi-plus" class="p-button-rounded" />
          <Button label="Registrar Atención" icon="pi pi-check" class="p-button-rounded" />
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue'
import Card from 'primevue/card'
import Button from 'primevue/button'
</script>

<style scoped lang="scss">
.dashboard-page {
  h1 {
    margin-top: 0;
    color: #333;
    font-size: 2rem;
  }
}

.metrics-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin: 2rem 0;
}

.metric-card {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);

  :deep(.p-card-content) {
    padding: 1.5rem;
  }
}

.metric {
  display: flex;
  align-items: center;
  gap: 1.5rem;
}

.metric-icon {
  font-size: 2.5rem;
  color: #667eea;
}

.metric-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.metric-label {
  margin: 0;
  font-size: 0.875rem;
  color: #999;
  font-weight: 500;
}

.metric-value {
  margin: 0;
  font-size: 1.75rem;
  font-weight: 700;
  color: #333;
}

.quick-actions {
  margin-top: 3rem;

  h2 {
    color: #333;
    margin-bottom: 1rem;
  }
}

.actions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}
</style>
