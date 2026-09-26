<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import appointmentService from '@/services/appointmentService'
import type { Appointment } from '@/types'

const authStore = useAuthStore()
const appointments = ref<Appointment[]>([])
const isLoading = ref(true)
const isUpdating = ref<number | null>(null)
const error = ref('')
const success = ref('')

const today = new Date().toISOString().slice(0, 10)

const professionalAppointments = computed(() =>
  appointments.value
    .sort((first, second) =>
      `${first.date}T${first.time}`.localeCompare(`${second.date}T${second.time}`)
    )
)

const requestedAppointments = computed(() =>
  professionalAppointments.value.filter(appointment => appointment.status === 'pending')
)

const confirmedAppointments = computed(() =>
  professionalAppointments.value.filter(
    appointment => appointment.status === 'confirmed'
  )
)

const todayAppointments = computed(() =>
  confirmedAppointments.value.filter(
    appointment => appointment.date.slice(0, 10) === today
  )
)

const upcomingAppointments = computed(() =>
  confirmedAppointments.value.filter(
    appointment => appointment.date.slice(0, 10) >= today
  )
)

const pendingAppointments = computed(() =>
  [...requestedAppointments.value, ...upcomingAppointments.value]
    .sort((first, second) =>
      `${first.date}T${first.time}`.localeCompare(`${second.date}T${second.time}`)
    )
)

const completedAppointments = computed(() =>
  professionalAppointments.value.filter(
    appointment => appointment.status === 'completed'
  )
)

const cancelledAppointments = computed(() =>
  professionalAppointments.value.filter(
    appointment => appointment.status === 'cancelled'
  )
)

const completionRate = computed(() => {
  const attendedAppointments =
    completedAppointments.value.length + cancelledAppointments.value.length

  if (!attendedAppointments) {
    return 0
  }

  return Math.round(
    (completedAppointments.value.length / attendedAppointments) * 100
  )
})

const nextAppointment = computed(() => upcomingAppointments.value[0] ?? null)

const formatDate = (date: string) =>
  new Intl.DateTimeFormat('es-AR', {
    weekday: 'long',
    day: '2-digit',
    month: 'long',
    year: 'numeric'
  }).format(new Date(`${date}T00:00:00`))

const clientName = (appointment: Appointment) => {
  const client = appointment.client ?? appointment.user

  return client
    ? `${client.name} ${client.lastName}`.trim()
    : 'Cliente no informado'
}

const serviceName = (appointment: Appointment) =>
  appointment.service?.title ?? 'Servicio no informado'

const statusLabel = (appointment: Appointment) =>
  appointment.status === 'pending' ? 'Pendiente de aceptación' :
    appointment.status === 'confirmed' ? 'Aceptado' :
      appointment.status === 'completed' ? 'Completado' : 'Cancelado'

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
  success.value = ''

  try {
    const updatedAppointment = action === 'accept'
      ? await appointmentService.accept(appointment)
      : action === 'complete'
        ? await appointmentService.complete(appointment.id)
        : await appointmentService.cancel(appointment.id)
    const index = appointments.value.findIndex(item => item.id === appointment.id)

    if (index !== -1) {
      appointments.value[index] = {
        ...appointments.value[index],
        ...updatedAppointment,
        status: action === 'accept' ? 'confirmed' :
          action === 'complete' ? 'completed' : 'cancelled'
      }
    }
    success.value = action === 'accept' ? 'El turno fue aceptado.' :
      action === 'complete' ? 'El turno fue completado.' : 'El turno fue cancelado.'
  } catch (requestError) {
    console.error('No se pudo actualizar el turno desde Inicio:', requestError)
    error.value = 'No se pudo actualizar el turno. Intentá nuevamente.'
  } finally {
    isUpdating.value = null
  }
}

onMounted(async () => {
  try {
    appointments.value = await appointmentService.getProfessionalAppointments()
  } catch (requestError) {
    console.error('No se pudieron cargar los turnos del profesional:', requestError)
    error.value = 'No se pudo cargar la información del dashboard.'
  } finally {
    isLoading.value = false
  }
})
</script>

