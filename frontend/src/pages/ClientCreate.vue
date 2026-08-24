<script setup>
import { useRouter } from "vue-router";
import AppPageHeader from "../components/AppPageHeader.vue";
import ClientForm from "../components/ClientForm.vue";
const router = useRouter();
const toast = useToast();
function saved() {
  toast.add({
    title: "Success",
    description: "Client created successfully.",
    color: "success",
  });
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
