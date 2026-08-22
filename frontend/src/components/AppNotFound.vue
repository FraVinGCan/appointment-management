<template>
  <div class="rounded-2xl border border-slate-800 bg-slate-900 p-8 text-center" role="alert">
    <p class="text-xs font-semibold uppercase tracking-[0.25em] text-amber-400">Not found</p>
    <h2 class="mt-3 text-xl font-semibold text-slate-100">{{ resource }} not found</h2>
    <p class="mt-2 text-sm text-slate-400">The requested {{ resource.toLowerCase() }} may have been removed or does not exist.</p>
    <RouterLink class="mt-5 inline-block rounded-lg border border-slate-700 px-4 py-2 text-sm font-semibold text-cyan-300" :to="backTo">Back to list</RouterLink>
  </div>
</template>

<script setup>
defineProps({
  resource: { type: String, required: true },
  backTo: { type: String, required: true },
})
</script>