<template>
  <section class="professional-page">
    <header class="page-header">
      <div>
        <h1>Hola, {{ authStore.user?.name ?? 'profesional' }}</h1>
        <p>Este es el resumen de tu agenda.</p>
      </div>
      <div class="actions">
        <RouterLink class="btn-primary" to="/professional/appointments">
          Ver mis turnos
        </RouterLink>
        <RouterLink class="btn-secondary" to="/professional/availability">
          Gestionar disponibilidad
        </RouterLink>
      </div>
    </header>

    <p v-if="isLoading" class="state">Cargando tus turnos...</p>
    <p v-else-if="error && !appointments.length" class="state error">{{ error }}</p>

    <template v-else>
      <div class="stats-grid">
        <article class="stat-card">
          <span>Turnos de hoy</span>
          <strong>{{ todayAppointments.length }}</strong>
        </article>
        <article class="stat-card">
          <span>Solicitudes por aceptar</span>
          <strong>{{ requestedAppointments.length }}</strong>
        </article>
        <article class="stat-card">
          <span>Próximos turnos aceptados</span>
          <strong>{{ upcomingAppointments.length }}</strong>
        </article>
        <article class="stat-card">
          <span>Turnos completados</span>
          <strong>{{ completedAppointments.length }}</strong>
        </article>
        <article class="stat-card">
          <span>Total de turnos</span>
          <strong>{{ professionalAppointments.length }}</strong>
        </article>
        <article class="stat-card">
          <span>Turnos cancelados</span>
          <strong>{{ cancelledAppointments.length }}</strong>
        </article>
        <article class="stat-card">
          <span>Tasa de realización</span>
          <strong>{{ completionRate }}%</strong>
        </article>
      </div>

      <div class="content-grid">
        <article class="panel">
          <div class="panel-header">
            <div>
              <h2>Solicitudes de turno</h2>
              <p>Revisá los datos y aceptá o cancelá las solicitudes.</p>
            </div>
            <span class="count">{{ requestedAppointments.length }}</span>
          </div>

          <div v-if="requestedAppointments.length" class="appointment-list">
            <div
              v-for="appointment in requestedAppointments"
              :key="appointment.id"
              class="appointment"
            >
              <div class="appointment-details">
                <span>{{ clientName(appointment) }}</span>
                <strong>{{ serviceName(appointment) }}</strong>
                <small>{{ formatDate(appointment.date) }} · {{ appointment.time }}</small>
                <small v-if="appointment.notes">Nota: {{ appointment.notes }}</small>
              </div>
              <div class="actions">
                <button
                  class="accept-button"
                  type="button"
                  :disabled="isUpdating === appointment.id"
                  @click="updateAppointment(appointment, 'accept')"
                >
                  {{ isUpdating === appointment.id ? 'Actualizando...' : 'Aceptar' }}
                </button>
                <button
                  class="cancel-button"
                  type="button"
                  :disabled="isUpdating === appointment.id"
                  @click="updateAppointment(appointment, 'cancel')"
                >
                  Cancelar
                </button>
              </div>
            </div>
          </div>
          <p v-else class="empty">No tenés solicitudes pendientes de aceptación.</p>
        </article>

        <article class="panel">
          <div class="panel-header">
            <div>
              <h2>Próximo turno</h2>
              <p>Tu siguiente atención confirmada.</p>
            </div>
          </div>

          <div v-if="nextAppointment" class="next-appointment">
            <strong>{{ clientName(nextAppointment) }}</strong>
            <span>{{ serviceName(nextAppointment) }}</span>
            <span>{{ formatDate(nextAppointment.date) }} · {{ nextAppointment.time }}</span>
            <span v-if="nextAppointment.service?.duration">
              Duración: {{ nextAppointment.service.duration }} minutos
            </span>
            <span v-if="nextAppointment.notes">Nota: {{ nextAppointment.notes }}</span>
            <span class="status status-confirmed">{{ statusLabel(nextAppointment) }}</span>
            <time>
              Próxima atención
            </time>
          </div>
          <p v-else class="empty">No tenés próximos turnos.</p>
        </article>
      </div>

      <article class="panel upcoming-panel">
        <div class="panel-header">
          <div>
            <h2>Turnos aceptados</h2>
            <p>Consultá los detalles de las próximas atenciones.</p>
          </div>
          <RouterLink to="/professional/appointments">Ver todos</RouterLink>
        </div>

        <div v-if="pendingAppointments.length" class="appointment-list">
          <div
            v-for="appointment in pendingAppointments.slice(0, 5)"
            :key="appointment.id"
            class="appointment"
          >
            <div class="appointment-details">
              <strong>{{ clientName(appointment) }}</strong>
              <span>{{ serviceName(appointment) }}</span>
              <small>{{ formatDate(appointment.date) }} · {{ appointment.time }}</small>
              <small v-if="appointment.notes">Nota: {{ appointment.notes }}</small>
            </div>
            <div class="appointment-actions">
              <span class="status" :class="`status-${appointment.status}`">
                {{ statusLabel(appointment) }}
              </span>
              <button
                v-if="appointment.status === 'confirmed'"
                class="complete-button"
                type="button"
                :disabled="isUpdating === appointment.id"
                @click="updateAppointment(appointment, 'complete')"
              >
                {{ isUpdating === appointment.id ? 'Actualizando...' : 'Completar' }}
              </button>
            </div>
          </div>
        </div>
        <p v-else class="empty">No hay solicitudes ni turnos aceptados próximos.</p>
      </article>
      <p v-if="success" class="state success">{{ success }}</p>
      <p v-if="error" class="state error">{{ error }}</p>
    </template>
  </section>
