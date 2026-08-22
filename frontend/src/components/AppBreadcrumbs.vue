<template>
  <div class="mb-6 overflow-x-auto">
    <UBreadcrumb :items="items" class="whitespace-nowrap" />
  </div>
</template>

<script setup>
defineProps({
  items: { type: Array, required: true },
})
</script>
