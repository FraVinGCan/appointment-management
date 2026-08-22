<template>
  <div class="space-y-3 p-8" role="status">
    <USkeleton class="mx-auto h-4 w-40" />
    <p class="text-center text-sm text-slate-400">{{ message }}</p>
  </div>
</template>

<script setup>
defineProps({
  message: { type: String, default: 'Loading...' },
})
</script>
