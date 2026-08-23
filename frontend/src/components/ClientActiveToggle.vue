<template>
  <div @click.stop>
    <USwitch
      :model-value="modelValue"
      :disabled="disabled"
      @update:model-value="$emit('toggle', $event)"
    />
  </div>
</template>

<script setup>
defineProps({
  modelValue: Boolean,
  disabled: Boolean,
});

defineEmits(["toggle"]);
</script>
