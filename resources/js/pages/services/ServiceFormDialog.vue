<template>
  <Dialog
    v-model:visible="isOpen"
    :header="selectedService ? 'Editar Servicio' : 'Nuevo Servicio'"
    :modal="true"
    :style="{ width: '600px' }"
    @hide="emit('close')"
  >
    <div class="form-content">
      <div class="form-group">
        <label for="name">Nombre *</label>
        <InputText
          id="name"
          v-model="form.name"
          class="w-full"
          placeholder="Nombre del servicio"
        />
        <small v-if="errors.name" class="error-text">{{ errors.name }}</small>
      </div>

      <div class="form-group">
        <label for="category_id">CategorÃ­a *</label>
        <Select
          id="category_id"
          v-model="form.category_id"
          :options="categories"
          option-label="name"
          option-value="id"
          placeholder="Selecciona una categorÃ­a"
          class="w-full"
        />
        <small v-if="errors.category_id" class="error-text">{{ errors.category_id }}</small>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="price">Precio *</label>
          <InputNumber
            id="price"
            v-model="form.price"
            :currency="'PEN'"
            :locale="'es-PE'"
            :min="0"
            :max-fraction-digits="2"
            class="w-full"
          />
          <small v-if="errors.price" class="error-text">{{ errors.price }}</small>
        </div>

        <div class="form-group">
          <label for="duration_minutes">DuraciÃ³n (minutos) *</label>
          <InputNumber
            id="duration_minutes"
            v-model="form.duration_minutes"
            :min="15"
            class="w-full"
          />
          <small v-if="errors.duration_minutes" class="error-text">{{ errors.duration_minutes }}</small>
        </div>
      </div>

      <div class="form-group">
        <label for="description">DescripciÃ³n</label>
        <Textarea
          id="description"
          v-model="form.description"
          class="w-full"
          placeholder="DescripciÃ³n del servicio"
          rows="3"
        />
      </div>

      <div class="form-group">
        <label for="active">
          <Checkbox v-model="form.active" binary input-id="active" />
          <span class="ml-2">Activo</span>
        </label>
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
import { ref, computed, watch } from 'vue';
import { useServicesStore } from '@/stores/services';

interface Props {
  visible: boolean;
  service?: any | null;
}

const emit = defineEmits(['close', 'submit']);
const props = withDefaults(defineProps<Props>(), { service: null });
const servicesStore = useServicesStore();

const isOpen = computed({
  get: () => props.visible,
  set: (value) => {
    if (!value) emit('close');
  },
});

const selectedService = computed(() => props.service);
const categories = computed(() => servicesStore.categories);
const loading = ref(false);
const errors = ref<any>({});
const form = ref({
  name: '',
  category_id: null,
  price: null,
  duration_minutes: 60,
  description: '',
  active: true,
});

watch(
  () => props.visible,
  (newVal) => {
    if (newVal) {
      if (selectedService.value) {
        form.value = {
          name: selectedService.value.name,
          category_id: selectedService.value.category_id,
          price: selectedService.value.price,
          duration_minutes: selectedService.value.duration_minutes,
          description: selectedService.value.description || '',
          active: selectedService.value.active,
        };
      } else {
        form.value = {
          name: '',
          category_id: null,
          price: null,
          duration_minutes: 60,
          description: '',
          active: true,
        };
      }
      errors.value = {};
    }
  }
);

const handleSubmit = async () => {
  errors.value = {};

  if (!form.value.name.trim()) {
    errors.value.name = 'El nombre es requerido';
  }
  if (!form.value.category_id) {
    errors.value.category_id = 'La categorÃ­a es requerida';
  }
  if (!form.value.price) {
    errors.value.price = 'El precio es requerido';
  }
  if (!form.value.duration_minutes) {
    errors.value.duration_minutes = 'La duraciÃ³n es requerida';
  }

  if (Object.keys(errors.value).length) return;

  loading.value = true;
  emit('submit', {
    name: form.value.name,
    category_id: form.value.category_id,
    price: form.value.price,
    duration_minutes: form.value.duration_minutes,
    description: form.value.description,
    active: form.value.active,
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
    display: flex;
    align-items: center;
  }
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.ml-2 {
  margin-left: 0.5rem;
}

.error-text {
  color: #ef4444;
  font-size: 0.875rem;
}

.w-full {
  width: 100%;
}
</style>

