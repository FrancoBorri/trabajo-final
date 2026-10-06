<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import appointmentService from '@/services/appointmentService'
import type { Appointment, AppointmentStatus } from '@/types'

const authStore = useAuthStore()
const appointments = ref<Appointment[]>([])
const isLoading = ref(true)
const isCancelling = ref<number | null>(null)
const error = ref('')
const statusFilter = ref<'all' | AppointmentStatus>('all')

const today = new Date().toISOString().slice(0, 10)

const clientAppointments = computed(() =>
  appointments.value
    .filter(appointment =>
      String(appointment.user_id) === String(authStore.user?.id)
    )
    .sort((first, second) =>
      `${first.date}T${first.time}`.localeCompare(`${second.date}T${second.time}`)
    )
)

const upcomingAppointments = computed(() =>
  clientAppointments.value.filter(appointment =>
    (appointment.status === 'pending' || appointment.status === 'confirmed') &&
    appointment.date.slice(0, 10) >= today
  )
)

const historyAppointments = computed(() =>
  clientAppointments.value
    .filter(appointment =>
      (appointment.status !== 'confirmed' && appointment.status !== 'pending') ||
      appointment.date.slice(0, 10) < today
    )
    .sort((first, second) =>
      `${second.date}T${second.time}`.localeCompare(`${first.date}T${first.time}`)
    )
)

const filteredHistory = computed(() =>
  statusFilter.value === 'all'
    ? historyAppointments.value
    : historyAppointments.value.filter(
      appointment => appointment.status === statusFilter.value
    )
)

const formatDate = (date: string) =>
  new Intl.DateTimeFormat('es-AR', {
    weekday: 'short',
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  }).format(new Date(`${date}T00:00:00`))

const serviceName = (appointment: Appointment) =>
  appointment.service?.title ?? 'Servicio no informado'

const professionalName = (appointment: Appointment) => {
  const professional = appointment.professional
  const name = professional?.user?.name || professional?.name
  const lastName = professional?.user?.lastName ||
    professional?.user?.last_name ||
    professional?.lastName
  const fullName = `${name ?? ''} ${lastName ?? ''}`.trim()

  return fullName || 'Profesional no informado'
}

const statusLabel = (status: AppointmentStatus) => {
  const labels: Record<AppointmentStatus, string> = {
    pending: 'Pendiente de aceptación',
    confirmed: 'Aceptado',
    completed: 'Completado',
    cancelled: 'Cancelado'
  }

  return labels[status]
}

const cancelAppointment = async (appointment: Appointment) => {
  if (!window.confirm('¿Querés cancelar este turno?')) {
    return
  }

  isCancelling.value = appointment.id
  error.value = ''

  try {
    const updatedAppointment = await appointmentService.cancel(appointment.id)
    const index = appointments.value.findIndex(item => item.id === appointment.id)

    if (index !== -1) {
      appointments.value[index] = {
        ...appointments.value[index],
        ...updatedAppointment,
        status: 'cancelled'
      }
    }
  } catch (requestError) {
    console.error('No se pudo cancelar el turno:', requestError)
    error.value = 'No se pudo cancelar el turno. Intentá nuevamente.'
  } finally {
    isCancelling.value = null
  }
}

onMounted(async () => {
  try {
    appointments.value = await appointmentService.getAll()
  } catch (requestError) {
    console.error('No se pudieron cargar los turnos:', requestError)
    error.value = 'No se pudieron cargar tus turnos.'
  } finally {
    isLoading.value = false
  }
})
</script>

