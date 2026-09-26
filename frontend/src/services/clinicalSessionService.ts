import api from "@/api/axios"
import {endpoints} from "@/api/endpoints"
import type { ClinicalSession } from "@/types"

class ClinicalSessionService {
  async getAll(): Promise<ClinicalSession[]> {
    const response = await api.get(endpoints.clinicalSession.all)
    return response.data
  }

  async getById(id: number): Promise<ClinicalSession> {
    const response = await api.get(endpoints.clinicalSession.detail(id))
    return response.data
  }

  async getByHistory(historyId: number): Promise<ClinicalSession[]> {
    const response = await api.get(endpoints.clinicalSession.all, {
      params: { clinical_history_id: historyId },
    })
    return response.data
  }

  async create(data: Partial<ClinicalSession>): Promise<ClinicalSession> {
    const response = await api.post(endpoints.clinicalSession.create, data)
    return response.data
  }

  async update(
    id: number,
    data: Partial<ClinicalSession>
  ): Promise<ClinicalSession> {
    const response = await api.put(
      endpoints.clinicalSession.update(id),
      data
    )
    return response.data
  }

  async delete(id: number): Promise<void> {
    await api.delete(endpoints.clinicalSession.delete(id))
  }
}

export default new ClinicalSessionService()
