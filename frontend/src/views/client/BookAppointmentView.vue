<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import BookingCalendar from '@/components/booking/BookingCalendar.vue'
import BookingTimesSlots from '@/components/booking/BookingTimesSlots.vue'
import appointmentService from '@/services/appointmentService'
import availabilityService from '@/services/availabilityService'
import professionalService from '@/services/professionalService'
import serviceService from '@/services/servicesService'
import type { Availability, Professional, Service } from '@/types'

const authStore = useAuthStore()
const professionals = ref<Professional[]>([])
const services = ref<Service[]>([])
const availabilities = ref<Availability[]>([])
const selectedProfessionalId = ref('')
const selectedServiceId = ref('')
const selectedDate = ref('')
const selectedTime = ref('')
const availableSlots = ref<string[]>([])
const isLoading = ref(true)
const isLoadingSlots = ref(false)
const isSaving = ref(false)
const error = ref('')
const slotsError = ref('')
const confirmationMessage = ref('')

const today = new Date().toISOString().slice(0, 10)
const daysOfWeek = [
  { value: 1, label: 'Lunes' },
  { value: 2, label: 'Martes' },
  { value: 3, label: 'Miércoles' },
  { value: 4, label: 'Jueves' },
  { value: 5, label: 'Viernes' },
  { value: 6, label: 'Sábado' },
  { value: 7, label: 'Domingo' }
]

const selectedProfessional = computed(() =>
  professionals.value.find(
    professional => String(professional.id) === selectedProfessionalId.value
  )
)

const servicesFromProfessional = (professional: Professional): Service[] => {
  if (Array.isArray(professional.services)) {
    const serviceObjects = professional.services.filter(
      (service): service is Service =>
        typeof service === 'object' && service !== null
    )

    const serviceIds = professional.services
      .filter((service): service is string | number => typeof service !== 'object')
      .map(service => String(service))

    return [
      ...serviceObjects,
      ...services.value.filter(service => serviceIds.includes(String(service.id)))
    ].filter((service, index, items) =>
      items.findIndex(item => String(item.id) === String(service.id)) === index
    )
  }

  return services.value.filter(service =>
    service.professional_id
      ? String(service.professional_id) === String(professional.id)
      : false
  )
}

const professionalServices = computed(() => {
  const professional = selectedProfessional.value

  if (!professional) {
    return []
  }

  return servicesFromProfessional(professional)
})

const selectedProfessionalAvailabilities = computed(() =>
  availabilities.value
    .filter(availability =>
      String(availability.professional_id) === selectedProfessionalId.value
    )
    .sort((first, second) =>
      first.day_week - second.day_week ||
      first.time_start.localeCompare(second.time_start)
    )
)

const availableDays = computed(() =>
  selectedProfessionalAvailabilities.value
    .map(availability => daysOfWeek.find(day => day.value === availability.day_week))
    .filter((day): day is (typeof daysOfWeek)[number] => Boolean(day))
    .filter((day, index, days) =>
      days.findIndex(item => item.value === day.value) === index
    )
)

const selectedService = computed(() =>
  professionalServices.value.find(
    service => String(service.id) === selectedServiceId.value
  )
)

const canSearchSlots = computed(() =>
  Boolean(
    selectedProfessionalId.value &&
    selectedServiceId.value &&
    selectedDate.value
  )
)

const professionalName = computed(() => {
  const professional = selectedProfessional.value

  return professional
    ? `${professional.name} ${professional.lastName}`.trim()
    : ''
})

const formatDate = (date: string) =>
  new Intl.DateTimeFormat('es-AR', {
    weekday: 'long',
    day: '2-digit',
    month: 'long',
    year: 'numeric'
  }).format(new Date(`${date}T00:00:00`))

const formatPrice = (price: number) =>
  new Intl.NumberFormat('es-AR', {
    style: 'currency',
    currency: 'ARS',
    maximumFractionDigits: 2
  }).format(price)

const isAvailableDate = (date: string) =>
  selectedProfessionalAvailabilities.value.some(
    availability => {
      const day = new Date(`${date}T00:00:00`).getDay()
      const dayOfWeek = day === 0 ? 7 : day

      return availability.day_week === dayOfWeek
    }
  )

const validateDate = () => {
  selectedDate.value = ''
  availableSlots.value = []
  selectedTime.value = ''
  slotsError.value = 'El profesional no atiende ese día. Elegí uno de los días disponibles.'
}

const loadOptions = async () => {
  isLoading.value = true
  error.value = ''

  try {
    const [professionalsResponse, servicesResponse, availabilitiesResponse] = await Promise.all([
      professionalService.getAll(),
      serviceService.getAll(),
      availabilityService.getAll()
    ])

    professionals.value = professionalsResponse.data
    services.value = servicesResponse
    availabilities.value = availabilitiesResponse
  } catch (requestError) {
    console.error('No se pudieron cargar las opciones de reserva:', requestError)
    error.value = 'No se pudieron cargar los profesionales y servicios.'
  } finally {
    isLoading.value = false
  }
}

