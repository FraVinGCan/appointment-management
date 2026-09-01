<template>
  <div class="flex min-h-screen flex-col bg-default text-default">
    <UHeader title="Appointment Desk" to="/marketplace">
      <UNavigationMenu :items="links" />

      <template #right>
        <UButton
          v-if="showSignIn"
          to="/login"
          size="sm"
          trailing-icon="i-lucide-log-in"
        >
          Sign in
        </UButton>
      </template>

      <template #body>
        <UNavigationMenu
          :items="links"
          orientation="vertical"
          class="-mx-2.5"
        />
      </template>
    </UHeader>

    <UContainer class="flex flex-1 flex-col py-10 sm:py-12">
      <slot />
    </UContainer>

    <UFooter>
      <template #left>
        <div class="flex items-center gap-2">
          <UIcon name="i-lucide-calendar-check" class="size-5 text-primary" />
          <span class="text-sm font-semibold">Appointment Desk</span>
        </div>
      </template>

      <template #right>
        <p class="text-sm text-muted">
          &copy; {{ year }} Appointment Desk. All rights reserved.
        </p>
      </template>
    </UFooter>
  </div>
</template>

<script setup>
import { computed } from "vue";
import { useRoute } from "vue-router";

const route = useRoute();

const links = [{ label: "Marketplace", to: "/marketplace" }];

const showSignIn = computed(() => route.name !== "login");

const year = new Date().getFullYear();
</script>