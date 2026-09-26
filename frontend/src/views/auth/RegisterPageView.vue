<script setup lang="ts">
import { ref } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import type { Role } from '@/types'
import AuthService from '@/services/authService.ts'
import AuthLayout from '@/components/layouts/AuthLayout.vue'

const router = useRouter()

const name = ref('')
const lastName = ref('')
const email = ref('')
const phone = ref('')
const password = ref('')
const passwordConfirmation = ref('')

const showPassword = ref(false)
const showPasswordConfirmation = ref(false)

const isLoading = ref(false)
const error = ref('')
const success = ref('')

const handleRegister = async () => {
  error.value = ''
  success.value = ''

  if (!name.value || !lastName.value || !email.value || !phone.value || !password.value || !passwordConfirmation.value) {
    error.value = 'Todos los campos son obligatorios.'
    return
  }

  if (password.value !== passwordConfirmation.value) {
    error.value = 'Las contraseñas no coinciden.'
    return
  }

  if (password.value.length < 8) {
    error.value = 'La contraseña debe tener al menos 8 caracteres.'
    return
  }

  try {
    isLoading.value = true
    const data = {
      name: name.value,
      lastName: lastName.value,
      email: email.value,
      phone: phone.value,
      role: 'client' as Role,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    }

    await AuthService.register(data)
    success.value = 'La cuenta se creó correctamente. Redirigiendo...'

    setTimeout(() => {
      router.push('/login')
    }, 1500)
  } catch (err: any) {
    console.error(err)
    error.value = err.response?.data?.message || 'No se pudo crear la cuenta.'
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <AuthLayout
    title="Sumate a nuestra plataforma"
    description="Creá tu cuenta en pocos pasos para empezar a reservar, gestionar y organizar tus turnos de forma rápida y sencilla."
  >
    <!-- Solo la tarjeta de registro -->
    <div class="register-card">
      <div class="header">
        <h2>Crear una cuenta</h2>
        <p>Completá tus datos para registrarte</p>
      </div>

      <form @submit.prevent="handleRegister">
        <div class="form-row">
          <div class="form-group">
            <label for="name">Nombre</label>
            <input id="name" v-model="name" type="text" placeholder="Juan" />
          </div>

          <div class="form-group">
            <label for="lastName">Apellido</label>
            <input id="lastName" v-model="lastName" type="text" placeholder="Pérez" />
          </div>
        </div>

        <div class="form-group">
          <label for="email">Correo electrónico</label>
          <input id="email" v-model="email" type="email" placeholder="juan.perez@ejemplo.com" />
        </div>

        <div class="form-group">
          <label for="phone">Teléfono</label>
          <input id="phone" v-model="phone" type="tel" placeholder="+54 11 1234-5678" />
        </div>

        <div class="form-group">
          <label for="password">Contraseña</label>
          <div class="password-input">
            <input id="password" v-model="password" :type="showPassword ? 'text' : 'password'" placeholder="••••••••" />
            <button type="button" class="toggle-password" @click="showPassword = !showPassword">
              <svg v-if="!showPassword" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z" /><circle cx="12" cy="12" r="3" /></svg>
              <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24" /><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68" /><path d="M6.61 6.61A13.52 13.52 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61" /><line x1="2" x2="22" y1="2" y2="22" /></svg>
            </button>
          </div>
          <small>Mínimo 8 caracteres.</small>
        </div>

        <div class="form-group">
          <label for="passwordConfirmation">Confirmar contraseña</label>
          <div class="password-input">
            <input id="passwordConfirmation" v-model="passwordConfirmation" :type="showPasswordConfirmation ? 'text' : 'password'" placeholder="••••••••" />
            <button type="button" class="toggle-password" @click="showPasswordConfirmation = !showPasswordConfirmation">
              <svg v-if="!showPasswordConfirmation" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z" /><circle cx="12" cy="12" r="3" /></svg>
              <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24" /><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68" /><path d="M6.61 6.61A13.52 13.52 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61" /><line x1="2" x2="22" y1="2" y2="22" /></svg>
            </button>
          </div>
        </div>

        <div v-if="error" class="message error">{{ error }}</div>
        <div v-if="success" class="message success">{{ success }}</div>

        <button type="submit" :disabled="isLoading" class="btn-primary submit-btn">
          <span v-if="isLoading" class="spinner"></span>
          <span>{{ isLoading ? 'Creando cuenta...' : 'Crear cuenta' }}</span>
        </button>
      </form>

      <div class="login-link">
        ¿Ya tenés una cuenta?
        <RouterLink to="/login">Iniciar sesión</RouterLink>
      </div>
    </div>
  </AuthLayout>
</template>

<style scoped>
.register-card {
  width: 100%;
  max-width: 500px;
  background: #ffffff;
  padding: 40px;
  border-radius: 20px;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
  border: 1px solid var(--border-light);
}

.header { margin-bottom: 24px; }
.header h2 { color: var(--text-main); font-size: 24px; font-weight: 700; }
.header p { margin-top: 6px; color: var(--text-muted); font-size: 14px; }

form { display: flex; flex-direction: column; gap: 16px; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.form-group { display: flex; flex-direction: column; gap: 6px; }

label { font-size: 13px; font-weight: 600; color: var(--text-main); }
input {
  width: 100%;
  padding: 11px 14px;
  border: 1px solid var(--border-light);
  border-radius: 10px;
  outline: none;
  font-size: 14px;
  transition: all 0.2s ease;
  font-family: inherit;
}
input:focus { border-color: var(--color-primary); box-shadow: 0 0 0 4px var(--color-primary-soft); }

small { color: var(--text-muted); font-size: 12px; }

.password-input { position: relative; display: flex; align-items: center; }
.password-input input { padding-right: 44px; }
.toggle-password {
  position: absolute;
  right: 12px;
  background: transparent;
  border: none;
  cursor: pointer;
  color: var(--text-muted);
  display: flex;
}
.toggle-password svg { width: 20px; height: 20px; }

.submit-btn {
  width: 100%;
  padding: 13px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  margin-top: 8px;
}

.spinner {
  width: 18px;
  height: 18px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  border-top-color: var(--text-primary-light);
  animation: spin 0.8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

.message { padding: 12px 14px; border-radius: 8px; font-size: 13px; font-weight: 500; }
.error { background: #fef2f2; color: var(--color-danger); border: 1px solid #fecaca; }
.success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }

.login-link { margin-top: 24px; text-align: center; font-size: 14px; color: var(--text-muted); }
.login-link a { color: var(--color-primary); font-weight: 600; text-decoration: none; margin-left: 4px; }
.login-link a:hover { color: var(--color-primary-hover); }

@media (max-width: 900px) {
  .register-card { border: none; box-shadow: none; padding: 20px 10px; background: transparent; }
  .form-row { grid-template-columns: 1fr; }
}
</style>
