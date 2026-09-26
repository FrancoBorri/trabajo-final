<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import appointmentService from '@/services/appointmentService'
import clinicalHistoryService from '@/services/clinicalHistoryService'
import clinicalSessionService from '@/services/clinicalSessionService'
import type { Appointment, ClinicalHistory, ClinicalSession } from '@/types'

interface Patient {
  id: number
  name: string
  lastName: string
  email: string
  phone?: string
  appointments: Appointment[]
}

const appointments = ref<Appointment[]>([])
const histories = ref<ClinicalHistory[]>([])
const sessions = ref<ClinicalSession[]>([])
const selectedPatientId = ref<number | null>(null)
const expandedSessionId = ref<string | null>(null)
const isLoading = ref(true)
const isLoadingSessions = ref(false)
const isSavingHistory = ref(false)
const isSavingSession = ref(false)
const error = ref('')
const historyError = ref('')
const sessionError = ref('')
const success = ref('')

const historyForm = reactive({
  chief_complaint: '',
  medical_history: '',
  initial_assessment: '',
  clinical_impression: '',
  therapeutic_goals: '',
  treatment_plans: '',
  notes: ''
})

const sessionForm = reactive({
  appointment_id: '',
  topic: '',
  evolution: '',
  observation: ''
})

const acceptedAppointments = computed(() =>
  appointments.value.filter(appointment =>
    appointment.status === 'confirmed' || appointment.status === 'completed'
  )
)

const patients = computed<Patient[]>(() => {
  const patientMap = new Map<number, Patient>()

  for (const appointment of acceptedAppointments.value) {
    const client = appointment.client ?? appointment.user
    if (!client) continue

    const id = Number(appointment.user_id || client.id)
    if (!Number.isFinite(id)) continue

    const patient = patientMap.get(id)
    if (patient) {
      patient.appointments.push(appointment)
    } else {
      patientMap.set(id, {
        id,
        name: client.name,
        lastName: client.lastName,
        email: client.email,
        phone: client.phone,
        appointments: [appointment]
      })
    }
  }

  return [...patientMap.values()].sort((first, second) =>
    `${first.lastName} ${first.name}`.localeCompare(`${second.lastName} ${second.name}`)
  )
})

const selectedPatient = computed(() =>
  patients.value.find(patient => patient.id === selectedPatientId.value) ?? null
)

const selectedHistory = computed(() =>
  selectedPatient.value
    ? histories.value.find(history => Number(history.user_id) === selectedPatient.value?.id) ?? null
    : null
)

const patientAppointments = computed(() =>
  selectedPatient.value
    ? [...selectedPatient.value.appointments].sort((first, second) =>
        `${second.date}T${second.time}`.localeCompare(`${first.date}T${first.time}`)
      )
    : []
)

const orderedSessions = computed(() =>
  [...sessions.value].sort((first, second) => {
    const firstAppointment = patientAppointments.value.find(
      appointment => String(appointment.id) === String(first.appointment_id)
    )
    const secondAppointment = patientAppointments.value.find(
      appointment => String(appointment.id) === String(second.appointment_id)
    )
    const firstDate = firstAppointment
      ? `${firstAppointment.date}T${firstAppointment.time}`
      : first.created_at
    const secondDate = secondAppointment
      ? `${secondAppointment.date}T${secondAppointment.time}`
      : second.created_at

    return secondDate.localeCompare(firstDate)
  })
)

const sessionAppointment = (session: ClinicalSession) =>
  patientAppointments.value.find(
    appointment => String(appointment.id) === String(session.appointment_id)
  )

const formatDate = (date: string) =>
  new Intl.DateTimeFormat('es-AR', {
    day: '2-digit',
    month: 'long',
    year: 'numeric'
  }).format(new Date(`${date.slice(0, 10)}T00:00:00`))

const toggleSessionDetails = (sessionId: string) => {
  expandedSessionId.value =
    expandedSessionId.value === sessionId ? null : sessionId
}

const formatPatientName = (patient: Patient) =>
  `${patient.name} ${patient.lastName}`.trim()

const resetHistoryForm = () => {
  historyForm.chief_complaint = ''
  historyForm.medical_history = ''
  historyForm.initial_assessment = ''
  historyForm.clinical_impression = ''
  historyForm.therapeutic_goals = ''
  historyForm.treatment_plans = ''
  historyForm.notes = ''
}

