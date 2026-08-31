<template>
  <div class="blank-layout">
    <slot />
    <Toast position="top-right" />
  </div>
</template>

<script setup lang="ts">
import Toast from 'primevue/toast'
</script>
