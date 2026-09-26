<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import appointmentService from '@/services/appointmentService'
import type { Appointment, AppointmentStatus } from '@/types'

const appointments = ref<Appointment[]>([])
const isLoading = ref(true)
const isUpdating = ref<number | null>(null)
const error = ref('')
const statusFilter = ref<'all' | AppointmentStatus>('all')
const dateFilter = ref('')

const filteredAppointments = computed(() =>
  [...appointments.value]
    .filter(appointment =>
      (statusFilter.value === 'all' || appointment.status === statusFilter.value) &&
      (!dateFilter.value || appointment.date.slice(0, 10) === dateFilter.value)
    )
    .sort((first, second) =>
      `${first.date}T${first.time}`.localeCompare(`${second.date}T${second.time}`)
    )
)

const pendingAppointments = computed(() =>
  filteredAppointments.value.filter(appointment =>
    appointment.status === 'pending' || appointment.status === 'confirmed'
  )
)

const historyAppointments = computed(() =>
  filteredAppointments.value.filter(appointment =>
    appointment.status !== 'confirmed' && appointment.status !== 'pending'
  )
)

const formatDate = (date: string) =>
  new Intl.DateTimeFormat('es-AR', {
    weekday: 'short',
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  }).format(new Date(`${date}T00:00:00`))

const clientName = (appointment: Appointment) => {
  const client = appointment.client ?? appointment.user
  return client ? `${client.name} ${client.lastName}`.trim() : 'Cliente no informado'
}

const serviceName = (appointment: Appointment) =>
  appointment.service?.title ?? 'Servicio no informado'

const statusLabel = (status: AppointmentStatus) => ({
  pending: 'Pendiente de aceptación',
  confirmed: 'Confirmado',
  completed: 'Completado',
  cancelled: 'Cancelado'
}[status])

const updateAppointment = async (
  appointment: Appointment,
  action: 'accept' | 'complete' | 'cancel'
) => {
  const messages = {
    accept: '¿Querés aceptar este turno?',
    complete: '¿Querés marcar este turno como completado?',
    cancel: '¿Querés cancelar este turno?'
  }

  if (!window.confirm(messages[action])) return

  isUpdating.value = appointment.id
  error.value = ''

  try {
    const updated = action === 'complete'
      ? await appointmentService.complete(appointment.id)
      : action === 'cancel'
        ? await appointmentService.cancel(appointment.id)
        : await appointmentService.accept(appointment)
    const index = appointments.value.findIndex(item => item.id === appointment.id)

    if (index !== -1) {
      appointments.value[index] = {
        ...appointments.value[index],
        ...updated,
        status: action === 'complete'
          ? 'completed'
          : action === 'cancel'
            ? 'cancelled'
            : 'confirmed'
      }
    }
  } catch (requestError) {
    console.error('No se pudo actualizar el turno:', requestError)
    error.value = 'No se pudo actualizar el turno. Intentá nuevamente.'
  } finally {
    isUpdating.value = null
  }
}

const deleteAppointment = async (appointment: Appointment) => {
  if (!window.confirm('¿Querés eliminar definitivamente este turno? Esta acción no se puede deshacer.')) {
    return
  }

  isUpdating.value = appointment.id
  error.value = ''

  try {
    await appointmentService.delete(appointment.id)
    appointments.value = appointments.value.filter(item => item.id !== appointment.id)
  } catch (requestError) {
    console.error('No se pudo eliminar el turno:', requestError)
    error.value = 'No se pudo eliminar el turno. Intentá nuevamente.'
  } finally {
    isUpdating.value = null
  }
}

onMounted(async () => {
  try {
    appointments.value = await appointmentService.getProfessionalAppointments()
  } catch (requestError) {
    console.error('No se pudieron cargar los turnos del profesional:', requestError)
    const responseMessage =
      requestError &&
      typeof requestError === 'object' &&
      'response' in requestError &&
      requestError.response &&
      typeof requestError.response === 'object' &&
      'data' in requestError.response &&
      requestError.response.data &&
      typeof requestError.response.data === 'object' &&
      'message' in requestError.response.data &&
      typeof requestError.response.data.message === 'string'
        ? requestError.response.data.message
        : null

    error.value = responseMessage ?? 'No se pudieron cargar tus turnos.'
  } finally {
    isLoading.value = false
  }
})
</script>

