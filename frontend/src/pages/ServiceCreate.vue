<script setup>
import { useRouter } from "vue-router";
import AppPageHeader from "../components/AppPageHeader.vue";
import ServiceForm from "../components/ServiceForm.vue";
import { useNotificationStore } from "../stores/notifications";
const router = useRouter();
const notifications = useNotificationStore();
function saved() {
  notifications.notify("Service created successfully.");
  router.push("/services");
}
</script>
<template>
  <section class="space-y-6">
    <AppPageHeader
      :breadcrumbs="[{ label: 'Services', to: '/services' }, { label: 'Add' }]"
      title="Add service"
    />
    <ServiceForm @saved="saved" />
  </section>
</template>
