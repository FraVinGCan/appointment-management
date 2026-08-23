<script setup>
import { useRouter } from "vue-router";
import AppPageHeader from "../components/AppPageHeader.vue";
import ClientForm from "../components/ClientForm.vue";
import { useNotificationStore } from "../stores/notifications";
const router = useRouter();
const notifications = useNotificationStore();
function saved() {
  notifications.notify("Client created successfully.");
  router.push("/clients");
}
</script>
<template>
  <section class="space-y-6">
    <AppPageHeader
      :breadcrumbs="[{ label: 'Clients', to: '/clients' }, { label: 'Add' }]"
      title="Add client"
    />
    <ClientForm @saved="saved" />
  </section>
</template>
