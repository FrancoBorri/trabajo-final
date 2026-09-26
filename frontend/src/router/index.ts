import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import type { Role } from '@/types'
import RegisterPageView from '@/views/auth/RegisterPageView.vue'
import LoginPageView from '@/views/auth/LoginPageView.vue'
import MainLayout from '@/components/layouts/MainLayout.vue'
import AgendaView from '@/views/admin/AgendaView.vue'
import DashboardView from '@/views/admin/DashboardView.vue'
import AppointmentsView from '@/views/admin/AppointmentsView.vue'
import ServicesView from '@/views/admin/ServicesView.vue'
import UserManagementView from '@/views/admin/UserManagementView.vue'
import BookAppointmentView from '@/views/client/BookAppointmentView.vue'
import ClientAppointmentsView from '@/views/client/AppointmentsView.vue'
import ClientDashboardView from '@/views/client/DashboardView.vue'

import ProfessionalAgendaView from '@/views/professional/AgendaView.vue'
import ProfessionalAppointmentsView from '@/views/professional/AppointmentsView.vue'
import ProfessionalAvailabilityView from '@/views/professional/AvailabilityView.vue'
import ProfessionalDashboardView from '@/views/professional/DashboardView.vue'
import ProfessionalServicesView from '@/views/professional/ServicesView.vue'
import ProfessionalPatientsView from '@/views/professional/PatientsView.vue'



const getHomePath = (role?: Role) =>
  role === 'client'
    ? '/client/dashboard'
    : role === 'professional'
      ? '/professional/dashboard'
      : '/admin/dashboard'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/register',
      name: 'register',
      component: RegisterPageView
    },
    {
      path: '/login',
      name: 'login',
      component: LoginPageView
    },
    {
      path: '/dashboard',
      redirect: () => getHomePath(useAuthStore().user?.role)
    },
    // Todas las pantallas privadas deben declararse dentro de este layout.
    {
      path: '/',
      component: MainLayout,
      children: [
        {
          path: '',
          redirect: () => getHomePath(useAuthStore().user?.role)
        },
        {
          path: 'admin/dashboard',
          name: 'admin-dashboard',
          component: DashboardView,
          meta: { roles: ['admin'] }
        },
        {
          path: 'admin/agenda',
          name: 'admin-agenda',
          component: AgendaView,
          meta: { roles: ['admin'] }
        },
        {
          path: 'admin/turnos',
          name: 'admin-turnos',
          component: AppointmentsView,
          meta: { roles: ['admin'] }
        },
        {
          path: 'admin/servicios',
          name: 'admin-servicios',
          component: ServicesView,
          meta: { roles: ['admin'] }
        },
        {
          path: 'client/book-appointments',
          name: 'client-book-appointments',
          component: BookAppointmentView,
          meta: {
            roles: ['client']
          }
        },
        {
          path: 'client/dashboard',
          name: 'client-dashboard',
          component: ClientDashboardView,
          meta: {
            roles: ['client']
          }
        },
        {
          path: 'client/appointments',
          name: 'client-appointments',
          component: ClientAppointmentsView,
          meta: {
            roles: ['client']
          }
        },
        {
          path: 'professional/appointments',
          name: 'professional-appointments',
          component: ProfessionalAppointmentsView,
          meta: {
            roles: ['professional']
          }
        },
        {
          path: 'professional/patients',
          name: 'professional-patients',
          component: ProfessionalPatientsView,
          meta: {
            roles: ['professional']
          }
        },
        {
          path: 'professional/agenda',
          name: 'professional-agenda',
          component: ProfessionalAgendaView,
          meta: {
            roles: ['professional']
          }
        },
        {
          path: 'professional/availability',
          name: 'professional-availability',
          component: ProfessionalAvailabilityView,
          meta: {
            roles: ['professional']
          }
        },
        {
          path: 'professional/dashboard',
          name: 'professional-dashboard',
          component: ProfessionalDashboardView,
          meta: {
            roles: ['professional']
          }
        },
        {
          path: 'professional/services',
          name: 'professional-services',
          component: ProfessionalServicesView,
          meta: {
            roles: ['professional']
          }
        },




        {
          path: 'admin/usuarios/administradores',
          name: 'admin-administradores',
          component: UserManagementView,
          meta: { roles: ['admin'] },
          props: {
            role: 'admin',
            title: 'Administradores',
            singularLabel: 'Administrador',
            description: 'Administrá los usuarios con acceso al panel.'
          }
        },
        {
          path: 'admin/usuarios/clientes',
          name: 'admin-clientes',
          component: UserManagementView,
          meta: { roles: ['admin'] },
          props: {
            role: 'client',
            title: 'Clientes',
            singularLabel: 'Cliente',
            description: 'Consultá y registrá los clientes del negocio.'
          }
        },
        {
          path: 'admin/usuarios/profesionales',
          name: 'admin-profesionales',
          component: UserManagementView,
          meta: { roles: ['admin'] },
          props: {
            role: 'professional',
            title: 'Profesionales',
            singularLabel: 'Profesional',
            description: 'Consultá y registrá los profesionales del negocio.'
          }
        }
      ]
    },
    {
      path: '/:pathMatch(.*)*',
      redirect: () => getHomePath(useAuthStore().user?.role)
    }
  ],
})

router.beforeEach((to) => {
  const authStore = useAuthStore()
  const isPublicRoute = to.path === '/login' || to.path === '/register'
  const hasToken = Boolean(localStorage.getItem('token'))

  if (!isPublicRoute && !hasToken) {
    return '/login'
  }

  if (!isPublicRoute && hasToken && !authStore.user) {
    authStore.logout()
    return '/login'
  }

  if (isPublicRoute && hasToken) {
    if (!authStore.user) {
      authStore.logout()
      return
    }

    return getHomePath(authStore.user.role)
  }

  const allowedRoles = to.meta.roles as Role[] | undefined

  if (
    allowedRoles &&
    (!authStore.user || !allowedRoles.includes(authStore.user.role))
  ) {
    return authStore.user ? getHomePath(authStore.user.role) : '/login'
  }
})

export default router
