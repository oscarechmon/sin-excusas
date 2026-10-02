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
      <!-- Con el sistema conectado, la operación diaria está allá. -->
      <template v-if="erp.enabled && erp.url">
        <p v-if="!collapsed" class="app-nav__section">Sistema</p>
        <ul class="app-nav__list">
          <li>
            <a :href="erp.url" target="_blank" rel="noopener" class="app-nav__item">
              <i class="pi pi-external-link app-nav__icon" />
              <span v-if="!collapsed" class="app-nav__label">Ir al sistema</span>
            </a>
          </li>
        </ul>
      </template>

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
import { navigation, type NavItem } from '@/config/navigation'
import { useErp } from '@/composables/useErp'
import Tag from 'primevue/tag'

withDefaults(defineProps<{ collapsed?: boolean }>(), { collapsed: false })
const emit = defineEmits<{ navigate: [] }>()

const route = useRoute()
const authStore = useAuthStore()
const { erp } = useErp()

// Un item sin permiso declarado es visible para cualquier usuario autenticado.
// Lo que se opera en el sistema no se muestra mientras esté conectado.
const canSee = (item: NavItem) =>
  (!item.permission || authStore.hasPermission(item.permission)) && !(item.erpManaged && erp.value.enabled)

const visibleSections = computed(() =>
  navigation
    .map((section) => ({ ...section, items: section.items.filter(canSee) }))
    .filter((section) => section.items.length > 0)
)

const isActive = (name: string) => route.name === name
</script>
