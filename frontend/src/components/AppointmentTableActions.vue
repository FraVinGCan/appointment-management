<template>
  <div class="flex gap-2" @click.stop>
    <UButton :size="size" variant="link" :to="`/appointments/${id}`">View</UButton>
    <UButton :size="size" color="neutral" variant="link" :to="`/appointments/${id}/edit`">Edit</UButton>
  </div>
</template>

<script setup>
defineProps({
  id: { type: [Number, String], required: true },
  size: { type: String, default: 'xs' },
})
</script>
