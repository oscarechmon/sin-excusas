<template>
  <div class="appointment-card" :class="{ compact }">
    <div class="card-header">
      <Tag :value="appointment.status_label" :severity="appointment.status_color" />
      <div class="time">{{ appointment.start_time }} - {{ appointment.end_time }}</div>
    </div>
    <div class="card-body">
      <div v-if="!compact" class="client-name">{{ appointment.client?.full_name }}</div>
      <div class="service-name">{{ appointment.service?.name }}</div>
      <div v-if="!compact" class="employee-name">{{ appointment.employee?.name }}</div>
      <div v-if="!compact && appointment.notes" class="notes">{{ appointment.notes }}</div>
    </div>
    <div class="card-actions">
      <Button
        icon="pi pi-pencil"
        class="p-button-sm p-button-text p-button-warning"
        @click="emit('edit', appointment)"
        text
      />
      <Button
        icon="pi pi-trash"
        class="p-button-sm p-button-text p-button-danger"
        @click="emit('delete', appointment.id)"
        text
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import Button from 'primevue/button';
import Tag from 'primevue/tag';

interface Props {
  appointment: any;
  compact?: boolean;
}

interface Emits {
  (e: 'edit', appointment: any): void;
  (e: 'delete', id: number): void;
}

defineProps<Props>();
const emit = defineEmits<Emits>();
</script>

<style scoped lang="scss">
.appointment-card {
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 0.375rem;
  padding: 0.5rem;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  font-size: 0.875rem;

  &.compact {
    padding: 0.25rem;

    .card-body {
      .client-name {
        display: none;
      }

      .service-name {
        font-size: 0.75rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }

      .employee-name {
        display: none;
      }

      .notes {
        display: none;
      }
    }
  }

  .card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 0.5rem;

    .time {
      font-size: 0.75rem;
      font-weight: 600;
      color: #666;
    }
  }

  .card-body {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;

    .client-name {
      font-weight: 600;
      color: #333;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .service-name {
      color: #666;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .employee-name {
      font-size: 0.75rem;
      color: #999;
    }

    .notes {
      font-size: 0.75rem;
      color: #999;
      font-style: italic;
      max-height: 40px;
      overflow: hidden;
      text-overflow: ellipsis;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
    }
  }

  .card-actions {
    display: flex;
    gap: 0.25rem;
    justify-content: flex-end;
    margin-top: 0.25rem;
  }
}
</style>
