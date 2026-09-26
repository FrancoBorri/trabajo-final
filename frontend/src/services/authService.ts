import api from '@/api/axios.ts'
import { endpoints } from '@/api/endpoints.ts'
import type { LoginRequest, RegisterRequest } from '@/types/auth.ts'
import type { User, Role } from '@/types'

export class AuthService {
  private normalizeRole(value: unknown): Role | null {
    if (typeof value === 'string') {
      const normalized = value.toLowerCase().trim()

      const roleAliases: Record<string, Role> = {
        admin: 'admin',
        administrador: 'admin',
        professional: 'professional',
        profesional: 'professional',
        client: 'client',
        cliente: 'client'
      }

      if (normalized in roleAliases) {
        return roleAliases[normalized] ?? null
      }
    }

    if (value && typeof value === 'object') {
      const roleObject = value as Record<string, unknown>

      for (const field of ['name', 'slug', 'value', 'key', 'role_name']) {
        const role = this.normalizeRole(roleObject[field])

        if (role) {
          return role
        }
      }
    }

    return null
  }

  private getRole(backendUser: Record<string, unknown>): Role {
    if (backendUser.professional || backendUser.professional_id) {
      return 'professional'
    }

    const directRole = this.normalizeRole(backendUser.role)

    if (directRole) {
      return directRole
    }

    for (const field of ['user_role', 'role_name', 'type', 'userType', 'rol']) {
      const role = this.normalizeRole(backendUser[field])

      if (role) {
        return role
      }
    }

    const roles = backendUser.roles

    if (Array.isArray(roles)) {
      for (const value of roles) {
        const role = this.normalizeRole(value)

        if (role) {
          return role
        }
      }
    }

    throw new Error('La API no devolvió un rol válido para el usuario autenticado.')
  }

  private mapUser(backendUser: Record<string, unknown>): User {
    return {
      id: String(backendUser.id),
      name: String(backendUser.name ?? ''),
      lastName: String(backendUser.lastName ?? backendUser.last_name ?? ''),
      email: String(backendUser.email ?? ''),
      role: this.getRole(backendUser),
      phone: backendUser.phone as string | undefined,
      specialty: backendUser.specialty as string | undefined,
      avatar: backendUser.avatar as string | undefined,
      created_at: String(backendUser.created_at ?? ''),
      updated_at: String(backendUser.updated_at ?? ''),
    }
  }

  async login(data: LoginRequest): Promise<{ user: User; token: string }> {
    const response = await api.post(endpoints.auth.login, data)
    const responseUser = response.data.user ?? response.data
    const user = {
      ...responseUser,
      ...(response.data.professional
        ? { professional: response.data.professional }
        : {}),
      role:
        responseUser.role ??
        response.data.role ??
        response.data.user_role ??
        response.data.role_name ??
        response.data.rol
    }

    return {
      user: this.mapUser(user),
      token: response.data.token
    }
  }

  async register(data: RegisterRequest): Promise<User> {
    const response = await api.post(endpoints.auth.register, data)

    return this.mapUser(response.data.user)
  }


  async logout(): Promise<void> {
    await api.post(endpoints.auth.logout)
  }


  async me(): Promise<User> {
    const response = await api.get(endpoints.auth.me)
    return this.mapUser(response.data.user ?? response.data)
  }

}

export default new AuthService()
