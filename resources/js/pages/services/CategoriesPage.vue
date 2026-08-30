<template>
  <AdminLayout>
    <div class="categories-page">
      <div class="page-header">
        <h1>CategorÃ­as de Servicios</h1>
        <Button
          label="Nueva CategorÃ­a"
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
        <Column field="description" header="DescripciÃ³n" />
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
import { useServicesStore } from '@/stores/services';
import AdminLayout from '@/layouts/AdminLayout.vue';
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
    message: 'Â¿EstÃ¡s seguro de que quieres eliminar esta categorÃ­a?',
    header: 'Confirmar eliminaciÃ³n',
    icon: 'pi pi-exclamation-triangle',
    accept: async () => {
      try {
        await servicesStore.deleteCategory(id);
        toast.add({ severity: 'success', summary: 'Ã‰xito', detail: 'CategorÃ­a eliminada' });
      } catch (err: any) {
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: err.message || 'Error al eliminar categorÃ­a',
        });
      }
    },
  });
};

const handleCategorySubmit = async (payload: any) => {
  try {
    if (selectedCategory.value) {
      await servicesStore.updateCategory(selectedCategory.value.id, payload);
      toast.add({ severity: 'success', summary: 'Ã‰xito', detail: 'CategorÃ­a actualizada' });
    } else {
      await servicesStore.createCategory(payload);
      toast.add({ severity: 'success', summary: 'Ã‰xito', detail: 'CategorÃ­a creada' });
    }
    showCategoryDialog.value = false;
  } catch (err: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.message || 'Error al guardar categorÃ­a',
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

