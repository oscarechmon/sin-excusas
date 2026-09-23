<template>
  <div class="hours">
    <div v-for="day in days" :key="day.key" class="hours__row" :class="{ 'hours__row--closed': !value[day.key]?.open }">
      <ToggleSwitch
        :model-value="value[day.key]?.open ?? false"
        :input-id="`open-${day.key}`"
        @update:model-value="(open: boolean) => set(day.key, 'open', open)"
      />
      <label class="hours__day" :for="`open-${day.key}`">{{ day.label }}</label>

      <template v-if="value[day.key]?.open">
        <input
          type="time"
          class="p-inputtext p-component hours__time"
          :value="value[day.key].from"
          :aria-label="`Apertura del ${day.label}`"
          @change="set(day.key, 'from', ($event.target as HTMLInputElement).value)"
        >
        <span class="hours__sep">a</span>
        <input
          type="time"
          class="p-inputtext p-component hours__time"
          :value="value[day.key].to"
          :aria-label="`Cierre del ${day.label}`"
          @change="set(day.key, 'to', ($event.target as HTMLInputElement).value)"
        >
        <Button
          v-if="canCopy(day.key)"
          label="Copiar al resto"
          text
          size="small"
          @click="copyToAll(day.key)"
        />
      </template>
      <span v-else class="hours__closed">Cerrado</span>
    </div>

    <p class="hours__preview">Se verá así en la web: <strong>{{ preview || 'Sin horario: no se muestra.' }}</strong></p>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import Button from 'primevue/button'
import ToggleSwitch from 'primevue/toggleswitch'
import type { DayOption, OpeningHours } from '@/stores/siteSettings'

const props = defineProps<{ modelValue: OpeningHours; days: DayOption[] }>()
const emit = defineEmits<{ 'update:modelValue': [OpeningHours] }>()

const value = computed(() => props.modelValue ?? {})

const set = (day: string, field: 'open' | 'from' | 'to', fieldValue: boolean | string) => {
  emit('update:modelValue', {
    ...value.value,
    [day]: { ...value.value[day], [field]: fieldValue },
  })
}

/** Solo tiene sentido ofrecer la copia si algún otro día tiene otro horario. */
const canCopy = (day: string) =>
  props.days.some((other) => {
    const entry = value.value[other.key]
    return other.key !== day && entry?.open && (entry.from !== value.value[day].from || entry.to !== value.value[day].to)
  })

const copyToAll = (day: string) => {
  const { from, to } = value.value[day]
  const copied = { ...value.value }
  props.days.forEach((other) => {
    copied[other.key] = { ...copied[other.key], from, to }
  })
  emit('update:modelValue', copied)
}

/** Mismo agrupado que hace el backend, para ver el resultado antes de guardar. */
const preview = computed(() => {
  const groups: { first: string; last: string; hours: string }[] = []
  const name = (key: string, field: 'label' | 'short') => props.days.find((day) => day.key === key)?.[field] ?? key

  props.days.forEach((day) => {
    const entry = value.value[day.key]
    if (!entry?.open) return

    const hours = `${entry.from.replace(/^0/, '')} a ${entry.to.replace(/^0/, '')}`
    const last = groups[groups.length - 1]
    const isNeighbour = last && props.days.findIndex((d) => d.key === last.last) === props.days.indexOf(day) - 1

    if (last && isNeighbour && last.hours === hours) last.last = day.key
    else groups.push({ first: day.key, last: day.key, hours })
  })

  return groups
    .map((group) =>
      group.first === group.last
        ? `${name(group.first, 'label')} ${group.hours}`
        : `${name(group.first, 'short')} a ${name(group.last, 'short')} ${group.hours}`
    )
    .join(' · ')
})
</script>

<style scoped lang="scss">
.hours {
  display: grid;
  gap: 0.5rem;

  &__row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
    padding: 0.4rem 0;
    border-bottom: 1px solid var(--p-content-border-color, #e5e7eb);

    &--closed {
      opacity: 0.65;
    }
  }

  &__day {
    min-width: 6.5rem;
    cursor: pointer;
  }

  &__time {
    width: 7.5rem;
  }

  &__sep,
  &__closed {
    color: #6b7280;
  }

  &__preview {
    margin: 0.5rem 0 0;
    color: #6b7280;
    font-size: 0.85rem;
  }
}
</style>
