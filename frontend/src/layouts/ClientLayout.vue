<template>
  <div class="min-h-screen bg-slate-950 text-slate-100">
    <USidebar
      v-model:open="sidebarOpen"
      title="Appointment Desk"
      description="Client workspace"
      collapsible="offcanvas"
      close
      mode="slideover"
      class="z-30 lg:hidden"
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
    <header class="border-b border-slate-800 bg-slate-900/90">
      <div
        class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-3 px-4 py-3 sm:px-6 sm:py-4"
      >
        <UButton to="/" variant="link" size="lg">Appointment Desk</UButton>
        <div class="flex items-center gap-2">
          <UButton
            class="lg:hidden"
            variant="ghost"
            color="neutral"
            icon="i-lucide-menu"
            aria-label="Open menu"
            @click="sidebarOpen = true"
          />
          <nav
            class="hidden items-center gap-2 lg:flex"
            aria-label="Client navigation"
          >
            <UNavigationMenu
              :items="links"
              orientation="horizontal"
              highlight
            />
            <UButton
              color="neutral"
              variant="outline"
              size="sm"
              @click="signOut"
              >Log out</UButton
            >
          </nav>
        </div>
      </div>
    </header>
    <main class="mx-auto max-w-5xl px-4 py-6 sm:px-6 sm:py-8"><slot /></main>
  </div>
</template>

<script setup>
import { ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";

import { useAuthStore } from "../stores/auth";

const auth = useAuthStore();
const route = useRoute();
const router = useRouter();
const sidebarOpen = ref(false);
const links = [
  { label: "Overview", to: "/", icon: "i-lucide-house" },
  { label: "Book an appointment", to: "/book", icon: "i-lucide-calendar-plus" },
  {
    label: "My appointments",
    to: "/client/appointments",
    icon: "i-lucide-calendar-check",
  },
];

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
