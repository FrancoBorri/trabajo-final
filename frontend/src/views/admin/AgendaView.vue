<script setup lang="ts">
import { onMounted, ref } from 'vue'
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue'
import EmptyState from '@/components/common/EmptyState.vue'
import appointmentService from '@/services/appointmentService'
import type { Appointment } from '@/types'

const appointments = ref<Appointment[]>([])
const isLoading = ref(true)
const error = ref('')

onMounted(async () => {
  try {
    appointments.value = await appointmentService.getAdminAppointments()
  } catch (requestError) {
    console.error('No se pudo cargar la agenda:', requestError)
    error.value = 'No se pudo cargar la agenda general.'
  } finally {
    isLoading.value = false
  }
})
</script>

<template>
  <section class="admin-page">
    <AdminPageHeader
      title="Agenda general"
      description="Visualizá todos los turnos de todos los profesionales."
    />

    <p v-if="isLoading" class="state">
      Cargando turnos...
    </p>

    <p v-else-if="error" class="state error">
      {{ error }}
    </p>

    <div v-else class="table-wrapper">
      <table v-if="appointments.length > 0">
        <thead>
        <tr>
          <th>Fecha</th>
          <th>Hora</th>
          <th>Profesional</th>
          <th>Servicio</th>
          <th>Estado</th>
        </tr>
        </thead>

        <tbody>
        <tr
          v-for="appointment in appointments"
          :key="appointment.id"
        >
          <td>{{ appointment.date }}</td>

          <td>
            {{ appointment.time }}
          </td>

          <td>
            {{ appointment.professional.name }}
            {{ appointment.professional.lastName }}
          </td>

          <td>
            {{ appointment.service.title }}
          </td>

          <td class="status">
            {{ appointment.status }}
          </td>
        </tr>
        </tbody>
      </table>

      <EmptyState
        v-else
        title="No hay turnos"
        description="No hay turnos registrados."
      />
    </div>
  </section>
</template>

<style scoped>
.admin-page {
  padding: 32px;
}

.table-wrapper {
  overflow-x: auto;
  border: 1px solid var(--border-light);
  border-radius: 12px;
  background: #fff;
}

table {
  width: 100%;
  border-collapse: collapse;
  min-width: 720px;
}

th,
td {
  padding: 14px 16px;
  border-bottom: 1px solid var(--border-light);
  text-align: left;
  font-size: 14px;
}

th {
  color: var(--text-muted);
  font-size: 12px;
  text-transform: uppercase;
}

.status {
  color: var(--color-primary);
  text-transform: capitalize;
}

.state {
  color: var(--text-muted);
}

.error {
  color: var(--color-danger);
}
</style>
