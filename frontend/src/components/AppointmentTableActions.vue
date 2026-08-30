<script setup>
import { computed } from "vue";

const props = defineProps({
  id: { type: [Number, String], required: true },
  status: { type: String, default: "" },
  size: { type: String, default: "md" },
});

const emit = defineEmits(["action"]);

const canCancel = computed(() =>
  ["Requested", "Confirmed"].includes(props.status),
);
const items = computed(() => {
  const workflowAction = props.status === "Requested"
    ? { label: "Confirm", icon: "i-lucide-check", color: "success", action: "confirm" }
    : props.status === "Confirmed"
      ? { label: "Complete", icon: "i-lucide-check-check", action: "complete" }
      : canCancel.value
        ? { label: "Cancel", icon: "i-lucide-ban", color: "error", action: "cancel" }
        : null;

  return [
    [
      { label: "View", icon: "i-lucide-eye", to: `/appointments/${props.id}` },
      { label: "Edit", icon: "i-lucide-pencil", to: `/appointments/${props.id}/edit` },
    ],
    workflowAction
      ? [{
          label: workflowAction.label,
          icon: workflowAction.icon,
          color: workflowAction.color,
          onSelect: () => emit("action", workflowAction.action),
        }]
      : [],
  ].filter((group) => group.length);
});
</script>

<template>
  <div @click.stop>
    <UDropdownMenu :items="items" :size="size" :content="{ align: 'end' }">
      <UButton
        :size="size"
        icon="i-lucide-ellipsis-vertical"
        color="neutral"
        variant="ghost"
        aria-label="Appointment actions"
      />
    </UDropdownMenu>
  </div>
</template>
