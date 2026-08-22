<script setup>
import { onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import AppError from "../components/AppError.vue";
import AppLoading from "../components/AppLoading.vue";
import AppNotFound from "../components/AppNotFound.vue";
import AppBreadcrumbs from "../components/AppBreadcrumbs.vue";
import ServiceForm from "../components/ServiceForm.vue";
import { useNotificationStore } from "../stores/notifications";
import { useServiceStore } from "../stores/services";
const route = useRoute();
const router = useRouter();
const services = useServiceStore();
const notifications = useNotificationStore();
onMounted(() => services.fetch(route.params.id));
function saved() {
  notifications.notify("Service updated successfully.");
  router.push(`/services/${route.params.id}`);
}
</script>
<template>
  <section class="space-y-6">
    <AppBreadcrumbs :items="[{ label: 'Services', to: '/services' }, { label: 'View', to: `/services/${route.params.id}` }, { label: 'Edit' }]" />
    <div>
      <p
        class="text-xs font-semibold uppercase tracking-[0.25em] text-cyan-400">
        Staff workspace
      </p>
      <h1 class="mt-2 text-3xl font-semibold">Edit service</h1>
    </div>
    <AppLoading
      v-if="services.isLoading"
      message="Loading service..." /><AppError
      v-else-if="services.error && services.errorStatus !== 404"
      :message="services.error" /><AppNotFound
      v-else-if="services.errorStatus === 404"
      resource="Service"
      back-to="/services" /><ServiceForm
      v-else-if="services.current"
      :service="services.current"
      @saved="saved" />
  </section>
</template>
