<template>
  <nav class="app-nav" :class="{ 'app-nav--collapsed': collapsed }" aria-label="Navegación principal">
    <div class="app-nav__brand">
      <span class="app-nav__logo">SE</span>
      <span v-if="!collapsed" class="app-nav__brand-text">
        <span class="app-nav__brand-name">Sin Excusas</span>
        <span class="app-nav__brand-sub">ERP</span>
      </span>
    </div>

    <div class="app-nav__scroll">
      <template v-for="section in visibleSections" :key="section.label">
        <p v-if="!collapsed" class="app-nav__section">{{ section.label }}</p>
        <ul class="app-nav__list">
          <li v-for="item in section.items" :key="item.label">
            <router-link
              v-if="item.route"
              :to="{ name: item.route }"
              class="app-nav__item"
              :class="{ 'app-nav__item--active': isActive(item.route) }"
              @click="emit('navigate')"
            >
              <i :class="item.icon" class="app-nav__icon" />
              <span v-if="!collapsed" class="app-nav__label">{{ item.label }}</span>
            </router-link>

            <span v-else class="app-nav__item app-nav__item--disabled">
              <i :class="item.icon" class="app-nav__icon" />
              <template v-if="!collapsed">
                <span class="app-nav__label">{{ item.label }}</span>
                <Tag value="Pronto" severity="secondary" class="app-nav__tag" />
              </template>
            </span>
          </li>
        </ul>
      </template>
    </div>
  </nav>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { navigation } from '@/config/navigation'
import Tag from 'primevue/tag'

withDefaults(defineProps<{ collapsed?: boolean }>(), { collapsed: false })
const emit = defineEmits<{ navigate: [] }>()

const route = useRoute()
const authStore = useAuthStore()

// Un item sin permiso declarado es visible para cualquier usuario autenticado.
const canSee = (permission?: string) => !permission || authStore.hasPermission(permission)

const visibleSections = computed(() =>
  navigation
    .map((section) => ({ ...section, items: section.items.filter((i) => canSee(i.permission)) }))
    .filter((section) => section.items.length > 0)
)

const isActive = (name: string) => route.name === name
</script>
