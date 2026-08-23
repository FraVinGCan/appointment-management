<script setup>
import { useRouter } from "vue-router";

import AppPageHeader from "../components/AppPageHeader.vue";
import AppointmentForm from "../components/AppointmentForm.vue";
import { useNotificationStore } from "../stores/notifications";

const router = useRouter();
const notifications = useNotificationStore();
function saved() {
  notifications.notify("Appointment created successfully.");
  router.push("/appointments");
}
</script>

<template>
  <section class="space-y-6">
    <AppPageHeader
      :breadcrumbs="[
        { label: 'Appointments', to: '/appointments' },
        { label: 'Create' },
      ]"
      title="Create appointment"
    />
    <AppointmentForm @saved="saved" />
  </section>
</template>
