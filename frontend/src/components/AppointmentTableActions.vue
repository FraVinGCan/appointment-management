<template>
  <div class="flex gap-2" @click.stop>
    <UButton :size="size" variant="link" :to="`/appointments/${id}`"
      >View</UButton
    >
    <UButton
      :size="size"
      color="neutral"
      variant="link"
      :to="`/appointments/${id}/edit`"
      >Edit</UButton
    >
    <UButton
      v-if="status === 'Requested'"
      :size="size"
      color="success"
      variant="link"
      @click="$emit('action', 'confirm')"
      >Confirm</UButton
    >
    <UButton
      v-else-if="status === 'Confirmed'"
      :size="size"
      variant="link"
      @click="$emit('action', 'complete')"
      >Complete</UButton
    >
    <UButton
      v-else-if="canCancel"
      :size="size"
      color="error"
      variant="link"
      @click="$emit('action', 'cancel')"
      >Cancel</UButton
    >
  </div>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
  id: { type: [Number, String], required: true },
  status: { type: String, default: "" },
  size: { type: String, default: "xs" },
});

defineEmits(["action"]);

const canCancel = computed(() =>
  ["Requested", "Confirmed"].includes(props.status),
);
</script>
