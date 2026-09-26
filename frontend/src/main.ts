import { createApp } from 'vue'
import { createPinia } from 'pinia'
import './assets/main.css'

import App from './App.vue'
import { useAuthStore } from '@/stores/auth'
import router from './router'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)

const authStore = useAuthStore(pinia)

try {
  await authStore.restoreSession()
} catch {
  // La sesión inválida ya fue limpiada por el store.
}

app.use(router)
app.mount('#app')
