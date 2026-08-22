<template>
  <section class="rounded-2xl border border-slate-800 bg-slate-900 p-5 sm:p-8">
    <p class="text-xs font-semibold uppercase tracking-[0.25em] text-cyan-400">Appointment Desk</p>
    <h1 class="mt-3 text-2xl sm:text-3xl font-semibold text-slate-100">{{ title }}</h1>
    <p class="mt-3 max-w-xl text-slate-400">This workspace is being prepared for the next application phase.</p>
  </section>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()
const title = computed(() => route.meta.title || 'Workspace')
</script>
