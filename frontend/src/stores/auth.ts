import { ref, computed } from 'vue'
import { defineStore } from 'pinia'
import api from '@/services/api'

interface User {
  id: number
  email: string
  firstName: string
  lastName: string
  roles: string[]
}

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(localStorage.getItem('token'))
  const user = ref<User | null>(null)

  const isAuthenticated = computed(() => !!token.value)
  const isAdmin = computed(() => user.value?.roles?.includes('ROLE_ADMIN') ?? false)
  const isAgent = computed(() => user.value?.roles?.includes('ROLE_AGENT') ?? false)
  const isAgentOrAdmin = computed(() => isAdmin.value || isAgent.value)

  function setToken(newToken: string | null) {
    token.value = newToken
    if (newToken) {
      localStorage.setItem('token', newToken)
    } else {
      localStorage.removeItem('token')
    }
  }

  async function fetchUser() {
    if (!token.value) return
    try {
      const { data } = await api.get('/me')
      user.value = data
    } catch {
      // don't logout — token may still be valid, /me might just be temporarily unavailable
    }
  }

  async function login(email: string, password: string) {
    const { data } = await api.post('/login', { email, password })
    setToken(data.token)
    await fetchUser()
  }

  async function register(email: string, password: string, firstName: string, lastName: string, inviteCode?: string, organizationName?: string, organizationSlug?: string) {
    await api.post('/register', { email, password, firstName, lastName, inviteCode, organizationName, organizationSlug })
  }

  function logout() {
    setToken(null)
    user.value = null
  }

  // Load user on store init if token exists
  if (token.value) {
    fetchUser()
  }

  return { token, user, isAuthenticated, isAdmin, isAgent, isAgentOrAdmin, login, register, logout, fetchUser }
})