<template>
  <section class="client-page">
    <header class="page-header">
      <div>
        <h1>Mis turnos</h1>
        <p>Consultá tus próximas citas y el historial de atención.</p>
      </div>
      <RouterLink class="btn-primary" to="/client/book-appointments">
        Reservar turno
      </RouterLink>
    </header>

    <p v-if="isLoading" class="state">Cargando tus turnos...</p>
    <p v-else-if="error && !appointments.length" class="state error">{{ error }}</p>

    <template v-else>
      <article class="panel">
        <div class="panel-header">
          <div>
            <h2>Próximos turnos</h2>
            <p>Consultá el estado de tus próximas citas.</p>
          </div>
          <span class="count">{{ upcomingAppointments.length }}</span>
        </div>

        <div v-if="upcomingAppointments.length" class="appointment-list">
          <div
            v-for="appointment in upcomingAppointments"
            :key="appointment.id"
            class="appointment"
          >
            <div class="appointment-main">
              <strong>{{ serviceName(appointment) }}</strong>
              <span>{{ professionalName(appointment) }}</span>
            </div>
            <div class="appointment-date">
              <strong>{{ formatDate(appointment.date) }}</strong>
              <span>{{ appointment.time }} · {{ appointment.service?.duration ?? '-' }} min</span>
            </div>
            <span class="status" :class="`status-${appointment.status}`">
              {{ statusLabel(appointment.status) }}
            </span>
            <button
              class="cancel-button"
              type="button"
              :disabled="isCancelling === appointment.id"
              @click="cancelAppointment(appointment)"
            >
              {{ isCancelling === appointment.id ? 'Cancelando...' : 'Cancelar' }}
            </button>
          </div>
        </div>
        <p v-else class="empty">No tenés próximos turnos confirmados.</p>
      </article>

      <article class="panel history-panel">
        <div class="panel-header history-header">
          <div>
            <h2>Historial de turnos</h2>
            <p>Revisá tus atenciones anteriores y turnos cancelados.</p>
          </div>
          <select v-model="statusFilter" aria-label="Filtrar historial">
            <option value="all">Todos los estados</option>
            <option value="completed">Completados</option>
            <option value="cancelled">Cancelados</option>
            <option value="confirmed">Confirmados pasados</option>
          </select>
        </div>

        <div v-if="filteredHistory.length" class="history-list">
          <div
            v-for="appointment in filteredHistory"
            :key="appointment.id"
            class="history-item"
          >
            <div class="appointment-main">
              <strong>{{ serviceName(appointment) }}</strong>
              <span>{{ professionalName(appointment) }}</span>
            </div>
            <div class="appointment-date">
              <strong>{{ formatDate(appointment.date) }}</strong>
              <span>{{ appointment.time }}</span>
            </div>
            <span
              class="status"
              :class="`status-${appointment.status}`"
            >
              {{ statusLabel(appointment.status) }}
            </span>
          </div>
        </div>
        <p v-else class="empty">No hay turnos en el historial con ese filtro.</p>
      </article>

      <p v-if="error" class="state error">{{ error }}</p>
    </template>
  </section>
</template>

<style scoped>
.client-page {
  padding: 32px;
}

.page-header,
.panel-header,
.appointment,
.history-item {
  display: flex;
  align-items: center;
  gap: 16px;
}

.page-header,
.panel-header {
  justify-content: space-between;
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
.state,
.appointment span,
.history-item span {
  color: var(--text-muted);
}

.btn-primary {
  color: white;
  text-decoration: none;
}

.panel {
  padding: 24px;
  border: 1px solid var(--border-light);
  border-radius: 12px;
  background: #fff;
}

.history-panel {
  margin-top: 16px;
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

.appointment-list,
.history-list {
  display: grid;
  gap: 10px;
  margin-top: 20px;
}

.appointment,
.history-item {
  justify-content: space-between;
  padding: 14px;
  border-radius: 8px;
  background: var(--bg-main);
}

.appointment-main,
.appointment-date {
  display: grid;
  min-width: 0;
  flex: 1;
  gap: 5px;
}

.appointment-date {
  flex: 0 1 220px;
}

.appointment-date strong {
  text-transform: capitalize;
}

.cancel-button {
  padding: 8px 12px;
  border: 1px solid #fecaca;
  border-radius: 7px;
  background: #fff;
  color: var(--color-danger);
  cursor: pointer;
  font: inherit;
  font-size: 12px;
  font-weight: 600;
}

.cancel-button:disabled {
  cursor: not-allowed;
  opacity: 0.6;
}

select {
  padding: 9px 10px;
  border: 1px solid var(--border-light);
  border-radius: 8px;
  background: #fff;
  color: var(--text-main);
  font: inherit;
}

.status {
  min-width: 100px;
  padding: 5px 9px;
  border-radius: 999px;
  text-align: center;
  font-size: 12px;
  font-weight: 700;
}

.status-confirmed {
  background: #dbeafe;
  color: #1d4ed8 !important;
}

.status-pending {
  background: #fef3c7;
  color: #b45309 !important;
}

.status-completed {
  background: #dcfce7;
  color: #15803d !important;
}

.status-cancelled {
  background: #fee2e2;
  color: #b91c1c !important;
}

.empty {
  margin: 24px 0 0;
  color: var(--text-muted);
}

.error {
  color: var(--color-danger);
}

@media (max-width: 760px) {
  .client-page {
    padding: 20px;
  }

  .page-header,
  .history-header {
    align-items: flex-start;
    flex-direction: column;
  }

  .appointment,
  .history-item {
    align-items: flex-start;
    flex-direction: column;
  }

  .appointment-date {
    flex: auto;
  }

  .status {
    align-self: flex-start;
  }

  .cancel-button {
    width: 100%;
  }
}
</style>
