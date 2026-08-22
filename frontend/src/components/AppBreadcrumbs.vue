<template>
  <UBreadcrumb :items="items" class="mb-6" />
</template>

<script setup>
defineProps({
  items: { type: Array, required: true },
})
</script>
