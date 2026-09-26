<script setup lang="ts">
import { Inbox } from 'lucide-vue-next'

defineProps<{
  title: string
  description?: string
}>()
</script>

<template>
  <div class="empty-content">
    <div class="empty-icon">
      <Inbox :size="20" :stroke-width="1.8" />
    </div>

    <div class="empty-text">
      <h3>{{ title }}</h3>

      <p v-if="description">
        {{ description }}
      </p>
    </div>
  </div>
</template>

<style scoped>
.empty-content {
  display: flex;
  align-items: center;
  gap: 14px;

  padding: 16px 18px;

  border: 1px solid var(--border-light);
  border-radius: 10px;

  background-color: #ffffff;
}

.empty-icon {
  display: flex;
  align-items: center;
  justify-content: center;

  width: 40px;
  height: 40px;

  flex-shrink: 0;

  border-radius: 8px;

  background-color: var(--color-primary-soft);
  color: var(--color-primary);
}

.empty-text {
  display: flex;
  flex-direction: column;
  gap: 3px;

  min-width: 0;
}

h3 {
  margin: 0;

  color: var(--text-main);

  font-size: 15px;
  font-weight: 600;
  line-height: 1.4;
}

p {
  margin: 0;

  max-width: 500px;

  color: var(--text-muted);

  font-size: 14px;
  font-weight: 500;
  line-height: 1.5;
}
</style>
