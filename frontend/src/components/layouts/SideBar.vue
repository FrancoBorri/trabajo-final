<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const isCollapsed = ref(false)
const isUsersMenuOpen = ref(true)

// El submenú solo se muestra si el sidebar está expandido
const showUsersMenu = computed(() => isUsersMenuOpen.value && !isCollapsed.value)

const toggleSidebar = () => {
  isCollapsed.value = !isCollapsed.value

  if (isCollapsed.value) {
    isUsersMenuOpen.value = false
  }
}

const toggleUsersMenu = () => {
  if (isCollapsed.value) {
    isCollapsed.value = false
    isUsersMenuOpen.value = true
    return
  }

  isUsersMenuOpen.value = !isUsersMenuOpen.value
}

const handleLogout = () => {
  authStore.logout()
  router.push('/login')
}

const userName = computed(() => {
  return authStore.user?.name ?? 'Usuario'
})

const userInitials = computed(() => {
  const words = userName.value.trim().split(/\s+/)

  const letters =
    words.length > 1
      ? `${words[0]?.[0] ?? ''}${words[1]?.[0] ?? ''}`
      : userName.value.slice(0, 2)

  return letters.toUpperCase()
})

interface NavChild {
  name: string
  path: string
}

interface NavItem {
  name: string
  path: string
  /** Trazos SVG (viewBox 24x24, se dibujan con stroke) */
  icon: string[]
  children?: NavChild[]
}

const icons = {
  panel: [
    'M5 4h14a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1z',
    'M9 4v16'
  ],
  dashboard: [
    'M4 4h6v6H4z',
    'M14 4h6v6h-6z',
    'M4 14h6v6H4z',
    'M14 14h6v6h-6z'
  ],
  chevron: ['M6 9l6 6 6-6'],
  logout: ['M9 4H5a1 1 0 0 0-1 1v14a1 1 0 0 0 1 1h4', 'M16 8l4 4-4 4', 'M20 12H9']
}

const adminMenuItems: NavItem[] = [
  {
    name: 'Dashboard',
    path: '/admin/dashboard',
    icon: icons.dashboard
  },
  {
    name: 'Gestión de Turnos',
    path: '/admin/turnos',
    icon: ['M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18z', 'M12 7v5l3 2']
  },
  {
    name: 'Servicios y Precios',
    path: '/admin/servicios',
    icon: ['M4 4h8l8 8-8 8-8-8z', 'M8.5 8.5h.01']
  },
  {
    name: 'Gestión de Usuarios',
    path: '/admin/usuarios',
    icon: [
      'M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z',
      'M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1'
    ],
    children: [
      { name: 'Administradores', path: '/admin/usuarios/administradores' },
      { name: 'Clientes', path: '/admin/usuarios/clientes' },
      { name: 'Profesionales', path: '/admin/usuarios/profesionales' }
    ]
  }
]

const clientMenuItems: NavItem[] = [
  {
    name: 'Inicio',
    path: '/client/dashboard',
    icon: icons.dashboard
  },
  {
    name: 'Reservar turno',
    path: '/client/book-appointments',
    icon: [
      'M5 5h14a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1z',
      'M4 10h16',
      'M8 3v4',
      'M16 3v4'
    ]
  },
  {
    name: 'Mis turnos',
    path: '/client/appointments',
    icon: ['M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18z', 'M12 7v5l3 2']
  }
]

const professionalMenuItems: NavItem[] = [
  {
    name: 'Inicio',
    path: '/professional/dashboard',
    icon: icons.dashboard
  },
  {
    name: 'Mis turnos',
    path: '/professional/appointments',
    icon: ['M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18z', 'M12 7v5l3 2']
  },
  {
    name: 'Pacientes',
    path: '/professional/patients',
    icon: [
      'M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2',
      'M10 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z',
      'M20 21v-2a4 4 0 0 0-3-3.87',
      'M16 3.13a4 4 0 0 1 0 7.75'
    ]
  },
  {
    name: 'Disponibilidad',
    path: '/professional/availability',
    icon: [
      'M12 3v18',
      'M3 12h18',
      'M5 5l14 14',
      'M19 5L5 19'
    ]
  },
  {
    name: 'Mis servicios',
    path: '/professional/services',
    icon: ['M4 4h8l8 8-8 8-8-8z', 'M8.5 8.5h.01']
  }
]

const menuItems = computed(() => {
  switch (authStore.user?.role) {
    case 'client':
      return clientMenuItems
    case 'professional':
      return professionalMenuItems
    default:
      return adminMenuItems
  }
})

const homePath = computed(() =>
  authStore.user?.role === 'client'
    ? '/client/dashboard'
    : authStore.user?.role === 'professional'
      ? '/professional/dashboard'
      : '/admin/agenda'
)

const panelName = computed(() =>
  authStore.user?.role === 'professional'
    ? 'ProfessionalPanel'
    : authStore.user?.role === 'client'
      ? 'ClientPanel'
      : 'AdminPanel'
)

const isChildRouteActive = (children?: NavChild[]) =>
  !!children?.some(child => route.path.startsWith(child.path))
