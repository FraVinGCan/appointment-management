<template>
  <UModal
    :open="open"
    :dismissible="!loading"
    @update:open="(value) => !value && $emit('cancel')"
  >
    <template #content>
      <div class="flex flex-col items-center px-6 pt-8 pb-6 text-center">
        <div
          class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full"
          :class="content.bg"
        >
          <slot name="icon">
            <UIcon
              :name="content.icon || content.iconName"
              class="h-7 w-7"
              :class="content.iconColor"
            />
          </slot>
        </div>

        <slot name="title">
          <h3 class="text-lg font-semibold text-neutral-900 dark:text-white">
            {{ content.title }}
          </h3>
        </slot>

        <slot name="description">
          <p v-if="content.description" class="mt-2 max-w-sm text-sm text-neutral-500 dark:text-neutral-400">
            {{ content.description }}
          </p>
        </slot>

        <div class="mt-6 flex w-full flex-col gap-2 sm:flex-row sm:gap-3">
          <slot name="footer">
            <slot name="cancel">
              <UButton
                color="neutral"
                variant="outline"
                block
                :ui="{ label: 'text-center w-full' }"
                :disabled="loading"
                @click="$emit('cancel')"
              >
                {{ content.cancelLabel }}
              </UButton>
            </slot>
            <slot name="confirm">
              <UButton
                :color="content.confirmColor || content.color"
                block
                :ui="{ label: 'text-center w-full' }"
                :loading="loading"
                @click="$emit('confirm')"
              >
                {{ content.confirmLabel }}
              </UButton>
            </slot>
          </slot>
        </div>
      </div>
    </template>
  </UModal>
</template>

<script setup>
import { ref, watch } from "vue";

const props = defineProps({
  open: Boolean,
  variant: {
    type: String,
    default: "warning",
    validator: (v) =>
      ["success", "warning", "error", "info", "primary", "neutral"].includes(v),
  },
  title: { type: String, default: "Confirm action" },
  description: { type: String, default: "Are you sure you want to continue?" },
  icon: { type: String, default: "" },
  confirmLabel: { type: String, default: "Confirm" },
  confirmColor: { type: String, default: "" },
  cancelLabel: { type: String, default: "Cancel" },
  loading: Boolean,
});

defineEmits(["cancel", "confirm"]);

const variants = {
  success: {
    icon: "i-lucide-circle-check",
    color: "success",
    bg: "bg-success-500/10",
    iconColor: "text-success-500",
  },
  warning: {
    icon: "i-lucide-triangle-alert",
    color: "warning",
    bg: "bg-warning-500/10",
    iconColor: "text-warning-500",
  },
  error: {
    icon: "i-lucide-circle-x",
    color: "error",
    bg: "bg-error-500/10",
    iconColor: "text-error-500",
  },
  info: {
    icon: "i-lucide-info",
    color: "info",
    bg: "bg-info-500/10",
    iconColor: "text-info-500",
  },
  primary: {
    icon: "i-lucide-circle-check",
    color: "primary",
    bg: "bg-primary-500/10",
    iconColor: "text-primary-500",
  },
  neutral: {
    icon: "i-lucide-circle-alert",
    color: "neutral",
    bg: "bg-neutral-500/10",
    iconColor: "text-neutral-500",
  },
};

const content = ref(snapshot());
function snapshot() {
  const styles = variants[props.variant];
  return {
    title: props.title,
    description: props.description,
    confirmLabel: props.confirmLabel,
    cancelLabel: props.cancelLabel,
    variant: props.variant,
    confirmColor: props.confirmColor,
    icon: props.icon,
    iconName: styles.icon,
    color: styles.color,
    bg: styles.bg,
    iconColor: styles.iconColor,
  };
}
watch(
  () => props.open,
  (open) => {
    if (open) content.value = snapshot();
  },
);
</script>
