import api from "@/api/axios"
import { endpoints } from "@/api/endpoints"
import type { Availability } from "@/types"

export interface AvailabilityPayload {
  professional_id: string
  day_week: number
  time_start: string
  time_end: string
}

const getAvailabilityItems = (value: unknown): Record<string, unknown>[] => {
  if (Array.isArray(value)) {
    return value as Record<string, unknown>[]
  }

  if (!value || typeof value !== 'object') {
    return []
  }

  const object = value as Record<string, unknown>

  if ('id' in object) {
    return [object]
  }

  for (const key of ['data', 'availabilities', 'availability']) {
    const items = getAvailabilityItems(object[key])

    if (items.length) {
      return items
    }
  }

  return []
}

const normalizeAvailability = (value: Record<string, unknown>): Availability => ({
  ...value,
  id: String(value.id),
  professional_id: String(
    value.professional_id ??
    value.professionalId ??
    (value.professional as Record<string, unknown> | undefined)?.id ??
    ''
  ),
  day_week: Number(value.day_week ?? value.dayWeek ?? value.day_of_week),
  time_start: String(value.time_start ?? value.start_time ?? ''),
  time_end: String(value.time_end ?? value.end_time ?? ''),
  created_at: String(value.created_at ?? ''),
  updated_at: String(value.updated_at ?? '')
}) as Availability

class AvailabilityService {
  async getAll(): Promise<Availability[]> {
    const response = await api.get(endpoints.availability.all)
    return getAvailabilityItems(response.data).map(availability =>
      normalizeAvailability(availability)
    )
  }

  async delete(id: string): Promise<void> {
    await api.delete(`${endpoints.availability.all}/${id}`)
  }

  async create(data: AvailabilityPayload): Promise<Availability> {
    const response = await api.post(endpoints.availability.all, data)
    const [availability] = getAvailabilityItems(response.data)

    return normalizeAvailability({
      ...(availability ?? {}),
      ...data,
      id: availability?.id ?? `local-${Date.now()}`
    })
  }

  async update(id: string, data: AvailabilityPayload): Promise<Availability> {
    const response = await api.put(`${endpoints.availability.all}/${id}`, data)
    const [availability] = getAvailabilityItems(response.data)

    return normalizeAvailability({
      ...(availability ?? {}),
      ...data,
      id
    })
  }

  async availableSlots(professionalId: string, serviceId: string, date: string): Promise<string[]> {
    const response = await api.get(endpoints.professionals.availableSlots(Number(professionalId)), {
      params: {
        service_id: serviceId,
        date,
      },
    })

    return response.data.slots
  }
}

export default new AvailabilityService()
