<template>
  <AdminLayout>
    <div class="categories-page">
      <div class="page-header">
        <h1>Categorías de Servicios</h1>
        <Button
          label="Nueva Categoría"
          icon="pi pi-plus"
          @click="openNewCategoryDialog"
          class="p-button-primary"
        />
      </div>

      <DataTable
        :value="servicesStore.categories"
        :loading="servicesStore.loading"
        striped-rows
        responsive-layout="scroll"
        class="categories-table"
      >
        <Column field="id" header="ID" :style="{ width: '60px' }" />
        <Column field="name" header="Nombre" />
        <Column field="description" header="Descripción" />
        <Column header="Estado">
          <template #body="slotProps">
            <Tag
              :value="slotProps.data.active ? 'Activo' : 'Inactivo'"
              :severity="slotProps.data.active ? 'success' : 'danger'"
            />
          </template>
        </Column>
        <Column header="Acciones" :style="{ width: '150px' }">
          <template #body="slotProps">
            <Button
              icon="pi pi-pencil"
              class="p-button-sm p-button-warning"
              @click="editCategory(slotProps.data)"
              text
            />
            <Button
              icon="pi pi-trash"
              class="p-button-sm p-button-danger"
              @click="confirmDeleteCategory(slotProps.data.id)"
              text
            />
          </template>
        </Column>
      </DataTable>

      <CategoryFormDialog
        v-if="showCategoryDialog"
        :visible="showCategoryDialog"
        :category="selectedCategory"
        @close="showCategoryDialog = false"
        @submit="handleCategorySubmit"
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
import { useServicesStore } from '@/js/stores/services';
import AdminLayout from '@/js/layouts/AdminLayout.vue';
import CategoryFormDialog from './CategoryFormDialog.vue';

const servicesStore = useServicesStore();
const confirm = useConfirm();
const toast = useToast();

const showCategoryDialog = ref(false);
const selectedCategory = ref<any | null>(null);

onMounted(() => {
  servicesStore.loadCategories();
});

const openNewCategoryDialog = () => {
  selectedCategory.value = null;
  showCategoryDialog.value = true;
};

const editCategory = (category: any) => {
  selectedCategory.value = category;
  showCategoryDialog.value = true;
};

const confirmDeleteCategory = (id: number) => {
  confirm.require({
    message: '¿Estás seguro de que quieres eliminar esta categoría?',
    header: 'Confirmar eliminación',
    icon: 'pi pi-exclamation-triangle',
    accept: async () => {
      try {
        await servicesStore.deleteCategory(id);
        toast.add({ severity: 'success', summary: 'Éxito', detail: 'Categoría eliminada' });
      } catch (err: any) {
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: err.message || 'Error al eliminar categoría',
        });
      }
    },
  });
};

const handleCategorySubmit = async (payload: any) => {
  try {
    if (selectedCategory.value) {
      await servicesStore.updateCategory(selectedCategory.value.id, payload);
      toast.add({ severity: 'success', summary: 'Éxito', detail: 'Categoría actualizada' });
    } else {
      await servicesStore.createCategory(payload);
      toast.add({ severity: 'success', summary: 'Éxito', detail: 'Categoría creada' });
    }
    showCategoryDialog.value = false;
  } catch (err: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.message || 'Error al guardar categoría',
    });
  }
};
</script>

<style scoped lang="scss">
.categories-page {
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
}
</style>
