<script setup>
import { onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";

import AppointmentForm from "@/components/appointments/AppointmentForm.vue";
import AppError from "@/components/ui/AppError.vue";
import AppLoading from "@/components/ui/AppLoading.vue";
import AppNotFound from "@/components/ui/AppNotFound.vue";
import AppBreadcrumbs from "@/components/ui/AppBreadcrumbs.vue";
import AppPageHeader from "@/components/ui/AppPageHeader.vue";
import { useAppointmentStore } from "@/stores/appointments";

const route = useRoute();
const router = useRouter();
const appointments = useAppointmentStore();
const toast = useToast();
onMounted(() => appointments.fetch(route.params.id));
function saved() {
  toast.add({
    title: "Success",
    description: "Appointment updated successfully.",
    color: "success",
  });
  router.push(`/appointments/${route.params.id}`);
}
</script>

<template>
  <section class="space-y-6">
    <AppPageHeader
      :breadcrumbs="[
        { label: 'Appointments', to: '/appointments' },
        { label: 'View', to: `/appointments/${route.params.id}` },
        { label: 'Edit' },
      ]"
      title="Edit appointment"
    />
    <AppLoading
      v-if="appointments.isLoading"
      message="Loading appointment..."
    />
    <AppError
      v-else-if="appointments.error && appointments.errorStatus !== 404"
      :message="appointments.error"
    />
    <AppNotFound
      v-else-if="appointments.errorStatus === 404"
      resource="Appointment"
      back-to="/appointments"
    />
    <AppointmentForm
      v-else-if="appointments.current"
      :appointment="appointments.current"
      @saved="saved"
    />
  </section>
</template>
