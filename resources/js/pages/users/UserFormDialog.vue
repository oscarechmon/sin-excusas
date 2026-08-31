<template>
  <Dialog
    :visible="visible"
    modal
    :header="isEdit ? 'Editar usuario' : 'Nuevo usuario'"
    :style="{ width: '560px' }"
    @update:visible="$emit('update:visible', $event)"
  >
    <form class="form-grid" @submit.prevent="submit">
      <div class="form-field">
        <label for="name">Nombre <span class="required">*</span></label>
        <InputText id="name" v-model="form.name" :invalid="!!errors.name" autofocus />
        <small v-if="errors.name" class="form-error">{{ errors.name }}</small>
      </div>

      <div class="form-field">
        <label for="email">Correo <span class="required">*</span></label>
        <InputText id="email" v-model="form.email" type="email" :invalid="!!errors.email" />
        <small v-if="errors.email" class="form-error">{{ errors.email }}</small>
        <small v-else class="form-hint">Es el usuario con el que inicia sesión.</small>
      </div>

      <div class="form-row">
        <div class="form-field">
          <label for="password">
            Contraseña <span v-if="!isEdit" class="required">*</span>
          </label>
          <Password
            id="password"
            v-model="form.password"
            toggle-mask
            :feedback="false"
            :invalid="!!errors.password"
            :placeholder="isEdit ? 'Dejar vacío para no cambiarla' : ''"
          />
          <small v-if="errors.password" class="form-error">{{ errors.password }}</small>
          <small v-else class="form-hint">Mínimo 8 caracteres.</small>
        </div>

        <div class="form-field">
          <label for="password_confirmation">Repetir contraseña</label>
          <Password
            id="password_confirmation"
            v-model="form.password_confirmation"
            toggle-mask
            :feedback="false"
          />
        </div>
      </div>

      <div class="form-field">
        <label for="roles">Roles <span class="required">*</span></label>
        <MultiSelect
          id="roles"
          v-model="form.roles"
          :options="roleOptions"
          display="chip"
          placeholder="Seleccione los roles"
          :invalid="!!errors.roles"
        />
        <small v-if="errors.roles" class="form-error">{{ errors.roles }}</small>
        <small v-else class="form-hint">Determinan a qué módulos accede.</small>
      </div>

      <div class="form-field">
        <label for="employee">Vincular con ficha de personal</label>
        <Select
          id="employee"
          v-model="form.employee_id"
          :options="linkableEmployees"
          option-label="name"
          option-value="id"
          filter
          placeholder="Sin vincular"
          show-clear
          :invalid="!!errors.employee_id"
        />
        <small v-if="errors.employee_id" class="form-error">{{ errors.employee_id }}</small>
        <small v-else class="form-hint">
          Necesario si esta persona atiende clientes y debe generar comisiones.
        </small>
      </div>

      <div class="form-field form-field--inline">
        <ToggleSwitch v-model="form.active" input-id="active" :disabled="isSelf" />
        <label for="active">Cuenta activa</label>
      </div>

      <Message v-if="isSelf" severity="info" :closable="false">
        Está editando su propia cuenta: no puede desactivarla ni quitarse el rol
        de Administrador.
      </Message>

      <Message v-if="generalError" severity="error" :closable="false">{{ generalError }}</Message>
    </form>

    <template #footer>
      <Button label="Cancelar" text severity="secondary" :disabled="saving" @click="$emit('update:visible', false)" />
      <Button label="Guardar" icon="pi pi-check" :loading="saving" @click="submit" />
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useUsersStore } from '@/stores/users'
import { useAuthStore } from '@/stores/auth'
import { useEmployeesStore } from '@/stores/employees'
import { extractMessage } from '@/composables/usePaginatedList'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import MultiSelect from 'primevue/multiselect'
import Password from 'primevue/password'
import Select from 'primevue/select'
import ToggleSwitch from 'primevue/toggleswitch'

const props = defineProps<{ visible: boolean; user: any | null }>()
const emit = defineEmits<{ 'update:visible': [boolean]; saved: [string] }>()

const store = useUsersStore()
const authStore = useAuthStore()
const employeesStore = useEmployeesStore()

const saving = ref(false)
const errors = ref<Record<string, string>>({})
const generalError = ref<string | null>(null)

const isEdit = computed(() => props.user !== null)
const isSelf = computed(() => props.user?.id === authStore.user?.id)
const roleOptions = computed(() => store.roles.map((r: any) => r.name))

/**
 * Solo se ofrecen fichas de personal libres, más la que ya tenga este usuario:
 * una ficha no puede estar vinculada a dos cuentas.
 */
const linkableEmployees = computed(() =>
  employeesStore.items.filter(
    (e: any) => !e.has_system_access || e.id === props.user?.employee?.id
  )
)

const emptyForm = () => ({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  roles: [] as string[],
  employee_id: null as number | null,
  active: true,
})

const form = ref(emptyForm())

watch(
  () => props.visible,
  (open) => {
    if (!open) return

    errors.value = {}
    generalError.value = null

    if (store.roles.length === 0) store.loadRoles()
    if (employeesStore.items.length === 0) employeesStore.load({ per_page: 100 })

    form.value = props.user
      ? {
          name: props.user.name,
          email: props.user.email,
          // Nunca se precarga la contraseña: no la conocemos ni debe viajar.
          password: '',
          password_confirmation: '',
          roles: [...(props.user.roles ?? [])],
          employee_id: props.user.employee?.id ?? null,
          active: props.user.active,
        }
      : emptyForm()
  }
)

const submit = async () => {
  saving.value = true
  errors.value = {}
  generalError.value = null

  const payload: Record<string, unknown> = {
    name: form.value.name,
    email: form.value.email,
    roles: form.value.roles,
    employee_id: form.value.employee_id ?? null,
    active: form.value.active,
  }

  // Al editar, una contraseña vacía significa "no cambiarla".
  if (form.value.password) {
    payload.password = form.value.password
    payload.password_confirmation = form.value.password_confirmation
  }

  try {
    const response = props.user
      ? await store.updateUser(props.user.id, payload)
      : await store.createUser(payload)

    emit('saved', response.message)
  } catch (err: any) {
    const fieldErrors = err?.response?.data?.errors ?? {}
    errors.value = Object.fromEntries(
      Object.entries(fieldErrors)
        .filter(([, messages]) => Array.isArray(messages))
        .map(([key, messages]) => [key, (messages as string[])[0]])
    )
    if (Object.keys(errors.value).length === 0) {
      generalError.value = extractMessage(err, 'No se pudo guardar el usuario.')
    }
  } finally {
    saving.value = false
  }
}
</script>

<style scoped lang="scss">
.form-hint {
  font-size: 0.75rem;
  color: #6b7280;
}

// Password de PrimeVue envuelve el input en un contenedor propio, que también
// debe ocupar el ancho del campo.
:deep(.p-password) {
  width: 100%;

  input {
    width: 100%;
  }
}
</style>
