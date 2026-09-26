import api from "@/api/axios"
import {endpoints} from "@/api/endpoints"
import type { Appointment } from "@/types"

class AppointmentService {
  async getAll(): Promise<Appointment[]> {
    const response = await api.get(endpoints.appointments.all)
    return response.data
  }

  async getProfessionalAppointments(): Promise<Appointment[]> {
    const response = await api.get(
      endpoints.appointments.professionalAppointments
    )
    return response.data
  }

  async getAdminAppointments(): Promise<Appointment[]> {
    const response = await api.get(
      endpoints.appointments.adminAppointments
    );

    return response.data;
  }

  async delete(id: number | string): Promise<void> {
    await api.delete(endpoints.appointments.detail(Number(id)))
  }

  async create(data: Partial<Appointment>): Promise<Appointment> {
    const response = await api.post(endpoints.appointments.all, data)

    return response.data
  }

  async update(id: number | string, data: Partial<Appointment>): Promise<Appointment> {
    const response = await api.put(`${endpoints.appointments.all}/${id}`, data)

    return response.data
  }

  async accept(appointment: Appointment): Promise<Appointment> {
    return this.update(appointment.id, {
      user_id: appointment.user_id,
      professional_id: appointment.professional_id,
      service_id: appointment.service_id,
      date: appointment.date,
      time: appointment.time.slice(0, 5),
      status: "confirmed",
      notes: appointment.notes,
    })
  }

  async cancel(id: number): Promise<Appointment> {
    const response = await api.patch(
      endpoints.appointments.cancel(id)
    )

    return response.data
  }

  async complete(id: number) {
    const response = await api.patch(
      endpoints.appointments.complete(id),
    );
    return response.data;
  }
}

export default new AppointmentService()
