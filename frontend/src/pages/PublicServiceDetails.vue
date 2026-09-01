<script setup>
import { onMounted } from "vue";
import { useRoute } from "vue-router";

import AppError from "../components/AppError.vue";
import AppLoading from "../components/AppLoading.vue";
import AppNotFound from "../components/AppNotFound.vue";
import { useAuthStore } from "../stores/auth";
import { useServiceStore } from "../stores/services";

const route = useRoute();
const auth = useAuthStore();
const services = useServiceStore();

onMounted(() => services.fetch(route.params.id));
</script>

<template>
  <div class="space-y-6">
    <UButton to="/marketplace" variant="link" icon="i-lucide-arrow-left">
      Back to marketplace
    </UButton>

      <AppLoading v-if="services.isLoading" message="Loading service..." />
      <AppError
        v-else-if="services.error && services.errorStatus !== 404"
        :message="services.error"
        :retry="true"
        @retry="services.fetch(route.params.id)"
      />
      <AppNotFound
        v-else-if="services.errorStatus === 404"
        resource="Service"
        back-to="/marketplace"
      />
      <UCard v-else-if="services.current" variant="subtle">
        <div class="flex flex-wrap items-start justify-between gap-4">
          <div>
            <p class="text-sm font-semibold uppercase tracking-[0.25em] text-primary">
              Service details
            </p>
            <h1 class="mt-3 text-2xl font-semibold sm:text-3xl">
              {{ services.current.name }}
            </h1>
          </div>
          <UBadge v-if="services.current.category" color="primary" variant="subtle">
            {{ services.current.category }}
          </UBadge>
        </div>

        <p v-if="services.current.shortDescription" class="mt-5 text-lg text-muted">
          {{ services.current.shortDescription }}
        </p>
        <p class="mt-4 whitespace-pre-line text-muted">
          {{ services.current.description || "No description provided for this service yet." }}
        </p>

        <UButton
          class="mt-8"
          :to="
            auth.isClient
              ? { name: 'client-service-details', params: { id: services.current.id } }
              : { path: '/login', query: { redirect: `/client/services/${services.current.id}` } }
          "
        >
          {{ auth.isClient ? "Book this service" : "Sign in to book" }}
        </UButton>
      </UCard>
  </div>
</template>
