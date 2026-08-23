<template>
  <AppBreadcrumbs v-if="breadcrumbs.length" :items="breadcrumbs" />
  <div class="flex flex-wrap items-end justify-between gap-4">
    <div>
      <h1 class="text-2xl sm:text-3xl font-semibold">{{ title }}</h1>
      <p v-if="description" class="mt-2 text-slate-400">
        {{ description }}
      </p>
    </div>
    <div v-if="$slots.actions" class="flex flex-wrap gap-2">
      <slot name="actions" />
    </div>
  </div>
</template>

<script setup>
import AppBreadcrumbs from "./AppBreadcrumbs.vue";

defineProps({
  title: { type: String, required: true },
  description: { type: String, default: "" },
  breadcrumbs: { type: Array, default: () => [] },
});
</script>
