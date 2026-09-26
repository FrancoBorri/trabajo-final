import api from "@/api/axios"
import {endpoints} from "@/api/endpoints"
import type { ClinicalHistory } from "@/types"

class ClinicalHistoryService {
  async getAll(): Promise<ClinicalHistory[]> {
    const response = await api.get(endpoints.clinicalHistory.all)
    return response.data
  }

  async getById(id: number): Promise<ClinicalHistory> {
    const response = await api.get(endpoints.clinicalHistory.detail(id))
    return response.data
  }

  async create(data: Partial<ClinicalHistory>): Promise<ClinicalHistory> {
    const response = await api.post(endpoints.clinicalHistory.create, data)
    return response.data
  }

  async update(
    id: number,
    data: Partial<ClinicalHistory>
  ): Promise<ClinicalHistory> {
    const response = await api.put(
      endpoints.clinicalHistory.update(id),
      data
    )
    return response.data
  }

  async delete(id: number): Promise<void> {
    await api.delete(endpoints.clinicalHistory.delete(id))
  }
}

export default new ClinicalHistoryService()
