import api from "@/api/axios"
import {endpoints} from "@/api/endpoints"
import type { User } from "@/types"

interface ClientsResponse {
  current_page: number
  data: User[]
  last_page: number
  per_page: number
  total: number
}

class ClientService {

  async getAll(page = 1): Promise<ClientsResponse> {
    const response = await api.get(endpoints.users.all, {
      params: {
        page,
        per_page: 7,
        role: 'client',
      },
    })

    return {
      current_page: response.data.current_page,
      data: response.data.data,
      last_page: response.data.last_page,
      per_page: response.data.per_page,
      total: response.data.total,
    }
  }

  async getById(id: number): Promise<User> {
    const response = await api.get(
      endpoints.users.detail(id)
    )

    return response.data.user ?? response.data
  }

  async create(data: Partial<User>): Promise<User> {
    const response = await api.post(
      endpoints.users.create,
      {
        ...data,
        role: "client"
      }
    )

    return response.data.user ?? response.data
  }

  async update(
    id: number,
    data: Partial<User>
  ): Promise<User> {
    const response = await api.put(
      endpoints.users.detail(id),
      data
    )

    return response.data.user ?? response.data
  }

  async delete(id: number): Promise<void> {
    await api.delete(
      endpoints.users.detail(id)
    )
  }
}

export default new ClientService()
