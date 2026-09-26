<script setup lang="ts">
defineProps<{
  modelValue: string
  slots: string[]
  isLoading: boolean
  error: string
  showEmptyState: boolean
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()
</script>

<template>
  <p v-if="isLoading" class="state">Buscando horarios libres...</p>
  <p v-else-if="error" class="state error">{{ error }}</p>
  <p v-else-if="showEmptyState" class="state">
    No hay horarios disponibles para este día.
  </p>

  <div v-else-if="slots.length" class="slots">
    <button
      v-for="slot in slots"
      :key="slot"
      type="button"
      class="slot"
      :class="{ selected: modelValue === slot }"
      @click="emit('update:modelValue', slot)"
    >
      {{ slot }}
    </button>
  </div>
</template>

<style scoped>
.state {
  color: var(--text-muted);
}

.error {
  color: var(--color-danger);
}

.slots {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(82px, 1fr));
  gap: 10px;
  margin-top: 20px;
}

.slot {
  padding: 10px;
  border: 1px solid var(--border-light);
  border-radius: 8px;
  background: #fff;
  color: var(--text-main);
  cursor: pointer;
  font: inherit;
  font-weight: 600;
}

.slot:hover,
.slot.selected {
  border-color: var(--color-primary);
  background: var(--color-primary-soft);
  color: var(--color-primary);
}
</style>
