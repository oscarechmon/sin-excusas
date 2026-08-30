<template>
  <div class="admin-layout">
    <Sidebar
      v-model:visible="sidebarVisible"
      :options="sidebarOptions"
      class="sidebar"
    />

    <div class="layout-container">
      <header class="top-header">
        <button @click="toggleSidebar" class="sidebar-toggle">
          <i class="pi pi-bars"></i>
        </button>

        <div class="header-spacer"></div>

        <div class="header-actions">
          <Button
            icon="pi pi-bell"
            text
            rounded
            class="p-button-rounded p-button-text"
          />
          <Button
            icon="pi pi-user"
            text
            rounded
            class="p-button-rounded p-button-text"
            @click="toggleUserMenu"
          />
          <Menu v-model:popup="showUserMenu" :model="userMenuItems" />
        </div>
      </header>

      <main class="layout-content">
        <div class="breadcrumb-wrapper" v-if="showBreadcrumb">
          <Breadcrumb :model="breadcrumbItems" />
        </div>

        <RouterView />
      </main>
    </div>

    <Toast />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import Sidebar from 'primevue/sidebar'
import Button from 'primevue/button'
import Menu from 'primevue/menu'
import Breadcrumb from 'primevue/breadcrumb'
import Toast from 'primevue/toast'

const router = useRouter()
const authStore = useAuthStore()

const sidebarVisible = ref(true)
const showUserMenu = ref(false)
const showBreadcrumb = ref(true)

const sidebarOptions = [
  {
    label: 'Principal',
    items: [
      {
        label: 'Dashboard',
        icon: 'pi pi-fw pi-home',
        command: () => router.push({ name: 'dashboard' }),
      },
    ],
  },
  {
    label: 'Gestión',
    items: [
      {
        label: 'Clientes',
        icon: 'pi pi-fw pi-users',
        command: () => router.push({ name: 'clients' }),
      },
      {
        label: 'Agenda',
        icon: 'pi pi-fw pi-calendar',
        badge: '0',
      },
      {
        label: 'Servicios',
        icon: 'pi pi-fw pi-star',
        badge: '0',
      },
    ],
  },
  {
    label: 'Operaciones',
    items: [
      {
        label: 'Atenciones',
        icon: 'pi pi-fw pi-check-square',
        badge: '0',
      },
      {
        label: 'Ventas',
        icon: 'pi pi-fw pi-shopping-cart',
        badge: '0',
      },
      {
        label: 'Caja',
        icon: 'pi pi-fw pi-wallet',
        badge: '0',
      },
    ],
  },
  {
    label: 'Configuración',
    items: [
      {
        label: 'Inventario',
        icon: 'pi pi-fw pi-boxes',
        badge: '0',
      },
      {
        label: 'Personal',
        icon: 'pi pi-fw pi-id-card',
        badge: '0',
      },
      {
        label: 'Usuarios',
        icon: 'pi pi-fw pi-users',
        badge: '0',
      },
    ],
  },
]

const userMenuItems = [
  {
    label: 'Perfil',
    icon: 'pi pi-user',
  },
  {
    label: 'Configuración',
    icon: 'pi pi-cog',
  },
  {
    separator: true,
  },
  {
    label: 'Cerrar Sesión',
    icon: 'pi pi-sign-out',
    command: () => handleLogout(),
  },
]

const breadcrumbItems = ref([
  { label: 'Dashboard', command: () => router.push({ name: 'dashboard' }) },
])

const toggleSidebar = () => {
  sidebarVisible.value = !sidebarVisible.value
}

const toggleUserMenu = (event: Event) => {
  showUserMenu.value = !showUserMenu.value
}

const handleLogout = async () => {
  await authStore.logout()
  router.push({ name: 'login' })
}
</script>

<style scoped lang="scss">
.admin-layout {
  display: flex;
  height: 100vh;
  background: #f5f5f5;
}

.sidebar {
  width: 280px;
  background: white;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.layout-container {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.top-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.75rem 1.5rem;
  background: white;
  border-bottom: 1px solid #e0e0e0;
  height: 60px;
}

.sidebar-toggle {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 1.5rem;
  color: #666;

  &:hover {
    color: #333;
  }
}

.header-spacer {
  flex: 1;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.layout-content {
  flex: 1;
  overflow-y: auto;
  padding: 1.5rem;
}

.breadcrumb-wrapper {
  margin-bottom: 1rem;
}
</style>
