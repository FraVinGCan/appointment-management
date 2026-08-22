<template>
  <div class="flex items-center justify-center gap-3 p-8 text-sm text-slate-400" role="status">
    <span class="size-4 animate-spin rounded-full border-2 border-slate-600 border-t-cyan-400" />
    <span>{{ message }}</span>
  </div>
</template>

<script setup>
defineProps({
  message: { type: String, default: 'Loading...' },
})
</script>
