<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue'
import EmptyState from '@/components/common/EmptyState.vue'
import appointmentService from '@/services/appointmentService'
import type { Appointment, AppointmentStatus } from '@/types'

type FilterStatus = 'all' | AppointmentStatus

const appointments = ref<Appointment[]>([])
const isLoading = ref(true)
const error = ref('')
const search = ref('')
const statusFilter = ref<FilterStatus>('all')

const filteredAppointments = computed(() => {
  const query = search.value.trim().toLowerCase()

  return appointments.value.filter((appointment) => {
    const matchesStatus =
      statusFilter.value === 'all' || appointment.status === statusFilter.value

    const searchableText = [
      appointment.id,
      appointment.date,
      appointment.time,
      appointment.user_id,
      appointment.professional?.name,
      appointment.professional?.lastName,
      appointment.service?.title,
      appointment.notes ?? ''
    ]
      .join(' ')
      .toLowerCase()

    return matchesStatus && (!query || searchableText.includes(query))
  })
})

const getClientName = (appointment: Appointment) => {
  const client = appointment.client ?? appointment.user

  if (client) {
    return `${client.name} ${client.lastName}`.trim()
  }

  return `Cliente #${appointment.user_id}`
}

const getProfessionalName = (appointment: Appointment) => {
  const professional = appointment.professional
  const user = professional?.user
  const name = user?.name ?? professional?.name
  const lastName = user?.lastName ?? user?.last_name ?? professional?.lastName
  const fullName = `${name ?? ''} ${lastName ?? ''}`.trim()

  return fullName && fullName !== professional?.specialty
    ? fullName
    : 'Profesional no informado'
}