</script>

<template>
  <aside
    class="sidebar"
    :class="{ 'is-collapsed': isCollapsed }"
  >
    <header class="sidebar-header">
      <RouterLink
        v-if="!isCollapsed"
        :to="homePath"
        class="brand"
      >
        {{ panelName }}
      </RouterLink>

      <button
        class="icon-btn"
        type="button"
        :aria-label="isCollapsed ? 'Expandir menú' : 'Contraer menú'"
        :title="isCollapsed ? 'Expandir menú' : 'Contraer menú'"
        @click="toggleSidebar"
      >
        <svg
          class="icon"
          viewBox="0 0 24 24"
          aria-hidden="true"
        >
          <path
            v-for="d in icons.panel"
            :key="d"
            :d="d"
          />
        </svg>
      </button>
    </header>

    <nav
      class="nav"
      aria-label="Navegación principal"
    >
      <template
        v-for="item in menuItems"
        :key="item.path"
      >
        <!-- Elemento simple -->
        <RouterLink
          v-if="!item.children"
          :to="item.path"
          class="nav-item"
          active-class="is-active"
          :title="isCollapsed ? item.name : undefined"
        >
          <svg
            class="icon"
            viewBox="0 0 24 24"
            aria-hidden="true"
          >
            <path
              v-for="d in item.icon"
              :key="d"
              :d="d"
            />
          </svg>

          <span class="label">{{ item.name }}</span>
        </RouterLink>

        <!-- Elemento con submenú -->
        <div
          v-else
          class="group"
        >
          <button
            type="button"
            class="nav-item"
            :class="{ 'is-current': isChildRouteActive(item.children) }"
            :aria-expanded="showUsersMenu"
            :title="isCollapsed ? item.name : undefined"
            @click="toggleUsersMenu"
          >
            <svg
              class="icon"
              viewBox="0 0 24 24"
              aria-hidden="true"
            >
              <path
                v-for="d in item.icon"
                :key="d"
                :d="d"
              />
            </svg>

            <span class="label">{{ item.name }}</span>

            <svg
              class="icon chevron"
              :class="{ open: showUsersMenu }"
              viewBox="0 0 24 24"
              aria-hidden="true"
            >
              <path
                v-for="d in icons.chevron"
                :key="d"
                :d="d"
              />
            </svg>
          </button>

          <div
            class="submenu"
            :class="{ open: showUsersMenu }"
          >
            <div class="submenu-inner">
              <RouterLink
                v-for="child in item.children"
                :key="child.path"
                :to="child.path"
                class="submenu-item"
                active-class="is-active"
              >
                {{ child.name }}
              </RouterLink>
            </div>
          </div>
        </div>
      </template>
    </nav>

    <footer class="sidebar-footer">
      <span
        class="avatar"
        aria-hidden="true"
      >
        {{ userInitials }}
      </span>

      <span class="user-name">{{ userName }}</span>

      <button
        class="icon-btn is-danger"
        type="button"
        aria-label="Cerrar sesión"
        title="Cerrar sesión"
        @click="handleLogout"
      >
        <svg
          class="icon"
          viewBox="0 0 24 24"
          aria-hidden="true"
        >
          <path
            v-for="d in icons.logout"
            :key="d"
            :d="d"
          />
        </svg>
      </button>
    </footer>
  </aside>
</template>

<style scoped>
.sidebar {
  --sidebar-w: 232px;
  --sidebar-w-collapsed: 56px;
  --row-h: 34px;
  --ease: cubic-bezier(0.32, 0.72, 0, 1);
  --hover: color-mix(in srgb, var(--text-primary-light) 6%, transparent);

  position: sticky;
  top: 0;

  display: flex;
  flex-direction: column;

  width: var(--sidebar-w);
  height: 100vh;
  height: 100dvh;
  padding: 12px 8px;
  box-sizing: border-box;
  overflow: hidden;

  background: var(--bg-sidebar);
  color: var(--text-secondary-light);
  /* Borde como sombra interna: no le resta ancho al contenido */
  box-shadow: inset -1px 0 0 var(--border-color);

  font-size: 13px;

  transition: width 0.2s var(--ease);
}

.sidebar.is-collapsed {
  width: var(--sidebar-w-collapsed);
}

/* Íconos: mismo trazo en todo el sidebar */
.icon {
  width: 16px;
  height: 16px;
  flex-shrink: 0;

  fill: none;
  stroke: currentColor;
  stroke-width: 1.5;
  stroke-linecap: round;
  stroke-linejoin: round;
}

/* Header */
.sidebar-header {
  display: flex;
  align-items: center;
  justify-content: space-between;

  height: 36px;
  margin-bottom: 16px;
  padding-left: 12px;
}

.sidebar.is-collapsed .sidebar-header {
  justify-content: center;
  padding-left: 0;
}

.brand {
  color: var(--text-primary-light);
  font-size: 14px;
  font-weight: 600;
  letter-spacing: -0.01em;
  text-decoration: none;
  white-space: nowrap;
}

