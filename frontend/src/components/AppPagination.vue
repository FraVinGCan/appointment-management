<script setup>
defineProps({
  currentPage: { type: Number, required: true },
  lastPage: { type: Number, required: true },
  isLoading: { type: Boolean, default: false },
});

defineEmits(["change"]);
</script>

<template>
  <div
    v-if="lastPage > 1"
    class="flex items-center justify-between gap-3 px-3 py-4 text-sm text-slate-400 sm:flex-row"
  >
    <UButton
      color="neutral"
      variant="outline"
      size="sm"
      :disabled="currentPage <= 1 || isLoading"
      @click="$emit('change', currentPage - 1)"
    >
      Previous
    </UButton>
    <span class="sm:inline">Page {{ currentPage }} of {{ lastPage }}</span>
    <UButton
      color="neutral"
      variant="outline"
      size="sm"
      :disabled="currentPage >= lastPage || isLoading"
      @click="$emit('change', currentPage + 1)"
    >
      Next
    </UButton>
  </div>
</template>
