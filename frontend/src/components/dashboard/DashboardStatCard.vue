<script setup>
defineProps({
  label: { type: String, required: true },
  value: { type: Number, required: true },
  icon: { type: String, default: "" },
});
</script>

<template>
  <UCard variant="subtle" class="border-slate-800 bg-slate-900/60">
    <div class="flex items-start justify-between gap-4">
      <div>
        <p class="text-sm font-medium text-slate-400">{{ label }}</p>
        <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-100">
          {{ value }}
        </p>
      </div>
      <div
        v-if="icon"
        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-800 text-cyan-400"
      >
        <UIcon :name="icon" class="h-5 w-5" />
      </div>
    </div>
  </UCard>
</template>
