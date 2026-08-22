import { defineStore } from 'pinia'

import * as authService from '../services/authService'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    isLoading: false,
    isInitialized: false,
    error: null,
    validationErrors: {},
  }),

  getters: {
    isAuthenticated: (state) => Boolean(state.user),
    isStaff: (state) => Boolean(state.user?.isStaff),
    isClient: (state) => Boolean(state.user?.client),
  },

  actions: {
    async initialize() {
      if (this.isInitialized) return

      try {
        this.user = await authService.currentUser()
      } catch (error) {
        if (error.response?.status !== 401) this.error = authService.errorMessage(error)
        this.user = null
      } finally {
        this.isInitialized = true
      }
    },

    async login(credentials) {
      return this.mutate(() => authService.login(credentials))
    },

    async register(payload) {
      return this.mutate(() => authService.registerClient(payload))
    },

    async logout() {
      this.isLoading = true
      this.error = null

      try {
        await authService.logout()
        this.user = null
      } catch (error) {
        this.error = authService.errorMessage(error)
        throw error
      } finally {
        this.isLoading = false
      }
    },

    async mutate(action) {
      this.isLoading = true
      this.error = null
      this.validationErrors = {}

      try {
        this.user = await action()
        this.isInitialized = true
        return this.user
      } catch (error) {
        this.error = authService.errorMessage(error)
        this.validationErrors = authService.validationErrors(error)
        throw error
      } finally {
        this.isLoading = false
      }
    },
  },
})
