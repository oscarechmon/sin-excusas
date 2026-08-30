<template>
  <div class="login-container">
    <div class="login-card">
      <div class="login-header">
        <h1>Sin Excusas</h1>
        <p>ERP System</p>
      </div>

      <form @submit.prevent="handleLogin" class="login-form">
        <div class="form-group">
          <label for="email">Email</label>
          <InputText
            id="email"
            v-model="form.email"
            type="email"
            placeholder="correo@ejemplo.com"
            :class="{ 'p-invalid': errors.email }"
          />
          <small class="p-error" v-if="errors.email">{{ errors.email }}</small>
        </div>

        <div class="form-group">
          <label for="password">Contraseña</label>
          <InputText
            id="password"
            v-model="form.password"
            type="password"
            placeholder="••••••••"
            :class="{ 'p-invalid': errors.password }"
          />
          <small class="p-error" v-if="errors.password">{{ errors.password }}</small>
        </div>

        <Button
          type="submit"
          label="Iniciar Sesión"
          class="w-full"
          :loading="isLoading"
          :disabled="isLoading"
        />
      </form>

      <div v-if="errorMessage" class="error-message">
        <Message severity="error" :text="errorMessage" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'
import Message from 'primevue/message'

const router = useRouter()
const authStore = useAuthStore()

const form = ref({
  email: '',
  password: '',
})

const errors = ref<Record<string, string>>({})
const errorMessage = ref('')
const isLoading = ref(false)

const handleLogin = async () => {
  errors.value = {}
  errorMessage.value = ''

  if (!form.value.email) {
    errors.value.email = 'El email es requerido'
  }
  if (!form.value.password) {
    errors.value.password = 'La contraseña es requerida'
  }

  if (Object.keys(errors.value).length > 0) return

  isLoading.value = true
  const success = await authStore.login(form.value.email, form.value.password)
  isLoading.value = false

  if (success) {
    router.push({ name: 'dashboard' })
  } else {
    errorMessage.value = 'Las credenciales son inválidas'
  }
}
</script>

<style scoped lang="scss">
.login-container {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.login-card {
  background: white;
  padding: 2rem;
  border-radius: 8px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
  width: 100%;
  max-width: 400px;
}

.login-header {
  text-align: center;
  margin-bottom: 2rem;

  h1 {
    margin: 0;
    font-size: 2rem;
    color: #333;
  }

  p {
    margin: 0.5rem 0 0 0;
    color: #999;
  }
}

.login-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;

  label {
    font-weight: 500;
    color: #333;
  }
}

.error-message {
  margin-top: 1rem;
}
</style>