<template>
  <section class="professional-page">
    <header class="page-header">
      <div>
        <h1>Mis turnos</h1>
        <p>Consultá y administrá los turnos asignados a tu agenda.</p>
      </div>
    </header>

    <p v-if="isLoading" class="state">Cargando tus turnos...</p>
    <p v-else-if="error && !appointments.length" class="state error">{{ error }}</p>

    <template v-else>
      <div class="toolbar panel">
        <label>
          Estado
          <select v-model="statusFilter">
            <option value="all">Todos</option>
            <option value="pending">Pendientes de aceptación</option>
            <option value="confirmed">Confirmados</option>
            <option value="completed">Completados</option>
            <option value="cancelled">Cancelados</option>
          </select>
        </label>
        <label>
          Fecha
          <input v-model="dateFilter" type="date">
        </label>
        <button
          v-if="statusFilter !== 'all' || dateFilter"
          class="clear-button"
          type="button"
          @click="statusFilter = 'all'; dateFilter = ''"
        >
          Limpiar filtros
        </button>
      </div>

      <div class="stats-grid">
        <article class="stat-card">
          <span>Turnos pendientes</span>
          <strong>{{ pendingAppointments.length }}</strong>
        </article>
        <article class="stat-card">
          <span>Historial</span>
          <strong>{{ historyAppointments.length }}</strong>
        </article>
        <article class="stat-card">
          <span>Total mostrado</span>
          <strong>{{ filteredAppointments.length }}</strong>
        </article>
      </div>

      <article class="panel">
        <div class="panel-header">
          <div>
            <h2>Turnos pendientes</h2>
            <p>Aceptá las solicitudes y administrá las próximas atenciones.</p>
          </div>
        </div>

        <div v-if="pendingAppointments.length" class="appointment-list">
          <div
            v-for="appointment in pendingAppointments"
            :key="appointment.id"
            class="appointment"
          >
            <div class="appointment-info">
              <strong>{{ clientName(appointment) }}</strong>
              <span>{{ serviceName(appointment) }}</span>
            </div>
            <div class="appointment-date">
              <strong>{{ formatDate(appointment.date) }}</strong>
              <span>{{ appointment.time }}</span>
            </div>
            <div class="actions">
              <button
                v-if="appointment.status === 'pending'"
                class="complete-button"
                type="button"
                :disabled="isUpdating === appointment.id"
                @click="updateAppointment(appointment, 'accept')"
              >
                {{ isUpdating === appointment.id ? 'Actualizando...' : 'Aceptar' }}
              </button>
              <button
                v-if="appointment.status === 'confirmed'"
                class="complete-button"
                type="button"
                :disabled="isUpdating === appointment.id"
                @click="updateAppointment(appointment, 'complete')"
              >
                {{ isUpdating === appointment.id ? 'Actualizando...' : 'Completar' }}
              </button>
              <button
                class="cancel-button"
                type="button"
                :disabled="isUpdating === appointment.id"
                @click="updateAppointment(appointment, 'cancel')"
              >
                Cancelar
              </button>
              <button
                class="delete-button"
                type="button"
                :disabled="isUpdating === appointment.id"
                @click="deleteAppointment(appointment)"
              >
                Eliminar
              </button>
            </div>
          </div>
        </div>
        <p v-else class="empty">No hay turnos pendientes con estos filtros.</p>
      </article>

      <article class="panel history-panel">
        <div class="panel-header">
          <div>
            <h2>Historial de turnos</h2>
            <p>Atenciones completadas y turnos cancelados.</p>
          </div>
        </div>

        <div v-if="historyAppointments.length" class="history-list">
          <div
            v-for="appointment in historyAppointments"
            :key="appointment.id"
            class="history-item"
          >
            <div class="appointment-info">
              <strong>{{ clientName(appointment) }}</strong>
              <span>{{ serviceName(appointment) }}</span>
            </div>
            <span>{{ formatDate(appointment.date) }} · {{ appointment.time }}</span>
            <span class="status" :class="`status-${appointment.status}`">
              {{ statusLabel(appointment.status) }}
            </span>
            <button
              class="delete-button"
              type="button"
              :disabled="isUpdating === appointment.id"
              @click="deleteAppointment(appointment)"
            >
              Eliminar
            </button>
          </div>
        </div>
        <p v-else class="empty">No hay registros en el historial.</p>
      </article>

      <p v-if="error" class="state error">{{ error }}</p>
    </template>
  </section>
