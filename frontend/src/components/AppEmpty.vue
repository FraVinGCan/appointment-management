<template>
  <UCard variant="subtle" class="text-center"
    ><p class="text-sm text-muted">{{ message }}</p>
    <slot
  /></UCard>
</template>

<script setup>
defineProps({
  message: { type: String, default: "Nothing to show yet." },
});
</script>
