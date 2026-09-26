<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue'
import EmptyState from '@/components/common/EmptyState.vue'
import userService, { type CreateUserData } from '@/services/userService'
import type { Role, User } from '@/types'
import { Eye, Pencil, Trash2 } from 'lucide-vue-next'

const props = defineProps<{
  role: Role
  title: string
  description: string
  singularLabel: string
}>()

const users = ref<User[]>([])
const isLoading = ref(true)
const isSaving = ref(false)
const error = ref('')
const formError = ref('')
const search = ref('')
const showForm = ref(false)
const showDetails = ref(false)
const editingUser = ref<User | null>(null)
const selectedUser = ref<User | null>(null)
const deletingId = ref<string | null>(null)
const currentPage = ref(1)
const lastPage = ref(1)

const form = reactive<CreateUserData>({
  name: '',
  lastName: '',
  email: '',
  phone: '',
  password: '',
  specialty: '',
  description: ''
})

const filteredUsers = computed(() => {
  const query = search.value.trim().toLowerCase()

  if (!query) {
    return users.value
  }

  return users.value.filter(user =>
    [user.name, user.lastName, user.email, user.phone ?? '']
      .join(' ')
      .toLowerCase()
      .includes(query)
  )
})

const loadUsers = async (page = currentPage.value) => {
  isLoading.value = true
  error.value = ''

  try {
    const response = await userService.getAll(props.role, page)
    users.value = response.data
    currentPage.value = response.current_page
    lastPage.value = response.last_page
  } catch (requestError) {
    console.error(`No se pudieron cargar los ${props.title.toLowerCase()}:`, requestError)
    error.value = `No se pudieron cargar los ${props.title.toLowerCase()}.`
  } finally {
    isLoading.value = false
  }
}

const resetForm = () => {
  form.name = ''
  form.lastName = ''
  form.email = ''
  form.phone = ''
  form.password = ''
  form.specialty = ''
  form.description = ''
  formError.value = ''
}

const openForm = () => {
  resetForm()
  editingUser.value = null
  showForm.value = true
}

const openEditForm = (user: User) => {
  form.name = user.name
  form.lastName = user.lastName
  form.email = user.email
  form.phone = user.phone ?? ''
  form.password = ''
  form.specialty = user.specialty ?? ''
  form.description = user.description ?? ''
  formError.value = ''
  editingUser.value = user
  showForm.value = true
}

const closeForm = () => {
  if (!isSaving.value) {
    showForm.value = false
  }
}

const openDetails = (user: User) => {
  selectedUser.value = user
  showDetails.value = true
}

const closeDetails = () => {
  showDetails.value = false
  selectedUser.value = null
}

const createUser = async () => {
  formError.value = ''

  if (!form.name || !form.lastName || !form.email || (!editingUser.value && !form.password)) {
    formError.value = 'Completá los campos obligatorios.'
    return
  }

  if (props.role === 'professional' && !form.specialty) {
    formError.value = 'La especialidad es obligatoria para los profesionales.'
    return
  }

  isSaving.value = true

  try {
    if (editingUser.value) {
      await userService.update(props.role, editingUser.value.id, {
        ...form,
        ...(form.password ? {} : { password: undefined })
      })
    } else {
      await userService.create(props.role, form)
    }
    showForm.value = false
    editingUser.value = null
    await loadUsers(1)
  } catch (requestError) {
    console.error(`No se pudo crear el ${props.singularLabel.toLowerCase()}:`, requestError)
    formError.value = `No se pudo crear el ${props.singularLabel.toLowerCase()}. Revisá los datos e intentá nuevamente.`
  } finally {
    isSaving.value = false
  }
}

const deleteUser = async (user: User) => {
  if (!window.confirm(`¿Querés eliminar a ${user.name} ${user.lastName}?`)) {
    return
  }

  deletingId.value = user.id
  error.value = ''

  try {
    await userService.delete(props.role, user.id)
    users.value = users.value.filter(item => item.id !== user.id)
  } catch (requestError) {
    console.error('No se pudo eliminar el usuario:', requestError)
    error.value = 'No se pudo eliminar el usuario.'
  } finally {
    deletingId.value = null
  }
}

const initials = (user: User) =>
  `${user.name.charAt(0)}${user.lastName.charAt(0)}`.toUpperCase()

onMounted(() => loadUsers(1))

