<script setup lang="ts">
import Sidebar from '@/components/layouts/SideBar.vue'
</script>

<template>
  <div class="layout">
    <Sidebar />

    <main class="content">
      <RouterView />
    </main>
  </div>
</template>

<style scoped>
.layout {
  display: flex;
  min-height: 100vh;
}

.content {
  flex: 1;
}
</style>
