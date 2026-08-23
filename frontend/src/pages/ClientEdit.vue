<script setup>
import { onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import ClientForm from "../components/ClientForm.vue";
import AppError from "../components/AppError.vue";
import AppLoading from "../components/AppLoading.vue";
import AppNotFound from "../components/AppNotFound.vue";
import AppBreadcrumbs from "../components/AppBreadcrumbs.vue";
import { useClientStore } from "../stores/clients";
import { useNotificationStore } from "../stores/notifications";
const route = useRoute();
const router = useRouter();
const clients = useClientStore();
const notifications = useNotificationStore();
onMounted(() => clients.fetch(route.params.id));
function saved() {
  notifications.notify("Client updated successfully.");
  router.push(`/clients/${route.params.id}`);
}
</script>
<template>
  <section class="space-y-6">
    <AppBreadcrumbs
      :items="[
        { label: 'Clients', to: '/clients' },
        { label: 'View', to: `/clients/${route.params.id}` },
        { label: 'Edit' },
      ]"
    />
    <div>
      <p
        class="text-xs font-semibold uppercase tracking-[0.25em] text-cyan-400"
      >
        Staff workspace
      </p>
      <h1 class="mt-2 text-2xl sm:text-3xl font-semibold">Edit client</h1>
    </div>
    <AppLoading v-if="clients.isLoading" message="Loading client..." />
    <AppError
      v-else-if="clients.error && clients.errorStatus !== 404"
      :message="clients.error"
    />
    <AppNotFound
      v-else-if="clients.errorStatus === 404"
      resource="Client"
      back-to="/clients"
    />
    <ClientForm
      v-else-if="clients.current"
      :client="clients.current"
      @saved="saved"
    />
  </section>
</template>
