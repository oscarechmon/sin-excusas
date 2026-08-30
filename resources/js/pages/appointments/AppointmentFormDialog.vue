<template>
  <Dialog
    v-model:visible="isOpen"
    :header="selectedAppointment ? 'Editar Cita' : 'Nueva Cita'"
    :modal="true"
    :style="{ width: '600px' }"
    @hide="emit('close')"
  >
    <div class="form-content">
      <div class="form-group">
        <label for="client_id">Cliente *</label>
        <AutoComplete
          id="client_id"
          v-model="form.client_id"
          :suggestions="filteredClients"
          @complete="searchClients"
          option-label="full_name"
          option-value="id"
          placeholder="Busca un cliente"
          class="w-full"
        />
        <small v-if="errors.client_id" class="error-text">{{ errors.client_id }}</small>
      </div>

      <div class="form-group">
        <label for="service_id">Servicio *</label>
        <Select
          id="service_id"
          v-model="form.service_id"
          :options="services"
          option-label="name"
          option-value="id"
          placeholder="Selecciona un servicio"
          class="w-full"
        />
        <small v-if="errors.service_id" class="error-text">{{ errors.service_id }}</small>
      </div>

      <div class="form-group">
        <label for="employee_id">Especialista *</label>
        <Select
          id="employee_id"
          v-model="form.employee_id"
          :options="employees"
          option-label="name"
          option-value="id"
          placeholder="Selecciona un especialista"
          class="w-full"
        />
        <small v-if="errors.employee_id" class="error-text">{{ errors.employee_id }}</small>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="appointment_date">Fecha *</label>
          <DatePicker
            id="appointment_date"
            v-model="form.appointment_date"
            date-format="dd/mm/yy"
            placeholder="Selecciona fecha"
            class="w-full"
          />
          <small v-if="errors.appointment_date" class="error-text">{{ errors.appointment_date }}</small>
        </div>

        <div class="form-group">
          <label for="start_time">Hora inicio *</label>
          <InputMask
            id="start_time"
            v-model="form.start_time"
            mask="99:99"
            placeholder="HH:MM"
            class="w-full"
          />
          <small v-if="errors.start_time" class="error-text">{{ errors.start_time }}</small>
        </div>

        <div class="form-group">
          <label for="end_time">Hora fin *</label>
          <InputMask
            id="end_time"
            v-model="form.end_time"
            mask="99:99"
            placeholder="HH:MM"
            class="w-full"
          />
          <small v-if="errors.end_time" class="error-text">{{ errors.end_time }}</small>
        </div>
      </div>

      <div class="form-group">
        <label for="status">Estado</label>
        <Select
          id="status"
          v-model="form.status"
          :options="statusOptions"
          option-label="label"
          option-value="value"
          placeholder="Selecciona estado"
          class="w-full"
        />
      </div>

      <div class="form-group">
        <label for="notes">Notas</label>
        <Textarea
          id="notes"
          v-model="form.notes"
          placeholder="Observaciones sobre la cita"
          rows="3"
          class="w-full"
        />
      </div>
    </div>

    <template #footer>
      <Button
        label="Cancelar"
        icon="pi pi-times"
        @click="emit('close')"
        text
      />
      <Button
        label="Guardar"
        icon="pi pi-check"
        @click="handleSubmit"
        :loading="loading"
      />
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import { useServicesStore } from '@/js/stores/services';
import { clientsApi } from '@/js/api/clients.api';

interface Props {
  visible: boolean;
  appointment?: any | null;
}

const emit = defineEmits(['close', 'submit']);
const props = withDefaults(defineProps<Props>(), { appointment: null });
const servicesStore = useServicesStore();

const isOpen = computed({
  get: () => props.visible,
  set: (value) => {
    if (!value) emit('close');
  },
});

const selectedAppointment = computed(() => props.appointment);
const loading = ref(false);
const errors = ref<any>({});
const filteredClients = ref<any[]>([]);
const clients = ref<any[]>([]);
const services = computed(() => servicesStore.categories);
const employees = ref<any[]>([]);

const statusOptions = [
  { label: 'Pendiente', value: 'pending' },
  { label: 'Confirmada', value: 'confirmed' },
  { label: 'Atendida', value: 'attended' },
  { label: 'Cancelada', value: 'cancelled' },
  { label: 'Pospuesta', value: 'postponed' },
  { label: 'No presentado', value: 'no_show' },
];

const form = ref({
  client_id: null,
  service_id: null,
  employee_id: null,
  appointment_date: null,
  start_time: '',
  end_time: '',
  status: 'pending',
  notes: '',
});

onMounted(async () => {
  await servicesStore.loadServices();
  const response = await clientsApi.list(1, '', true);
  clients.value = response.data || [];
  filteredClients.value = clients.value;
});

watch(
  () => props.visible,
  (newVal) => {
    if (newVal) {
      if (selectedAppointment.value) {
        form.value = {
          client_id: selectedAppointment.value.client_id,
          service_id: selectedAppointment.value.service_id,
          employee_id: selectedAppointment.value.employee_id,
          appointment_date: new Date(selectedAppointment.value.appointment_date),
          start_time: selectedAppointment.value.start_time,
          end_time: selectedAppointment.value.end_time,
          status: selectedAppointment.value.status,
          notes: selectedAppointment.value.notes || '',
        };
      } else {
        form.value = {
          client_id: null,
          service_id: null,
          employee_id: null,
          appointment_date: new Date(),
          start_time: '10:00',
          end_time: '11:00',
          status: 'pending',
          notes: '',
        };
      }
      errors.value = {};
    }
  }
);

const searchClients = (event: any) => {
  const query = event.query.toLowerCase();
  filteredClients.value = clients.value.filter((c) =>
    c.full_name.toLowerCase().includes(query) || c.phone.includes(query)
  );
};

const handleSubmit = async () => {
  errors.value = {};

  if (!form.value.client_id) {
    errors.value.client_id = 'El cliente es requerido';
  }
  if (!form.value.service_id) {
    errors.value.service_id = 'El servicio es requerido';
  }
  if (!form.value.employee_id) {
    errors.value.employee_id = 'El especialista es requerido';
  }
  if (!form.value.appointment_date) {
    errors.value.appointment_date = 'La fecha es requerida';
  }
  if (!form.value.start_time) {
    errors.value.start_time = 'La hora de inicio es requerida';
  }
  if (!form.value.end_time) {
    errors.value.end_time = 'La hora de fin es requerida';
  }

  if (Object.keys(errors.value).length) return;

  loading.value = true;
  emit('submit', {
    client_id: form.value.client_id,
    service_id: form.value.service_id,
    employee_id: form.value.employee_id,
    appointment_date: form.value.appointment_date?.toISOString().split('T')[0],
    start_time: form.value.start_time,
    end_time: form.value.end_time,
    status: form.value.status,
    notes: form.value.notes,
  });
  loading.value = false;
};
</script>

<style scoped lang="scss">
.form-content {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;

  label {
    font-weight: 500;
  }
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 1rem;
}

.error-text {
  color: #ef4444;
  font-size: 0.875rem;
}

.w-full {
  width: 100%;
}
</style>
