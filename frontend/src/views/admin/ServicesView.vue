<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue'
import EmptyState from '@/components/common/EmptyState.vue'
import { Search, Clock3, BriefcaseBusiness, RefreshCw, Sparkles } from 'lucide-vue-next'
import professionalService from '@/services/professionalService'
import serviceService from '@/services/servicesService'
import type { Professional, Service } from '@/types'

const services = ref<Service[]>([])
const professionals = ref<Professional[]>([])
const isLoading = ref(true)
const error = ref('')
const search = ref('')

const normalizedSearch = computed(() => search.value.trim().toLowerCase())
const serviceCount = computed(() => services.value.length)
const professionalCount = computed(() => professionals.value.length)
const averagePrice = computed(() => {
  if (!serviceCount.value) return 0
  return services.value.reduce((total, service) => total + service.price, 0) / serviceCount.value
})

const getProfessionalServices = (professional: Professional) =>
  services.value.filter(
    service => String(service.professional_id) === String(professional.id)
  )

const filteredProfessionals = computed(() =>
  professionals.value
    .map(professional => ({
      professional,
      services: getProfessionalServices(professional)
    }))
    .filter(({ professional, services: professionalServices }) => {
      if (!normalizedSearch.value) {
        return true
      }

      const professionalText = [
        professional.name,
        professional.lastName,
        professional.specialty,
        ...professionalServices.map(service => service.title)
      ]
        .join(' ')
        .toLowerCase()

      return professionalText.includes(normalizedSearch.value)
    })
)

const unassignedServices = computed(() =>
  services.value.filter(
    service =>
      !service.professional_id ||
      !professionals.value.some(
        professional =>
          String(professional.id) === String(service.professional_id)
      )
  )
)

const formatPrice = (price: number) =>
  new Intl.NumberFormat('es-AR', {
    style: 'currency',
    currency: 'ARS',
    maximumFractionDigits: 2
  }).format(price)

const loadData = async () => {
  isLoading.value = true
  error.value = ''

  try {
    const [professionalsResponse, servicesResponse] = await Promise.all([
      professionalService.getAll(),
      serviceService.getAll()
    ])

    professionals.value = professionalsResponse.data
    services.value = servicesResponse
  } catch (requestError) {
    console.error('No se pudieron cargar los profesionales y servicios:', requestError)
    error.value = 'No se pudieron cargar los servicios y precios.'
  } finally {
    isLoading.value = false
  }
}

onMounted(loadData)
</script>

