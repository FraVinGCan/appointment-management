<template>
  <div class="rounded-xl border border-rose-900/70 bg-rose-950/50 p-4 text-sm text-rose-200" role="alert">
    <p>{{ message }}</p>
    <button v-if="retry" class="mt-3 font-semibold text-rose-300 underline" type="button" @click="$emit('retry')">Try again</button>
  </div>
</template>

<script setup>
defineProps({
  message: { type: String, required: true },
  retry: { type: Boolean, default: false },
})

defineEmits(['retry'])
</script>
