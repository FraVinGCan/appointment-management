<script setup>
import { computed } from "vue";

const props = defineProps({
  id: { type: [Number, String], required: true },
  size: { type: String, default: "md" },
});

const items = computed(() => [
  [
    { label: "View", icon: "i-lucide-eye", to: `/clients/${props.id}` },
    { label: "Edit", icon: "i-lucide-pencil", to: `/clients/${props.id}/edit` },
  ],
]);
</script>

<template>
  <div @click.stop>
    <UDropdownMenu :items="items" :size="size" :content="{ align: 'end' }">
      <UButton
        :size="size"
        icon="i-lucide-ellipsis-vertical"
        color="neutral"
        variant="ghost"
        aria-label="Client actions"
      />
    </UDropdownMenu>
  </div>
</template>
