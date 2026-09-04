<script setup>
import { onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import AppError from "@/components/ui/AppError.vue";
import AppLoading from "@/components/ui/AppLoading.vue";
import AppNotFound from "@/components/ui/AppNotFound.vue";
import AppBreadcrumbs from "@/components/ui/AppBreadcrumbs.vue";
import AppPageHeader from "@/components/ui/AppPageHeader.vue";
import ServiceForm from "@/components/services/ServiceForm.vue";
import { useServiceStore } from "@/stores/services";
const route = useRoute();
const router = useRouter();
const services = useServiceStore();
const toast = useToast();
onMounted(() => services.fetch(route.params.id));
function saved() {
  toast.add({
    title: "Success",
    description: "Service updated successfully.",
    color: "success",
  });
  router.push(`/services/${route.params.id}`);
}
</script>
<template>
  <section class="space-y-6">
    <AppBreadcrumbs
      :items="[
        { label: 'Services', to: '/services' },
        { label: 'View', to: `/services/${route.params.id}` },
        { label: 'Edit' },
      ]"
    />
    <AppPageHeader title="Edit service" />
    <AppLoading
      v-if="services.isLoading && !services.current"
      message="Loading service..."
    />
    <AppError
      v-else-if="services.error && services.errorStatus !== 404"
      :message="services.error"
    />
    <AppNotFound
      v-else-if="services.errorStatus === 404"
      resource="Service"
      back-to="/services"
    />
    <ServiceForm
      v-else-if="services.current"
      :service="services.current"
      @saved="saved"
    />
  </section>
</template>
