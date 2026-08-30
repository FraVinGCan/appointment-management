<script setup>
import { computed, ref } from "vue";

const props = defineProps({
  modelValue: { type: Object, default: null },
  placeholder: { type: String, default: "Select date range" },
});

const emit = defineEmits(["update:modelValue"]);
const formatter = new Intl.DateTimeFormat(undefined, { dateStyle: "medium" });
const open = ref(false);

const value = computed({
  get: () => props.modelValue || { start: undefined, end: undefined },
  set: (nextValue) => emit("update:modelValue", nextValue),
});

const label = computed(() => {
  const { start, end } = value.value;
  if (!start) return props.placeholder;

  const format = (date) =>
    formatter.format(new Date(date.year, date.month - 1, date.day));

  return end ? `${format(start)} - ${format(end)}` : format(start);
});

function clear() {
  emit("update:modelValue", null);
  open.value = false;
}
</script>

<template>
  <UPopover v-model:open="open">
    <UButton
      block
      color="neutral"
      variant="outline"
      class="justify-between font-normal"
      trailing-icon="i-lucide-calendar"
    >
      <span class="truncate">{{ label }}</span>
    </UButton>
    <template #content>
      <div class="flex flex-col gap-2 p-2">
        <UCalendar v-model="value" range />
        <UButton
          color="neutral"
          variant="ghost"
          :disabled="!value.start"
          @click="clear"
        >
          Clear date range
        </UButton>
      </div>
    </template>
  </UPopover>
</template>
