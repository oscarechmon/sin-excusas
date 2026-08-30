<template>
  <div class="table-view">
    <DataTable
      :value="appointments"
      :loading="loading"
      striped-rows
      responsive-layout="scroll"
      lazy
      :paginator="true"
      :rows="15"
      :total-records="total"
      @page="onPageChange"
    >
      <Column field="appointment_date" header="Fecha">
        <template #body="slotProps">
          {{ formatDate(slotProps.data.appointment_date) }}
        </template>
      </Column>
      <Column field="start_time" header="Hora" :style="{ width: '100px' }" />
      <Column field="client.full_name" header="Cliente" />
      <Column field="service.name" header="Servicio" />
      <Column field="employee.name" header="Especialista" />
      <Column header="Estado">
        <template #body="slotProps">
          <Tag
            :value="slotProps.data.status_label"
            :severity="slotProps.data.status_color"
          />
        </template>
      </Column>
      <Column header="Acciones" :style="{ width: '150px' }">
        <template #body="slotProps">
          <Button
            icon="pi pi-pencil"
            class="p-button-sm p-button-warning"
            @click="emit('edit', slotProps.data)"
            text
          />
          <Button
            icon="pi pi-trash"
            class="p-button-sm p-button-danger"
            @click="emit('delete', slotProps.data.id)"
            text
          />
        </template>
      </Column>
    </DataTable>
  </div>
</template>

<script setup lang="ts">
interface Props {
  appointments: any[];
  loading: boolean;
  currentPage: number;
  lastPage: number;
  total?: number;
}

interface Emits {
  (e: 'edit', appointment: any): void;
  (e: 'delete', id: number): void;
  (e: 'page-change', page: number): void;
}

const props = withDefaults(defineProps<Props>(), { total: 0 });
const emit = defineEmits<Emits>();

const total = computed(() => props.total || props.appointments.length);

const onPageChange = (event: any) => {
  emit('page-change', event.page + 1);
};

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('es-PE', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
  });
};
</script>

<style scoped lang="scss">
.table-view {
  background: white;
  border-radius: 0.5rem;
  padding: 1rem;
}
</style>
