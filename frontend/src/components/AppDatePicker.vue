<script setup>
import { computed, ref } from "vue";

const props = defineProps({
  modelValue: { type: Object, default: null },
  minValue: { type: Object, default: null },
  placeholder: { type: String, default: "Select a date" },
});

const emit = defineEmits(["update:modelValue"]);

const open = ref(false);
const formatter = new Intl.DateTimeFormat(undefined, { dateStyle: "medium" });

const label = computed(() =>
  props.modelValue
    ? formatter.format(
        new Date(
          props.modelValue.year,
          props.modelValue.month - 1,
          props.modelValue.day,
        ),
      )
    : props.placeholder,
);

function select(date) {
  if (!date) return;
  emit("update:modelValue", date);
  open.value = false;
}
</script>

<template>
  <UPopover v-model:open="open">
    <UButton
      block
      color="neutral"
      variant="outline"
      size="md"
      class="justify-between font-normal"
      trailing-icon="i-lucide-calendar"
    >
      <span class="truncate">{{ label }}</span>
    </UButton>
    <template #content>
      <UCalendar
        :model-value="modelValue"
        :min-value="minValue || undefined"
        class="p-2"
        @update:model-value="select"
      />
    </template>
  </UPopover>
</template>
