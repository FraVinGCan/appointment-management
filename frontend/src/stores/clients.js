import { defineStore } from "pinia";

import * as clientService from "../services/clientService";
import { errorMessage, validationErrors } from "../services/error";

export const useClientStore = defineStore("clients", {
  state: () => ({
    items: [],
    pagination: null,
    current: null,
    isLoading: false,
    isSaving: false,
    error: null,
    validationErrors: {},
    errorStatus: 0,
    requestVersion: 0,
  }),

  actions: {
    async fetchList(params = {}) {
      const version = ++this.requestVersion;
      return this.run(async () => {
        const response = await clientService.list(params);
        if (version !== this.requestVersion) return this.items;
        this.items = response.data;
        this.pagination = response.meta;
        return this.items;
      });
    },
    async fetch(id) {
      return this.run(async () => {
        this.current = await clientService.get(id);
        return this.current;
      });
    },
    async create(payload) {
      return this.mutate(() => clientService.create(payload));
    },
    async update(id, payload) {
      return this.mutate(() => clientService.update(id, payload));
    },
    async deactivate(id) {
      return this.mutate(() => clientService.deactivate(id));
    },
    async activate(id) {
      return this.mutate(() => clientService.activate(id));
    },
    updateItem(item) {
      const index = this.items.findIndex((client) => client.id === item.id);
      if (index >= 0) this.items.splice(index, 1, item);
      if (this.current?.id === item.id) this.current = item;
    },
    async run(action) {
      this.isLoading = true;
      this.clearError();
      try {
        return await action();
      } catch (error) {
        this.setError(error);
        throw error;
      } finally {
        this.isLoading = false;
      }
    },
    async mutate(action) {
      this.isSaving = true;
      this.clearError();
      try {
        const item = await action();
        if (item?.id) this.current = item;
        return item;
      } catch (error) {
        this.setError(error);
        throw error;
      } finally {
        this.isSaving = false;
      }
    },
    clearError() {
      this.error = null;
      this.validationErrors = {};
      this.errorStatus = 0;
    },
    setError(error) {
      this.error = errorMessage(error);
      this.validationErrors = validationErrors(error);
      this.errorStatus = error.status || error.response?.status || 0;
    },
  },
});
