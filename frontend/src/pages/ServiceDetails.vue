<script setup>
import { onMounted } from "vue";
import { useRoute } from "vue-router";
import AppError from "../components/AppError.vue";
import AppLoading from "../components/AppLoading.vue";
import AppNotFound from "../components/AppNotFound.vue";
import AppBreadcrumbs from "../components/AppBreadcrumbs.vue";
import AppPageHeader from "../components/AppPageHeader.vue";
import EnumBadge from "../components/EnumBadge.vue";
import { useServiceStore } from "../stores/services";
const route = useRoute();
const services = useServiceStore();
onMounted(() => services.fetch(route.params.id));
</script>
<template>
  <section class="space-y-6">
    <AppBreadcrumbs
      :items="[{ label: 'Services', to: '/services' }, { label: 'View' }]"
    />
    <AppPageHeader title="Service details">
      <template v-if="services.current" #actions>
        <UButton
          color="neutral"
          variant="outline"
          class="w-full sm:w-auto"
          :to="`/services/${services.current.id}/edit`"
          >Edit</UButton
        >
      </template>
    </AppPageHeader>
    <AppLoading v-if="services.isLoading" message="Loading service..." />
    <AppError
      v-else-if="services.error && services.errorStatus !== 404"
      :message="services.error"
    />
    <AppNotFound
      v-else-if="services.errorStatus === 404"
      resource="Service"
      back-to="/services"
    />
    <div
      v-else-if="services.current"
      class="max-w-2xl rounded-2xl border border-slate-800 bg-slate-900 p-4 sm:p-6"
    >
      <div class="flex flex-wrap items-start justify-between gap-3">
        <h2 class="text-xl sm:text-2xl font-semibold truncate">
          {{ services.current.name }}
        </h2>
        <span
          :class="
            services.current.active ? 'text-emerald-300' : 'text-slate-500'
          "
          ><EnumBadge
            :value="services.current.active ? 'Active' : 'Inactive'"
            kind="active"
        /></span>
      </div>
      <p class="mt-5 text-slate-400">
        {{ services.current.description || "No description" }}
      </p>
    </div>
  </section>
</template>