watch(
  () => props.role,
  () => {
    search.value = ''
    loadUsers(1)
  }
)
</script>

<template>
  <section class="admin-page">
    <AdminPageHeader :title="props.title" :description="props.description">
      <template #actions>
        <button class="primary-button" type="button" @click="openForm">
          Agregar {{ props.singularLabel.toLowerCase() }}
        </button>
      </template>
    </AdminPageHeader>

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
        :disabled="isLoading"
        @click="loadUsers()"
      >
        Actualizar
      </button>
    </div>

    <p v-if="isLoading" class="state">Cargando {{ props.title.toLowerCase() }}...</p>
    <p v-else-if="error" class="state error">{{ error }}</p>
    <div v-else-if="filteredUsers.length > 0" class="table-wrapper">
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
        <tr v-for="user in filteredUsers" :key="user.id">
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
              title="Ver detalles"
              aria-label="Ver detalles"
              @click="openDetails(user)"
            >
              <Eye :size="16" />
            </button>

            <button
              class="icon-button default"
              type="button"
              title="Editar usuario"
              aria-label="Editar usuario"
              @click="openEditForm(user)"
            >
              <Pencil :size="16" />
            </button>

            <button
              class="icon-button danger"
              type="button"
              :title="deletingId === user.id ? 'Eliminando...' : 'Eliminar usuario'"
              :aria-label="deletingId === user.id ? 'Eliminando...' : 'Eliminar usuario'"
              :disabled="deletingId === user.id"
              @click="deleteUser(user)"
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

    <div v-if="!isLoading && lastPage > 1" class="pagination">
      <button
        class="secondary-button"
        type="button"
        :disabled="currentPage === 1"
        @click="loadUsers(currentPage - 1)"
      >
        Anterior
      </button>
      <span>Página {{ currentPage }} de {{ lastPage }}</span>
      <button
        class="secondary-button"
        type="button"
        :disabled="currentPage === lastPage"
        @click="loadUsers(currentPage + 1)"
      >
        Siguiente
      </button>
    </div>

    <div v-if="showForm" class="modal-backdrop" @click.self="closeForm">
      <form class="modal" @submit.prevent="createUser">
        <header class="modal-header">
          <div>
            <h2>{{ editingUser ? 'Editar' : 'Agregar' }} {{ props.singularLabel.toLowerCase() }}</h2>
            <p>{{ editingUser ? 'Actualizá los datos del usuario.' : 'Completá los datos para crear un nuevo usuario.' }}</p>
          </div>
          <button class="close-button" type="button" aria-label="Cerrar" @click="closeForm">×</button>
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
            Contraseña {{ editingUser ? '(opcional)' : '*' }}
            <input v-model="form.password" type="password" autocomplete="new-password" :required="!editingUser">
          </label>
        </div>

        <p v-if="formError" class="form-error">{{ formError }}</p>

        <footer class="modal-actions">
          <button class="secondary-button" type="button" :disabled="isSaving" @click="closeForm">
            Cancelar
          </button>
          <button class="primary-button" type="submit" :disabled="isSaving">
            {{ isSaving ? 'Guardando...' : editingUser ? 'Guardar cambios' : 'Guardar usuario' }}
          </button>
        </footer>
      </form>
    </div>

    <div v-if="showDetails && selectedUser" class="modal-backdrop" @click.self="closeDetails">
      <article class="modal">
        <header class="modal-header">
          <div>
            <h2>Detalle del {{ props.singularLabel.toLowerCase() }}</h2>
            <p>{{ selectedUser.name }} {{ selectedUser.lastName }}</p>
          </div>
          <button class="close-button" type="button" @click="closeDetails">×</button>
        </header>
        <dl class="details">
          <div><dt>ID</dt><dd>{{ selectedUser.id }}</dd></div>
          <div><dt>Email</dt><dd>{{ selectedUser.email }}</dd></div>
          <div><dt>Teléfono</dt><dd>{{ selectedUser.phone || 'Sin teléfono' }}</dd></div>
          <div v-if="selectedUser.specialty"><dt>Especialidad</dt><dd>{{ selectedUser.specialty }}</dd></div>
          <div v-if="selectedUser.description"><dt>Descripción</dt><dd>{{ selectedUser.description }}</dd></div>
        </dl>
        <footer class="modal-actions">
          <button class="secondary-button" type="button" @click="closeDetails">Cerrar</button>
          <button class="primary-button" type="button" @click="closeDetails(); openEditForm(selectedUser)">Editar</button>
        </footer>
      </article>
    </div>
  </section>
