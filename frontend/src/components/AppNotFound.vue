<template>
  <div class="flex flex-col items-center justify-center px-4 py-16 text-center">
    <div
      class="flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-800/70"
    >
      <UIcon name="i-lucide-search-x" class="text-4xl text-slate-400" />
    </div>
    <p class="mt-6 text-sm font-semibold uppercase tracking-[0.25em] text-cyan-400">
      404
    </p>
    <h1 class="mt-2 text-2xl font-semibold sm:text-3xl">
      {{ heading }}
    </h1>
    <p class="mt-3 max-w-md text-slate-400">
      {{ description }}
    </p>
    <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
      <UButton
        v-if="backTo"
        :to="backTo"
        :label="backLabel"
        :icon="backIcon"
        variant="outline"
      />
      <UButton
        v-if="homeTo"
        :to="homeTo"
        :label="homeLabel"
        :icon="homeIcon"
      />
    </div>
  </div>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
  resource: { type: String, default: "" },
  backTo: { type: String, default: "" },
  backLabel: { type: String, default: "Back to list" },
  backIcon: { type: String, default: "i-lucide-arrow-left" },
  homeTo: { type: String, default: "/" },
  homeLabel: { type: String, default: "Go to home" },
  homeIcon: { type: String, default: "i-lucide-house" },
});

const heading = computed(() =>
  props.resource ? `${props.resource} not found` : "Page not found",
);

const description = computed(() =>
  props.resource
    ? `The ${props.resource.toLowerCase()} you are looking for may have been removed or no longer exists.`
    : "The page you are looking for does not exist or may have been moved.",
);
</script>