.icon-btn {
  display: grid;
  place-items: center;

  width: 28px;
  height: 28px;
  padding: 0;
  flex-shrink: 0;

  border: 0;
  border-radius: 6px;
  background: transparent;
  color: var(--text-secondary-light);

  cursor: pointer;
  transition:
    background-color 0.12s ease,
    color 0.12s ease;
}

.icon-btn:hover {
  background: var(--hover);
  color: var(--text-primary-light);
}

.icon-btn.is-danger:hover {
  background: var(--color-danger-bg);
  color: #f87171;
}

/* Navegación */
.nav {
  flex: 1;

  display: flex;
  flex-direction: column;
  gap: 2px;

  /* El margen negativo deja lugar al indicador activo, pegado al borde del sidebar */
  margin: 0 -8px;
  padding: 0 8px;

  overflow-x: hidden;
  overflow-y: auto;
  scrollbar-width: thin;
}

.nav-item {
  position: relative;

  display: flex;
  align-items: center;
  gap: 12px;

  width: 100%;
  height: var(--row-h);
  padding: 0 12px;
  box-sizing: border-box;
  flex-shrink: 0;

  border: 0;
  border-radius: 6px;
  background: transparent;
  color: var(--text-secondary-light);

  font: inherit;
  font-weight: 500;
  text-align: left;
  text-decoration: none;
  white-space: nowrap;

  cursor: pointer;
  transition:
    background-color 0.12s ease,
    color 0.12s ease;
}

.nav-item:hover {
  background: var(--hover);
  color: var(--text-primary-light);
}

.nav-item.is-active,
.nav-item.is-current {
  color: var(--text-primary-light);
}

/* Indicador de página activa */
.nav-item.is-active::before,
.submenu-item.is-active::before {
  content: '';

  position: absolute;
  left: -8px;
  top: 50%;

  width: 2px;
  height: 16px;
  margin-top: -8px;

  border-radius: 0 2px 2px 0;
  background: var(--color-primary);
}

.label {
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;

  transition: opacity 0.15s ease;
}

/* Los íconos no se mueven al colapsar: solo se recorta el texto */
.sidebar.is-collapsed .label,
.sidebar.is-collapsed .chevron {
  opacity: 0;
  pointer-events: none;
}

.chevron {
  width: 14px;
  height: 14px;
  margin-left: auto;

  opacity: 0.6;
  transition:
    transform 0.2s var(--ease),
    opacity 0.15s ease;
}

.chevron.open {
  transform: rotate(180deg);
}

/* Submenú */
.submenu {
  display: grid;
  grid-template-rows: 0fr;
  visibility: hidden;

  transition:
    grid-template-rows 0.2s var(--ease),
    visibility 0s linear 0.2s;
}

.submenu.open {
  grid-template-rows: 1fr;
  visibility: visible;

  transition:
    grid-template-rows 0.2s var(--ease),
    visibility 0s;
}

.submenu-inner {
  min-height: 0;
  margin: 0 -8px;
  padding: 0 8px;
  overflow: hidden;

  display: flex;
  flex-direction: column;
  gap: 1px;
}

.submenu-item:first-child {
  margin-top: 2px;
}

.submenu-item:last-child {
  margin-bottom: 4px;
}

.submenu-item {
  position: relative;

  display: flex;
  align-items: center;

  height: 30px;
  /* 12 (padding) + 16 (ícono) + 12 (gap): alineado con las etiquetas de arriba */
  padding: 0 12px 0 40px;
  box-sizing: border-box;

  border-radius: 6px;
  color: var(--text-secondary-light);

  font-weight: 500;
  text-decoration: none;
  white-space: nowrap;

  transition:
    background-color 0.12s ease,
    color 0.12s ease;
}

.submenu-item:hover {
  background: var(--hover);
  color: var(--text-primary-light);
}

.submenu-item.is-active {
  color: var(--text-primary-light);
}

/* Footer */
.sidebar-footer {
  display: flex;
  align-items: center;
  gap: 10px;

  margin-top: 12px;
  padding: 12px 0 0 8px;

  border-top: 1px solid var(--border-color);
}

.avatar {
  display: grid;
  place-items: center;

  width: 24px;
  height: 24px;
  flex-shrink: 0;

  border: 1px solid var(--border-color);
  border-radius: 50%;
  color: var(--text-primary-light);

  font-size: 10px;
  font-weight: 600;
  user-select: none;
}

.user-name {
  flex: 1;
  min-width: 0;
  overflow: hidden;

  color: var(--text-primary-light);
  font-weight: 500;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.sidebar.is-collapsed .sidebar-footer {
  flex-direction: column;
  gap: 6px;
  padding: 12px 0 0;
}

.sidebar.is-collapsed .user-name {
  display: none;
}

/* Accesibilidad */
.brand:focus-visible,
.nav-item:focus-visible,
.submenu-item:focus-visible,
.icon-btn:focus-visible {
  outline: 2px solid var(--color-primary);
  outline-offset: -2px;
}

@media (prefers-reduced-motion: reduce) {
  .sidebar,
  .submenu,
  .submenu.open,
  .chevron,
  .label {
    transition: none;
  }
}
</style>
