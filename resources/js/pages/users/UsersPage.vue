<template>
  <div class="users-page">
    <div class="page-header">
      <div>
        <h1 class="page-header__title">Usuarios y roles</h1>
        <p class="page-header__subtitle">
          Cuentas de acceso al sistema y permisos de cada rol.
        </p>
      </div>
      <div v-if="canManage" class="page-header__actions">
        <Button label="Nuevo usuario" icon="pi pi-plus" @click="openCreate" />
      </div>
    </div>

    <Tabs v-model:value="activeTab">
      <TabList>
        <Tab value="users">Usuarios</Tab>
        <Tab value="roles">Roles y permisos</Tab>
      </TabList>

      <TabPanels>
        <TabPanel value="users">
          <Card class="filter-card">
            <template #content>
              <div class="filters">
                <IconField>
                  <InputIcon class="pi pi-search" />
                  <InputText v-model="search" placeholder="Buscar por nombre o correo" @keyup.enter="reload" />
                </IconField>
                <Select
                  v-model="roleFilter"
                  :options="roleOptions"
                  placeholder="Rol"
                  show-clear
                  @change="reload"
                />
                <Select
                  v-model="activeFilter"
                  :options="activeOptions"
                  option-label="label"
                  option-value="value"
                  placeholder="Estado"
                  show-clear
                  @change="reload"
                />
                <Button icon="pi pi-search" label="Buscar" outlined @click="reload" />
              </div>
            </template>
          </Card>

          <Message v-if="store.error" severity="error" :closable="false">{{ store.error }}</Message>

          <Card>
            <template #content>
              <DataTable
                :value="store.items"
                :loading="store.loading"
                lazy
                paginator
                :rows="15"
                :total-records="store.total"
                :first="(store.currentPage - 1) * 15"
                @page="onPage"
              >
                <template #empty>
                  <p class="table-empty">No hay usuarios registrados.</p>
                </template>

                <Column header="Usuario">
                  <template #body="{ data }">
                    <div class="user-cell">
                      <Avatar :label="initials(data.name)" shape="circle" class="user-cell__avatar" />
                      <div class="user-cell__info">
                        <span class="cell-strong">
                          {{ data.name }}
                          <Tag v-if="data.id === currentUserId" value="Tú" severity="info" class="self-tag" />
                        </span>
                        <span class="cell-muted">{{ data.email }}</span>
                      </div>
                    </div>
                  </template>
                </Column>

                <Column header="Roles" :style="{ width: '220px' }">
                  <template #body="{ data }">
                    <Tag
                      v-for="role in data.roles"
                      :key="role"
                      :value="role"
                      :severity="roleSeverity(role)"
                      class="role-tag"
                    />
                  </template>
                </Column>

                <Column header="Personal" :style="{ width: '170px' }">
                  <template #body="{ data }">
                    <span v-if="data.employee" class="cell-muted">{{ data.employee.name }}</span>
                    <span v-else class="cell-muted">—</span>
                  </template>
                </Column>

                <Column header="Estado" :style="{ width: '110px' }">
                  <template #body="{ data }">
                    <Tag
                      :value="data.active ? 'Activo' : 'Inactivo'"
                      :severity="data.active ? 'success' : 'secondary'"
                    />
                  </template>
                </Column>

                <Column v-if="canManage" header="Acciones" :style="{ width: '150px' }">
                  <template #body="{ data }">
                    <div class="row-actions">
                      <Button
                        icon="pi pi-sign-out"
                        text
                        rounded
                        severity="secondary"
                        aria-label="Cerrar sesiones"
                        @click="confirmRevoke(data)"
                      />
                      <Button icon="pi pi-pencil" text rounded severity="secondary" @click="openEdit(data)" />
                      <Button
                        icon="pi pi-trash"
                        text
                        rounded
                        severity="danger"
                        :disabled="data.id === currentUserId"
                        @click="confirmDelete(data)"
                      />
                    </div>
                  </template>
                </Column>
              </DataTable>
            </template>
          </Card>
        </TabPanel>

        <TabPanel value="roles">
          <RolePermissionsPanel :can-manage="canManage" />
        </TabPanel>
      </TabPanels>
    </Tabs>

    <UserFormDialog v-model:visible="formVisible" :user="editing" @saved="onSaved" />
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'
import { useUsersStore } from '@/stores/users'
import { useAuthStore } from '@/stores/auth'
import { extractMessage } from '@/composables/usePaginatedList'
import RolePermissionsPanel from './RolePermissionsPanel.vue'
import UserFormDialog from './UserFormDialog.vue'
import Avatar from 'primevue/avatar'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import Select from 'primevue/select'
import Tab from 'primevue/tab'
import TabList from 'primevue/tablist'
import TabPanel from 'primevue/tabpanel'
import TabPanels from 'primevue/tabpanels'
import Tabs from 'primevue/tabs'
import Tag from 'primevue/tag'

