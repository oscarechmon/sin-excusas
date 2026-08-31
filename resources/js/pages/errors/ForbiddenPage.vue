<template>
  <div class="forbidden-page">
    <i class="pi pi-lock forbidden-page__icon" />
    <h1 class="forbidden-page__title">No tienes acceso a esta sección</h1>
    <p class="forbidden-page__text">
      Tu rol <strong>{{ role }}</strong> no incluye este módulo. Si necesitas
      entrar, pide al administrador que ajuste tus permisos.
    </p>
    <Button label="Volver al dashboard" icon="pi pi-home" @click="router.push({ name: 'dashboard' })" />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import Button from 'primevue/button'

const router = useRouter()
const authStore = useAuthStore()

const role = computed(() => authStore.roles[0] ?? 'actual')
</script>

<style scoped lang="scss">
.forbidden-page {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  min-height: 60vh;
  text-align: center;

  &__icon {
    font-size: 2.5rem;
    color: #9ca3af;
  }

  &__title {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 600;
    color: #111827;
  }

  &__text {
    margin: 0;
    max-width: 420px;
    color: #6b7280;
  }
}
</style>
