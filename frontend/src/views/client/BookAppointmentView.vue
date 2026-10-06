<script setup lang="ts">
import {computed, onMounted, ref, watch} from 'vue'
import {useAuthStore} from '@/stores/auth'
import BookingConfirmation from '@/components/booking/BookingConfirmation.vue'
import BookingCalendar from '@/components/booking/BookingCalendar.vue'
import BookingServiceSelection from '@/components/booking/BookingServiceSelection.vue'
import BookingTimesSlots from '@/components/booking/BookingTimesSlots.vue'
import appointmentService from '@/services/appointmentService'
import availabilityService from '@/services/availabilityService'
import professionalService from '@/services/professionalService'
import serviceService from '@/services/servicesService'
import type {Availability, Professional, Service} from '@/types'

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

const now = new Date()
const today = [
  now.getFullYear(),
  String(now.getMonth() + 1).padStart(2, '0'),
  String(now.getDate()).padStart(2, '0')
].join('-')
const daysOfWeek = [
  {value: 1, label: 'Lunes'},
  {value: 2, label: 'Martes'},
  {value: 3, label: 'Miércoles'},
  {value: 4, label: 'Jueves'},
  {value: 5, label: 'Viernes'},
  {value: 6, label: 'Sábado'},
  {value: 7, label: 'Domingo'}
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

const professionalName = computed(() =>
  selectedProfessional.value
    ? `${selectedProfessional.value.name} ${selectedProfessional.value.lastName}`.trim()
    : ''
)

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

    const remainingPages = await Promise.all(
      Array.from(
        {length: Math.max(professionalsResponse.last_page - 1, 0)},
        (_, index) => professionalService.getAll(index + 2)
      )
    )

    professionals.value = [
      ...professionalsResponse.data,
      ...remainingPages.flatMap(page => page.data)
    ]
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
  } catch (requestError: any) {
    console.error('No se pudo reservar el turno:', requestError)

    error.value = requestError.response?.data?.message
      ?? 'No se pudo confirmar el turno. Intentá nuevamente.'
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
        <BookingServiceSelection
          v-model:professional-id="selectedProfessionalId"
          v-model:service-id="selectedServiceId"
          :professionals="professionals"
          :professional-services="professionalServices"
        />

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

      <BookingConfirmation
        :professional="selectedProfessional"
        :service="selectedService"
        :professional-name="professionalName"
        :date="selectedDate"
        :time="selectedTime"
        :error="error"
        :confirmation-message="confirmationMessage"
        :is-saving="isSaving"
        @confirm="submitAppointment"
      />
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
.state {
  color: var(--text-muted);
}

.error {
  color: var(--color-danger);
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

.step-heading p {
  color: var(--text-muted);
}

@media (max-width: 800px) {
  .client-page {
    padding: 20px;
  }

  .booking-grid {
    grid-template-columns: 1fr;
  }
}
</style>
