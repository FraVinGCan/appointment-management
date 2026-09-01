<template>
  <UError
    :error="{ statusCode: 404, statusMessage: heading, message: description }"
    icon="i-lucide-search-x"
    :clear="false"
    :ui="{ root: 'flex flex-col items-center justify-center text-center' }"
  >
    <template #links>
      <UButton
        v-if="backTo"
        :to="backTo"
        label="Back to list"
        icon="i-lucide-arrow-left"
        variant="outline"
      />
      <UButton
        v-if="homeTo"
        :to="homeTo"
        label="Go to home"
        icon="i-lucide-house"
      />
    </template>
  </UError>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
  resource: { type: String, default: "" },
  backTo: { type: String, default: "" },
  homeTo: { type: String, default: "/" },
});

const heading = computed(() =>
  props.resource ? `${props.resource} not found` : "Page not found",
);

const description = computed(() =>
  props.resource
    ? `The ${props.resource.toLowerCase()} you are looking for may have been removed or no longer exists.`
    : "The page you are looking for does not exist or may have been moved.",
);
</script>
