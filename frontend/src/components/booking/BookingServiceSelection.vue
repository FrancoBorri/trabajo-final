<script setup lang="ts">
import type { Professional, Service } from '@/types'

const props = defineProps<{
  professionals: Professional[]
  professionalServices: Service[]
}>()

const professionalId = defineModel<string>('professionalId', { required: true })
const serviceId = defineModel<string>('serviceId', { required: true })

const formatPrice = (price: number) =>
  new Intl.NumberFormat('es-AR', {
    style: 'currency',
    currency: 'ARS',
    maximumFractionDigits: 2
  }).format(price)
</script>

<template>
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
      <select v-model="professionalId">
        <option value="" disabled>Seleccioná un profesional</option>
        <option
          v-for="professional in props.professionals"
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

    <fieldset class="service-picker" :disabled="!professionalId">
      <legend>Servicio</legend>
      <p v-if="!professionalId" class="service-hint">
        Primero seleccioná un profesional para ver sus servicios.
      </p>
      <div v-else-if="props.professionalServices.length" class="service-options">
        <button
          v-for="service in props.professionalServices"
          :key="service.id"
          class="service-option"
          :class="{ selected: serviceId === String(service.id) }"
          type="button"
          :aria-pressed="serviceId === String(service.id)"
          @click="serviceId = String(service.id)"
        >
          <span class="service-option-top">
            <span class="service-title">{{ service.title }}</span>
            <span class="service-price">{{ formatPrice(service.price) }}</span>
          </span>
          <span v-if="service.description" class="service-description">
            {{ service.description }}
          </span>
          <span class="service-duration">{{ service.duration }} minutos</span>
        </button>
      </div>
    </fieldset>

    <p
      v-if="professionalId && !props.professionalServices.length"
      class="state"
    >
      Este profesional no tiene servicios disponibles.
    </p>
  </article>
</template>

<style scoped>
.panel { padding: 24px; border: 1px solid var(--border-light); border-radius: 12px; background: #fff; }
h2,
p { margin-top: 0; }
h2 { margin-bottom: 6px; font-size: 18px; }
.step-heading { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 24px; }
.step-heading p,
.state { color: var(--text-muted); }
.step { display: grid; width: 30px; height: 30px; flex: 0 0 30px; place-items: center; border-radius: 50%; background: var(--color-primary-soft); color: var(--color-primary); font-weight: 700; }
label { display: grid; gap: 8px; color: var(--text-main); font-size: 14px; font-weight: 600; }
select { width: 100%; padding: 11px 12px; border: 1px solid var(--border-light); border-radius: 8px; background: #fff; color: var(--text-main); font: inherit; }
.service-picker { min-width: 0; margin: 20px 0 0; padding: 0; border: 0; }
.service-picker legend { margin-bottom: 10px; color: var(--text-main); font-size: 14px; font-weight: 600; }
.service-options { display: grid; gap: 10px; }
.service-option { display: grid; gap: 8px; width: 100%; padding: 14px; border: 1px solid var(--border-light); border-radius: 10px; background: #fff; color: var(--text-main); cursor: pointer; font: inherit; text-align: left; transition: border-color .15s ease, background-color .15s ease, box-shadow .15s ease; }
.service-option:hover { border-color: #93c5fd; background: #f8fbff; }
.service-option.selected { border-color: var(--color-primary); background: #eff6ff; box-shadow: 0 0 0 2px var(--color-primary-soft); }
.service-option:focus-visible { outline: 2px solid var(--color-primary); outline-offset: 2px; }
.service-option-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; }
.service-title { font-size: 14px; font-weight: 700; }
.service-price { flex: 0 0 auto; color: var(--color-primary); font-size: 14px; font-weight: 700; }
.service-description,
.service-duration,
.service-hint { color: var(--text-muted); font-size: 12px; line-height: 1.5; }
.service-duration { width: fit-content; padding: 3px 8px; border-radius: 999px; background: #f1f5f9; }
</style>
