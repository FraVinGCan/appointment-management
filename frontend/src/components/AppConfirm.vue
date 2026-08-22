<template>
  <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 p-6" role="dialog" aria-modal="true">
    <div class="w-full max-w-md rounded-2xl border border-slate-700 bg-slate-900 p-6 shadow-2xl">
      <h2 class="text-lg font-semibold text-slate-100">{{ title }}</h2>
      <p class="mt-2 text-sm text-slate-400">{{ message }}</p>
      <div class="mt-6 flex justify-end gap-3">
        <button class="rounded-lg border border-slate-700 px-4 py-2 text-sm text-slate-200" type="button" :disabled="loading" @click="$emit('cancel')">Cancel</button>
        <button class="rounded-lg bg-rose-500 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50" type="button" :disabled="loading" @click="$emit('confirm')">{{ loading ? 'Working...' : confirmLabel }}</button>
      </div>
    </div>
  </div>
</template>

<script setup>
  open: Boolean,
  title: { type: String, default: 'Confirm action' },
  message: { type: String, default: 'Are you sure you want to continue?' },
  confirmLabel: { type: String, default: 'Confirm' },
  loading: Boolean,
})

defineEmits(['cancel', 'confirm'])
</script>