const loadSlots = async () => {
  if (!canSearchSlots.value) {
    availableSlots.value = []
    selectedTime.value = ''
    return
  }

  if (!isAvailableDate(selectedDate.value)) {
    availableSlots.value = []
    slotsError.value = 'El profesional no atiende ese día. Elegí uno de los días disponibles.'
    return
  }

  isLoadingSlots.value = true
  slotsError.value = ''
  selectedTime.value = ''

  try {
    availableSlots.value = await availabilityService.availableSlots(
      selectedProfessionalId.value,
      selectedServiceId.value,
      selectedDate.value
    )
  } catch (requestError) {
    console.error('No se pudieron cargar los horarios disponibles:', requestError)
    availableSlots.value = []
    slotsError.value = 'No se pudieron cargar los horarios para esa fecha.'
  } finally {
    isLoadingSlots.value = false
  }
}

const submitAppointment = async () => {
  const userId = authStore.user?.id

  if (
    !userId ||
    !selectedProfessional.value ||
    !selectedService.value ||
    !selectedTime.value
  ) {
    error.value = 'No se pudo identificar al usuario autenticado.'
    return
  }

  isSaving.value = true
  error.value = ''

  try {
    await appointmentService.create({
      user_id: Number(userId),
      professional_id: Number(selectedProfessional.value.id),
      service_id: Number(selectedService.value.id),
      date: selectedDate.value,
      time: selectedTime.value
    })

    confirmationMessage.value = 'Tu turno fue reservado correctamente.'
    availableSlots.value = availableSlots.value.filter(
      slot => slot !== selectedTime.value
    )
  } catch (requestError) {
    console.error('No se pudo reservar el turno:', requestError)
    error.value = 'No se pudo confirmar el turno. Intentá nuevamente.'
  } finally {
    isSaving.value = false
  }
}

watch(selectedProfessionalId, () => {
  selectedServiceId.value = ''
  selectedDate.value = ''
  selectedTime.value = ''
  availableSlots.value = []
  slotsError.value = ''
  confirmationMessage.value = ''
})

watch(selectedServiceId, () => {
  selectedDate.value = ''
  selectedTime.value = ''
  availableSlots.value = []
  slotsError.value = ''
  confirmationMessage.value = ''
})

watch(selectedDate, loadSlots)

onMounted(loadOptions)
</script>

<template>
  <section class="client-page">
    <header class="page-header">
      <div>
        <h1>Reservar turno</h1>
        <p>Elegí un profesional, un servicio, un día y un horario.</p>
      </div>
    </header>

    <p v-if="isLoading" class="state">Cargando profesionales y servicios...</p>
    <p v-else-if="error && !selectedProfessional" class="state error">{{ error }}</p>

    <template v-else>
      <div class="booking-grid">
        <article class="panel">
          <div class="step-heading">
            <span class="step">1</span>
            <div>
              <h2>Elegí la atención</h2>
              <p>Seleccioná quién te atenderá y el servicio.</p>
            </div>
          </div>

          <label>
            Profesional
            <select v-model="selectedProfessionalId">
              <option value="" disabled>Seleccioná un profesional</option>
              <option
                v-for="professional in professionals"
                :key="professional.id"
                :value="professional.id"
              >
                {{ professional.name }} {{ professional.lastName }}
                <template v-if="professional.specialty">
                  · {{ professional.specialty }}
                </template>
              </option>
            </select>
          </label>

          <fieldset class="service-picker" :disabled="!selectedProfessionalId">
            <legend>Servicio</legend>
            <p v-if="!selectedProfessionalId" class="service-hint">
              Primero seleccioná un profesional para ver sus servicios.
            </p>
            <div v-else-if="professionalServices.length" class="service-options">
              <button
                v-for="service in professionalServices"
                :key="service.id"
                class="service-option"
                :class="{ selected: selectedServiceId === String(service.id) }"
                type="button"
                :aria-pressed="selectedServiceId === String(service.id)"
                @click="selectedServiceId = String(service.id)"
              >
                <span class="service-option-top">
                  <span class="service-title">{{ service.title }}</span>
                  <span class="service-price">{{ formatPrice(service.price) }}</span>
                </span>
                <span v-if="service.description" class="service-description">
                  {{ service.description }}
                </span>
                <span class="service-duration">
                  {{ service.duration }} minutos
                </span>
              </button>
            </div>
          </fieldset>

          <p
            v-if="selectedProfessionalId && !professionalServices.length"
            class="state"
          >
            Este profesional no tiene servicios disponibles.
          </p>
        </article>

        <article class="panel">
          <div class="step-heading">
            <span class="step">2</span>
            <div>
              <h2>Elegí día y horario</h2>
              <p>Los horarios se consultan según tu selección.</p>
            </div>
          </div>

          <BookingCalendar
            :model-value="selectedDate"
            :min="today"
            :disabled="!selectedServiceId"
            :available-days="availableDays"
            @update:model-value="selectedDate = $event"
            @invalid="validateDate"
          />

          <BookingTimesSlots
            :model-value="selectedTime"
            :slots="availableSlots"
            :is-loading="isLoadingSlots"
            :error="slotsError"
            :show-empty-state="Boolean(selectedDate && !availableSlots.length)"
            @update:model-value="selectedTime = $event; confirmationMessage = ''"
          />
        </article>
      </div>

      <article class="panel confirmation-panel">
        <div class="step-heading">
          <span class="step">3</span>
          <div>
            <h2>Confirmación del turno</h2>
            <p>Revisá los datos antes de confirmar.</p>
          </div>
        </div>

        <div v-if="selectedProfessional && selectedService && selectedTime" class="summary">
          <div>
            <span>Profesional</span>
            <strong>{{ professionalName }}</strong>
          </div>
          <div>
            <span>Servicio</span>
            <strong>{{ selectedService.title }}</strong>
          </div>
          <div>
            <span>Fecha y hora</span>
            <strong>{{ formatDate(selectedDate) }} · {{ selectedTime }}</strong>
          </div>
          <div>
            <span>Duración</span>
            <strong>{{ selectedService.duration }} minutos</strong>
          </div>
          <div>
            <span>Precio</span>
            <strong class="price">{{ formatPrice(selectedService.price) }}</strong>
          </div>
        </div>
        <p v-else class="state">Completá los pasos anteriores para ver el resumen.</p>

        <p v-if="confirmationMessage" class="success">{{ confirmationMessage }}</p>
        <p v-if="error && selectedProfessional" class="state error">{{ error }}</p>

        <button
          class="btn-primary confirm-button"
          type="button"
          :disabled="!selectedTime || isSaving"
          @click="submitAppointment"
        >
          {{ isSaving ? 'Confirmando...' : 'Confirmar turno' }}
        </button>
      </article>
    </template>
  </section>
