<script setup>
import { computed, ref } from "vue";

import AppTimePicker from "./AppTimePicker.vue";

const props = defineProps({
  modelValue: { type: Object, default: null },
  minValue: { type: Object, default: null },
  placeholder: { type: String, default: "Select date and time" },
});

const emit = defineEmits(["update:modelValue"]);
const open = ref(false);
const dateFormatter = new Intl.DateTimeFormat(undefined, { dateStyle: "medium" });

const value = computed(() => props.modelValue || {});
const hasValue = computed(
  () => Boolean(value.value.date || value.value.startTime || value.value.endTime),
);
const label = computed(() => {
  const parts = [];

  if (value.value.date) {
    parts.push(
      dateFormatter.format(
        new Date(value.value.date.year, value.value.date.month - 1, value.value.date.day),
      ),
    );
  }

  if (value.value.startTime || value.value.endTime) {
    parts.push(`${formatTime(value.value.startTime)} - ${formatTime(value.value.endTime)}`);
  }

  return parts.length ? parts.join(" | ") : props.placeholder;
});

function formatTime(time) {
  return time
    ? `${String(time.hour).padStart(2, "0")}:${String(time.minute).padStart(2, "0")}`
    : "--:--";
}

function updateValue(key, nextValue) {
  emit("update:modelValue", { ...value.value, [key]: nextValue });
}

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
      size="md"
      class="justify-between font-normal"
      trailing-icon="i-lucide-calendar-clock"
    >
      <span class="truncate">{{ label }}</span>
    </UButton>

    <template #content>
      <div class="grid gap-4 p-3 sm:p-4">
        <UCalendar
          :model-value="value.date"
          :min-value="minValue || undefined"
          @update:model-value="updateValue('date', $event)"
        />

        <div class="grid gap-3 border-t border-(--ui-border) pt-4">
          <div class="grid gap-1.5">
            <span class="text-sm font-medium text-highlighted">Start time <span class="text-error">*</span></span>
            <AppTimePicker
              :model-value="value.startTime"
              @update:model-value="updateValue('startTime', $event)"
            />
          </div>
          <div class="grid gap-1.5">
            <span class="text-sm font-medium text-highlighted">End time <span class="text-error">*</span></span>
            <AppTimePicker
              :model-value="value.endTime"
              @update:model-value="updateValue('endTime', $event)"
            />
          </div>
          <UButton
            color="neutral"
            variant="ghost"
            :disabled="!hasValue"
            @click="clear"
          >
            Clear date & time
          </UButton>
        </div>
      </div>
    </template>
  </UPopover>
</template>
