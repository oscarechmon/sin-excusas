<template>
  <Message v-if="erp.enabled" severity="info" :closable="false" class="erp-notice">
    <div class="erp-notice__body">
      <span>
        {{ what }} se administran en el
        <a :href="erp.url ?? '#'" target="_blank" rel="noopener">sistema</a>.
        Aquí editas lo de la web: imagen, descripción y qué se publica.
      </span>
      <Button
        v-if="canSync"
        label="Sincronizar ahora"
        icon="pi pi-sync"
        size="small"
        outlined
        :loading="syncing"
        @click="sync"
      />
    </div>
  </Message>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import Message from 'primevue/message'
import { erpApi } from '@/api/erp.api'
import { useErp } from '@/composables/useErp'
import { extractMessage } from '@/composables/usePaginatedList'
import { useAuthStore } from '@/stores/auth'

/**
 * Aviso de que el catálogo vive en el sistema, con el botón para traerlo de
 * nuevo si algún cambio no llegó solo.
 */
defineProps<{ what: string }>()
const emit = defineEmits<{ synced: [] }>()

const { erp } = useErp()
const auth = useAuthStore()
const toast = useToast()
const syncing = ref(false)

const canSync = computed(() => auth.hasPermission('inventory.manage'))

const sync = async () => {
  syncing.value = true
  try {
    const response = await erpApi.sync()
    toast.add({ severity: 'success', summary: 'Listo', detail: response.message, life: 3000 })
    emit('synced')
  } catch (err) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: extractMessage(err, 'No se pudo sincronizar con el sistema.'),
      life: 5000,
    })
  } finally {
    syncing.value = false
  }
}

</script>

<style scoped lang="scss">
.erp-notice {
  margin-bottom: 1rem;

  &__body {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    width: 100%;
  }

  a {
    text-decoration: underline;
  }
}
</style>
