<template>
  <div class="min-h-screen bg-slate-950 text-slate-100">
    <header class="border-b border-slate-800 bg-slate-900/90">
      <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-4 px-6 py-4">
        <UButton to="/" variant="link" size="lg">Appointment Desk</UButton>
        <nav class="flex flex-wrap items-center gap-2" aria-label="Client navigation"><UNavigationMenu :items="links" orientation="horizontal" highlight /><UButton color="neutral" variant="outline" size="sm" @click="signOut">Log out</UButton></nav>
      </div>
    </header>
    <main class="mx-auto max-w-5xl px-6 py-8"><slot /></main>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'

import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const router = useRouter()
const links = [
  { label: 'Overview', to: '/', icon: 'i-lucide-house' },
  { label: 'Book an appointment', to: '/book', icon: 'i-lucide-calendar-plus' },
  { label: 'My appointments', to: '/client/appointments', icon: 'i-lucide-calendar-check' },
]

async function signOut() {
  await auth.logout()
  router.push('/login')
}
</script>
