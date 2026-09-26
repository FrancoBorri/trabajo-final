<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import availabilityService, {
  type AvailabilityPayload
} from '@/services/availabilityService'
import type { Availability } from '@/types'
import { Pencil, Trash2 } from 'lucide-vue-next'

const authStore = useAuthStore()
const availabilities = ref<Availability[]>([])
const isLoading = ref(true)
const isSaving = ref(false)
const deletingId = ref<string | null>(null)
const error = ref('')
const success = ref('')
const formError = ref('')
const editingId = ref<string | null>(null)

const form = reactive({
  dayOfWeek: '',
  time_start: '09:00',
  time_end: '17:00'
})

const daysOfWeek = [
  { value: 1, label: 'Lunes' },
  { value: 2, label: 'Martes' },
  { value: 3, label: 'Miércoles' },
  { value: 4, label: 'Jueves' },
  { value: 5, label: 'Viernes' },
  { value: 6, label: 'Sábado' },
  { value: 7, label: 'Domingo' }
]

const professionalAvailabilities = computed(() =>
  availabilities.value
    .sort((first, second) => {
      const firstDay = daysOfWeek.findIndex(day => day.value === first.day_week)
      const secondDay = daysOfWeek.findIndex(day => day.value === second.day_week)

      return firstDay - secondDay || first.time_start.localeCompare(second.time_start)
    })
)

const dayLabel = (dayOfWeek: number) =>
  daysOfWeek.find(day => day.value === dayOfWeek)?.label ?? 'Día no informado'

const resetForm = () => {
  form.dayOfWeek = ''
  form.time_start = '09:00'
  form.time_end = '17:00'
  formError.value = ''
  editingId.value = null
}

const editAvailability = (availability: Availability) => {
  editingId.value = availability.id
  form.dayOfWeek = String(availability.day_week)
  form.time_start = availability.time_start.slice(0, 5)
  form.time_end = availability.time_end.slice(0, 5)
  formError.value = ''
  success.value = ''
}

const validateForm = () => {
  if (!form.dayOfWeek || !form.time_start || !form.time_end) {
    formError.value = 'Completá el día y los horarios.'
    return false
  }

  if (form.time_start >= form.time_end) {
    formError.value = 'El horario de inicio debe ser anterior al horario de fin.'
    return false
  }

  const overlaps = professionalAvailabilities.value.some(availability => {
    const start = availability.time_start.slice(0, 5)
    const end = availability.time_end.slice(0, 5)

    return (
      availability.id !== editingId.value &&
      availability.day_week === Number(form.dayOfWeek) &&
      form.time_start < end &&
      form.time_end > start
    )
  })

  if (overlaps) {
    formError.value = 'El horario se superpone con otra disponibilidad.'
    return false
  }

  return true
}

const loadAvailabilities = async () => {
  availabilities.value = await availabilityService.getAll()
}

const saveAvailability = async () => {
  formError.value = ''
  error.value = ''
  success.value = ''

  if (!validateForm()) {
    return
  }

  const professionalId = authStore.user?.id

  if (!professionalId) {
    error.value = 'No se pudo identificar al profesional autenticado.'
    return
  }

  isSaving.value = true

  const data: AvailabilityPayload = {
    professional_id: professionalId,
    day_week: Number(form.dayOfWeek),
    time_start: form.time_start,
    time_end: form.time_end
  }

  try {
    if (editingId.value) {
      const updated = await availabilityService.update(editingId.value, data)
      const index = availabilities.value.findIndex(item => item.id === editingId.value)

      if (index === -1) {
        availabilities.value.unshift(updated)
      } else {
        availabilities.value[index] = updated
      }
      success.value = 'La disponibilidad se actualizó correctamente.'
    } else {
      const created = await availabilityService.create(data)
      availabilities.value.unshift(created)
      success.value = 'La disponibilidad se agregó correctamente.'
    }

    resetForm()
  } catch (requestError) {
    console.error('No se pudo guardar la disponibilidad:', requestError)
    error.value = 'No se pudo guardar la disponibilidad.'
  } finally {
    isSaving.value = false
  }
}

const deleteAvailability = async (availability: Availability) => {
  if (!window.confirm(`¿Querés eliminar la disponibilidad del ${dayLabel(availability.day_week)}?`)) {
    return
  }

  deletingId.value = availability.id
  error.value = ''
  success.value = ''

  try {
    await availabilityService.delete(availability.id)
    availabilities.value = availabilities.value.filter(
      item => item.id !== availability.id
    )
    success.value = 'La disponibilidad se eliminó correctamente.'
  } catch (requestError) {
    console.error('No se pudo eliminar la disponibilidad:', requestError)
    error.value = 'No se pudo eliminar la disponibilidad.'
  } finally {
    deletingId.value = null
  }
}

