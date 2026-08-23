<template>
  <UBadge :color="color" variant="subtle">{{ value }}</UBadge>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
  value: { type: String, required: true },
  kind: { type: String, default: "status" },
});

const colors = {
  status: {
    Requested: "warning",
    Confirmed: "info",
    Completed: "success",
    Cancelled: "neutral",
  },
  priority: { Low: "neutral", Medium: "warning", High: "error" },
  active: { Active: "success", Inactive: "neutral" },
};
const color = computed(() => colors[props.kind]?.[props.value] || "neutral");
</script>
