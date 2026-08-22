import { defineStore } from 'pinia'

import * as serviceApi from '../services/serviceService'
import { errorMessage, validationErrors } from '../services/error'

export const useServiceStore = defineStore('services', {
  state: () => ({
    items: [],
    current: null,
    isLoading: false,
    isSaving: false,
    error: null,
    validationErrors: {},
    errorStatus: 0,
  }),

  actions: {
    async fetchActive() {
      return this.run(async () => {
        this.items = await serviceApi.listActive()
        return this.items
      })
    },
    async fetchAll() {
      return this.run(async () => {
        this.items = await serviceApi.listAll()
        return this.items
      })
    },
    async fetch(id) {
      return this.run(async () => {
        this.current = await serviceApi.get(id)
        return this.current
      })
    },
    async create(payload) { return this.mutate(() => serviceApi.create(payload)) },
    async update(id, payload) { return this.mutate(() => serviceApi.update(id, payload)) },
    async deactivate(id) { return this.mutate(() => serviceApi.deactivate(id)) },
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
      this.errorStatus = 0
    },
    setError(error) {
      this.error = errorMessage(error)
      this.validationErrors = validationErrors(error)
      this.errorStatus = error.status || error.response?.status || 0
    },
  },
})