</template>

<style scoped>
.professional-page {
  padding: 32px;
}

.page-header,
.panel-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
}

.page-header {
  margin-bottom: 28px;
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
.appointment small,
.empty,
.next-appointment span {
  color: var(--text-muted);
}

.actions {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.btn-primary,
.btn-secondary {
  text-decoration: none;
  font-weight: 600;
}

.btn-primary {
  color: white;
}

.btn-secondary {
  padding: 9px 14px;
  border: 1px solid var(--border-light);
  border-radius: 8px;
  color: var(--text-main);
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}

.stat-card,
.panel {
  padding: 20px;
  border: 1px solid var(--border-light);
  border-radius: 12px;
  background: #fff;
}

.stat-card {
  display: grid;
  gap: 8px;
}

.stat-card span {
  color: var(--text-muted);
  font-size: 13px;
}

.stat-card strong {
  font-size: 28px;
}

.content-grid {
  display: grid;
  grid-template-columns: 1.2fr 0.8fr;
  gap: 16px;
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

.appointment-list {
  display: grid;
  gap: 10px;
  margin-top: 20px;
}

.appointment {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 12px;
  border-radius: 8px;
  background: var(--bg-main);
}

.appointment div,
.next-appointment {
  display: grid;
  gap: 5px;
}

.appointment-details {
  min-width: 0;
  flex: 1;
}

.appointment-details small {
  color: var(--text-muted);
}

.appointment-actions,
.actions {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px;
}

.appointment strong {
  font-size: 15px;
}

.appointment small {
  font-size: 12px;
}

.complete-button,
.accept-button,
.cancel-button {
  padding: 8px 11px;
  border-radius: 7px;
  cursor: pointer;
  font: inherit;
  font-size: 12px;
  font-weight: 700;
}

.complete-button,
.accept-button {
  border: 0;
  background: var(--color-primary-soft);
  color: var(--color-primary);
}

.cancel-button {
  border: 1px solid #fecaca;
  background: #fff;
  color: var(--color-danger);
}

.complete-button:disabled,
.accept-button:disabled,
.cancel-button:disabled {
  cursor: not-allowed;
  opacity: .6;
}

.status {
  width: fit-content;
  padding: 5px 9px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 700;
}

.status-pending { background: #fef3c7; color: #b45309; }
.status-confirmed { background: #dbeafe; color: #1d4ed8; }
.status-completed { background: #dcfce7; color: #15803d; }
.status-cancelled { background: #fee2e2; color: #b91c1c; }

.next-appointment {
  margin-top: 24px;
}

.next-appointment time {
  color: var(--color-primary);
  font-weight: 700;
}

.upcoming-panel {
  margin-top: 16px;
}

.panel-header a {
  color: var(--color-primary);
  font-size: 13px;
  font-weight: 600;
}

.error {
  color: var(--color-danger);
}

.success {
  color: #15803d;
}

@media (max-width: 900px) {
  .professional-page {
    padding: 20px;
  }

  .stats-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .content-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 600px) {
  .page-header {
    align-items: flex-start;
    flex-direction: column;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .appointment {
    align-items: flex-start;
    flex-direction: column;
  }
}
</style>
