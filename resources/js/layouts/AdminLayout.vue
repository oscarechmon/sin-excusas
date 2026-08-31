<template>
  <div class="admin-layout">
    <!-- Escritorio: sidebar fijo. Móvil/tablet: Drawer de PrimeVue. -->
    <aside class="app-sidebar app-sidebar--fixed" :class="{ 'app-sidebar--collapsed': collapsed }">
      <SidebarNav :collapsed="collapsed" />
    </aside>

    <Drawer v-model:visible="mobileNavVisible" class="app-sidebar-drawer" :showCloseIcon="true">
      <template #container="{ closeCallback }">
        <SidebarNav @navigate="closeCallback" />
      </template>
    </Drawer>

    <div class="app-main">
      <header class="app-header">
        <Button
          class="app-header__toggle"
          icon="pi pi-bars"
          text
          rounded
          severity="secondary"
          aria-label="Alternar menú"
          @click="toggleNav"
        />

        <Breadcrumb v-if="breadcrumbItems.length" :home="breadcrumbHome" :model="breadcrumbItems" class="app-header__breadcrumb">
          <template #item="{ item }">
            <router-link v-if="item.route" :to="item.route" class="app-breadcrumb__link">
              <span v-if="item.icon" :class="item.icon" />
              <span v-else>{{ item.label }}</span>
            </router-link>
            <span v-else class="app-breadcrumb__current">{{ item.label }}</span>
          </template>
        </Breadcrumb>

        <div class="app-header__spacer" />

        <div class="app-header__actions">
          <Button
            icon="pi pi-bell"
            text
            rounded
            severity="secondary"
            aria-label="Notificaciones"
          />

          <button
            type="button"
            class="app-user"
            aria-haspopup="true"
            aria-controls="user-menu"
            @click="toggleUserMenu"
          >
            <Avatar :label="userInitials" shape="circle" class="app-user__avatar" />
            <span class="app-user__meta">
              <span class="app-user__name">{{ userName }}</span>
              <span class="app-user__role">{{ primaryRole }}</span>
            </span>
            <i class="pi pi-angle-down app-user__caret" />
          </button>

          <Menu id="user-menu" ref="userMenu" :model="userMenuItems" :popup="true" />
        </div>
      </header>

      <main class="app-content">
        <slot />
      </main>
    </div>

    <!-- Montados una sola vez para toda la app; las páginas usan
         useToast() / useConfirm() sin declarar sus propios overlays. -->
    <Toast position="top-right" />
    <ConfirmDialog />
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { routeTitles } from '@/config/navigation'
import SidebarNav from '@/components/common/SidebarNav.vue'
import Avatar from 'primevue/avatar'
import Breadcrumb from 'primevue/breadcrumb'
import Button from 'primevue/button'
import ConfirmDialog from 'primevue/confirmdialog'
import Drawer from 'primevue/drawer'
import Menu from 'primevue/menu'
import Toast from 'primevue/toast'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const collapsed = ref(false)
const mobileNavVisible = ref(false)
const userMenu = ref()

const isMobile = () => window.matchMedia('(max-width: 1023px)').matches

const toggleNav = () => {
  if (isMobile()) {
    mobileNavVisible.value = !mobileNavVisible.value
  } else {
    collapsed.value = !collapsed.value
  }
}

const toggleUserMenu = (event: Event) => {
  userMenu.value?.toggle(event)
}

const userName = computed(() => authStore.user?.name ?? 'Usuario')
const primaryRole = computed(() => authStore.roles[0] ?? '—')

const userInitials = computed(() =>
  userName.value
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase() ?? '')
    .join('')
)

const breadcrumbHome = computed(() => ({
  icon: 'pi pi-home',
  route: { name: 'dashboard' },
}))

const breadcrumbItems = computed(() => {
  const name = route.name as string | undefined
  if (!name || name === 'dashboard') return []
  return [{ label: routeTitles[name] ?? name }]
})

const handleLogout = async () => {
  await authStore.logout()
  router.push({ name: 'login' })
}

const userMenuItems = [
  { label: 'Perfil', icon: 'pi pi-user', disabled: true },
  { label: 'Configuración', icon: 'pi pi-cog', disabled: true },
  { separator: true },
  { label: 'Cerrar sesión', icon: 'pi pi-sign-out', command: handleLogout },
]
</script>
