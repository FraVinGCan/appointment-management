<script setup>
import { useRouter } from "vue-router";
import AppPageHeader from "../components/AppPageHeader.vue";
import ServiceForm from "../components/ServiceForm.vue";
const router = useRouter();
const toast = useToast();
function saved() {
  toast.add({
    title: "Success",
    description: "Service created successfully.",
    color: "success",
  });
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
