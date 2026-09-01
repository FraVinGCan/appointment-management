<template>
  <UDropdownMenu :items="items" :content="{ align: 'end' }">
    <UButton
      icon="i-lucide-circle-user-round"
      color="neutral"
      variant="ghost"
      aria-label="Open profile menu"
    />
  </UDropdownMenu>
</template>

<script setup>
import { computed } from "vue";
import { useRouter } from "vue-router";

import { useAuthStore } from "../stores/auth";

const auth = useAuthStore();
const router = useRouter();

const items = computed(() => [
  [
    {
      type: "label",
      label: auth.user?.name || "User",
      description: auth.user?.email || "",
    },
  ],
  [
    {
      label: "Log out",
      icon: "i-lucide-log-out",
      color: "error",
      disabled: auth.isLoading,
      onSelect: signOut,
    },
  ],
]);

async function signOut() {
  await auth.logout();
  router.push("/login");
}
</script>