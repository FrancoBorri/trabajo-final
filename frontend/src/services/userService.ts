import api from '@/api/axios'
import { endpoints } from '@/api/endpoints'
import type { Role, User } from '@/types'

export interface UsersResponse {
  current_page: number
  data: User[]
  last_page: number
  per_page: number
  total: number
}

export interface CreateUserData {
  name: string
  lastName: string
  email: string
  phone?: string
  password: string
  specialty?: string
  description?: string
}

export type UpdateUserData = Partial<Omit<CreateUserData, 'password'>> & {
  password?: string
}

class UserService {
  async getAll(role: Role, page = 1): Promise<UsersResponse> {
    // Las tres secciones administran usuarios. El endpoint de profesionales
    // devuelve perfiles profesionales y puede incluir usuarios de otro rol.
    const response = await api.get(endpoints.users.all, {
      params: { page, per_page: 10, role }
    })

    const payload = response.data
    const responseUsers = Array.isArray(payload) ? payload : payload.data ?? []
    const data = responseUsers
      .filter((user: User) => !user.role || user.role === role)
      .map((user: User) => ({
        ...user,
        id: String(user.id),
        role
      }))

    return {
      current_page: payload.current_page ?? 1,
      data,
      last_page: payload.last_page ?? 1,
      per_page: payload.per_page ?? 10,
      total: payload.total ?? data.length
    }
  }

  async create(role: Role, data: CreateUserData): Promise<User> {
    const endpoint = role === 'professional'
      ? endpoints.professionals.all
      : endpoints.users.create
    const response = await api.post(endpoint, {
      ...data,
      role
    })

    return response.data.user ?? response.data
  }

  async update(role: Role, id: string, data: UpdateUserData): Promise<User> {
    const endpoint = role === 'professional'
      ? endpoints.professionals.detail(id)
      : endpoints.users.detail(id)
    const response = await api.put(endpoint, data)
    return response.data.user ?? response.data
  }

  async delete(role: Role, id: string): Promise<void> {
    const endpoint = role === 'professional'
      ? endpoints.professionals.detail(id)
      : endpoints.users.detail(id)
    await api.delete(endpoint)
  }
}

export default new UserService()
