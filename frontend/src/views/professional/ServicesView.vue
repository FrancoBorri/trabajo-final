<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import professionalService from '@/services/professionalService'
import serviceService from '@/services/servicesService'
import type { Service } from '@/types'

const authStore = useAuthStore()
const services = ref<Service[]>([])
const professionalId = ref<string | null>(null)
const isLoading = ref(true)
const isSaving = ref(false)
const deletingId = ref<string | null>(null)
const editingId = ref<string | null>(null)
const error = ref('')
const success = ref('')
const formError = ref('')

const form = reactive({
  title: '',
  description: '',
  price: '',
  duration: ''
})

const professionalServices = computed(() =>
  [...services.value].sort((first, second) =>
    first.title.localeCompare(second.title)
  )
)

const formatPrice = (price: number) =>
  new Intl.NumberFormat('es-AR', {
    style: 'currency',
    currency: 'ARS',
    maximumFractionDigits: 2
  }).format(price)

const resetForm = () => {
  form.title = ''
  form.description = ''
  form.price = ''
  form.duration = ''
  formError.value = ''
  editingId.value = null
}

const editService = (service: Service) => {
  editingId.value = service.id
  form.title = service.title
  form.description = service.description
  form.price = String(service.price)
  form.duration = String(service.duration)
  formError.value = ''
  success.value = ''
}

const validateForm = () => {
  if (!form.title.trim() || !form.description.trim()) {
    formError.value = 'Completá el título y la descripción.'
    return false
  }

  const price = Number(form.price)
  const duration = Number(form.duration)

  if (!Number.isFinite(price) || price < 0) {
    formError.value = 'Ingresá un precio válido.'
    return false
  }

  if (!Number.isInteger(duration) || duration <= 0) {
    formError.value = 'La duración debe ser un número entero mayor a cero.'
    return false
  }

  return true
}

const deleteService = async (service: Service) => {
  if (!window.confirm(`¿Querés eliminar el servicio "${service.title}"?`)) {
    return
  }

  deletingId.value = service.id
  error.value = ''
  success.value = ''

  try {
    await serviceService.delete(service.id)
    services.value = services.value.filter(item => item.id !== service.id)
    success.value = 'El servicio se eliminó correctamente.'
  } catch (requestError) {
    console.error('No se pudo eliminar el servicio:', requestError)
    error.value = 'No se pudo eliminar el servicio.'
  } finally {
    deletingId.value = null
  }
}

const saveService = async () => {
  formError.value = ''
  error.value = ''
  success.value = ''

  if (!validateForm()) {
    return
  }

  const currentProfessionalId = professionalId.value

  if (!currentProfessionalId) {
    error.value = 'No se pudo identificar al profesional autenticado.'
    return
  }

  const data = {
    title: form.title.trim(),
    description: form.description.trim(),
    price: Number(form.price),
    duration: Number(form.duration),
    professional_id: currentProfessionalId,
  }

  isSaving.value = true

  try {
    if (editingId.value !== null) {
      const updated = await serviceService.update(editingId.value, data)
      const index = services.value.findIndex(service => service.id === editingId.value)

      if (index !== -1) {
        services.value[index] = updated
      }
      success.value = 'El servicio se actualizó correctamente.'
    } else {
      const created = await serviceService.create(data)
      services.value.push(created)
      success.value = 'El servicio se agregó correctamente.'
    }

    resetForm()
  } catch (requestError) {
    console.error('No se pudo guardar el servicio:', requestError)
    error.value = 'No se pudo guardar el servicio.'
  } finally {
    isSaving.value = false
  }
}

onMounted(async () => {
  try {
    const [professionalsResponse, loadedServices] = await Promise.all([
      professionalService.getAll(),
      serviceService.getAll()
    ])

    const professional = professionalsResponse.data.find(item =>
      String(item.userId) === String(authStore.user?.id)
    )

    if (!professional) {
      throw new Error('No se encontró el perfil profesional del usuario autenticado.')
    }

    professionalId.value = String(professional.id)
    services.value = loadedServices.filter(service =>
      service.professional_id
        ? String(service.professional_id) === professionalId.value
        : false
    )
  } catch (requestError) {
    console.error('No se pudieron cargar los servicios:', requestError)
    error.value = 'No se pudieron cargar tus servicios.'
  } finally {
    isLoading.value = false
  }
})
</script>

