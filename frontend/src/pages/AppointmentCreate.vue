<script setup>
import { useRouter } from "vue-router";

import AppPageHeader from "../components/AppPageHeader.vue";
import AppointmentForm from "../components/AppointmentForm.vue";

const router = useRouter();
const toast = useToast();
function saved() {
  toast.add({
    title: "Success",
    description: "Appointment created successfully.",
    color: "success",
  });
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
