import { defineStore } from "pinia";

import * as dashboardService from "../services/dashboardService";
import { errorMessage } from "../services/error";

export const useDashboardStore = defineStore("dashboard", {
  state: () => ({
    stats: null,
    isLoading: false,
    error: null,
    errorStatus: 0,
  }),

  actions: {
    async fetchStats() {
      this.isLoading = true;
      this.error = null;
      this.errorStatus = 0;

      try {
        const response = await dashboardService.getStats();
        this.stats = response.data;
        return this.stats;
      } catch (error) {
        this.error = errorMessage(error);
        this.errorStatus = error.status || error.response?.status || 0;
        throw error;
      } finally {
        this.isLoading = false;
      }
    },
  },
});
