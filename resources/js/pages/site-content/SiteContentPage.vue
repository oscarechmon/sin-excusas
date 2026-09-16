<template>
  <div class="site-content-page">
    <div class="page-header">
      <div>
        <h1 class="page-header__title">Contenido web</h1>
        <p class="page-header__subtitle">
          Fotos y textos de las páginas Inicio y Nosotros. Los cambios se ven en la web al instante.
        </p>
      </div>
      <div class="page-header__actions">
        <Button label="Ver la web" icon="pi pi-external-link" outlined severity="secondary" @click="openSite" />
      </div>
    </div>

    <Message v-if="store.error" severity="error" :closable="false">{{ store.error }}</Message>

    <div v-if="store.loading" class="loading"><ProgressSpinner /></div>

    <section v-for="(slots, group) in grouped" v-else :key="group" class="group">
      <h2 class="group__title">{{ group }}</h2>

      <div class="group__grid">
        <Card v-for="slot in slots" :key="slot.key">
          <template #title>{{ slot.label }}</template>
          <template #subtitle>{{ slot.hint }}</template>
          <template #content>
            <div class="slot">
              <CatalogImageField
                v-if="slot.fields.includes('image')"
                :image-url="slot.image_url"
                :enabled="true"
                :aspect-ratio="slot.aspect"
                :busy="busyKey === slot.key"
                @upload="(file: File) => changeImage(slot, file)"
                @remove="changeImage(slot, null)"
              />

              <div v-if="slot.fields.includes('title')" class="form-field">
                <label :for="`title-${slot.key}`">Título</label>
                <InputText :id="`title-${slot.key}`" v-model="slot.title" />
              </div>

              <div v-if="slot.fields.includes('text')" class="form-field">
                <label :for="`text-${slot.key}`">Texto</label>
                <Textarea :id="`text-${slot.key}`" v-model="slot.text" rows="2" auto-resize maxlength="500" />
              </div>

              <div v-if="hasTexts(slot)">
                <Button
                  label="Guardar textos"
                  icon="pi pi-check"
                  size="small"
                  :loading="savingKey === slot.key"
                  @click="saveTexts(slot)"
                />
              </div>
            </div>
          </template>
        </Card>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useSiteContentStore, type SiteContentSlot } from '@/stores/siteContent'
import { extractMessage } from '@/composables/usePaginatedList'
import CatalogImageField from '@/components/common/CatalogImageField.vue'
import Button from 'primevue/button'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import ProgressSpinner from 'primevue/progressspinner'
import Textarea from 'primevue/textarea'

const store = useSiteContentStore()
const toast = useToast()

const busyKey = ref<string | null>(null)
const savingKey = ref<string | null>(null)

/** Agrupa los espacios por página para que la pantalla siga el orden de la web. */
const grouped = computed(() =>
  store.slots.reduce<Record<string, SiteContentSlot[]>>((groups, slot) => {
    ;(groups[slot.group] ??= []).push(slot)
    return groups
  }, {})
)

const hasTexts = (slot: SiteContentSlot) =>
  slot.fields.includes('title') || slot.fields.includes('text')

const openSite = () => window.open('/', '_blank', 'noopener')

const changeImage = async (slot: SiteContentSlot, file: File | null) => {
  busyKey.value = slot.key
  try {
    const response = await store.setImage(slot.key, file)
    toast.add({ severity: 'success', summary: 'Listo', detail: response.message, life: 3000 })
  } catch (err) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: extractMessage(err, 'No se pudo guardar la foto.'),
      life: 5000,
    })
  } finally {
    busyKey.value = null
  }
}

const saveTexts = async (slot: SiteContentSlot) => {
  savingKey.value = slot.key
  try {
    const response = await store.saveTexts(slot.key, { title: slot.title, text: slot.text })
    toast.add({ severity: 'success', summary: 'Listo', detail: response.message, life: 3000 })
  } catch (err) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: extractMessage(err, 'No se pudo guardar.'),
      life: 5000,
    })
  } finally {
    savingKey.value = null
  }
}

onMounted(store.load)
</script>

<style scoped lang="scss">
.loading {
  display: grid;
  place-items: center;
  padding: 3rem;
}

.group {
  margin-top: 2rem;

  &__title {
    margin: 0 0 1rem;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #6b7280;
  }

  &__grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1rem;
  }
}

.slot {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
</style>
