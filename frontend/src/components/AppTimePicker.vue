<script setup>
import { computed, ref } from "vue";
import { Time } from "@internationalized/date";

const props = defineProps({
  modelValue: { type: Object, default: null },
});

const emit = defineEmits(["update:modelValue"]);

const popoverOpen = ref(false);
const activeSegment = ref(null);
const segmentOptions = computed(() =>
  activeSegment.value === "hour"
    ? Array.from({ length: 24 }, (_, value) => String(value).padStart(2, "0"))
    : Array.from({ length: 60 }, (_, value) => String(value).padStart(2, "0")),
);

function openSegment(event) {
  const segment = event.target.closest?.("[data-segment]")?.dataset.segment;

  if (segment !== "hour" && segment !== "minute") return;

  activeSegment.value = segment;
  popoverOpen.value = true;
}

function selectSegment(value) {
  const hour = props.modelValue?.hour || 0;
  const minute = props.modelValue?.minute || 0;

  emit(
    "update:modelValue",
    activeSegment.value === "hour"
      ? new Time(Number(value), minute)
      : new Time(hour, Number(value)),
  );
  popoverOpen.value = false;
}
</script>

<template>
  <UPopover v-model:open="popoverOpen" :content="{ align: 'start', side: 'bottom', sideOffset: 6, collisionPadding: 8 }">
    <UInputTime
      :model-value="modelValue"
      hide-time-zone
      :hour-cycle="24"
      readonly
      icon="i-lucide-clock"
      class="w-full"
      @click="openSegment"
    />
    <template #content>
      <div class="grid max-h-52 w-44 grid-cols-4 gap-1 overflow-y-auto p-1.5">
        <UButton
          v-for="option in segmentOptions"
          :key="option"
          type="button"
          color="neutral"
          variant="ghost"
          size="sm"
          class="justify-center"
          :aria-label="`${activeSegment} ${option}`"
          @click="selectSegment(option)"
        >
          {{ option }}
        </UButton>
      </div>
    </template>
  </UPopover>
</template>
