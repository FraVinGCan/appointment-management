<script setup>
import { onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";

import AppointmentForm from "../components/AppointmentForm.vue";
import AppError from "../components/AppError.vue";
import AppLoading from "../components/AppLoading.vue";
import AppNotFound from "../components/AppNotFound.vue";
import AppBreadcrumbs from "../components/AppBreadcrumbs.vue";
import { useAppointmentStore } from "../stores/appointments";
import { useNotificationStore } from "../stores/notifications";

const route = useRoute();
const router = useRouter();
const appointments = useAppointmentStore();
const notifications = useNotificationStore();
onMounted(() => appointments.fetch(route.params.id));
function saved() {
  notifications.notify("Appointment updated successfully.");
  router.push(`/appointments/${route.params.id}`);
}
</script>

<template>
  <section class="space-y-6">
    <AppBreadcrumbs
      :items="[
        { label: 'Appointments', to: '/appointments' },
        { label: 'View', to: `/appointments/${route.params.id}` },
        { label: 'Edit' },
      ]"
    />
    <div>
      <p
        class="text-xs font-semibold uppercase tracking-[0.25em] text-cyan-400"
      >
        Staff workspace
      </p>
      <h1 class="mt-2 text-2xl sm:text-3xl font-semibold">Edit appointment</h1>
    </div>
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
