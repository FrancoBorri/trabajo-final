<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue'
import UserFormModal from '@/components/admin/UserFormModal.vue'
import UserTable from '@/components/admin/UserTable.vue'
import userService, { type CreateUserData } from '@/services/userService'
import type { Role, User } from '@/types'

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
const editingUser = ref<User | null>(null)
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

const saveUser = async () => {
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
    console.error(`No se pudo guardar el ${props.singularLabel.toLowerCase()}:`, requestError)
    formError.value = `No se pudo guardar el ${props.singularLabel.toLowerCase()}. Revisá los datos e intentá nuevamente.`
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

    <UserTable
      v-model:search="search"
      :users="filteredUsers"
      :title="props.title"
      :singular-label="props.singularLabel"
      :is-loading="isLoading"
      :error="error"
      :deleting-id="deletingId"
      :current-page="currentPage"
      :last-page="lastPage"
      @refresh="loadUsers()"
      @page="loadUsers"
      @edit="openEditForm"
      @delete="deleteUser"
    />

    <UserFormModal
      v-if="showForm"
      v-model:form="form"
      :role="props.role"
      :singular-label="props.singularLabel"
      :editing="Boolean(editingUser)"
      :is-saving="isSaving"
      :error="formError"
      @submit="saveUser"
      @close="closeForm"
    />

  </section>
</template>

<style scoped>
.admin-page { padding: 32px; }
.primary-button {
  border: 0;
  border-radius: 7px;
  padding: 10px 14px;
  background: var(--color-primary);
  color: #fff;
  font: inherit;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
}
@media (max-width: 600px) {
  .admin-page { padding: 20px; }
}
</style>
