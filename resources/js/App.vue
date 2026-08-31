<template>
  <!--
    El layout se resuelve aquí, una sola vez, a partir de route.meta.layout.
    Así ninguna página necesita importar AdminLayout ni puede olvidarse de hacerlo.
  -->
  <component :is="layout">
    <RouterView />
  </component>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import AdminLayout from '@/layouts/AdminLayout.vue'
import BlankLayout from '@/layouts/BlankLayout.vue'

const route = useRoute()

// La sesión se restaura en el guard del router (router/index.ts), no aquí:
// debe estar resuelta antes de decidir la primera navegación.
const layout = computed(() => (route.meta.layout === 'blank' ? BlankLayout : AdminLayout))
</script>
