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
        <label for="category_id">Categoría *</label>
        <Select
          id="category_id"
          v-model="form.category_id"
          :options="categories"
          option-label="name"
          option-value="id"
          placeholder="Selecciona una categoría"
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
          <label for="duration_minutes">Duración (minutos) *</label>
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
        <label for="description">Descripción</label>
        <Textarea
          id="description"
          v-model="form.description"
          class="w-full"
          placeholder="Descripción del servicio"
          rows="3"
        />
      </div>

      <CatalogImageField
        :aspect-ratio="4 / 3"
        aspect-label="4:3"
        :image-url="imageUrl"
        :enabled="!!selectedService"
        :busy="imageBusy"
        @upload="changeImage"
        @remove="changeImage(null)"
      />

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
import { useToast } from 'primevue/usetoast';
import { useServicesStore } from '@/stores/services';
import CatalogImageField from '@/components/common/CatalogImageField.vue';
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import Dialog from 'primevue/dialog';
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';

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
const toast = useToast();

// La foto se guarda al instante, independiente del botón "Guardar".
const imageUrl = ref<string | null>(null);
const imageBusy = ref(false);

const changeImage = async (file: File | null) => {
  if (!selectedService.value) return;
  imageBusy.value = true;
  try {
    const response = await servicesStore.setImage(selectedService.value.id, file);
    imageUrl.value = response.data.image_url;
    toast.add({ severity: 'success', summary: 'Listo', detail: response.message, life: 3000 });
  } catch (err: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || 'No se pudo guardar la foto.',
      life: 5000,
    });
  } finally {
    imageBusy.value = false;
  }
};
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
      imageUrl.value = selectedService.value?.image_url ?? null;
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
    errors.value.category_id = 'La categoría es requerida';
  }
  // Precio 0 es válido: la web muestra "Consultar" en lugar del monto.
  if (form.value.price === null) {
    errors.value.price = 'El precio es requerido';
  }
  if (!form.value.duration_minutes) {
    errors.value.duration_minutes = 'La duración es requerida';
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

