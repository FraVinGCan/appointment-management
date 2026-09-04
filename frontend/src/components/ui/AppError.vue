<template>
  <UAlert
    color="error"
    variant="soft"
    title="Something went wrong"
    :description="message"
    icon="i-lucide-circle-alert"
  >
    <template v-if="retry" #actions
      ><UButton
        color="error"
        variant="outline"
        size="sm"
        @click="$emit('retry')"
        >Try again</UButton
      ></template
    >
  </UAlert>
</template>

<script setup>
defineProps({
  message: { type: String, required: true },
  retry: { type: Boolean, default: false },
});

defineEmits(["retry"]);
</script>
