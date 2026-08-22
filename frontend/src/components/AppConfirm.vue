<template>
  <UModal :open="open" :title="title" :description="message" :dismissible="!loading" @update:open="(value) => !value && $emit('cancel')">
    <template #body><div /></template>
    <template #footer><div class="flex w-full justify-end gap-3"><UButton color="neutral" variant="outline" :disabled="loading" @click="$emit('cancel')">Cancel</UButton><UButton color="error" :loading="loading" @click="$emit('confirm')">{{ confirmLabel }}</UButton></div></template>
  </UModal>
</template>

<script setup>
defineProps({
  open: Boolean,
  title: { type: String, default: 'Confirm action' },
  message: { type: String, default: 'Are you sure you want to continue?' },
  confirmLabel: { type: String, default: 'Confirm' },
  loading: Boolean,
})

defineEmits(['cancel', 'confirm'])
</script>
