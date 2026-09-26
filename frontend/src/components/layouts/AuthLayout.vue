<script setup lang="ts">
defineProps<{
  title?: string
  description?: string
}>()
</script>

<template>
  <div class="auth-container">
    <!-- Panel Lateral Izquierdo (Reutilizable) -->
    <div class="brand-panel">
      <div class="brand-content">
        <h1>{{ title || '¡Te damos la bienvenida!' }}</h1>
        <p class="brand-description">
          {{ description || 'Simplificá la agenda de tu negocio, gestioná citas en tiempo real y brindá una mejor experiencia a tus clientes.' }}
        </p>
      </div>
    </div>

    <!-- Panel Derecho (Aquí entra el formulario de Login o Register) -->
    <div class="form-panel">
      <slot />
    </div>
  </div>
</template>

<style scoped>
.auth-container {
  min-height: 100vh;
  display: flex;
  background-color: var(--bg-main);
}

/* Panel Izquierdo */
.brand-panel {
  flex: 1;
  background: linear-gradient(135deg, var(--bg-surface) 0%, var(--color-primary) 50%, var(--color-primary-hover) 100%);
  color: var(--text-primary-light);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 60px;
  position: relative;
  overflow: hidden;
}

.brand-panel::before {
  content: '';
  position: absolute;
  width: 300px;
  height: 300px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.08);
  top: -50px;
  left: -50px;
}

.brand-content {
  max-width: 480px;
  z-index: 1;
}

.badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(8px);
  padding: 6px 14px;
  border-radius: 20px;
  font-size: 13px;
  font-weight: 600;
  margin-bottom: 24px;
}

.brand-content h1 {
  font-size: 38px;
  font-weight: 800;
  line-height: 1.2;
  margin-bottom: 16px;
  letter-spacing: -0.5px;
}

.brand-description {
  font-size: 16px;
  color: var(--text-secondary-light);
  line-height: 1.6;
  margin-bottom: 32px;
}

.features-list {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.feature-item {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 15px;
  color: var(--text-primary-light);
}


/* Panel Derecho */
.form-panel {
  flex: 1.1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 40px 20px;
}

@media (max-width: 900px) {
  .brand-panel {
    display: none;
  }
  .form-panel {
    padding: 20px;
  }
}
</style>
