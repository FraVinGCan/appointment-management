<template>
  <div class="rounded-xl border border-dashed border-slate-700 p-8 text-center text-sm text-slate-400">
    <p>{{ message }}</p>
    <slot />
  </div>
</template>

<script setup>
defineProps({
  message: { type: String, default: 'Nothing to show yet.' },
})
</script>
