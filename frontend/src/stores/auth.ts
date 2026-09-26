import { defineStore } from 'pinia'
import { ref } from 'vue'
import type { User } from '@/types'
import authService from '@/services/authService'

export const useAuthStore = defineStore('auth', () => {

  const user = ref<User | null>(null)
  const token = ref<string | null>(null)
  const isAuthenticated = ref(false)

  const login = (userData: User, userToken: string) => {
    user.value = userData
    token.value = userToken
    isAuthenticated.value = true
    localStorage.setItem('token', userToken)
  }

  const restoreSession = async () => {
    const storedToken = localStorage.getItem('token')

    if (!storedToken) {
      return
    }

    try {
      const userData = await authService.me()
      user.value = userData
      token.value = storedToken
      isAuthenticated.value = true
    } catch (error) {
      logout()
      throw error
    }
  }

  const logout = () => {
    user.value = null
    token.value = null
    isAuthenticated.value = false
    localStorage.removeItem('token')
    localStorage.removeItem('user_role')
  }

  return {
    user,
    token,
    isAuthenticated,
    login,
    restoreSession,
    logout
  }
})