const store = useUsersStore()
const authStore = useAuthStore()
const toast = useToast()
const confirm = useConfirm()
const route = useRoute()
const router = useRouter()

// La pestaña vive en la URL para que un refresco no devuelva al usuario a la
// primera solapa y para poder enlazar directamente a los permisos.
const activeTab = ref(route.query.tab === 'roles' ? 'roles' : 'users')

watch(activeTab, (tab) => {
  router.replace({ query: { ...route.query, tab: tab === 'users' ? undefined : tab } })
})

const search = ref('')
const roleFilter = ref<string | null>(null)
const activeFilter = ref<boolean | null>(null)
const formVisible = ref(false)
const editing = ref<any | null>(null)

const canManage = computed(() => authStore.hasPermission('users.manage'))
const currentUserId = computed(() => authStore.user?.id)
const roleOptions = computed(() => store.roles.map((r: any) => r.name))

const activeOptions = [
  { label: 'Activos', value: true },
  { label: 'Inactivos', value: false },
]

const initials = (name: string) =>
  name.split(' ').filter(Boolean).slice(0, 2).map((p) => p[0].toUpperCase()).join('')

const roleSeverity = (role: string) =>
  ({ Administrador: 'danger', 'Recepción': 'info', Especialista: 'success' })[role] ?? 'secondary'

const params = (page = 1) => ({
  page,
  search: search.value || undefined,
  role: roleFilter.value ?? undefined,
  active: activeFilter.value ?? undefined,
})

const reload = () => store.load(params(1))
const onPage = (event: { page: number }) => store.load(params(event.page + 1))

const openCreate = () => {
  editing.value = null
  formVisible.value = true
}

const openEdit = (user: any) => {
  editing.value = user
  formVisible.value = true
}

const onSaved = (message: string) => {
  formVisible.value = false
  toast.add({ severity: 'success', summary: 'Listo', detail: message, life: 4000 })
}

const notifyError = (err: unknown, fallback: string) =>
  toast.add({ severity: 'error', summary: 'Error', detail: extractMessage(err, fallback), life: 6000 })

const confirmRevoke = (user: any) => {
  confirm.require({
    header: 'Cerrar sesiones',
    message: `Se cerrarán todas las sesiones abiertas de ${user.name}. Tendrá que volver a iniciar sesión.`,
    icon: 'pi pi-question-circle',
    acceptLabel: 'Cerrar sesiones',
    rejectLabel: 'Cancelar',
    accept: async () => {
      try {
        const response = await store.revokeSessions(user.id)
        toast.add({ severity: 'success', summary: 'Listo', detail: response.message, life: 4000 })
      } catch (err) {
        notifyError(err, 'No se pudieron cerrar las sesiones.')
      }
    },
  })
}

const confirmDelete = (user: any) => {
  confirm.require({
    header: 'Eliminar usuario',
    message: `¿Eliminar la cuenta de ${user.name}? Si tiene historial se desactivará para conservar la trazabilidad.`,
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Eliminar',
    rejectLabel: 'Cancelar',
    acceptProps: { severity: 'danger' },
    accept: async () => {
      try {
        const response = await store.deleteUser(user.id)
        toast.add({ severity: 'success', summary: 'Listo', detail: response.message, life: 5000 })
      } catch (err) {
        // Incluye las reglas de "último administrador" y "cuenta propia",
        // que el backend explica con su propio mensaje.
        notifyError(err, 'No se pudo eliminar el usuario.')
      }
    },
  })
}

onMounted(() => {
  store.loadRoles()
  reload()
})
</script>

<style scoped lang="scss">
.user-cell {
  display: flex;
  align-items: center;
  gap: 0.75rem;

  &__avatar {
    width: 34px;
    height: 34px;
    font-size: 0.75rem;
    font-weight: 600;
    flex-shrink: 0;
  }

  &__info {
    display: flex;
    flex-direction: column;
    min-width: 0;
  }
}

.role-tag {
  margin-right: 0.25rem;
}

.self-tag {
  margin-left: 0.375rem;
}
</style>
