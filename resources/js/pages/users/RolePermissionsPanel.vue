<template>
  <div class="roles-panel">
    <Message severity="info" :closable="false" class="roles-note">
      Los roles son fijos porque el código los referencia por nombre. Lo que sí
      puede ajustar son los permisos de cada uno. El rol
      <strong>{{ store.lockedRole }}</strong> conserva siempre todos los
      permisos, para que nunca quede el sistema sin nadie que pueda administrarlo.
    </Message>

    <div class="roles-layout">
      <Card class="roles-list">
        <template #content>
          <h3 class="section-title">Roles</h3>
          <ul class="role-items">
            <li v-for="role in store.roles" :key="role.id">
              <button
                type="button"
                class="role-item"
                :class="{ 'role-item--active': role.id === selectedRoleId }"
                @click="selectRole(role)"
              >
                <span class="role-item__name">{{ role.name }}</span>
                <span class="role-item__meta">
                  {{ role.users_count }} usuario(s) · {{ role.permissions.length }} permisos
                </span>
                <i v-if="role.name === store.lockedRole" class="pi pi-lock role-item__lock" />
              </button>
            </li>
          </ul>
        </template>
      </Card>

      <Card class="roles-matrix">
        <template #content>
          <div class="matrix-header">
            <h3 class="section-title">
              Permisos de {{ selectedRole?.name ?? '—' }}
            </h3>
            <div v-if="canEditSelected" class="matrix-header__actions">
              <Button label="Marcar todo" size="small" text @click="selectAll(true)" />
              <Button label="Desmarcar todo" size="small" text severity="secondary" @click="selectAll(false)" />
              <Button
                label="Guardar permisos"
                icon="pi pi-check"
                size="small"
                :loading="saving"
                :disabled="!hasChanges"
                @click="save"
              />
            </div>
          </div>

          <Message v-if="isLocked" severity="warn" :closable="false">
            Los permisos del rol {{ selectedRole?.name }} no se pueden modificar.
          </Message>
          <Message v-else-if="!canManage" severity="warn" :closable="false">
            No tiene permiso para modificar la configuración de roles.
          </Message>

          <div v-for="group in store.permissionGroups" :key="group.module" class="perm-group">
            <div class="perm-group__header">
              <Checkbox
                :model-value="groupState(group)"
                :binary="true"
                :disabled="!canEditSelected"
                :input-id="`group-${group.module}`"
                @update:model-value="toggleGroup(group, $event)"
              />
              <label :for="`group-${group.module}`" class="perm-group__title">{{ group.label }}</label>
            </div>

            <div class="perm-group__items">
              <div v-for="permission in group.permissions" :key="permission.name" class="perm">
                <Checkbox
                  v-model="selected"
                  :value="permission.name"
                  :disabled="!canEditSelected"
                  :input-id="permission.name"
                />
                <label :for="permission.name" class="perm__label">{{ permission.label }}</label>
              </div>
            </div>
          </div>

          <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>
        </template>
      </Card>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useUsersStore } from '@/stores/users'
import { extractMessage } from '@/composables/usePaginatedList'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Checkbox from 'primevue/checkbox'
import Message from 'primevue/message'

const props = defineProps<{ canManage: boolean }>()

const store = useUsersStore()
const toast = useToast()

const selectedRoleId = ref<number | null>(null)
const selected = ref<string[]>([])
const saving = ref(false)
const error = ref<string | null>(null)

const selectedRole = computed(() => store.roles.find((r: any) => r.id === selectedRoleId.value))
const isLocked = computed(() => selectedRole.value?.name === store.lockedRole)
const canEditSelected = computed(() => props.canManage && !isLocked.value)

/** El botón de guardar solo se habilita si algo cambió respecto al servidor. */
const hasChanges = computed(() => {
  const original = [...(selectedRole.value?.permissions ?? [])].sort()
  const current = [...selected.value].sort()
  return JSON.stringify(original) !== JSON.stringify(current)
})

const selectRole = (role: any) => {
  selectedRoleId.value = role.id
  selected.value = [...role.permissions]
  error.value = null
}

/** Estado del grupo: true si todos sus permisos están marcados. */
const groupState = (group: any) =>
  group.permissions.every((p: any) => selected.value.includes(p.name))

const toggleGroup = (group: any, checked: boolean) => {
  const names = group.permissions.map((p: any) => p.name)

  selected.value = checked
    ? [...new Set([...selected.value, ...names])]
    : selected.value.filter((name) => !names.includes(name))
}

const selectAll = (checked: boolean) => {
  selected.value = checked
    ? store.permissionGroups.flatMap((g: any) => g.permissions.map((p: any) => p.name))
    : []
}

const save = async () => {
  if (!selectedRole.value) return

  saving.value = true
  error.value = null

  try {
    const response = await store.syncRolePermissions(selectedRole.value.id, selected.value)
    toast.add({ severity: 'success', summary: 'Listo', detail: response.message, life: 4000 })
  } catch (err) {
    error.value = extractMessage(err, 'No se pudieron guardar los permisos.')
  } finally {
    saving.value = false
  }
}

// Se selecciona el primer rol editable en cuanto llegan los datos, para que el
// panel no arranque vacío.
watch(
  () => store.roles,
  (roles) => {
    if (selectedRoleId.value !== null || roles.length === 0) return
    const firstEditable = roles.find((r: any) => r.name !== store.lockedRole) ?? roles[0]
    selectRole(firstEditable)
  },
  { immediate: true, deep: true }
)
</script>

<style scoped lang="scss">
.roles-note {
  margin: 1rem 0;
}

.roles-layout {
  display: grid;
  grid-template-columns: 260px 1fr;
  gap: 1rem;
  align-items: start;

  @media (max-width: 1023px) {
    grid-template-columns: 1fr;
  }
}

.section-title {
  margin: 0 0 0.75rem;
  font-size: 0.9375rem;
  font-weight: 600;
  color: #374151;
}

.role-items {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.role-item {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 0.125rem;
  width: 100%;
  padding: 0.625rem 0.75rem;
  border: none;
  border-radius: 0.375rem;
  background: none;
  cursor: pointer;
  text-align: left;
  position: relative;
  transition: background-color 150ms ease-in-out;

  &:hover {
    background-color: #f3f4f6;
  }

  &--active {
    background-color: rgba(102, 126, 234, 0.1);

    .role-item__name {
      color: #667eea;
    }
  }

  &__name {
    font-size: 0.9375rem;
    font-weight: 600;
    color: #111827;
  }

  &__meta {
    font-size: 0.75rem;
    color: #6b7280;
  }

  &__lock {
    position: absolute;
    top: 0.75rem;
    right: 0.75rem;
    font-size: 0.75rem;
    color: #9ca3af;
  }
}

.matrix-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
  margin-bottom: 0.5rem;

  &__actions {
    display: flex;
    gap: 0.25rem;
    align-items: center;
  }

  .section-title {
    margin: 0;
  }
}

.perm-group {
  padding: 0.75rem 0;
  border-bottom: 1px solid #f3f4f6;

  &:last-of-type {
    border-bottom: none;
  }

  &__header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
  }

  &__title {
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
    cursor: pointer;
  }

  &__items {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 0.375rem 1rem;
    padding-left: 1.75rem;
  }
}

.perm {
  display: flex;
  align-items: center;
  gap: 0.5rem;

  &__label {
    font-size: 0.875rem;
    color: #4b5563;
    cursor: pointer;
  }
}
</style>
