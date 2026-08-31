<template>
  <UCard v-if="variant !== 'inline'" variant="subtle" class="text-center p-6">
    <UIcon :name="displayIcon" class="mx-auto mb-3 text-4xl text-slate-400" />
    <p class="text-sm text-muted">{{ message }}</p>
    <UButton
      v-if="action.to || action.onClick"
      :to="action.to"
      :label="action.label"
      :icon="action.icon"
      variant="outline"
      class="mt-3"
      @click="action.onClick"
    />
    <slot />
  </UCard>
  <div v-else class="text-center text-sm text-muted p-4">
    <UIcon :name="displayIcon" class="mx-auto mb-3 text-4xl text-slate-400" />
    <p>{{ message }}</p>
    <UButton
      v-if="action.to || action.onClick"
      :to="action.to"
      :label="action.label"
      :icon="action.icon"
      variant="outline"
      class="mt-3"
      @click="action.onClick"
    />
    <slot />
  </div>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
  message: { type: String, default: "No results found." },
  variant: { type: String, default: "card" },
  icon: { type: String, default: "" },
  action: { type: Object, default: () => ({}) },
});

const displayIcon = computed(() => props.icon || "i-lucide-inbox");
</script>
