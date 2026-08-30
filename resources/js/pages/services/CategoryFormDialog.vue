<template>
  <Dialog
    v-model:visible="isOpen"
    :header="selectedCategory ? 'Editar Categoría' : 'Nueva Categoría'"
    :modal="true"
    :style="{ width: '500px' }"
    @hide="emit('close')"
  >
    <div class="form-content">
      <div class="form-group">
        <label for="name">Nombre *</label>
        <InputText
          id="name"
          v-model="form.name"
          class="w-full"
          placeholder="Nombre de la categoría"
        />
        <small v-if="errors.name" class="error-text">{{ errors.name }}</small>
      </div>

      <div class="form-group">
        <label for="description">Descripción</label>
        <Textarea
          id="description"
          v-model="form.description"
          class="w-full"
          placeholder="Descripción de la categoría"
          rows="3"
        />
      </div>

      <div class="form-group">
        <label for="active">
          <Checkbox v-model="form.active" binary input-id="active" />
          <span class="ml-2">Activa</span>
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

interface Props {
  visible: boolean;
  category?: any | null;
}

const emit = defineEmits(['close', 'submit']);
const props = withDefaults(defineProps<Props>(), { category: null });

const isOpen = computed({
  get: () => props.visible,
  set: (value) => {
    if (!value) emit('close');
  },
});

const selectedCategory = computed(() => props.category);
const loading = ref(false);
const errors = ref<any>({});
const form = ref({
  name: '',
  description: '',
  active: true,
});

watch(
  () => props.visible,
  (newVal) => {
    if (newVal) {
      if (selectedCategory.value) {
        form.value = { ...selectedCategory.value };
      } else {
        form.value = { name: '', description: '', active: true };
      }
      errors.value = {};
    }
  }
);

const handleSubmit = async () => {
  errors.value = {};
  if (!form.value.name.trim()) {
    errors.value.name = 'El nombre es requerido';
    return;
  }

  loading.value = true;
  emit('submit', {
    name: form.value.name,
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
