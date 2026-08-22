import { defineStore } from 'pinia'

import * as clientService from '../services/clientService'
import { errorMessage, validationErrors } from '../services/error'

export const useClientStore = defineStore('clients', {
  state: () => ({
    items: [],
    pagination: null,
    current: null,
    isLoading: false,
    isSaving: false,
    error: null,
    validationErrors: {},
  }),

  actions: {
    async fetchList(params = {}) {
      return this.run(async () => {
        const response = await clientService.list(params)
        this.items = response.data
        this.pagination = response.meta
        return this.items
      })
    },
    async fetch(id) {
      return this.run(async () => {
        this.current = await clientService.get(id)
        return this.current
      })
    },
    async create(payload) { return this.mutate(() => clientService.create(payload)) },
    async update(id, payload) { return this.mutate(() => clientService.update(id, payload)) },
    async deactivate(id) { return this.mutate(() => clientService.deactivate(id)) },
    async run(action) {
      this.isLoading = true
      this.clearError()
      try {
        return await action()
      } catch (error) {
        this.setError(error)
        throw error
      } finally {
        this.isLoading = false
      }
    },
    async mutate(action) {
      this.isSaving = true
      this.clearError()
      try {
        const item = await action()
        if (item?.id) this.current = item
        return item
      } catch (error) {
        this.setError(error)
        throw error
      } finally {
        this.isSaving = false
      }
    },
    clearError() {
      this.error = null
      this.validationErrors = {}
    },
    setError(error) {
      this.error = errorMessage(error)
      this.validationErrors = validationErrors(error)
    },
  },
})
