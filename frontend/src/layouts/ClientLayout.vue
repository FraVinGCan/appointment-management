<template>
  <div class="flex min-h-screen flex-col bg-default text-default">
    <UHeader title="Appointment Desk" to="/">
      <UNavigationMenu :items="links" />

      <template #right>
        <ProfileMenu />
      </template>

      <template #body>
        <UNavigationMenu
          :items="links"
          orientation="vertical"
          class="-mx-2.5"
        />
      </template>
    </UHeader>

    <main class="flex-1 py-6 sm:py-8">
      <UContainer>
        <slot />
      </UContainer>
    </main>
  </div>
</template>

<script setup>
import ProfileMenu from "../components/ProfileMenu.vue";

const links = [
  { label: "Overview", to: "/" },
  {
    label: "Service marketplace",
    to: "/client/marketplace",
  },
  {
    label: "My appointments",
    to: "/client/appointments",
  },
];
</script>