const formatDate = (date: string) => {
  const parsedDate = new Date(`${date}T00:00:00`)

  if (Number.isNaN(parsedDate.getTime())) {
    return date
  }

  return new Intl.DateTimeFormat('es-AR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  }).format(parsedDate)
}

const statusLabel = (status: AppointmentStatus) => {
  const labels: Record<AppointmentStatus, string> = {
    pending: 'Pendiente de aceptación',
    confirmed: 'Confirmado',
    completed: 'Completado',
    cancelled: 'Cancelado'
  }

  return labels[status]
}

const loadAppointments = async () => {
  isLoading.value = true
  error.value = ''

  try {
    appointments.value = await appointmentService.getAdminAppointments()
  } catch (requestError) {
    console.error('No se pudieron cargar los turnos:', requestError)
    error.value = 'No se pudieron cargar los turnos.'
  } finally {
    isLoading.value = false
  }
}

onMounted(loadAppointments)
</script>

<template>
  <section class="admin-page">
    <AdminPageHeader
      title="Gestión de turnos"
      description="Consultá y administrá todos los turnos registrados."
    >
      <template #actions>
        <button
          class="refresh-button"
          type="button"
          :disabled="isLoading"
          @click="loadAppointments"
        >
          Actualizar
        </button>
      </template>
    </AdminPageHeader>

    <div class="filters">
      <label class="search-field">
        <span>Buscar</span>
        <input
          v-model="search"
          type="search"
          placeholder="ID, cliente, profesional o servicio"
        >
      </label>

      <label class="status-filter">
        <span>Estado</span>
        <select v-model="statusFilter">
          <option value="all">Todos</option>
          <option value="pending">Pendientes de aceptación</option>
          <option value="confirmed">Confirmados</option>
          <option value="completed">Completados</option>
          <option value="cancelled">Cancelados</option>
        </select>
      </label>
    </div>

    <p v-if="isLoading" class="state">Cargando turnos...</p>
    <p v-else-if="error" class="state error">{{ error }}</p>

    <div v-else class="table-wrapper">
      <table v-if="filteredAppointments.length > 0">
        <thead>
          <tr>
            <th>ID</th>
            <th>Fecha y hora</th>
            <th>Cliente</th>
            <th>Profesional</th>
            <th>Servicio</th>
            <th>Estado</th>
            <th>Notas</th>
          </tr>
        </thead>

        <tbody>
          <tr v-for="appointment in filteredAppointments" :key="appointment.id">
            <td>#{{ appointment.id }}</td>
            <td>
              <strong>{{ formatDate(appointment.date) }}</strong>
              <span class="secondary-text">{{ appointment.time }}</span>
            </td>
            <td>
              <strong>{{ getClientName(appointment) }}</strong>
              <span class="secondary-text">ID: {{ appointment.user_id }}</span>
            </td>
            <td>
              <strong>{{ getProfessionalName(appointment) }}</strong>
              <span class="secondary-text">{{ appointment.professional.specialty }}</span>
            </td>
            <td>
              <strong>{{ appointment.service.title }}</strong>
              <span class="secondary-text">{{ appointment.service.duration }} min</span>
            </td>
            <td>
              <span class="status" :class="`status-${appointment.status}`">
                {{ statusLabel(appointment.status) }}
              </span>
            </td>
            <td class="notes">{{ appointment.notes || 'Sin notas' }}</td>
          </tr>
        </tbody>
      </table>

      <EmptyState
        v-else
        title="No hay turnos"
        description="No hay turnos que coincidan con los filtros seleccionados."
      />
    </div>
  </section>
</template>

<style scoped>
.admin-page {
  padding: 32px;
}

.refresh-button {
  border: 0;
  border-radius: 6px;
  padding: 9px 12px;
  font: inherit;
  font-size: 13px;
  cursor: pointer;
}

.refresh-button {
  background: var(--color-primary);
  color: #fff;
}

.refresh-button:disabled {
  cursor: wait;
  opacity: 0.6;
}

.filters {
  display: flex;
  gap: 16px;
  margin-bottom: 20px;
}

.search-field,
.status-filter {
  display: grid;
  gap: 6px;
  color: var(--text-muted);
  font-size: 12px;
  font-weight: 600;
}

.search-field {
  flex: 1;
}

input,
select {
  min-height: 38px;
  padding: 0 10px;
  border: 1px solid var(--border-light);
  border-radius: 6px;
  background: #fff;
  color: var(--text-main);
  font: inherit;
}

.table-wrapper {
  overflow-x: auto;
  border: 1px solid var(--border-light);
  border-radius: 12px;
  background: #fff;
}

table {
  width: 100%;
  min-width: 1120px;
  border-collapse: collapse;
}

th,
td {
  padding: 14px 16px;
  border-bottom: 1px solid var(--border-light);
  text-align: left;
  vertical-align: top;
  font-size: 14px;
}

th {
  color: var(--text-muted);
  font-size: 12px;
  text-transform: uppercase;
  white-space: nowrap;
}

tbody tr:last-child td {
  border-bottom: 0;
}

td strong,
td > span,
td > .secondary-text {
  display: block;
}

.secondary-text {
  margin-top: 4px;
  color: var(--text-muted);
  font-size: 12px;
}

.status {
  display: inline-block;
  border-radius: 999px;
  padding: 4px 8px;
  font-size: 12px;
  font-weight: 600;
  white-space: nowrap;
}

.status-confirmed {
  background: var(--color-primary-bg, #e8f5ee);
  color: var(--color-primary);
}

.status-pending {
  background: #fff4d6;
  color: #916a00;
}

.status-completed {
  background: #e8f5ee;
  color: #18794e;
}

.status-cancelled {
  background: #fdecec;
  color: var(--color-danger);
}

.notes {
  max-width: 220px;
  color: var(--text-muted);
  white-space: normal;
}

.state {
  color: var(--text-muted);
}

.error {
  color: var(--color-danger);
}

@media (max-width: 700px) {
  .admin-page {
    padding: 20px;
  }

  .filters {
    flex-direction: column;
  }
}
</style>