<template>
  <section class="admin-page">
    <AdminPageHeader title="Servicios y precios" description="Explorá la oferta profesional y consultá precios y duración de cada servicio.">
      <template #actions>
        <button class="refresh-button" type="button" :disabled="isLoading" @click="loadData">
          <RefreshCw :size="16" :class="{ spinning: isLoading }" />
          Actualizar
        </button>
      </template>
    </AdminPageHeader>

    <div v-if="!isLoading && !error" class="overview">
      <article class="overview-card">
        <span class="overview-icon"><BriefcaseBusiness :size="18" /></span>
        <div><small>Servicios disponibles</small><strong>{{ serviceCount }}</strong></div>
      </article>
      <article class="overview-card">
        <span class="overview-icon"><Sparkles :size="18" /></span>
        <div><small>Profesionales</small><strong>{{ professionalCount }}</strong></div>
      </article>
      <article class="overview-card">
        <span class="overview-icon price-icon">$</span>
        <div><small>Precio promedio</small><strong>{{ formatPrice(averagePrice) }}</strong></div>
      </article>
    </div>

    <div v-if="!isLoading && !error" class="catalog-toolbar">
      <label class="search-field">
        <Search :size="17" aria-hidden="true" />
        <input v-model="search" type="search" placeholder="Buscar profesional, especialidad o servicio">
      </label>
      <span class="results-count">{{ filteredProfessionals.length }} profesionales</span>
    </div>

    <p v-if="isLoading" class="state">Cargando profesionales y servicios...</p>
    <p v-else-if="error" class="state error">{{ error }}</p>
    <div v-else-if="filteredProfessionals.length > 0" class="professionals">
      <article
        v-for="{ professional, services: professionalServices } in filteredProfessionals"
        :key="professional.id"
        class="professional-card"
      >
        <header class="professional-header">
          <div class="professional-identity">
            <div class="avatar">
              {{ professional.name.charAt(0) }}{{ professional.lastName.charAt(0) }}
            </div>
            <div class="professional-copy">
              <h2>{{ professional.name }} {{ professional.lastName }}</h2>
              <p>{{ professional.specialty }}</p>
            </div>
          </div>
          <span class="service-count">{{ professionalServices.length }} {{ professionalServices.length === 1 ? 'servicio' : 'servicios' }}</span>
        </header>

        <div v-if="professionalServices.length > 0" class="service-list">
          <div
            v-for="service in professionalServices"
            :key="service.id"
            class="service-row"
          >
            <span class="service-mark"><Sparkles :size="17" /></span>
            <div class="service-info">
              <strong>{{ service.title }}</strong>
              <p>{{ service.description || 'Sin descripción disponible.' }}</p>
              <span class="duration"><Clock3 :size="14" /> {{ service.duration }} minutos</span>
            </div>
            <strong class="service-price">{{ formatPrice(service.price) }}</strong>
          </div>
        </div>
        <div v-else class="no-services">
          <p>Este profesional todavía no tiene servicios asociados.</p>
        </div>
      </article>
    </div>
    <EmptyState
      v-else
      title="No hay resultados"
      description="No hay profesionales o servicios que coincidan con la búsqueda."
    />

    <section v-if="!isLoading && unassignedServices.length > 0" class="unassigned">
    <header class="unassigned-header">
      <div>
        <h2>Servicios sin profesional asignado</h2>
        <p>Estos servicios no están vinculados a un perfil profesional.</p>
      </div>
      <span class="service-count">{{ unassignedServices.length }}</span>
    </header>
    <div class="unassigned-list">
      <div v-for="service in unassignedServices" :key="service.id" class="service-row">
        <span class="service-mark"><Sparkles :size="17" /></span>
        <div class="service-info">
          <strong>{{ service.title }}</strong>
          <p>{{ service.description || 'Sin descripción disponible.' }}</p>
          <span class="duration"><Clock3 :size="14" /> {{ service.duration }} minutos</span>
        </div>
        <strong class="service-price">{{ formatPrice(service.price) }}</strong>
      </div>
    </div>
    </section>
  </section>
</template>

<style scoped>
.admin-page {
  max-width: 1440px;
  margin: 0 auto;
  padding: 36px;
}

.refresh-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  min-height: 40px;
  padding: 0 14px;
  border: 1px solid var(--border-light);
  border-radius: 9px;
  background: white;
  color: var(--text-main);
  cursor: pointer;
  font: inherit;
  font-weight: 600;
}

.refresh-button:disabled {
  cursor: wait;
  opacity: 0.6;
}

.spinning { animation: spin 1s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

.overview {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 14px;
  margin: 0 0 24px;
}

.overview-card {
  display: flex;
  align-items: center;
  gap: 14px;
  min-height: 92px;
  padding: 18px;
  border: 1px solid var(--border-light);
  border-radius: 12px;
  background: #fff;
}

.overview-icon {
  display: grid;
  width: 42px;
  height: 42px;
  flex: 0 0 auto;
  place-items: center;
  border-radius: 11px;
  background: var(--color-primary-soft);
  color: var(--color-primary);
}

.price-icon { font-size: 20px; font-weight: 700; }
.overview-card div { display: grid; gap: 4px; }
.overview-card small { color: var(--text-muted); font-size: 12px; }
.overview-card strong { color: var(--text-main); font-size: 19px; }

.catalog-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 16px;
}

