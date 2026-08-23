<template>
  <UApp>
    <component v-if="layout" :is="layout">
      <RouterView />
    </component>
    <RouterView v-else />
  </UApp>
  <NotificationStack />
</template>

<script setup>
import { computed, onMounted, onUnmounted } from "vue";
import { useRoute } from "vue-router";

import ClientLayout from "./layouts/ClientLayout.vue";
import StaffLayout from "./layouts/StaffLayout.vue";
import NotificationStack from "./components/NotificationStack.vue";
import { useAuthStore } from "./stores/auth";
import { useNotificationStore } from "./stores/notifications";

const auth = useAuthStore();
const notifications = useNotificationStore();
const route = useRoute();
const layout = computed(() => {
  if (!auth.isAuthenticated || route.meta.guestOnly) return null;
  return auth.isStaff ? StaffLayout : ClientLayout;
});

function expireSession() {
  auth.user = null;
  notifications.notify(
    "Your session has expired. Please sign in again.",
    "error",
  );
}

onMounted(() => window.addEventListener("auth:expired", expireSession));
onUnmounted(() => window.removeEventListener("auth:expired", expireSession));
</script>