</template>

<style scoped>
.professional-page { padding: 32px; }
.page-header { margin-bottom: 24px; }
h1, h2, p { margin-top: 0; }
h1 { margin-bottom: 8px; }
h2 { margin-bottom: 6px; font-size: 18px; }
.page-header p, .panel-header p, .state, .appointment span,
.history-item > span:not(.status), .empty { color: var(--text-muted); }
.panel { padding: 24px; border: 1px solid var(--border-light); border-radius: 12px; background: #fff; }
.toolbar { display: flex; align-items: flex-end; gap: 16px; margin-bottom: 16px; }
label { display: grid; min-width: 180px; gap: 8px; color: var(--text-main); font-size: 13px; font-weight: 600; }
select, input { padding: 10px 12px; border: 1px solid var(--border-light); border-radius: 8px; background: #fff; color: var(--text-main); font: inherit; }
.clear-button { padding: 10px 12px; border: 1px solid var(--border-light); border-radius: 8px; background: #fff; color: var(--text-main); cursor: pointer; font: inherit; font-weight: 600; }
.stats-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; margin-bottom: 16px; }
.stat-card { display: grid; gap: 8px; padding: 20px; border: 1px solid var(--border-light); border-radius: 12px; background: #fff; }
.stat-card span { color: var(--text-muted); font-size: 13px; }
.stat-card strong { font-size: 28px; }
.panel-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; }
.appointment-list, .history-list { display: grid; gap: 10px; margin-top: 20px; }
.appointment, .history-item { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 14px; border-radius: 8px; background: var(--bg-main); }
.appointment-info, .appointment-date { display: grid; min-width: 0; flex: 1; gap: 5px; }
.appointment-date { flex: 0 1 190px; }
.actions { display: flex; flex-wrap: wrap; gap: 8px; }
button { cursor: pointer; font: inherit; font-size: 12px; font-weight: 700; }
.complete-button, .cancel-button { padding: 8px 11px; border-radius: 7px; }
.complete-button { border: 0; background: var(--color-primary-soft); color: var(--color-primary); }
.cancel-button { border: 1px solid #fecaca; background: #fff; color: var(--color-danger); }
.delete-button { padding: 8px 11px; border: 1px solid #fecaca; border-radius: 7px; background: #fff; color: var(--color-danger); }
button:disabled { cursor: not-allowed; opacity: 0.6; }
.history-panel { margin-top: 16px; }
.status { min-width: 94px; padding: 5px 9px; border-radius: 999px; text-align: center; font-size: 12px; font-weight: 700; }
.status-completed { background: #dcfce7; color: #15803d !important; }
.status-cancelled { background: #fee2e2; color: #b91c1c !important; }
.status-pending { background: #fef3c7; color: #b45309 !important; }
.error { color: var(--color-danger); }
@media (max-width: 800px) {
  .professional-page { padding: 20px; }
  .toolbar, .appointment, .history-item { align-items: stretch; flex-direction: column; }
  label, .appointment-date { width: 100%; flex: auto; }
  .stats-grid { grid-template-columns: 1fr; }
  .actions button { flex: 1; }
}
</style>
