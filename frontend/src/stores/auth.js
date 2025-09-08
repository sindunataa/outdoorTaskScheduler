import { defineStore } from 'pinia'
import api from '@/lib/api'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: localStorage.getItem('auth_token'),
    isAuthenticated: false
  }),

  getters: {
    isLoggedIn: (state) => !!state.token && !!state.user
  },

  actions: {
    async login(credentials) {
      try {
        const response = await api.post('/login', credentials)
        this.token = response.data.token
        this.user = response.data.user
        this.isAuthenticated = true
        
        localStorage.setItem('auth_token', this.token)
        return { success: true }
      } catch (error) {
        return { 
          success: false, 
          errors: error.response?.data?.errors || { email: ['Login failed'] }
        }
      }
    },

    async register(userData) {
      try {
        const response = await api.post('/register', userData)
        this.token = response.data.token
        this.user = response.data.user
        this.isAuthenticated = true
        
        localStorage.setItem('auth_token', this.token)
        return { success: true }
      } catch (error) {
        return { 
          success: false, 
          errors: error.response?.data?.errors || { email: ['Registration failed'] }
        }
      }
    },

    async logout() {
      try {
        await api.post('/logout')
      } catch (error) {
        console.error('Logout error:', error)
      } finally {
        this.token = null
        this.user = null
        this.isAuthenticated = false
        localStorage.removeItem('auth_token')
      }
    },

    async fetchUser() {
      if (!this.token) return

      try {
        const response = await api.get('/me')
        this.user = response.data
        this.isAuthenticated = true
      } catch (error) {
        this.logout()
      }
    }
  }
})