const populateHistoryForm = (history: ClinicalHistory | null) => {
  if (!history) {
    resetHistoryForm()
    return
  }

  historyForm.chief_complaint = history.chief_complaint ?? ''
  historyForm.medical_history = history.medical_history ?? ''
  historyForm.initial_assessment = history.initial_assessment ?? ''
  historyForm.clinical_impression = history.clinical_impression ?? ''
  historyForm.therapeutic_goals = history.therapeutic_goals ?? ''
  historyForm.treatment_plans = history.treatment_plans ?? ''
  historyForm.notes = history.notes ?? ''
}

const loadSessions = async (history: ClinicalHistory | null) => {
  sessions.value = []
  sessionError.value = ''

  if (!history) return

  isLoadingSessions.value = true
  try {
    const loadedSessions = await clinicalSessionService.getByHistory(Number(history.id))
    if (selectedHistory.value?.id === history.id) {
      sessions.value = loadedSessions
      expandedSessionId.value = orderedSessions.value[0]?.id ?? null
    }
  } catch (requestError) {
    console.error('No se pudieron cargar las sesiones clínicas:', requestError)
    sessionError.value = 'No se pudieron cargar las sesiones de esta historia clínica.'
  } finally {
    isLoadingSessions.value = false
  }
}

const selectPatient = (patient: Patient) => {
  selectedPatientId.value = patient.id
  success.value = ''
  historyError.value = ''
  populateHistoryForm(
    histories.value.find(history => Number(history.user_id) === patient.id) ?? null
  )
}

const saveHistory = async () => {
  const patient = selectedPatient.value
  if (!patient) return

  historyError.value = ''
  success.value = ''
  isSavingHistory.value = true

  try {
    const data = {
      user_id: patient.id,
      chief_complaint: historyForm.chief_complaint.trim(),
      medical_history: historyForm.medical_history.trim(),
      initial_assessment: historyForm.initial_assessment.trim(),
      clinical_impression: historyForm.clinical_impression.trim(),
      therapeutic_goals: historyForm.therapeutic_goals.trim(),
      treatment_plans: historyForm.treatment_plans.trim(),
      notes: historyForm.notes.trim()
    }
    const savedHistory = selectedHistory.value
      ? await clinicalHistoryService.update(Number(selectedHistory.value.id), data)
      : await clinicalHistoryService.create(data)

    const index = histories.value.findIndex(history =>
      Number(history.user_id) === patient.id
    )
    if (index === -1) {
      histories.value.push(savedHistory)
    } else {
      histories.value[index] = savedHistory
    }

    populateHistoryForm(savedHistory)
    success.value = 'La historia clínica se guardó correctamente.'
  } catch (requestError) {
    console.error('No se pudo guardar la historia clínica:', requestError)
    historyError.value = 'No se pudo guardar la historia clínica. Intentá nuevamente.'
  } finally {
    isSavingHistory.value = false
  }
}

const saveSession = async () => {
  const history = selectedHistory.value
  if (!history) return

  if (!sessionForm.appointment_id || !sessionForm.topic.trim()) {
    sessionError.value = 'Seleccioná un turno e ingresá el tema de la sesión.'
    return
  }

  sessionError.value = ''
  success.value = ''
  isSavingSession.value = true

  try {
    const createdSession = await clinicalSessionService.create({
      clinical_history_id: String(history.id),
      appointment_id: sessionForm.appointment_id,
      session_number: String(sessions.value.length + 1),
      topic: sessionForm.topic.trim(),
      evolution: sessionForm.evolution.trim(),
      observation: sessionForm.observation.trim()
    })
    sessions.value.push(createdSession)
    expandedSessionId.value = createdSession.id
    sessionForm.topic = ''
    sessionForm.evolution = ''
    sessionForm.observation = ''
    success.value = 'La sesión se agregó correctamente.'
  } catch (requestError) {
    console.error('No se pudo guardar la sesión clínica:', requestError)
    sessionError.value = 'No se pudo guardar la sesión. Intentá nuevamente.'
  } finally {
    isSavingSession.value = false
  }
}

watch(selectedHistory, history => {
  populateHistoryForm(history)
  void loadSessions(history)
})

