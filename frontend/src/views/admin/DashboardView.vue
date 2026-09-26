<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue'
import appointmentService from '@/services/appointmentService'
import professionalService from '@/services/professionalService'
import serviceService from '@/services/servicesService'
import userService from '@/services/userService'
import EmptyState from '@/components/common/EmptyState.vue'
import type { Appointment, Professional, Service } from '@/types'

const appointments = ref<Appointment[]>([])
const professionals = ref<Professional[]>([])
const services = ref<Service[]>([])
const patientCount = ref(0)
const isLoading = ref(true)
const error = ref('')

const confirmedAppointments = computed(() =>
  appointments.value.filter(appointment => appointment.status === 'confirmed').length
)

onMounted(async () => {
  try {
    const [
      appointmentsResponse,
      professionalsResponse,
      servicesResponse,
      patientsResponse
    ] = await Promise.all([
      appointmentService.getAdminAppointments(),
      professionalService.getAll(),
      serviceService.getAll(),
      userService.getAll('client')
    ])

    appointments.value = appointmentsResponse
    professionals.value = professionalsResponse.data
    services.value = servicesResponse
    patientCount.value = patientsResponse.total
  } catch (requestError) {
    console.error('No se pudo cargar el dashboard:', requestError)
    error.value = 'No se pudo cargar la información del dashboard.'
  } finally {
    isLoading.value = false
  }
})
</script>

<template>
  <section class="admin-page">
    <AdminPageHeader
      title="Dashboard"
      description="Resumen general de la actividad de tu negocio."
    />

    <p v-if="isLoading" class="state">Cargando información...</p>
    <p v-else-if="error" class="state error">{{ error }}</p>

    <template v-else>
      <div class="stats-grid">
        <article class="stat-card">
          <span>Turnos totales</span>
          <strong>{{ appointments.length }}</strong>
        </article>
        <article class="stat-card">
          <span>Turnos confirmados</span>
          <strong>{{ confirmedAppointments }}</strong>
        </article>
        <article class="stat-card">
          <span>Profesionales</span>
          <strong>{{ professionals.length }}</strong>
        </article>
        <article class="stat-card">
          <span>Servicios</span>
          <strong>{{ services.length }}</strong>
        </article>
        <article class="stat-card">
          <span>Pacientes</span>
          <strong>{{ patientCount }}</strong>
        </article>
      </div>

     <EmptyState
       title="Proximos Turnos"
       description="No hay turnos registrados">
     </EmptyState>
    </template>
  </section>
</template>

<style scoped>
.admin-page {
  padding: 32px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
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
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.stat-card span,
.appointment span {
  color: var(--text-muted);
  font-size: 13px;
}

.stat-card strong {
  color: var(--text-main);
  font-size: 28px;
}

h2 {
  margin: 0 0 16px;
  font-size: 18px;
}

.appointment-list {
  display: grid;
  gap: 10px;
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

.appointment div {
  display: grid;
  gap: 4px;
}

.status {
  color: var(--color-primary) !important;
  text-transform: capitalize;
}

.state {
  color: var(--text-muted);
}

.error {
  color: var(--color-danger);
}

@media (max-width: 900px) {
  .admin-page {
    padding: 20px;
  }

  .stats-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}
</style>
