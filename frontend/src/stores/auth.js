import { defineStore } from "pinia";

import * as authService from "../services/authService";

export const useAuthStore = defineStore("auth", {
  state: () => ({
    user: null,
    isLoading: false,
    isInitialized: false,
    error: null,
    validationErrors: {},
    errorStatus: 0,
  }),

  getters: {
    isAuthenticated: (state) => Boolean(state.user),
    isAdmin: (state) => Boolean(state.user?.isAdmin),
    isClient: (state) => Boolean(state.user?.client),
  },

  actions: {
    async initialize() {
      if (this.isInitialized) return;

      try {
        this.user = await authService.currentUser();
      } catch (error) {
        if (error.response?.status !== 401)
          this.error = authService.errorMessage(error);
        this.user = null;
      } finally {
        this.isInitialized = true;
      }
    },

    async login(credentials) {
      const user = await this.mutate(() => authService.login(credentials));
      useToast().add({
        title: "Success",
        description: "Signed in successfully.",
        color: "success",
      });
      return user;
    },

    async register(payload) {
      const user = await this.mutate(() => authService.registerClient(payload));
      useToast().add({
        title: "Success",
        description: "Your client account was created successfully.",
        color: "success",
      });
      return user;
    },

    async logout() {
      this.isLoading = true;
      this.error = null;

      try {
        await authService.logout();
        this.user = null;
        useToast().add({
          title: "Success",
          description: "You have been signed out.",
          color: "success",
        });
      } catch (error) {
        this.error = authService.errorMessage(error);
        throw error;
      } finally {
        this.isLoading = false;
      }
    },

    async mutate(action) {
      this.isLoading = true;
      this.error = null;
      this.validationErrors = {};
      this.errorStatus = 0;

      try {
        this.user = await action();
        this.isInitialized = true;
        return this.user;
      } catch (error) {
        this.error = authService.errorMessage(error);
        this.validationErrors = authService.validationErrors(error);
        this.errorStatus = error.status || error.response?.status || 0;
        throw error;
      } finally {
        this.isLoading = false;
      }
    },
  },
});