onMounted(async () => {
  try {
    const [loadedAppointments, loadedHistories] = await Promise.all([
      appointmentService.getProfessionalAppointments(),
      clinicalHistoryService.getAll()
    ])

    appointments.value = loadedAppointments
    histories.value = loadedHistories
  } catch (requestError) {
    console.error('No se pudieron cargar los pacientes:', requestError)
    error.value = 'No se pudieron cargar tus pacientes. Intentá nuevamente.'
  } finally {
    isLoading.value = false
  }
})
</script>

<template>
  <section class="professional-page">
    <header class="page-header">
      <div>
        <h1>Pacientes</h1>
        <p>Consultá tus pacientes y mantené sus historias clínicas y sesiones.</p>
      </div>
      <span class="patient-count">{{ patients.length }} pacientes</span>
    </header>

    <p v-if="isLoading" class="state">Cargando pacientes...</p>
    <p v-else-if="error" class="message error">{{ error }}</p>
    <div v-else class="patients-layout">
      <aside class="panel patient-panel">
        <h2>Mis pacientes</h2>
        <p class="description">Pacientes con turnos en tu agenda.</p>

        <div v-if="patients.length" class="patient-list">
          <button
            v-for="patient in patients"
            :key="patient.id"
            type="button"
            class="patient-option"
            :class="{ selected: selectedPatientId === patient.id }"
            @click="selectPatient(patient)"
          >
            <span class="patient-avatar">
              {{ patient.name.slice(0, 1) }}{{ patient.lastName.slice(0, 1) }}
            </span>
            <span class="patient-summary">
              <strong>{{ formatPatientName(patient) }}</strong>
              <small>{{ patient.appointments.length }} turnos</small>
            </span>
          </button>
        </div>
        <p v-else class="empty">Todavía no tenés pacientes asociados a tus turnos.</p>
      </aside>

      <div v-if="selectedPatient" class="patient-details">
        <article class="panel patient-header">
          <div>
            <h2>{{ formatPatientName(selectedPatient) }}</h2>
            <p>{{ selectedPatient.email }}<span v-if="selectedPatient.phone"> · {{ selectedPatient.phone }}</span></p>
          </div>
          <span class="appointment-count">{{ patientAppointments.length }} turnos</span>
        </article>

        <article class="panel">
          <div class="panel-heading">
            <div>
              <h2>Historia clínica</h2>
              <p class="description">Información clínica y plan de tratamiento del paciente.</p>
            </div>
            <span v-if="selectedHistory" class="saved-label">Registrada</span>
          </div>

          <form class="clinical-form" @submit.prevent="saveHistory">
            <label>
              Motivo de consulta
              <textarea v-model="historyForm.chief_complaint" rows="2" />
            </label>
            <label>
              Antecedentes médicos
              <textarea v-model="historyForm.medical_history" rows="3" />
            </label>
            <label>
              Evaluación inicial
              <textarea v-model="historyForm.initial_assessment" rows="3" />
            </label>
            <label>
              Impresión clínica
              <textarea v-model="historyForm.clinical_impression" rows="3" />
            </label>
            <label>
              Objetivos terapéuticos
              <textarea v-model="historyForm.therapeutic_goals" rows="3" />
            </label>
            <label>
              Plan de tratamiento
              <textarea v-model="historyForm.treatment_plans" rows="3" />
            </label>
            <label>
              Notas
              <textarea v-model="historyForm.notes" rows="3" />
            </label>

            <p v-if="historyError" class="message error">{{ historyError }}</p>
            <button class="primary-button" type="submit" :disabled="isSavingHistory">
              {{ isSavingHistory ? 'Guardando...' : selectedHistory ? 'Guardar cambios' : 'Crear historia clínica' }}
            </button>
          </form>
        </article>

        <article class="panel sessions-panel">
          <div class="panel-heading">
            <div>
              <h2>Sesiones clínicas</h2>
              <p class="description">Registrá la evolución de cada atención.</p>
            </div>
            <span class="appointment-count">{{ sessions.length }}</span>
          </div>

          <p v-if="!selectedHistory" class="empty">
            Primero guardá la historia clínica para poder agregar sesiones.
          </p>
          <form v-else class="clinical-form session-form" @submit.prevent="saveSession">
            <label>
              Turno asociado
              <select v-model="sessionForm.appointment_id" required>
                <option value="" disabled>Seleccioná un turno</option>
                <option
                  v-for="appointment in patientAppointments"
                  :key="appointment.id"
                  :value="String(appointment.id)"
                >
                  {{ formatDate(appointment.date) }} · {{ appointment.time }}
                  <template v-if="appointment.service?.title"> · {{ appointment.service.title }}</template>
                </option>
              </select>
            </label>
            <label>
              Tema de la sesión
              <input v-model="sessionForm.topic" type="text" required>
            </label>
            <label>
              Evolución
              <textarea v-model="sessionForm.evolution" rows="3" />
            </label>
            <label>
              Observaciones
              <textarea v-model="sessionForm.observation" rows="3" />
            </label>

            <p v-if="sessionError" class="message error">{{ sessionError }}</p>
            <button class="primary-button" type="submit" :disabled="isSavingSession">
              {{ isSavingSession ? 'Guardando...' : 'Agregar sesión' }}
            </button>
          </form>

          <p v-if="isLoadingSessions" class="state">Cargando sesiones...</p>
          <div v-else-if="orderedSessions.length" class="session-list">
            <article
              v-for="session in orderedSessions"
              :key="session.id"
              class="session-item"
              :class="{ expanded: expandedSessionId === session.id }"
            >
              <button
                class="session-toggle"
                type="button"
                :aria-expanded="expandedSessionId === session.id"
                @click="toggleSessionDetails(session.id)"
              >
                <span class="session-summary">
                  <strong>Sesión {{ session.session_number }} · {{ session.topic }}</strong>
                  <small>
                    <template v-if="sessionAppointment(session)">
                      {{ formatDate(sessionAppointment(session)!.date) }} ·
                      {{ sessionAppointment(session)!.time }}
                    </template>
                    <template v-else>
                      Registrada el {{ formatDate(session.created_at) }}
                    </template>
                    <template v-if="sessionAppointment(session)?.service?.title">
                      · {{ sessionAppointment(session)?.service?.title }}
                    </template>
                  </small>
                </span>
                <span class="toggle-label">
                  {{ expandedSessionId === session.id ? 'Ocultar' : 'Ver detalles' }}
                </span>
              </button>

              <div v-if="expandedSessionId === session.id" class="session-details">
                <p><strong>Tema:</strong> {{ session.topic || 'Sin tema registrado.' }}</p>
                <p>
                  <strong>Evolución:</strong>
                  {{ session.evolution || 'Sin evolución registrada.' }}
                </p>
                <p>
                  <strong>Observaciones:</strong>
                  {{ session.observation || 'Sin observaciones registradas.' }}
                </p>
              </div>
            </article>
          </div>
          <p v-else-if="selectedHistory && !sessionError" class="empty">Todavía no hay sesiones registradas.</p>
          <p v-if="sessionError" class="message error">{{ sessionError }}</p>
        </article>

        <article class="panel appointments-panel">
          <h2>Turnos del paciente</h2>
          <div class="appointment-list">
            <div v-for="appointment in patientAppointments" :key="appointment.id" class="appointment-item">
              <div>
                <strong>{{ formatDate(appointment.date) }} · {{ appointment.time }}</strong>
                <span>{{ appointment.service?.title ?? 'Servicio' }}</span>
              </div>
              <span class="status" :class="`status-${appointment.status}`">{{ appointment.status }}</span>
            </div>
          </div>
        </article>

        <p v-if="success" class="message success">{{ success }}</p>
      </div>
      <p v-else-if="patients.length" class="panel empty select-patient">
        Seleccioná un paciente para consultar o completar su historia clínica.
      </p>
    </div>
  </section>
