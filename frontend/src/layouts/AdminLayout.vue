<template>
  <div
    class="flex min-h-screen text-default"
    :class="isDesktop ? 'bg-neutral-50 dark:bg-neutral-950' : 'bg-default'"
  >
    <USidebar
      v-model:open="sidebarOpen"
      :variant="isDesktop ? 'inset' : 'sidebar'"
      collapsible="icon"
      title="Appointment Desk"
      description="Admin workspace"
    >
      <template #header="{ state, close }">
        <div class="flex w-full items-center justify-between">
          <div class="flex items-center gap-2">
            <UIcon name="i-lucide-calendar-check" class="size-8 shrink-0 text-primary" />
            <span v-if="state === 'expanded'" class="truncate text-lg font-semibold">
              Appointment Desk
            </span>
          </div>
          <UButton
            icon="i-lucide-x"
            color="neutral"
            variant="ghost"
            aria-label="Close menu"
            class="lg:hidden"
            @click="close"
          />
        </div>
      </template>

      <template #default="{ state }">
        <UNavigationMenu
          :items="getLinks(state)"
          orientation="vertical"
          :ui="{ link: 'p-1.5 overflow-hidden' }"
        />
      </template>
    </USidebar>

    <div
      class="flex flex-1 flex-col overflow-hidden bg-default"
      :class="isDesktop && 'm-4 rounded-xl shadow-sm ring ring-default lg:ms-0'"
    >
      <div
        class="h-(--ui-header-height) shrink-0 flex items-center justify-between px-4 border-b border-default"
      >
        <UButton
          icon="i-lucide-panel-left"
          color="neutral"
          variant="ghost"
          aria-label="Toggle sidebar"
          @click="sidebarOpen = !sidebarOpen"
        />
        <ProfileMenu />
      </div>

      <div class="flex-1 p-4 overflow-y-auto">
        <slot />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onBeforeUnmount } from "vue";
import { useStorage } from "@vueuse/core";

import ProfileMenu from "@/components/ProfileMenu.vue";

const sidebarOpen = useStorage("sidebar-open", true);

const mediaQuery = window.matchMedia("(min-width: 1024px)");
const isDesktop = ref(mediaQuery.matches);

function onMediaChange(event) {
  isDesktop.value = event.matches;
}

mediaQuery.addEventListener("change", onMediaChange);

onBeforeUnmount(() => mediaQuery.removeEventListener("change", onMediaChange));

const adminLinks = [
  { label: "Dashboard", to: "/", icon: "i-lucide-house" },
  { label: "Appointments", to: "/appointments", icon: "i-lucide-calendar" },
  { label: "Clients", to: "/clients", icon: "i-lucide-users" },
  { label: "Services", to: "/services", icon: "i-lucide-briefcase-business" },
];

const collapsedLinks = [
  { icon: "i-lucide-house", to: "/", tooltip: "Dashboard" },
  { icon: "i-lucide-calendar", to: "/appointments", tooltip: "Appointments" },
  { icon: "i-lucide-users", to: "/clients", tooltip: "Clients" },
  {
    icon: "i-lucide-briefcase-business",
    to: "/services",
    tooltip: "Services",
  },
];

function getLinks(state) {
  return state === "collapsed" ? collapsedLinks : adminLinks;
}

</script>
