<template>
  <div class="not-found-page">
    <div class="not-found-content">
      <h1>404</h1>
      <h2>Página no encontrada</h2>
      <p>La página que buscas no existe.</p>
      <Button label="Volver al inicio" icon="pi pi-arrow-left" @click="goHome" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router'
import Button from 'primevue/button'

const router = useRouter()

const goHome = () => {
  router.push({ name: 'dashboard' })
}
</script>

<style scoped lang="scss">
.not-found-page {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.not-found-content {
  text-align: center;
  color: white;

  h1 {
    font-size: 8rem;
    margin: 0;
    font-weight: 700;
  }

  h2 {
    font-size: 2rem;
    margin: 1rem 0;
  }

  p {
    font-size: 1.25rem;
    margin: 1rem 0 2rem 0;
    opacity: 0.9;
  }
}
</style>
