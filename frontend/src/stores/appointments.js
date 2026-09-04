import { defineStore } from "pinia";

import * as appointmentService from "../services/appointmentService";
import { errorMessage, validationErrors } from "../services/error";

export const useAppointmentStore = defineStore("appointments", {
  state: () => ({
    items: [],
    pagination: null,
    clientDashboard: null,
    calendarEvents: [],
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
        const response = await appointmentService.list(params);
        if (version !== this.requestVersion) return this.items;
        this.items = response.data;
        this.pagination = response.meta;
        return this.items;
      });
    },

    async fetchCalendar(params = {}) {
      return this.run(async () => {
        const response = await appointmentService.calendar(params);
        this.calendarEvents = response.data;
        return this.calendarEvents;
      });
    },

    async fetchClientList(params = {}) {
      const version = ++this.requestVersion;
      return this.run(async () => {
        const response = await appointmentService.listClient(params);
        if (version !== this.requestVersion) return this.items;
        this.items = response.data;
        this.pagination = response.meta;
        return this.items;
      });
    },

    async fetchClientDashboard() {
      return this.run(async () => {
        const response = await appointmentService.getClientDashboard();
        this.clientDashboard = response.data;
        return this.clientDashboard;
      });
    },

    async fetch(id) {
      return this.run(async () => {
        this.current = await appointmentService.get(id);
        return this.current;
      });
    },

    async create(payload) {
      return this.mutate(() => appointmentService.create(payload));
    },

    async createBooking(payload) {
      return this.mutate(() => appointmentService.createBooking(payload));
    },

    async update(id, payload) {
      return this.mutate(() => appointmentService.update(id, payload));
    },

    async remove(id) {
      return this.mutate(() => appointmentService.remove(id));
    },

    async confirm(id) {
      return this.mutate(() => appointmentService.confirm(id));
    },

    async complete(id) {
      return this.mutate(() => appointmentService.complete(id));
    },

    async cancel(id) {
      return this.mutate(() => appointmentService.cancel(id));
    },

    async cancelClient(id) {
      return this.mutate(() => appointmentService.cancelClient(id));
    },

    updateItem(item) {
      const index = this.items.findIndex((current) => current.id === item.id);
      if (index >= 0) this.items.splice(index, 1, item);
      if (this.current?.id === item.id) this.current = item;
    },

    removeItem(id) {
      this.items = this.items.filter((item) => item.id !== id);
      if (this.current?.id === id) this.current = null;
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
