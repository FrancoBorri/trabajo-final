export const endpoints = {
  auth: {
    login: "/login",
    register: "/register",
    logout: "/logout",
    me: "/user",
  },

  users: {
    all: "/users",
    admins: "/users",
    clients: "/users",
    create: "/users",
    detail: (id: number | string) => `/users/${id}`,
    profile: "/users/profile",
  },


  professionals: {
    all: "/professionals",
    detail: (id: number | string) => `/professionals/${id}`,
    availableSlots: (id: number) => `/professionals/${id}/available-slots`,
    profile: "/professional/profile",
  },

  services: {
    all: "/services",
    detail: (id: number | string) => `/services/${id}`,
  },

  appointments: {
    all: "/appointments",
    create: "/appointments",
    detail: (id: number) => `/appointments/${id}`,
    cancel: (id: number) => `/appointments/${id}/cancel`,
    complete: (id: number) => `/appointments/${id}/complete`,
    professionalAppointments: "/professional/appointments",
    adminAppointments: "/admin/appointments",
  },

  availability: {
    all: "/availability",
    create: "/availability",
    detail: (id: string) => `/availability/${id}`,
  },

  clinicalHistory: {
    all: "/clinical-histories",
    create: "/clinical-histories",
    detail: (id: number) => `/clinical-histories/${id}`,
    update: (id: number) => `/clinical-histories/${id}`,
    delete: (id: number) => `/clinical-histories/${id}`,
  },

  clinicalSession: {
    all: "/clinical-sessions",
    create: "/clinical-sessions",
    detail: (id: number) => `/clinical-sessions/${id}`,
    update: (id: number) => `/clinical-sessions/${id}`,
    delete: (id: number) => `/clinical-sessions/${id}`,
  },

  payments: {
    create: (appointmentId: number) =>
      `/appointments/${appointmentId}/payment`,
  },
}
