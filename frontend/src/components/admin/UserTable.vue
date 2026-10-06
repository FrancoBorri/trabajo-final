<script setup lang="ts">
import EmptyState from '@/components/common/EmptyState.vue'
import type { User } from '@/types'
import { Pencil, Trash2 } from 'lucide-vue-next'

const props = defineProps<{
  users: User[]
  title: string
  singularLabel: string
  isLoading: boolean
  error: string
  deletingId: string | null
  currentPage: number
  lastPage: number
}>()

const search = defineModel<string>('search', { required: true })

const emit = defineEmits<{
  refresh: []
  page: [page: number]
  edit: [user: User]
  delete: [user: User]
}>()

const initials = (user: User) =>
  `${user.name.charAt(0)}${user.lastName.charAt(0)}`.toUpperCase()
</script>

<template>
  <div class="toolbar">
    <label class="search-field">
      <span>Buscar</span>
      <input
        v-model="search"
        type="search"
        placeholder="Nombre, apellido, email o teléfono"
      >
    </label>
    <button
      class="secondary-button"
      type="button"
      :disabled="props.isLoading"
      @click="emit('refresh')"
    >
      Actualizar
    </button>
  </div>

  <p v-if="props.isLoading" class="state">Cargando {{ props.title.toLowerCase() }}...</p>
  <p v-else-if="props.error" class="state error">{{ props.error }}</p>
  <div v-else-if="props.users.length > 0" class="table-wrapper">
    <table>
      <thead>
        <tr>
          <th>Usuario</th>
          <th>Email</th>
          <th>Teléfono</th>
          <th>Rol</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="user in props.users" :key="user.id">
          <td class="user-cell">
            <span class="avatar">{{ initials(user) }}</span>
            <span>
              <strong>{{ user.name }} {{ user.lastName }}</strong>
              <small>ID: {{ user.id }}</small>
            </span>
          </td>
          <td>{{ user.email }}</td>
          <td>{{ user.phone || 'Sin teléfono' }}</td>
          <td class="role">{{ props.singularLabel }}</td>
          <td class="actions">
            <button
              class="icon-button default"
              type="button"
              title="Editar usuario"
              aria-label="Editar usuario"
              @click="emit('edit', user)"
            >
              <Pencil :size="16" />
            </button>
            <button
              class="icon-button danger"
              type="button"
              :title="props.deletingId === user.id ? 'Eliminando...' : 'Eliminar usuario'"
              :aria-label="props.deletingId === user.id ? 'Eliminando...' : 'Eliminar usuario'"
              :disabled="props.deletingId === user.id"
              @click="emit('delete', user)"
            >
              <Trash2 :size="16" />
            </button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
  <EmptyState
    v-else
    :title="`No hay ${props.title.toLowerCase()}`"
    :description="`Todavía no hay ${props.title.toLowerCase()} registrados.`"
  />

  <div v-if="!props.isLoading && props.lastPage > 1" class="pagination">
    <button
      class="secondary-button"
      type="button"
      :disabled="props.currentPage === 1"
      @click="emit('page', props.currentPage - 1)"
    >
      Anterior
    </button>
    <span>Página {{ props.currentPage }} de {{ props.lastPage }}</span>
    <button
      class="secondary-button"
      type="button"
      :disabled="props.currentPage === props.lastPage"
      @click="emit('page', props.currentPage + 1)"
    >
      Siguiente
    </button>
  </div>
</template>

<style scoped>
.secondary-button {
  border: 1px solid var(--border-light);
  border-radius: 7px;
  padding: 10px 14px;
  background: #fff;
  color: var(--text-main);
  font: inherit;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
}
button:disabled { cursor: wait; opacity: .6; }
.toolbar { display: flex; align-items: end; gap: 12px; margin-bottom: 20px; }
.search-field { display: grid; gap: 6px; width: min(460px, 100%); color: var(--text-muted); font-size: 12px; font-weight: 600; }
input { min-height: 38px; box-sizing: border-box; padding: 8px 10px; border: 1px solid var(--border-light); border-radius: 6px; background: #fff; color: var(--text-main); font: inherit; }
.table-wrapper { overflow-x: auto; border: 1px solid var(--border-light); border-radius: 12px; background: #fff; }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 14px 16px; border-bottom: 1px solid var(--border-light); text-align: left; font-size: 14px; }
th { color: var(--text-muted); font-size: 12px; text-transform: uppercase; }
tbody tr:last-child td { border-bottom: 0; }
.user-cell { display: flex; align-items: center; gap: 10px; }
.user-cell small { display: block; margin-top: 4px; color: var(--text-muted); font-size: 12px; }
.avatar { display: grid; place-items: center; width: 32px; height: 32px; border-radius: 50%; background: var(--color-primary); color: #fff; font-size: 11px; font-weight: 700; }
.role { color: var(--color-primary); font-weight: 600; text-transform: capitalize; }
.actions { display: flex; align-items: center; gap: 6px; white-space: nowrap; }
.icon-button { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; padding: 0; border: 1px solid var(--border-light); border-radius: 8px; background-color: #fff; color: #64748b; cursor: pointer; transition: all .15s ease-in-out; }
.icon-button.default:hover { background-color: #f8fafc; border-color: #cbd5e1; color: var(--color-primary, #0969da); }
.icon-button.danger:hover { background-color: #fef2f2; border-color: #fecaca; color: var(--color-danger, #dc2626); }
.icon-button:active { transform: scale(.95); }
.icon-button:disabled { opacity: .5; cursor: not-allowed; transform: none; }
.state { color: var(--text-muted); }
.error { color: var(--color-danger); }
.pagination { display: flex; align-items: center; justify-content: center; gap: 14px; margin-top: 20px; color: var(--text-muted); font-size: 13px; }
@media (max-width: 600px) {
  .toolbar { align-items: stretch; flex-direction: column; }
}
</style>
