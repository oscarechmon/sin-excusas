<template>
  <AdminLayout>
    <div class="appointments-page">
      <div class="page-header">
        <h1>Agenda</h1>
        <Button
          label="Nueva Cita"
          icon="pi pi-plus"
          @click="openNewAppointmentDialog"
          class="p-button-primary"
        />
      </div>

      <Tabs v-model:activeIndex="activeTab" class="appointments-tabs">
        <TabPanel header="Vista de Día">
          <DayViewAppointments
            :appointments="appointmentsStore.appointments"
            :loading="appointmentsStore.loading"
            @edit="editAppointment"
            @delete="confirmDeleteAppointment"
            @date-change="handleDateChange"
          />
        </TabPanel>
        <TabPanel header="Vista Semanal">
          <WeekViewAppointments
            :appointments="appointmentsStore.appointments"
            :loading="appointmentsStore.loading"
            @edit="editAppointment"
            @delete="confirmDeleteAppointment"
            @week-change="handleWeekChange"
          />
        </TabPanel>
        <TabPanel header="Tabla">
          <TableViewAppointments
            :appointments="appointmentsStore.appointments"
            :loading="appointmentsStore.loading"
            :current-page="appointmentsStore.currentPage"
            :last-page="appointmentsStore.lastPage"
            @edit="editAppointment"
            @delete="confirmDeleteAppointment"
            @page-change="handlePageChange"
          />
        </TabPanel>
      </Tabs>

      <AppointmentFormDialog
        v-if="showAppointmentDialog"
        :visible="showAppointmentDialog"
        :appointment="selectedAppointment"
        @close="showAppointmentDialog = false"
        @submit="handleAppointmentSubmit"
      />

      <Toast />
      <ConfirmDialog />
    </div>
  </AdminLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { useAppointmentsStore } from '@/js/stores/appointments';
import AdminLayout from '@/js/layouts/AdminLayout.vue';
import AppointmentFormDialog from './AppointmentFormDialog.vue';
import DayViewAppointments from './DayViewAppointments.vue';
import WeekViewAppointments from './WeekViewAppointments.vue';
import TableViewAppointments from './TableViewAppointments.vue';

const appointmentsStore = useAppointmentsStore();
const confirm = useConfirm();
const toast = useToast();

const activeTab = ref(0);
const showAppointmentDialog = ref(false);
const selectedAppointment = ref<any | null>(null);
const currentDate = ref(new Date());

onMounted(() => {
  loadDayAppointments(currentDate.value);
});

const loadDayAppointments = (date: Date) => {
  const dateStr = date.toISOString().split('T')[0];
  appointmentsStore.loadByDate(dateStr);
};

const handleDateChange = (date: Date) => {
  currentDate.value = date;
  loadDayAppointments(date);
};

const handleWeekChange = (weekStart: Date) => {
  const weekEnd = new Date(weekStart);
  weekEnd.setDate(weekEnd.getDate() + 6);
  const startStr = weekStart.toISOString().split('T')[0];
  const endStr = weekEnd.toISOString().split('T')[0];
  appointmentsStore.loadByWeek(startStr, endStr);
};

const handlePageChange = (page: number) => {
  appointmentsStore.loadAppointments({ page });
};

const openNewAppointmentDialog = () => {
  selectedAppointment.value = null;
  showAppointmentDialog.value = true;
};

const editAppointment = (appointment: any) => {
  selectedAppointment.value = appointment;
  showAppointmentDialog.value = true;
};

const confirmDeleteAppointment = (id: number) => {
  confirm.require({
    message: '¿Estás seguro de que quieres eliminar esta cita?',
    header: 'Confirmar eliminación',
    icon: 'pi pi-exclamation-triangle',
    accept: async () => {
      try {
        await appointmentsStore.deleteAppointment(id);
        toast.add({ severity: 'success', summary: 'Éxito', detail: 'Cita eliminada' });
      } catch (err: any) {
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: err.message || 'Error al eliminar cita',
        });
      }
    },
  });
};

const handleAppointmentSubmit = async (payload: any) => {
  try {
    if (selectedAppointment.value) {
      await appointmentsStore.updateAppointment(selectedAppointment.value.id, payload);
      toast.add({ severity: 'success', summary: 'Éxito', detail: 'Cita actualizada' });
    } else {
      await appointmentsStore.createAppointment(payload);
      toast.add({ severity: 'success', summary: 'Éxito', detail: 'Cita creada' });
    }
    showAppointmentDialog.value = false;
    loadDayAppointments(currentDate.value);
  } catch (err: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.message || 'Error al guardar cita',
    });
  }
};
</script>

<style scoped lang="scss">
.appointments-page {
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

  .appointments-tabs {
    margin-top: 1rem;
  }
}
</style>
