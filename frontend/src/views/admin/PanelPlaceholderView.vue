<script setup lang="ts">
import { useRoute } from 'vue-router'

const route = useRoute()
</script>

<template>
  <section class="panel-placeholder">
    <h1>{{ route.name }}</h1>
    <p>Esta vista está lista para reemplazarse por el contenido de la sección.</p>
  </section>
</template>

<style scoped>
.panel-placeholder {
  padding: 32px;
}
</style>
