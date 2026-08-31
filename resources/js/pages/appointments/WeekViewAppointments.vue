<template>
  <div class="week-view">
    <div class="week-header">
      <Button
        icon="pi pi-chevron-left"
        @click="previousWeek"
        text
        rounded
      />
      <span class="week-label">{{ weekLabel }}</span>
      <Button
        icon="pi pi-chevron-right"
        @click="nextWeek"
        text
        rounded
      />
      <Button
        label="Esta semana"
        @click="goThisWeek"
        text
      />
    </div>

    <div v-if="loading" class="loading-state">
      <ProgressSpinner />
    </div>

    <div v-else class="week-calendar">
      <div class="day-column" v-for="day in weekDays" :key="day.dateStr">
        <div class="day-header">
          <div class="day-name">{{ day.name }}</div>
          <div class="day-date">{{ day.date }}</div>
        </div>
        <div class="day-appointments">
          <AppointmentCard
            v-for="apt in getAppointmentsForDay(day.dateStr)"
            :key="apt.id"
            :appointment="apt"
            compact
            @edit="emit('edit', apt)"
            @delete="emit('delete', apt.id)"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import AppointmentCard from './AppointmentCard.vue';
import Button from 'primevue/button';
import ProgressSpinner from 'primevue/progressspinner';

interface Props {
  appointments: any[];
  loading: boolean;
}

interface Emits {
  (e: 'edit', appointment: any): void;
  (e: 'delete', id: number): void;
  (e: 'week-change', weekStart: Date): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const weekStart = ref(getWeekStart(new Date()));

const weekDays = computed(() => {
  const days = [];
  for (let i = 0; i < 7; i++) {
    const date = new Date(weekStart.value);
    date.setDate(date.getDate() + i);
    const dateStr = date.toISOString().split('T')[0];
    days.push({
      name: getDateName(date),
      date: date.toLocaleDateString('es-PE', { month: 'short', day: '2-digit' }),
      dateStr,
    });
  }
  return days;
});

const weekLabel = computed(() => {
  const end = new Date(weekStart.value);
  end.setDate(end.getDate() + 6);
  return `${weekStart.value.toLocaleDateString('es-PE')} - ${end.toLocaleDateString('es-PE')}`;
});

function getWeekStart(date: Date): Date {
  const d = new Date(date);
  const day = d.getDay();
  const diff = d.getDate() - day + (day === 0 ? -6 : 1);
  return new Date(d.setDate(diff));
}

function getDateName(date: Date): string {
  return date.toLocaleDateString('es-PE', { weekday: 'short' });
}

const previousWeek = () => {
  weekStart.value = new Date(weekStart.value.getTime() - 7 * 24 * 60 * 60 * 1000);
  emit('week-change', weekStart.value);
};

const nextWeek = () => {
  weekStart.value = new Date(weekStart.value.getTime() + 7 * 24 * 60 * 60 * 1000);
  emit('week-change', weekStart.value);
};

const goThisWeek = () => {
  weekStart.value = getWeekStart(new Date());
  emit('week-change', weekStart.value);
};

const getAppointmentsForDay = (dateStr: string) => {
  return props.appointments.filter((apt) => apt.appointment_date === dateStr);
};
</script>

<style scoped lang="scss">
.week-view {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.week-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: white;
  border-radius: 0.5rem;

  .week-label {
    font-weight: 600;
    min-width: 200px;
  }
}

.loading-state {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 400px;
}

.week-calendar {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 0.5rem;
  background: white;
  padding: 1rem;
  border-radius: 0.5rem;
  border: 1px solid #e0e0e0;
}

.day-column {
  border: 1px solid #e0e0e0;
  border-radius: 0.5rem;
  overflow: hidden;

  .day-header {
    background: #f5f5f5;
    padding: 0.75rem;
    text-align: center;
    border-bottom: 1px solid #e0e0e0;

    .day-name {
      font-weight: 600;
      font-size: 0.875rem;
    }

    .day-date {
      font-size: 0.75rem;
      color: #666;
    }
  }

  .day-appointments {
    padding: 0.5rem;
    min-height: 200px;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
  }
}
</style>
