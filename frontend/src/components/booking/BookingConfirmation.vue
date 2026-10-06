<script setup lang="ts">
import type { Professional, Service } from '@/types'

const props = defineProps<{
  professional?: Professional
  service?: Service
  professionalName: string
  date: string
  time: string
  error: string
  confirmationMessage: string
  isSaving: boolean
}>()

const emit = defineEmits<{
  confirm: []
}>()

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
</script>

<template>
  <article class="panel confirmation-panel">
    <div class="step-heading">
      <span class="step">3</span>
      <div>
        <h2>Confirmación del turno</h2>
        <p>Revisá los datos antes de confirmar.</p>
      </div>
    </div>

    <div v-if="props.professional && props.service && props.time" class="summary">
      <div>
        <span>Profesional</span>
        <strong>{{ props.professionalName }}</strong>
      </div>
      <div>
        <span>Servicio</span>
        <strong>{{ props.service.title }}</strong>
      </div>
      <div>
        <span>Fecha y hora</span>
        <strong>{{ formatDate(props.date) }} · {{ props.time }}</strong>
      </div>
      <div>
        <span>Duración</span>
        <strong>{{ props.service.duration }} minutos</strong>
      </div>
      <div>
        <span>Precio</span>
        <strong class="price">{{ formatPrice(props.service.price) }}</strong>
      </div>
    </div>
    <p v-else class="state">Completá los pasos anteriores para ver el resumen.</p>

    <p v-if="props.confirmationMessage" class="success">{{ props.confirmationMessage }}</p>
    <p v-if="props.error && props.professional" class="state error">{{ props.error }}</p>

    <button
      class="btn-primary confirm-button"
      type="button"
      :disabled="!props.time || props.isSaving"
      @click="emit('confirm')"
    >
      {{ props.isSaving ? 'Confirmando...' : 'Confirmar turno' }}
    </button>
  </article>
</template>

<style scoped>
h2,
p { margin-top: 0; }
h2 { margin-bottom: 6px; font-size: 18px; }
.step-heading { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 24px; }
.step-heading p,
.state { color: var(--text-muted); }
.step { display: grid; width: 30px; height: 30px; flex: 0 0 30px; place-items: center; border-radius: 50%; background: var(--color-primary-soft); color: var(--color-primary); font-weight: 700; }
.confirmation-panel { margin-top: 16px; padding: 24px; border: 1px solid var(--border-light); border-radius: 12px; background: #fff; }
.summary { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; margin-bottom: 24px; padding: 16px; border-radius: 8px; background: var(--bg-main); }
.summary div { display: grid; gap: 6px; }
.summary span { color: var(--text-muted); font-size: 12px; }
.summary strong { font-size: 14px; }
.summary strong.price { color: var(--color-primary); font-size: 16px; }
.confirm-button { margin-top: 8px; }
.confirm-button:disabled { cursor: not-allowed; opacity: .55; }
.error { color: var(--color-danger); }
.success { margin-bottom: 12px; color: #15803d; font-weight: 600; }
@media (max-width: 800px) {
  .summary { grid-template-columns: 1fr; }
}
</style>
