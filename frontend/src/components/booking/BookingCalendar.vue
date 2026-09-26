<script setup lang="ts">
import { computed, ref, watch } from 'vue'

interface AvailableDay {
  value: number
  label: string
}

const props = withDefaults(defineProps<{
  modelValue: string
  min: string
  disabled?: boolean
  availableDays: AvailableDay[]
}>(), {
  disabled: false
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
  invalid: []
}>()

const today = new Date()
const todayValue = [
  today.getFullYear(),
  String(today.getMonth() + 1).padStart(2, '0'),
  String(today.getDate()).padStart(2, '0')
].join('-')
const visibleMonth = ref(
  props.modelValue
    ? props.modelValue.slice(0, 7)
    : props.min.slice(0, 7) || todayValue.slice(0, 7)
)

const monthDate = computed(() => {
  const [year = today.getFullYear(), month = today.getMonth() + 1] =
    visibleMonth.value.split('-').map(Number)
  return new Date(year, month - 1, 1)
})

const monthLabel = computed(() =>
  new Intl.DateTimeFormat('es-AR', {
    month: 'long',
    year: 'numeric'
  }).format(monthDate.value)
)

const daysInMonth = computed(() => {
  const year = monthDate.value.getFullYear()
  const month = monthDate.value.getMonth()
  const firstDay = new Date(year, month, 1).getDay()
  const mondayOffset = firstDay === 0 ? 6 : firstDay - 1
  const totalDays = new Date(year, month + 1, 0).getDate()

  return [
    ...Array.from({ length: mondayOffset }, () => null),
    ...Array.from({ length: totalDays }, (_, index) => {
      const day = index + 1
      return `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`
    })
  ]
})

const dateDayOfWeek = (date: string) => {
  const [year = 0, month = 0, day = 0] = date.split('-').map(Number)
  const weekDay = new Date(year, month - 1, day).getDay()
  return weekDay === 0 ? 7 : weekDay
}

const isDateAvailable = (date: string) =>
  Boolean(date) &&
  date >= props.min &&
  props.availableDays.some(day => day.value === dateDayOfWeek(date))

const canGoPrevious = computed(() => visibleMonth.value > props.min.slice(0, 7))

const goToMonth = (offset: number) => {
  const nextMonth = new Date(
    monthDate.value.getFullYear(),
    monthDate.value.getMonth() + offset,
    1
  )

  visibleMonth.value = [
    nextMonth.getFullYear(),
    String(nextMonth.getMonth() + 1).padStart(2, '0')
  ].join('-')
}

const selectDate = (date: string | null) => {
  if (!date || !isDateAvailable(date)) {
    return
  }

  emit('update:modelValue', date)
}

watch(() => props.modelValue, value => {
  if (value) {
    visibleMonth.value = value.slice(0, 7)
  }
})

watch(() => props.availableDays, days => {
  if (!days.length && props.modelValue) {
    emit('invalid')
  }
})
</script>

<template>
  <div class="calendar" :class="{ disabled }">
    <div class="calendar-header">
      <button
        type="button"
        class="month-button"
        :disabled="disabled || !canGoPrevious"
        aria-label="Mes anterior"
        @click="goToMonth(-1)"
      >
        ‹
      </button>
      <strong>{{ monthLabel }}</strong>
      <button
        type="button"
        class="month-button"
        :disabled="disabled"
        aria-label="Mes siguiente"
        @click="goToMonth(1)"
      >
        ›
      </button>
    </div>

    <div v-if="!disabled" class="weekdays">
      <span v-for="day in ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom']" :key="day">
        {{ day }}
      </span>
    </div>

    <div v-if="!disabled" class="calendar-grid">
      <button
        v-for="(date, index) in daysInMonth"
        :key="date ?? `empty-${index}`"
        type="button"
        class="calendar-day"
        :class="{
          selected: date === modelValue,
          available: date && isDateAvailable(date),
          today: date === todayValue
        }"
        :disabled="!date || !isDateAvailable(date)"
        @click="selectDate(date)"
      >
        {{ date ? Number(date.slice(-2)) : '' }}
      </button>
    </div>

    <p v-if="!disabled && !availableDays.length" class="state error">
      Este profesional todavía no configuró días de atención.
    </p>
    <p v-else-if="!disabled" class="calendar-help">
      Los días destacados tienen turnos disponibles.
    </p>
  </div>
</template>

<style scoped>
.calendar {
  margin-top: 18px;
  padding: 16px;
  border: 1px solid var(--border-light);
  border-radius: 10px;
  background: var(--bg-main);
}

.calendar.disabled {
  cursor: not-allowed;
  opacity: 0.6;
}

.calendar-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 16px;
  text-transform: capitalize;
}

.month-button {
  width: 30px;
  height: 30px;
  border: 1px solid var(--border-light);
  border-radius: 7px;
  background: #fff;
  color: var(--text-main);
  cursor: pointer;
  font-size: 22px;
  line-height: 1;
}

.month-button:hover:not(:disabled) {
  border-color: var(--color-primary);
  color: var(--color-primary);
}

.month-button:disabled {
  cursor: not-allowed;
  opacity: 0.4;
}

.weekdays,
.calendar-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 6px;
}

.weekdays {
  margin-bottom: 6px;
  color: var(--text-muted);
  font-size: 11px;
  font-weight: 700;
  text-align: center;
}

.calendar-day {
  aspect-ratio: 1;
  min-width: 0;
  border: 1px solid transparent;
  border-radius: 7px;
  background: transparent;
  color: var(--text-muted);
  font: inherit;
}

.calendar-day.available {
  border-color: var(--border-light);
  background: #fff;
  color: var(--text-main);
  cursor: pointer;
}

.calendar-day.available:hover,
.calendar-day.selected {
  border-color: var(--color-primary);
  background: var(--color-primary-soft);
  color: var(--color-primary);
  font-weight: 700;
}

.calendar-day.today {
  box-shadow: inset 0 0 0 1px var(--color-primary);
}

.calendar-help {
  margin: 12px 0 0;
  color: var(--text-muted);
  font-size: 12px;
  text-align: center;
}

.state {
  color: var(--text-muted);
}

.error {
  color: var(--color-danger);
}
</style>