</template>

<style scoped>
.professional-page { padding: 32px; }
.page-header, .panel-heading, .patient-header, .session-heading, .appointment-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
}
.page-header { margin-bottom: 24px; }
h1, h2, p { margin-top: 0; }
h1 { margin-bottom: 8px; }
h2 { margin-bottom: 6px; font-size: 18px; }
.page-header p, .description, .patient-header p, .state, .empty, .session-item p, .appointment-item span, .session-heading time { color: var(--text-muted); }
.patient-count, .appointment-count, .saved-label {
  padding: 7px 11px;
  border-radius: 999px;
  background: var(--color-primary-soft);
  color: var(--color-primary);
  font-size: 12px;
  font-weight: 700;
  white-space: nowrap;
}
.patients-layout { display: grid; grid-template-columns: minmax(220px, 280px) minmax(0, 1fr); align-items: start; gap: 16px; }
.patient-details { display: grid; gap: 16px; min-width: 0; }
.panel { padding: 22px; border: 1px solid var(--border-light); border-radius: 12px; background: #fff; }
.patient-panel { position: sticky; top: 20px; }
.patient-panel > .description { margin-bottom: 18px; font-size: 13px; }
.patient-list { display: grid; gap: 6px; }
.patient-option {
  display: flex;
  align-items: center;
  gap: 11px;
  width: 100%;
  padding: 10px;
  border: 0;
  border-radius: 8px;
  background: transparent;
  color: var(--text-main);
  cursor: pointer;
  font: inherit;
  text-align: left;
}
.patient-option:hover, .patient-option.selected { background: var(--color-primary-soft); }
.patient-avatar { display: grid; width: 36px; height: 36px; flex: 0 0 auto; place-items: center; border-radius: 50%; background: #dbeafe; color: var(--color-primary); font-size: 12px; font-weight: 700; }
.patient-summary { display: grid; min-width: 0; gap: 4px; }
.patient-summary strong { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 13px; }
.patient-summary small { color: var(--text-muted); font-size: 11px; }
.patient-header { align-items: flex-start; }
.patient-header h2 { margin-bottom: 5px; }
.patient-header p { margin-bottom: 0; font-size: 13px; }
.clinical-form { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; margin-top: 20px; }
label { display: grid; align-content: start; gap: 7px; color: var(--text-main); font-size: 13px; font-weight: 600; }
input, textarea, select { width: 100%; padding: 10px 12px; border: 1px solid var(--border-light); border-radius: 8px; background: #fff; color: var(--text-main); font: inherit; font-weight: 400; }
textarea { resize: vertical; }
.primary-button { justify-self: start; padding: 10px 14px; border: 0; border-radius: 8px; background: var(--color-primary); color: white; cursor: pointer; font: inherit; font-weight: 700; }
.primary-button:disabled { cursor: not-allowed; opacity: .6; }
.clinical-form > .message, .clinical-form > .primary-button { grid-column: 1 / -1; }
.session-form { grid-template-columns: repeat(2, minmax(0, 1fr)); }
.session-list, .appointment-list { display: grid; gap: 10px; margin-top: 18px; }
.session-item, .appointment-item { padding: 14px; border-radius: 8px; background: var(--bg-main); }
.session-item.expanded { background: #f1f5f9; }
.session-toggle { display: flex; align-items: center; justify-content: space-between; gap: 14px; width: 100%; padding: 0; border: 0; background: transparent; color: var(--text-main); cursor: pointer; font: inherit; text-align: left; }
.session-summary { display: grid; min-width: 0; gap: 6px; }
.session-summary strong { overflow-wrap: anywhere; }
.session-summary small { color: var(--text-muted); font-size: 12px; }
.toggle-label { flex: 0 0 auto; color: var(--color-primary); font-size: 12px; font-weight: 700; }
.session-details { display: grid; gap: 8px; margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--border-light); }
.session-details p { margin: 0; color: var(--text-main); font-size: 13px; line-height: 1.6; white-space: pre-line; overflow-wrap: anywhere; }
.appointment-item { align-items: center; }
.appointment-item > div { display: grid; gap: 5px; }
.appointment-item > div span { font-size: 12px; }
.status { padding: 5px 9px; border-radius: 999px; background: #e2e8f0; color: #475569 !important; font-size: 11px; font-weight: 700; text-transform: capitalize; }
.status-completed { background: #dcfce7; color: #15803d !important; }
.status-confirmed { background: #dbeafe; color: #1d4ed8 !important; }
.status-cancelled { background: #fee2e2; color: #b91c1c !important; }
.select-patient { min-height: 140px; display: grid; place-items: center; color: var(--text-muted); text-align: center; }
.message { margin: 10px 0 0; font-size: 13px; font-weight: 600; }
.success { color: #15803d; }
.error { color: var(--color-danger); }
.empty { font-size: 13px; }
@media (max-width: 900px) {
  .professional-page { padding: 20px; }
  .patients-layout { grid-template-columns: 1fr; }
  .patient-panel { position: static; }
  .patient-list { grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); }
}
@media (max-width: 600px) {
  .page-header, .patient-header, .session-heading { align-items: flex-start; flex-direction: column; }
  .clinical-form, .session-form { grid-template-columns: 1fr; }
  .patient-count { display: none; }
}
</style>
