<template>
  <div class="min-h-screen bg-slate-950 text-slate-100">
    <header class="lg:hidden border-b border-slate-800 bg-slate-900/90">
      <div class="flex items-center justify-between px-4 py-3">
        <UButton
          variant="ghost"
          color="neutral"
          icon="i-lucide-menu"
          aria-label="Open menu"
          @click="sidebarOpen = true"
        />
        <span class="text-lg font-semibold">Appointment Desk</span>
        <UButton
          color="neutral"
          variant="outline"
          size="sm"
          :loading="auth.isLoading"
          @click="signOut"
        >
          Log out
        </UButton>
      </div>
    </header>
    <USidebar
      v-model:open="sidebarOpen"
      title="Appointment Desk"
      description="Admin workspace"
      collapsible
      class="z-30"
    >
      <template #default>
        <UNavigationMenu
          :items="links"
          orientation="vertical"
          highlight
          class="w-full"
        />
      </template>
      <template #footer>
        <UButton
          color="neutral"
          variant="outline"
          block
          :loading="auth.isLoading"
          @click="signOut"
          >Log out</UButton
        >
      </template>
    </USidebar>
    <main class="min-h-screen px-4 py-6 sm:px-6 sm:py-8 lg:pl-72">
      <div class="mx-auto max-w-7xl">
        <slot />
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, watch } from "vue";
import { useRouter, useRoute } from "vue-router";

import { useAuthStore } from "../stores/auth";

const auth = useAuthStore();
const router = useRouter();
const route = useRoute();
const links = [
  { label: "Dashboard", to: "/", icon: "i-lucide-house" },
  { label: "Appointments", to: "/appointments", icon: "i-lucide-calendar" },
  { label: "Clients", to: "/clients", icon: "i-lucide-users" },
  { label: "Services", to: "/services", icon: "i-lucide-briefcase-business" },
];

const sidebarOpen = ref(true);

watch(
  () => route.fullPath,
  () => {
    sidebarOpen.value = false;
  },
);

async function signOut() {
  await auth.logout();
  router.push("/login");
}
</script>