</template>

<style scoped>
.admin-page { padding: 32px; }
.primary-button, .secondary-button {
  border-radius: 7px;
  padding: 10px 14px;
  font: inherit;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
}
.primary-button { border: 0; background: var(--color-primary); color: #fff; }
.secondary-button { border: 1px solid var(--border-light); background: #fff; color: var(--text-main); }
button:disabled { cursor: wait; opacity: .6; }
.toolbar { display: flex; align-items: end; gap: 12px; margin-bottom: 20px; }
.search-field { display: grid; gap: 6px; width: min(460px, 100%); color: var(--text-muted); font-size: 12px; font-weight: 600; }
input, textarea { min-height: 38px; box-sizing: border-box; padding: 8px 10px; border: 1px solid var(--border-light); border-radius: 6px; background: #fff; color: var(--text-main); font: inherit; }
textarea { resize: vertical; }
.table-wrapper { overflow-x: auto; border: 1px solid var(--border-light); border-radius: 12px; background: #fff; }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 14px 16px; border-bottom: 1px solid var(--border-light); text-align: left; font-size: 14px; }
th { color: var(--text-muted); font-size: 12px; text-transform: uppercase; }
tbody tr:last-child td { border-bottom: 0; }
.user-cell { display: flex; align-items: center; gap: 10px; }
.user-cell small { display: block; margin-top: 4px; color: var(--text-muted); font-size: 12px; }
.avatar { display: grid; place-items: center; width: 32px; height: 32px; border-radius: 50%; background: var(--color-primary); color: #fff; font-size: 11px; font-weight: 700; }
.role { color: var(--color-primary); font-weight: 600; text-transform: capitalize; }

/* Acciones en la tabla */
.actions {
  display: flex;
  align-items: center;
  gap: 6px;
  white-space: nowrap;
}
.icon-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  padding: 0;
  border: 1px solid var(--border-light);
  border-radius: 8px;
  background-color: #ffffff;
  color: #64748b;
  cursor: pointer;
  transition: all 0.15s ease-in-out;
}
.icon-button.default:hover {
  background-color: #f8fafc;
  border-color: #cbd5e1;
  color: var(--color-primary, #0969da);
}
.icon-button.danger:hover {
  background-color: #fef2f2;
  border-color: #fecaca;
  color: var(--color-danger, #dc2626);
}
.icon-button:active {
  transform: scale(0.95);
}
.icon-button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none;
}

.state { color: var(--text-muted); }
.error, .form-error { color: var(--color-danger); }
.pagination { display: flex; align-items: center; justify-content: center; gap: 14px; margin-top: 20px; color: var(--text-muted); font-size: 13px; }
.modal-backdrop { position: fixed; inset: 0; z-index: 10; display: grid; place-items: center; padding: 20px; background: rgb(0 0 0 / 45%); }
.modal { width: min(560px, 100%); padding: 24px; border-radius: 12px; background: #fff; box-shadow: 0 18px 50px rgb(0 0 0 / 20%); }
.modal-header { display: flex; justify-content: space-between; gap: 20px; margin-bottom: 20px; }
.modal-header h2 { margin: 0; color: var(--text-main); font-size: 20px; }
.modal-header p { margin: 6px 0 0; color: var(--text-muted); font-size: 13px; }
.close-button { border: 0; background: transparent; color: var(--text-muted); font-size: 26px; line-height: 1; cursor: pointer; }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.form-grid label { display: grid; gap: 6px; color: var(--text-main); font-size: 13px; font-weight: 600; }
.form-grid .full-width { grid-column: 1 / -1; }
.form-error { margin: 16px 0 0; font-size: 13px; }
.modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 24px; }
.details { display: grid; gap: 12px; margin: 0; }
.details div { display: flex; justify-content: space-between; gap: 20px; border-bottom: 1px solid var(--border-light); padding-bottom: 8px; }
.details dt { color: var(--text-muted); font-size: 13px; }
.details dd { margin: 0; font-size: 14px; text-align: right; }
@media (max-width: 600px) {
  .admin-page { padding: 20px; }
  .toolbar { align-items: stretch; flex-direction: column; }
  .form-grid { grid-template-columns: 1fr; }
}
</style>