onMounted(async () => {
  try {
    await loadAvailabilities()
  } catch (requestError) {
    console.error('No se pudo cargar la disponibilidad:', requestError)
    error.value = 'No se pudo cargar tu disponibilidad.'
  } finally {
    isLoading.value = false
  }
})
</script>

<template>
  <section class="professional-page">
    <header class="page-header">
      <div>
        <h1>Mi disponibilidad</h1>
        <p>Definí los días y horarios en los que podés atender.</p>
      </div>
    </header>

    <div class="content-grid">
      <article class="panel">
        <div class="panel-header">
          <div>
            <h2>{{ editingId ? 'Editar horario' : 'Agregar disponibilidad' }}</h2>
            <p>Los clientes podrán reservar dentro de estas franjas.</p>
          </div>
        </div>

        <form @submit.prevent="saveAvailability">
          <label>
            Día de la semana
            <select v-model="form.dayOfWeek">
              <option value="" disabled>Seleccioná un día</option>
              <option
                v-for="day in daysOfWeek"
                :key="day.value"
                :value="day.value"
              >
                {{ day.label }}
              </option>
            </select>
          </label>

          <div class="time-grid">
            <label>
              Desde
              <input v-model="form.time_start" type="time">
            </label>
            <label>
              Hasta
              <input v-model="form.time_end" type="time">
            </label>
          </div>

          <p v-if="formError" class="message error">{{ formError }}</p>

          <div class="form-actions">
            <button class="btn-primary" type="submit" :disabled="isSaving">
              {{ isSaving ? 'Guardando...' : editingId ? 'Actualizar' : 'Agregar horario' }}
            </button>
            <button
              v-if="editingId"
              class="btn-secondary"
              type="button"
              @click="resetForm"
            >
              Cancelar edición
            </button>
          </div>
        </form>
      </article>

      <article class="panel">
        <div class="panel-header">
          <div>
            <h2>Horarios configurados</h2>
            <p>Tu disponibilidad actual.</p>
          </div>
          <span class="count">{{ professionalAvailabilities.length }}</span>
        </div>

        <p v-if="isLoading" class="state">Cargando disponibilidad...</p>
        <p v-else-if="!professionalAvailabilities.length" class="state">
          Todavía no configuraste horarios.
        </p>

        <div v-else class="availability-list">
          <div
            v-for="availability in professionalAvailabilities"
            :key="availability.id"
            class="availability-item"
          >
            <div>
              <strong>{{ dayLabel(availability.day_week) }}</strong>
              <span>{{ availability.time_start.slice(0, 5) }} - {{ availability.time_end.slice(0, 5) }}</span>
            </div>
            <div class="item-actions">
              <button
                class="icon-button default"
                type="button"
                title="Editar horario"
                aria-label="Editar horario"
                @click="editAvailability(availability)"
              >
                <Pencil :size="16" />
              </button>
              <button
                class="icon-button danger"
                type="button"
                :disabled="deletingId === availability.id"
                :title="deletingId === availability.id ? 'Eliminando...' : 'Eliminar horario'"
                :aria-label="deletingId === availability.id ? 'Eliminando...' : 'Eliminar horario'"
                @click="deleteAvailability(availability)"
              >
                <Trash2 :size="16" />
              </button>
            </div>
          </div>
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

.page-header p,
.panel-header p,
.state {
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

label {
  display: grid;
  gap: 8px;
  margin-top: 18px;
  color: var(--text-main);
  font-size: 14px;
  font-weight: 600;
}

select,
input {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid var(--border-light);
  border-radius: 8px;
  background: #fff;
  color: var(--text-main);
  font: inherit;
}

.time-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.form-actions,
.item-actions {
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

.btn-secondary {
  padding: 9px 12px;
  border-radius: 8px;
  background: #fff;
}

.btn-secondary {
  border: 1px solid var(--border-light);
  color: var(--text-main);
}

.icon-button {
  display: inline-grid;
  width: 34px;
  height: 34px;
  place-items: center;
  padding: 0;
  border-radius: 8px;
  background: #fff;
}

.icon-button.default {
  border: 1px solid var(--border-light);
  color: var(--text-main);
}

.icon-button.danger {
  border: 1px solid #fecaca;
  color: var(--color-danger);
}

button:disabled {
  cursor: not-allowed;
  opacity: 0.6;
}

.availability-list {
  display: grid;
  gap: 10px;
}

.availability-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 14px;
  border-radius: 8px;
  background: var(--bg-main);
}

.availability-item > div:first-child {
  display: grid;
  gap: 5px;
}

.availability-item span {
  color: var(--text-muted);
  font-size: 13px;
}

.item-actions {
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
  .time-grid {
    grid-template-columns: 1fr;
  }

  .availability-item {
    align-items: flex-start;
    flex-direction: column;
  }
}
</style>
