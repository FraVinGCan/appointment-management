<template>
  <UApp :toaster="{ position: 'top-right' }">
    <component v-if="layout" :is="layout">
      <RouterView />
    </component>
    <RouterView v-else />
  </UApp>
</template>

<script setup>
import { computed, onMounted, onUnmounted } from "vue";
import { useRoute } from "vue-router";

import ClientLayout from "./layouts/ClientLayout.vue";
import AdminLayout from "./layouts/AdminLayout.vue";
import { useAuthStore } from "./stores/auth";

const auth = useAuthStore();
const toast = useToast();
const route = useRoute();
const layout = computed(() => {
  if (!auth.isAuthenticated || route.meta.guestOnly) return null;
  return auth.isAdmin ? AdminLayout : ClientLayout;
});

function expireSession() {
  if (!auth.user) return;
  auth.user = null;
  toast.add({
    title: "Request failed",
    description: "Your session has expired. Please sign in again.",
    color: "error",
  });
}

onMounted(() => window.addEventListener("auth:expired", expireSession));
onUnmounted(() => window.removeEventListener("auth:expired", expireSession));
</script>