.search-field {
  display: flex;
  align-items: center;
  gap: 10px;
  width: min(100%, 520px);
  min-height: 44px;
  padding: 0 12px;
  border: 1px solid var(--border-light);
  border-radius: 10px;
  background: #fff;
  color: var(--text-muted);
}

input {
  width: 100%;
  min-height: 40px;
  padding: 0;
  border: 0;
  outline: 0;
  background: transparent;
  color: var(--text-main);
  font: inherit;
  font-size: 13px;
}

.results-count { color: var(--text-muted); font-size: 13px; }
.professionals {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  align-items: start;
  gap: 16px;
}

.professional-card,
.unassigned {
  overflow: hidden;
  border: 1px solid var(--border-light);
  border-radius: 14px;
  background: #fff;
  box-shadow: 0 2px 8px rgb(15 23 42 / 3%);
}

.professional-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  padding: 18px 20px;
  border-bottom: 1px solid var(--border-light);
  background: linear-gradient(120deg, #fff, #f8fbff);
}

.professional-identity {
  display: flex;
  align-items: center;
  min-width: 0;
  gap: 12px;
}

.avatar {
  display: grid;
  place-items: center;
  width: 42px;
  height: 42px;
  flex-shrink: 0;
  border: 1px solid #bfdbfe;
  border-radius: 12px;
  background: #eff6ff;
  color: #2563eb;
  font-size: 14px;
  font-weight: 700;
  text-transform: uppercase;
}

.professional-copy { min-width: 0; }
h2 {
  margin: 0;
  color: var(--text-main);
  font-size: 16px;
}

p,
.state {
  margin: 0;
  color: var(--text-muted);
  font-size: 14px;
}

.professional-header p {
  margin-top: 4px;
  font-size: 12px;
}

.service-count {
  flex: 0 0 auto;
  padding: 6px 9px;
  border-radius: 999px;
  background: #f1f5f9;
  color: #475569;
  font-size: 11px;
  font-weight: 700;
}

.service-list,
.unassigned-list {
  display: grid;
}

.service-row {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px 20px;
  border-bottom: 1px solid var(--border-light);
}

.service-row:last-child {
  border-bottom: 0;
}

.service-mark {
  display: grid;
  width: 34px;
  height: 34px;
  flex: 0 0 auto;
  place-items: center;
  border-radius: 9px;
  background: #f1f5f9;
  color: #64748b;
}

.service-info { min-width: 0; flex: 1; }
.service-info strong { color: var(--text-main); font-size: 14px; }
.service-info p {
  margin-top: 4px;
  overflow-wrap: anywhere;
  font-size: 12px;
  line-height: 1.5;
}

.duration {
  display: flex;
  align-items: center;
  gap: 5px;
  margin-top: 9px;
  color: var(--text-muted);
  font-size: 11px;
}

.service-price {
  flex: 0 0 auto;
  color: var(--text-main);
  font-size: 15px;
  white-space: nowrap;
}

.no-services { padding: 22px 20px; }
.no-services p { font-size: 13px; }

.unassigned { margin-top: 24px; }
.unassigned-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 20px;
  border-bottom: 1px solid var(--border-light);
}
.unassigned-header p { margin-top: 5px; font-size: 12px; }
.unassigned-header .service-count { background: #fff4d6; color: #916a00; }
.unassigned h2 {
  padding: 0;
  border: 0;
  font-size: 16px;
}

.error { color: var(--color-danger); }
@media (max-width: 1000px) {
  .professionals { grid-template-columns: 1fr; }
}

@media (max-width: 700px) {
  .admin-page { padding: 22px 18px; }
  .overview { grid-template-columns: 1fr; gap: 10px; }
  .overview-card { min-height: 76px; }
  .catalog-toolbar { align-items: stretch; flex-direction: column; }
  .search-field { width: 100%; }
  .results-count { align-self: flex-end; }
  .service-row { align-items: flex-start; padding: 15px; }
  .service-price { font-size: 14px; }
}

@media (prefers-reduced-motion: reduce) {
  .spinning { animation: none; }
}
</style>