<template>
  <section class="professional-page">
    <header class="page-header">
      <div>
        <h1>Mis servicios</h1>
        <p>Administrá los servicios que ofrecés y sus precios.</p>
      </div>
    </header>

    <div class="content-grid">
      <article class="panel">
        <h2>{{ editingId !== null ? 'Editar servicio' : 'Agregar servicio' }}</h2>
        <p class="description">Estos datos estarán disponibles para los clientes.</p>

        <form @submit.prevent="saveService">
          <label>
            Nombre del servicio
            <input v-model="form.title" type="text" placeholder="Ej. Consulta inicial">
          </label>

          <label>
            Descripción
            <textarea
              v-model="form.description"
              rows="4"
              placeholder="Describí brevemente el servicio"
            />
          </label>

          <div class="form-grid">
            <label>
              Precio
              <input v-model="form.price" type="number" min="0" step="0.01" placeholder="0">
            </label>
            <label>
              Duración (minutos)
              <input v-model="form.duration" type="number" min="1" step="1" placeholder="60">
            </label>
          </div>

          <p v-if="formError" class="message error">{{ formError }}</p>

          <div class="actions">
            <button class="btn-primary" type="submit" :disabled="isSaving">
              {{ isSaving ? 'Guardando...' : editingId !== null ? 'Actualizar' : 'Agregar servicio' }}
            </button>
            <button
              v-if="editingId !== null"
              class="btn-secondary"
              type="button"
              @click="resetForm"
            >
              Cancelar
            </button>
          </div>
        </form>
      </article>

      <article class="panel">
        <div class="panel-header">
          <div>
            <h2>Servicios cargados</h2>
            <p class="description">Servicios visibles para tus clientes.</p>
          </div>
          <span class="count">{{ professionalServices.length }}</span>
        </div>

        <p v-if="isLoading" class="state">Cargando servicios...</p>
        <p v-else-if="!professionalServices.length" class="state">
          Todavía no cargaste ningún servicio.
        </p>

        <div v-else class="service-list">
          <article
            v-for="service in professionalServices"
            :key="service.id"
            class="service-card"
          >
            <div>
              <h3>{{ service.title }}</h3>
              <p>{{ service.description }}</p>
              <span>{{ formatPrice(service.price) }} · {{ service.duration }} min</span>
            </div>
            <div class="card-actions">
              <button class="edit-button" type="button" @click="editService(service)">
                Editar
              </button>
              <button
                class="delete-button"
                type="button"
                :disabled="deletingId === service.id"
                @click="deleteService(service)"
              >
                {{ deletingId === service.id ? 'Eliminando...' : 'Eliminar' }}
              </button>
            </div>
          </article>
        </div>
      </article>
    </div>

    <p v-if="success" class="message success">{{ success }}</p>
    <p v-if="error" class="message error">{{ error }}</p>
  </section>
</template>

<style scoped>
.professional-page {
  padding: 32px;
}

.page-header {
  margin-bottom: 24px;
}

h1,
h2,
h3,
p {
  margin-top: 0;
}

h1 {
  margin-bottom: 8px;
}

h2 {
  margin-bottom: 6px;
  font-size: 18px;
}

h3 {
  margin-bottom: 6px;
  font-size: 16px;
}

.page-header p,
.description,
.state,
.service-card p {
  color: var(--text-muted);
}

.content-grid {
  display: grid;
  grid-template-columns: minmax(280px, 0.8fr) minmax(360px, 1.2fr);
  gap: 16px;
}

.panel {
  padding: 24px;
  border: 1px solid var(--border-light);
  border-radius: 12px;
  background: #fff;
}

.panel-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 20px;
}

label {
  display: grid;
  gap: 8px;
  margin-top: 18px;
  color: var(--text-main);
  font-size: 14px;
  font-weight: 600;
}

input,
textarea {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid var(--border-light);
  border-radius: 8px;
  background: #fff;
  color: var(--text-main);
  font: inherit;
  resize: vertical;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.actions,
.card-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 24px;
}

button {
  cursor: pointer;
  font: inherit;
  font-weight: 600;
}

.btn-primary {
  padding: 10px 14px;
  border: 0;
  border-radius: 8px;
  background: var(--color-primary);
  color: white;
}

.btn-secondary,
.edit-button,
.delete-button {
  padding: 9px 12px;
  border-radius: 8px;
  background: #fff;
}

.btn-secondary,
.edit-button {
  border: 1px solid var(--border-light);
  color: var(--text-main);
}

.delete-button {
  border: 1px solid #fecaca;
  color: var(--color-danger);
}

button:disabled {
  cursor: not-allowed;
  opacity: 0.6;
}

.count {
  display: grid;
  width: 32px;
  height: 32px;
  place-items: center;
  border-radius: 50%;
  background: var(--color-primary-soft);
  color: var(--color-primary);
  font-weight: 700;
}

.service-list {
  display: grid;
  gap: 12px;
}

.service-card {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  padding: 16px;
  border-radius: 8px;
  background: var(--bg-main);
}

.service-card p {
  margin-bottom: 8px;
}

.service-card span {
  color: var(--color-primary);
  font-size: 13px;
  font-weight: 700;
}

.card-actions {
  flex-shrink: 0;
  margin-top: 0;
}

.message {
  margin-top: 16px;
  font-size: 13px;
  font-weight: 600;
}

.success {
  color: #15803d;
}

.error {
  color: var(--color-danger);
}

@media (max-width: 800px) {
  .professional-page {
    padding: 20px;
  }

  .content-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 520px) {
  .form-grid {
    grid-template-columns: 1fr;
  }

  .service-card {
    flex-direction: column;
  }
}
</style>
