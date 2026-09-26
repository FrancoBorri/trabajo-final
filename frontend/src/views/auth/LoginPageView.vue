<script setup lang="ts">
import { ref } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import {useAuthStore} from '@/stores/auth.ts'
import authService from '@/services/authService.ts'
import AuthLayout from '@/components/layouts/AuthLayout.vue'

const authStore = useAuthStore()
const router = useRouter()
const isLoading = ref(false)
const email = ref('')
const password = ref('')
const showPassword = ref(false)
const error = ref('')
const success = ref('')


const handleLogin = async () => {
  error.value = ''
  success.value = ''

  if (!email.value || !password.value) {
    error.value = 'Todos los campos son obligatorios.'
    return
  }

  try {
    isLoading.value = true

    const data = {
      email: email.value,
      password: password.value,
    }

    const response = await authService.login(data)

    authStore.login(response.user, response.token)

    success.value = '¡Sesión iniciada correctamente!'

    setTimeout(() => {
      router.push(
        response.user.role === 'client'
          ? '/client/dashboard'
          : response.user.role === 'professional'
            ? '/professional/dashboard'
          : '/admin/agenda'
      )
    }, 1000)

  } catch (err: any) {
    console.error('Error en login:', err)
    error.value = err.response?.data?.message || 'Credenciales inválidas o error al iniciar sesión.'
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <AuthLayout
    title="¡Te damos la bienvenida!"
    description="Simplificá la agenda de tu negocio, gestioná citas en tiempo real y brindá una mejor experiencia a tus clientes."
  >
    <!-- Tarjeta de Login -->
    <div class="login-card">
      <div class="header">
        <h2>Iniciar sesión</h2>
        <p>Ingresá tus credenciales para acceder al panel</p>
      </div>

      <form @submit.prevent="handleLogin">
        <!-- Email -->
        <div class="form-group">
          <label for="email">Correo electrónico</label>
          <input
            id="email"
            v-model="email"
            type="email"
            autocomplete="email"
            placeholder="ejemplo@correo.com"
          />
        </div>

        <!-- Contraseña -->
        <div class="form-group">
          <div class="label-row">
            <label for="password">Contraseña</label>
          </div>

          <div class="password-input">
            <input
              id="password"
              v-model="password"
              :type="showPassword ? 'text' : 'password'"
              autocomplete="current-password"
              placeholder="••••••••"
            />

            <button
              type="button"
              class="toggle-password"
              @click="showPassword = !showPassword"
              :aria-label="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'"
            >
              <!-- SVG Ojo Abierto -->
              <svg
                v-if="!showPassword"
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              >
                <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z" />
                <circle cx="12" cy="12" r="3" />
              </svg>

              <!-- SVG Ojo Tachado -->
              <svg
                v-else
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              >
                <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24" />
                <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68" />
                <path d="M6.61 6.61A13.52 13.52 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61" />
                <line x1="2" x2="22" y1="2" y2="22" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Mensajes de Feedback -->
        <div v-if="error" class="message error">
          {{ error }}
        </div>

        <div v-if="success" class="message success">
          {{ success }}
        </div>

        <!-- Botón con estado de carga -->
        <button type="submit" :disabled="isLoading" class="submit-btn">
          <span v-if="isLoading" class="spinner"></span>
          <span>{{ isLoading ? 'Ingresando...' : 'Iniciar sesión' }}</span>
        </button>
      </form>

      <!-- Enlace a Registro -->
      <div class="register-link">
        ¿No tenés una cuenta todavía?
        <RouterLink to="/register">Registrate</RouterLink>
      </div>
    </div>
  </AuthLayout>
</template>

<style scoped>
/* Estilos específicos de la tarjeta de Login */
.login-card {
  width: 100%;
  max-width: 420px;
  background: white;
  padding: 40px;
  border-radius: 20px;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
  border: 1px solid #e2e8f0;
}

.header {
  margin-bottom: 28px;
}

.header h2 {
  color: #0f172a;
  font-size: 24px;
  font-weight: 700;
}

.header p {
  margin-top: 6px;
  color: #64748b;
  font-size: 14px;
}

/* Formulario e Inputs */
form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

label {
  font-size: 14px;
  font-weight: 600;
  color: #334155;
}

input {
  width: 100%;
  padding: 12px 16px;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  outline: none;
  font-size: 14px;
  color: #0f172a;
  background-color: #ffffff;
  transition: all 0.2s ease;
}

input::placeholder {
  color: #94a3b8;
}

input:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
}

/* Campo Password con Ojo */
.password-input {
  position: relative;
  display: flex;
  align-items: center;
}

.password-input input {
  padding-right: 46px;
}

.toggle-password {
  position: absolute;
  right: 12px;
  background: transparent;
  border: none;
  padding: 4px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #64748b;
  border-radius: 6px;
  transition: color 0.2s;
}

.toggle-password:hover {
  color: #0f172a;
}

.toggle-password svg {
  width: 20px;
  height: 20px;
}

/* Botón de Enviar */
.submit-btn {
  width: 100%;
  padding: 13px;
  border: none;
  border-radius: 10px;
  background: #2563eb;
  color: white;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  transition: background-color 0.2s, transform 0.1s;
  margin-top: 4px;
}

.submit-btn:hover:not(:disabled) {
  background: #1d4ed8;
}

.submit-btn:active:not(:disabled) {
  transform: scale(0.99);
}

.submit-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

/* Spinner de carga */
.spinner {
  width: 18px;
  height: 18px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  border-top-color: white;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* Mensajes */
.message {
  padding: 12px 14px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 500;
}

.error {
  background: #fef2f2;
  color: #991b1b;
  border: 1px solid #fecaca;
}

.success {
  background: #f0fdf4;
  color: #166534;
  border: 1px solid #bbf7d0;
}

/* Link de Registro */
.register-link {
  margin-top: 28px;
  text-align: center;
  font-size: 14px;
  color: #64748b;
}

.register-link a {
  color: #2563eb;
  font-weight: 600;
  text-decoration: none;
  margin-left: 4px;
}

.register-link a:hover {
  text-decoration: underline;
}

@media (max-width: 900px) {
  .login-card {
    border: none;
    box-shadow: none;
    padding: 20px 10px;
    background: transparent;
  }
}
</style>
