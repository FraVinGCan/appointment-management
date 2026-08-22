<script setup>
import { onMounted } from "vue";
import { useRoute } from "vue-router";
import AppError from "../components/AppError.vue";
import AppLoading from "../components/AppLoading.vue";
import AppNotFound from "../components/AppNotFound.vue";
import { useServiceStore } from "../stores/services";
const route = useRoute();
const services = useServiceStore();
onMounted(() => services.fetch(route.params.id));
</script>
<template>
  <section class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
      <div>
        <p
          class="text-xs font-semibold uppercase tracking-[0.25em] text-cyan-400">
          Staff workspace
        </p>
        <h1 class="mt-2 text-3xl font-semibold">Service details</h1>
      </div>
      <RouterLink
        v-if="services.current"
        class="rounded-lg border border-slate-700 px-4 py-2 text-sm"
        :to="`/services/${services.current.id}/edit`"
        >Edit</RouterLink
      >
    </div>
    <AppLoading
      v-if="services.isLoading"
      message="Loading service..." /><AppError
      v-else-if="services.error && services.errorStatus !== 404"
      :message="services.error" />
    <AppNotFound
      v-else-if="services.errorStatus === 404"
      resource="Service"
      back-to="/services" />
    <div
      v-else-if="services.current"
      class="max-w-2xl rounded-2xl border border-slate-800 bg-slate-900 p-6">
      <div class="flex items-start justify-between gap-3">
        <h2 class="text-2xl font-semibold">{{ services.current.name }}</h2>
        <span
          :class="
            services.current.active ? 'text-emerald-300' : 'text-slate-500'
          "
          >{{ services.current.active ? "Active" : "Inactive" }}</span
        >
      </div>
      <p class="mt-5 text-slate-400">
        {{ services.current.description || "No description" }}
      </p>
      <p class="mt-5 border-t border-slate-800 pt-5 text-sm">
        Duration: {{ services.current.durationMinutes }} minutes
      </p>
    </div>
  </section>
</template>
