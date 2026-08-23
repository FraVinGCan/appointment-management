<script setup>
import { useRouter } from "vue-router";
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
    <div>
      <p
        class="text-xs font-semibold uppercase tracking-[0.25em] text-cyan-400"
      >
        Staff workspace
      </p>
      <h1 class="mt-2 text-2xl sm:text-3xl font-semibold">Add client</h1>
    </div>
    <ClientForm @saved="saved" />
  </section>
</template>