</template>

<style scoped>
.client-page {
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
.step-heading p,
.state,
label span {
  color: var(--text-muted);
}

.booking-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.panel {
  padding: 24px;
  border: 1px solid var(--border-light);
  border-radius: 12px;
  background: #fff;
}

.step-heading {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  margin-bottom: 24px;
}

.step {
  display: grid;
  width: 30px;
  height: 30px;
  flex: 0 0 30px;
  place-items: center;
  border-radius: 50%;
  background: var(--color-primary-soft);
  color: var(--color-primary);
  font-weight: 700;
}

select,
input {
  width: 100%;
  padding: 11px 12px;
  border: 1px solid var(--border-light);
  border-radius: 8px;
  background: #fff;
  color: var(--text-main);
  font: inherit;
}

select:disabled,
input:disabled {
  cursor: not-allowed;
  opacity: 0.6;
}

.service-picker {
  min-width: 0;
  margin: 20px 0 0;
  padding: 0;
  border: 0;
}

.service-picker legend {
  margin-bottom: 10px;
  color: var(--text-main);
  font-size: 14px;
  font-weight: 600;
}

.service-options {
  display: grid;
  gap: 10px;
}

.service-option {
  display: grid;
  gap: 8px;
  width: 100%;
  padding: 14px;
  border: 1px solid var(--border-light);
  border-radius: 10px;
  background: #fff;
  color: var(--text-main);
  cursor: pointer;
  font: inherit;
  text-align: left;
  transition: border-color .15s ease, background-color .15s ease, box-shadow .15s ease;
}

.service-option:hover {
  border-color: #93c5fd;
  background: #f8fbff;
}

.service-option.selected {
  border-color: var(--color-primary);
  background: #eff6ff;
  box-shadow: 0 0 0 2px var(--color-primary-soft);
}

.service-option:focus-visible {
  outline: 2px solid var(--color-primary);
  outline-offset: 2px;
}

.service-option-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.service-title {
  font-size: 14px;
  font-weight: 700;
}

.service-price {
  flex: 0 0 auto;
  color: var(--color-primary);
  font-size: 14px;
  font-weight: 700;
}

.service-description,
.service-duration,
.service-hint {
  color: var(--text-muted);
  font-size: 12px;
  line-height: 1.5;
}

.service-duration {
  width: fit-content;
  padding: 3px 8px;
  border-radius: 999px;
  background: #f1f5f9;
}

.confirmation-panel {
  margin-top: 16px;
}

.summary {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 16px;
  margin-bottom: 24px;
  padding: 16px;
  border-radius: 8px;
  background: var(--bg-main);
}

.summary div {
  display: grid;
  gap: 6px;
}

.summary span {
  color: var(--text-muted);
  font-size: 12px;
}

.summary strong {
  font-size: 14px;
}

.summary strong.price {
  color: var(--color-primary);
  font-size: 16px;
}

.confirm-button {
  margin-top: 8px;
}

.confirm-button:disabled {
  cursor: not-allowed;
  opacity: 0.55;
}

.error {
  color: var(--color-danger);
}

.success {
  margin-bottom: 12px;
  color: #15803d;
  font-weight: 600;
}

@media (max-width: 800px) {
  .client-page {
    padding: 20px;
  }

  .booking-grid,
  .summary {
    grid-template-columns: 1fr;
  }
}
</style>
