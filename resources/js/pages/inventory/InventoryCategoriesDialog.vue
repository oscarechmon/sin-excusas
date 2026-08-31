<template>
  <Dialog
    :visible="visible"
    modal
    header="Categorías de inventario"
    :style="{ width: '620px' }"
    @update:visible="$emit('update:visible', $event)"
  >
    <form class="category-form" @submit.prevent="save">
      <div class="form-field">
        <label for="cat-name">{{ editing ? 'Editar categoría' : 'Nueva categoría' }}</label>
        <InputText id="cat-name" v-model="form.name" placeholder="Nombre de la categoría" />
      </div>
      <div class="category-form__actions">
        <Button
          v-if="editing"
          label="Cancelar"
          text
          severity="secondary"
          type="button"
          @click="resetForm"
        />
        <Button :label="editing ? 'Actualizar' : 'Agregar'" icon="pi pi-check" :loading="saving" type="submit" />
      </div>
    </form>

    <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>

    <DataTable :value="store.categories" class="category-table">
      <template #empty>
        <p class="table-empty">No hay categorías registradas.</p>
      </template>

      <Column field="name" header="Nombre" />
      <Column header="Productos" :style="{ width: '110px' }">
        <template #body="{ data }">
          <span class="cell-amount">{{ data.items_count ?? 0 }}</span>
        </template>
      </Column>
      <Column header="Acciones" :style="{ width: '110px' }">
        <template #body="{ data }">
          <div class="row-actions">
            <Button icon="pi pi-pencil" text rounded severity="secondary" @click="edit(data)" />
            <Button icon="pi pi-trash" text rounded severity="danger" @click="remove(data)" />
          </div>
        </template>
      </Column>
    </DataTable>

    <template #footer>
      <Button label="Cerrar" text severity="secondary" @click="$emit('update:visible', false)" />
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useInventoryStore } from '@/stores/inventory'
import { extractMessage } from '@/composables/usePaginatedList'
import Button from 'primevue/button'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'

const props = defineProps<{ visible: boolean }>()
defineEmits<{ 'update:visible': [boolean] }>()

const store = useInventoryStore()
const toast = useToast()

const form = ref({ name: '' })
const editing = ref<any | null>(null)
const saving = ref(false)
const error = ref<string | null>(null)

const resetForm = () => {
  form.value = { name: '' }
  editing.value = null
  error.value = null
}

const edit = (category: any) => {
  editing.value = category
  form.value = { name: category.name }
}

const save = async () => {
  if (!form.value.name.trim()) return

  saving.value = true
  error.value = null

  try {
    const response = editing.value
      ? await store.updateCategory(editing.value.id, form.value)
      : await store.createCategory(form.value)

    toast.add({ severity: 'success', summary: 'Listo', detail: response.message, life: 3000 })
    resetForm()
  } catch (err) {
    error.value = extractMessage(err, 'No se pudo guardar la categoría.')
  } finally {
    saving.value = false
  }
}

const remove = async (category: any) => {
  try {
    const response = await store.deleteCategory(category.id)
    toast.add({ severity: 'success', summary: 'Listo', detail: response.message, life: 3000 })
  } catch (err) {
    // El backend impide borrar una categoría con productos: se muestra su motivo.
    error.value = extractMessage(err, 'No se pudo eliminar la categoría.')
  }
}

watch(
  () => props.visible,
  (open) => {
    if (open) {
      resetForm()
      store.loadCategories()
    }
  }
)
</script>

<style scoped lang="scss">
.category-form {
  display: flex;
  align-items: flex-end;
  gap: 0.75rem;
  margin-bottom: 1rem;

  .form-field {
    flex: 1;
  }

  &__actions {
    display: flex;
    gap: 0.5rem;
  }
}

.category-table {
  margin-top: 0.5rem;
}
</style>
