<template>
  <UAlert color="warning" variant="soft" title="Resource not found" :description="`The requested ${resource.toLowerCase()} may have been removed or does not exist.`" icon="i-lucide-search-x" :actions="[{ label: 'Back to list', color: 'warning', variant: 'outline', to: backTo }]" />
</template>

<script setup>
defineProps({
  resource: { type: String, required: true },
  backTo: { type: String, required: true },
})
</script>
