<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import appointmentService from '@/services/appointmentService'
import type { Appointment } from '@/types'

const authStore = useAuthStore()
const userName = computed(() => authStore.user?.name ?? 'cliente')
const appointments = ref<Appointment[]>([])
const isLoading = ref(true)
const error = ref('')

const formatDate = (date: string) =>
  new Intl.DateTimeFormat('es-AR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  }).format(new Date(`${date}T00:00:00`))

const today = new Date().toISOString().slice(0, 10)

const clientAppointments = computed(() =>
  appointments.value
    .filter(appointment => String(appointment.user_id) === String(authStore.user?.id))
    .sort((first, second) =>
      `${first.date}T${first.time}`.localeCompare(`${second.date}T${second.time}`)
    )
)

const pendingAppointments = computed(() =>
  clientAppointments.value.filter(appointment =>
    (appointment.status === 'pending' || appointment.status === 'confirmed') &&
    appointment.date.slice(0, 10) >= today
  )
)

const todayAppointments = computed(() =>
  pendingAppointments.value.filter(appointment => appointment.date.slice(0, 10) === today)
)

const nextAppointment = computed(() => pendingAppointments.value[0] ?? null)

const appointmentTitle = (appointment: Appointment) =>
  appointment.service?.title ?? 'Turno'

const statusLabel = (appointment: Appointment) =>
  appointment.status === 'confirmed' ? 'Aceptado' : 'Pendiente de aceptación'

const professionalName = (appointment: Appointment) => {
  const professional = appointment.professional
  const name = professional?.user?.name || professional?.name
  const lastName = professional?.user?.lastName ||
    professional?.user?.last_name ||
    professional?.lastName
  const fullName = `${name ?? ''} ${lastName ?? ''}`.trim()

  return fullName || 'Profesional no informado'
}

onMounted(async () => {
  try {
    appointments.value = await appointmentService.getAll()
  } catch (requestError) {
    console.error('No se pudieron cargar los turnos del cliente:', requestError)
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
        <h1>Hola, {{ userName }}</h1>
        <p>Este es el resumen de tus turnos.</p>
      </div>
      <RouterLink class="btn-primary" to="/client/book-appointments">
        Reservar turno
      </RouterLink>
    </header>

    <p v-if="isLoading" class="state">Cargando tus turnos...</p>
    <p v-else-if="error" class="state error">{{ error }}</p>

    <template v-else>
      <div class="stats-grid">
        <article class="stat-card">
          <span>Turno de hoy</span>
          <strong>{{ todayAppointments.length }}</strong>
        </article>
        <article class="stat-card">
          <span>Turnos pendientes</span>
          <strong>{{ pendingAppointments.length }}</strong>
        </article>
        <article class="stat-card">
          <span>Turnos completados</span>
          <strong>{{ clientAppointments.filter(appointment => appointment.status === 'completed').length }}</strong>
        </article>
      </div>

      <div class="content-grid">
        <article class="panel today-panel">
          <div class="panel-header">
            <div>
              <h2>Turno del día</h2>
              <p>{{ formatDate(today) }}</p>
            </div>
            <span class="panel-count">{{ todayAppointments.length }}</span>
          </div>

          <div v-if="todayAppointments.length" class="appointment-list">
            <div
              v-for="appointment in todayAppointments"
              :key="appointment.id"
              class="appointment"
            >
              <div>
                <strong>{{ appointmentTitle(appointment) }}</strong>
                <span>{{ professionalName(appointment) }}</span>
              </div>
              <span class="status" :class="`status-${appointment.status}`">
                {{ statusLabel(appointment) }}
              </span>
              <time>{{ appointment.time }}</time>
            </div>
          </div>
          <p v-else class="empty">No tenés turnos confirmados para hoy.</p>
        </article>

        <article class="panel">
          <div class="panel-header">
            <div>
              <h2>Próximo turno</h2>
              <p>Tu siguiente cita confirmada</p>
            </div>
          </div>

          <div v-if="nextAppointment" class="next-appointment">
            <strong>{{ appointmentTitle(nextAppointment) }}</strong>
            <span>{{ professionalName(nextAppointment) }}</span>
            <span class="status" :class="`status-${nextAppointment.status}`">
              {{ statusLabel(nextAppointment) }}
            </span>
            <time>
              {{ formatDate(nextAppointment.date) }} · {{ nextAppointment.time }}
            </time>
          </div>
          <p v-else class="empty">No tenés próximos turnos.</p>
        </article>
      </div>

      <article class="panel pending-panel">
        <div class="panel-header">
          <div>
            <h2>Turnos pendientes</h2>
            <p>Próximas citas confirmadas</p>
          </div>
          <RouterLink to="/client/appointments">Ver todos</RouterLink>
        </div>

        <div v-if="pendingAppointments.length" class="appointment-list">
          <div
            v-for="appointment in pendingAppointments.slice(0, 5)"
            :key="appointment.id"
            class="appointment"
          >
            <div>
              <strong>{{ appointmentTitle(appointment) }}</strong>
              <span>{{ professionalName(appointment) }}</span>
            </div>
            <span class="status" :class="`status-${appointment.status}`">
              {{ statusLabel(appointment) }}
            </span>
            <span>{{ formatDate(appointment.date) }} · {{ appointment.time }}</span>
          </div>
        </div>
        <p v-else class="empty">No tenés turnos pendientes.</p>
      </article>
    </template>
  </section>
</template>

<style scoped>
.client-page {
  padding: 32px;
}

.page-header,
.panel-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
}

h1,
h2 {
  margin: 0;
}

h1 {
  font-size: 28px;
}

.page-header p,
.panel-header p,
.appointment span,
.empty,
.state {
  margin: 6px 0 0;
  color: var(--text-muted);
}

.btn-primary {
  color: white;
  text-decoration: none;
}

.btn-secondary {
  padding: 10px 18px;
  border: 1px solid var(--border-light);
  border-radius: 8px;
  color: var(--text-main);
  text-decoration: none;
  font-weight: 600;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 16px;
  margin: 28px 0 24px;
}

.stat-card,
.panel {
  padding: 20px;
  border: 1px solid var(--border-light);
  border-radius: 12px;
  background: white;
}

.stat-card {
  display: grid;
  gap: 8px;
}

.stat-card span,
.panel-header p {
  font-size: 13px;
}

.stat-card strong {
  font-size: 28px;
}

.content-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.panel h2 {
  font-size: 18px;
}

.panel-header a {
  color: var(--color-primary);
  font-size: 13px;
  font-weight: 600;
}

.panel-count {
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

.appointment time,
.next-appointment time {
  color: var(--color-primary);
  font-weight: 700;
  white-space: nowrap;
}

.status {
  width: fit-content;
  padding: 5px 9px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 700;
}

.status-confirmed {
  background: #dbeafe;
  color: #1d4ed8;
}

.status-pending {
  background: #fef3c7;
  color: #b45309;
}

.next-appointment {
  margin-top: 24px;
}

.pending-panel {
  margin-top: 16px;
}

.error {
  color: var(--color-danger);
}

@media (max-width: 800px) {
  .client-page {
    padding: 20px;
  }

  .page-header,
  .content-grid {
    grid-template-columns: 1fr;
  }

  .page-header {
    align-items: flex-start;
    flex-direction: column;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }
}
</style>
