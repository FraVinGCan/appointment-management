<script setup>
defineProps({
  currentPage: { type: Number, default: 1 },
  total: { type: Number, default: 0 },
  perPage: { type: Number, default: 10 },
  isLoading: { type: Boolean, default: false },
});

defineEmits(["change"]);
</script>

<template>
  <div
    v-if="total > perPage"
    class="flex justify-center px-3 py-4"
  >
    <UPagination
      :page="currentPage"
      :total="total"
      :items-per-page="perPage"
      :disabled="isLoading"
      show-edges
      :ui="{ first: 'hidden', last: 'hidden' }"
      @update:page="$emit('change', $event)"
    />
  </div>
</template>
