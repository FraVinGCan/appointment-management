<template>
  <UDropdownMenu :items="items" :content="{ align: 'end' }">
    <UButton
      icon="i-lucide-circle-user-round"
      color="neutral"
      variant="ghost"
      aria-label="Open profile menu"
    />
    <template #item-trailing="{ item }">
      <UIcon v-if="item.active" name="i-lucide-check" class="size-4 text-primary" />
    </template>
  </UDropdownMenu>
</template>

<script setup>
import { computed } from "vue";
import { useRouter } from "vue-router";
import { useColorMode } from "@vueuse/core";

import { useAuthStore } from "@/stores/auth";

const auth = useAuthStore();
const router = useRouter();
const colorMode = useColorMode();

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
      label: "Light",
      icon: "i-lucide-sun",
      active: colorMode.store.value === "light",
      onSelect: (e) => { e.preventDefault(); colorMode.value = "light"; },
    },
    {
      label: "Dark",
      icon: "i-lucide-moon",
      active: colorMode.store.value === "dark",
      onSelect: (e) => { e.preventDefault(); colorMode.value = "dark"; },
    },
    {
      label: "System",
      icon: "i-lucide-monitor",
      active: colorMode.store.value === "auto",
      onSelect: (e) => { e.preventDefault(); colorMode.value = "auto"; },
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