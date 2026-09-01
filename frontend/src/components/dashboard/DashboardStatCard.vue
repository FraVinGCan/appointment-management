<script setup>
defineProps({
  label: { type: String, required: true },
  value: { type: Number, required: true },
  icon: { type: String, default: "" },
});
</script>

<template>
  <UCard variant="subtle" class="border-default bg-default">
    <div class="flex items-start justify-between gap-4">
      <div>
        <p class="text-sm font-medium text-muted">{{ label }}</p>
        <p class="mt-2 text-3xl font-semibold tracking-tight text-default">
          {{ value }}
        </p>
      </div>
      <div
        v-if="icon"
        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-muted text-primary"
      >
        <UIcon :name="icon" class="h-5 w-5" />
      </div>
    </div>
  </UCard>
</template>
