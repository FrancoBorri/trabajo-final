<script setup lang="ts">
import type {Role} from '@/types'
import type {CreateUserData} from '@/services/userService'

const props = defineProps<{
  role: Role
  singularLabel: string
  editing: boolean
  isSaving: boolean
  error: string
}>()

const form = defineModel<CreateUserData>('form', {required: true})

const emit = defineEmits<{
  submit: []
  close: []
}>()
</script>

<template>
  <div class="modal-backdrop" @click.self="emit('close')">
    <form class="modal" @submit.prevent="emit('submit')">
      <header class="modal-header">
        <div>
          <h2>{{ props.editing ? 'Editar' : 'Agregar' }} {{
              props.singularLabel.toLowerCase()
            }}</h2>
          <p>{{
              props.editing ? 'Actualizá los datos del usuario.' : 'Completá los datos para crear un nuevo usuario.'
            }}</p>
        </div>
        <button class="close-button" type="button" aria-label="Cerrar" @click="emit('close')">×
        </button>
      </header>

      <div class="form-grid">
        <label>
          Nombre *
          <input v-model="form.name" type="text" autocomplete="given-name" required>
        </label>
        <label>
          Apellido *
          <input v-model="form.lastName" type="text" autocomplete="family-name" required>
        </label>
        <label>
          Email *
          <input v-model="form.email" type="email" autocomplete="email" required>
        </label>
        <label>
          Teléfono
          <input v-model="form.phone" type="tel" autocomplete="tel">
        </label>
        <label v-if="props.role === 'professional'">
          Especialidad *
          <input v-model="form.specialty" type="text" required>
        </label>
        <label v-if="props.role === 'professional'" class="full-width">
          Descripción
          <textarea
            v-model="form.description"
            rows="3"
            placeholder="Contá brevemente sobre tu experiencia y los servicios que ofrecés"
          />
        </label>
        <label>
          Contraseña {{ props.editing ? '(opcional)' : '*' }}
          <input
            v-model="form.password"
            type="password"
            autocomplete="new-password"
            :required="!props.editing"
          >
        </label>
      </div>

      <p v-if="props.error" class="form-error">{{ props.error }}</p>

      <footer class="modal-actions">
        <button class="secondary-button" type="button" :disabled="props.isSaving"
                @click="emit('close')">
          Cancelar
        </button>
        <button class="primary-button" type="submit" :disabled="props.isSaving">
          {{
            props.isSaving ? 'Guardando...' : props.editing ? 'Guardar cambios' : 'Guardar usuario'
          }}
        </button>
      </footer>
    </form>
  </div>
</template>

<style scoped>
.primary-button, .secondary-button {
  border-radius: 7px;
  padding: 10px 14px;
  font: inherit;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
}

.primary-button {
  border: 0;
  background: var(--color-primary);
  color: #fff;
}

.secondary-button {
  border: 1px solid var(--border-light);
  background: #fff;
  color: var(--text-main);
}

button:disabled {
  cursor: wait;
  opacity: .6;
}

input, textarea {
  min-height: 38px;
  box-sizing: border-box;
  padding: 8px 10px;
  border: 1px solid var(--border-light);
  border-radius: 6px;
  background: #fff;
  color: var(--text-main);
  font: inherit;
}

textarea {
  resize: vertical;
}

.modal-backdrop {
  position: fixed;
  inset: 0;
  z-index: 10;
  display: grid;
  place-items: center;
  padding: 20px;
  background: rgb(0 0 0 / 45%);
}

.modal {
  width: min(560px, 100%);
  padding: 24px;
  border: 0;
  border-radius: 12px;
  background: #fff;
  box-shadow: 0 18px 50px rgb(0 0 0 / 20%);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  gap: 20px;
  margin-bottom: 20px;
}

.modal-header h2 {
  margin: 0;
  color: var(--text-main);
  font-size: 20px;
}

.modal-header p {
  margin: 6px 0 0;
  color: var(--text-muted);
  font-size: 13px;
}

.close-button {
  border: 0;
  background: transparent;
  color: var(--text-muted);
  font-size: 26px;
  line-height: 1;
  cursor: pointer;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
}

.form-grid label {
  display: grid;
  gap: 6px;
  color: var(--text-main);
  font-size: 13px;
  font-weight: 600;
}

.form-grid .full-width {
  grid-column: 1 / -1;
}

.form-error {
  margin: 16px 0 0;
  color: var(--color-danger);
  font-size: 13px;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 24px;
}

@media (max-width: 600px) {
  .form-grid {
    grid-template-columns: 1fr;
  }
}
</style>
