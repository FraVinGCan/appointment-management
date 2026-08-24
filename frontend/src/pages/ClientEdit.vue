<script setup>
import { onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import ClientForm from "../components/ClientForm.vue";
import AppError from "../components/AppError.vue";
import AppLoading from "../components/AppLoading.vue";
import AppNotFound from "../components/AppNotFound.vue";
import AppBreadcrumbs from "../components/AppBreadcrumbs.vue";
import AppPageHeader from "../components/AppPageHeader.vue";
import { useClientStore } from "../stores/clients";
const route = useRoute();
const router = useRouter();
const clients = useClientStore();
const toast = useToast();
onMounted(() => clients.fetch(route.params.id));
function saved() {
  toast.add({
    title: "Success",
    description: "Client updated successfully.",
    color: "success",
  });
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
    <AppPageHeader title="Edit client" />
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
