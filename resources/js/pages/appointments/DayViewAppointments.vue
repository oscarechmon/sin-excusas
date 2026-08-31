<template>
  <div class="day-view">
    <div class="day-header">
      <Button
        icon="pi pi-chevron-left"
        @click="previousDay"
        text
        rounded
      />
      <DatePicker
        v-model="selectedDate"
        date-format="dd/mm/yy"
        @date-select="handleDateChange"
        class="date-picker"
      />
      <Button
        icon="pi pi-chevron-right"
        @click="nextDay"
        text
        rounded
      />
      <Button
        label="Hoy"
        @click="goToday"
        text
      />
    </div>

    <div v-if="loading" class="loading-state">
      <ProgressSpinner />
    </div>

    <div v-else class="schedule-container">
      <div class="time-slots">
        <div v-for="hour in timeSlots" :key="hour" class="time-slot">
          <div class="time-label">{{ hour }}</div>
          <div class="appointments-for-hour">
            <AppointmentCard
              v-for="apt in getAppointmentsForHour(hour)"
              :key="apt.id"
              :appointment="apt"
              @edit="emit('edit', apt)"
              @delete="emit('delete', apt.id)"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import AppointmentCard from './AppointmentCard.vue';
import Button from 'primevue/button';
import DatePicker from 'primevue/datepicker';
import ProgressSpinner from 'primevue/progressspinner';

interface Props {
  appointments: any[];
  loading: boolean;
}

interface Emits {
  (e: 'edit', appointment: any): void;
  (e: 'delete', id: number): void;
  (e: 'date-change', date: Date): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const selectedDate = ref(new Date());
const timeSlots = computed(() => {
  const slots = [];
  for (let i = 8; i < 20; i++) {
    slots.push(`${String(i).padStart(2, '0')}:00`);
  }
  return slots;
});

const previousDay = () => {
  selectedDate.value = new Date(selectedDate.value.getTime() - 24 * 60 * 60 * 1000);
  emit('date-change', selectedDate.value);
};

const nextDay = () => {
  selectedDate.value = new Date(selectedDate.value.getTime() + 24 * 60 * 60 * 1000);
  emit('date-change', selectedDate.value);
};

const goToday = () => {
  selectedDate.value = new Date();
  emit('date-change', selectedDate.value);
};

const handleDateChange = () => {
  emit('date-change', selectedDate.value);
};

const getAppointmentsForHour = (timeSlot: string) => {
  const hour = parseInt(timeSlot.split(':')[0]);
  return props.appointments.filter((apt) => {
    const startHour = parseInt(apt.start_time.split(':')[0]);
    return startHour === hour;
  });
};
</script>

<style scoped lang="scss">
.day-view {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  height: 100%;
}

.day-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: white;
  border-radius: 0.5rem;

  .date-picker {
    width: 200px;
  }
}

.loading-state {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 400px;
}

.schedule-container {
  flex: 1;
  overflow-y: auto;
  border: 1px solid #e0e0e0;
  border-radius: 0.5rem;
  background: white;
}

.time-slots {
  display: flex;
  flex-direction: column;
}

.time-slot {
  display: grid;
  grid-template-columns: 80px 1fr;
  border-bottom: 1px solid #f0f0f0;
  min-height: 80px;

  &:hover {
    background: #fafafa;
  }
}

.time-label {
  padding: 0.75rem;
  font-weight: 600;
  font-size: 0.875rem;
  background: #f9f9f9;
  border-right: 1px solid #e0e0e0;
  text-align: center;
  display: flex;
  align-items: center;
  justify-content: center;
}

.appointments-for-hour {
  padding: 0.5rem;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}
</style>
