import api from "@/api/axios"
import {endpoints} from "@/api/endpoints"
import type { Professional } from "@/types"

interface ProfessionalsResponse {
  current_page: number
  data: Professional[]
  last_page: number
  per_page: number
  total: number
}

const normalizeProfessional = (value: Record<string, unknown>): Professional => {
  const user = value.user && typeof value.user === 'object'
    ? value.user as Record<string, unknown>
    : undefined

  return {
    ...value,
    id: String(value.id),
    userId: String(value.userId ?? value.user_id ?? user?.id ?? ''),
    name: String(value.name ?? user?.name ?? ''),
    lastName: String(value.lastName ?? value.last_name ?? user?.lastName ?? user?.last_name ?? ''),
    email: String(value.email ?? user?.email ?? ''),
    specialty: String(value.specialty ?? ''),
    phone: String(value.phone ?? user?.phone ?? '')
  }
}

class ProfessionalService {
  async getProfile(): Promise<Professional> {
    const response = await api.get(endpoints.professionals.profile)
    const professional = response.data.professional ?? response.data.data ?? response.data

    return professional
  }

  async getAll(page = 1): Promise<ProfessionalsResponse> {
    const response = await api.get(endpoints.professionals.all, {
      params: {
        page,
        per_page: 7,
      },
    })
    
    const payload = response.data

    return {
      ...payload,
      data: Array.isArray(payload.data)
        ? payload.data.map((professional: Record<string, unknown>) =>
            normalizeProfessional(professional)
          )
        : []
    }
  }

  async delete(id: string): Promise<void> {
    await api.delete(`${endpoints.professionals.all}/${id}`)
  }

  async create(data: Partial<Professional>): Promise<Professional> {
    const response = await api.post(
      endpoints.professionals.all,
      data
    )

    return response.data
  }

  async update(
    id: string,
    data: Partial<Professional>
  ): Promise<Professional> {
    const response = await api.put(
      `${endpoints.professionals.all}/${id}`,
      data
    )

    return response.data
  }
}

export default new ProfessionalService()
