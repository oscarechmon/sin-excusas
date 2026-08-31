<template>
  <div class="services-page">
    <div class="page-header">
      <h1>Servicios</h1>
      <Button
        label="Nuevo Servicio"
        icon="pi pi-plus"
        @click="openNewServiceDialog"
        class="p-button-primary"
      />
    </div>

    <div class="filters">
      <div class="filter-group">
        <label>Categoría:</label>
        <Select
          v-model="selectedCategoryFilter"
          :options="servicesStore.categories"
          option-label="name"
          option-value="id"
          placeholder="Todas las categorías"
          @change="loadServices(1)"
          :show-clear="true"
        />
      </div>
      <div class="filter-group">
        <label>Estado:</label>
        <Select
          v-model="selectedStatusFilter"
          :options="statusOptions"
          placeholder="Todos los estados"
          @change="loadServices(1)"
          :show-clear="true"
        />
      </div>
    </div>

    <DataTable
      :value="servicesStore.services"
      :loading="servicesStore.loading"
      striped-rows
      responsive-layout="scroll"
      class="services-table"
      lazy
      :paginator="true"
      :rows="15"
      :total-records="servicesStore.total"
      :first="(servicesStore.currentPage - 1) * 15"
      @page="onPageChange"
    >
      <Column field="id" header="ID" :style="{ width: '60px' }" />
      <Column field="name" header="Nombre" />
      <Column field="category.name" header="Categoría" />
      <Column field="price" header="Precio">
        <template #body="slotProps">
          {{ formatCurrency(slotProps.data.price) }}
        </template>
      </Column>
      <Column field="duration_minutes" header="Duración (min)" :style="{ width: '120px' }" />
      <Column header="Estado">
        <template #body="slotProps">
          <Tag
            :value="slotProps.data.active ? 'Activo' : 'Inactivo'"
            :severity="slotProps.data.active ? 'success' : 'danger'"
          />
        </template>
      </Column>
      <Column header="Acciones" :style="{ width: '150px' }">
        <template #body="slotProps">
          <Button
            icon="pi pi-pencil"
            class="p-button-sm p-button-warning"
            @click="editService(slotProps.data)"
            text
          />
          <Button
            icon="pi pi-trash"
            class="p-button-sm p-button-danger"
            @click="confirmDeleteService(slotProps.data.id)"
            text
          />
        </template>
      </Column>
    </DataTable>

    <ServiceFormDialog
      v-if="showServiceDialog"
      :visible="showServiceDialog"
      :service="selectedService"
      @close="showServiceDialog = false"
      @submit="handleServiceSubmit"
    />

  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { useServicesStore } from '@/stores/services';
import ServiceFormDialog from './ServiceFormDialog.vue';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Select from 'primevue/select';
import Tag from 'primevue/tag';

const servicesStore = useServicesStore();
const confirm = useConfirm();
const toast = useToast();

const showServiceDialog = ref(false);
const selectedService = ref<any | null>(null);
const selectedCategoryFilter = ref<number | null>(null);
const selectedStatusFilter = ref<boolean | null>(null);

const statusOptions = [
  { label: 'Activos', value: true },
  { label: 'Inactivos', value: false },
];

onMounted(() => {
  servicesStore.loadCategories();
  loadServices(1);
});

const loadServices = async (page: number) => {
  await servicesStore.loadServices(
    page,
    selectedCategoryFilter.value || undefined,
    selectedStatusFilter.value
  );
};

const onPageChange = (event: any) => {
  loadServices(event.page + 1);
};

const openNewServiceDialog = () => {
  selectedService.value = null;
  showServiceDialog.value = true;
};

const editService = (service: any) => {
  selectedService.value = service;
  showServiceDialog.value = true;
};

const confirmDeleteService = (id: number) => {
  confirm.require({
    message: '¿Estás seguro de que quieres eliminar este servicio?',
    header: 'Confirmar eliminación',
    icon: 'pi pi-exclamation-triangle',
    accept: async () => {
      try {
        await servicesStore.deleteService(id);
        toast.add({ severity: 'success', summary: 'Éxito', detail: 'Servicio eliminado' });
      } catch (err: any) {
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: err.message || 'Error al eliminar servicio',
        });
      }
    },
  });
};

const handleServiceSubmit = async (payload: any) => {
  try {
    if (selectedService.value) {
      await servicesStore.updateService(selectedService.value.id, payload);
      toast.add({ severity: 'success', summary: 'Éxito', detail: 'Servicio actualizado' });
    } else {
      await servicesStore.createService(payload);
      toast.add({ severity: 'success', summary: 'Éxito', detail: 'Servicio creado' });
    }
    showServiceDialog.value = false;
    await loadServices(1);
  } catch (err: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.message || 'Error al guardar servicio',
    });
  }
};

const formatCurrency = (value: number) => {
  return new Intl.NumberFormat('es-PE', {
    style: 'currency',
    currency: 'PEN',
  }).format(value);
};
</script>

<style scoped lang="scss">
.services-page {
  padding: 2rem;

  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;

    h1 {
      margin: 0;
      font-size: 1.75rem;
    }
  }

  .filters {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;

    .filter-group {
      display: flex;
      flex-direction: column;
      gap: 0.5rem;

      label {
        font-weight: 500;
        font-size: 0.875rem;
      }
    }
  }
}
</style>

