<template>
  <UApp :toaster="{ position: 'top-right' }">
    <template v-if="auth.isInitialized">
      <component :is="layout">
        <RouterView />
      </component>
    </template>
    <div v-else class="flex h-screen items-center justify-center">
      <svg
        class="h-8 w-8 animate-spin text-primary"
        xmlns="http://www.w3.org/2000/svg"
        fill="none"
        viewBox="0 0 24 24"
      >
        <circle
          class="opacity-25"
          cx="12"
          cy="12"
          r="10"
          stroke="currentColor"
          stroke-width="4"
        />
        <path
          class="opacity-75"
          fill="currentColor"
          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
        />
      </svg>
    </div>
  </UApp>
</template>

<script setup>
import { computed, onMounted, onUnmounted } from "vue";
import { useRoute } from "vue-router";

import AdminLayout from "./layouts/AdminLayout.vue";
import ClientLayout from "./layouts/ClientLayout.vue";
import GuestLayout from "./layouts/GuestLayout.vue";
import { useAuthStore } from "./stores/auth";

const auth = useAuthStore();
const toast = useToast();
const route = useRoute();
const layout = computed(() => {
  if (!auth.isAuthenticated) return GuestLayout;
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
