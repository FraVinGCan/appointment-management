<template>
  <div class="min-h-screen bg-slate-950 text-slate-100">
    <header class="border-b border-slate-800 bg-slate-900/90">
      <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-6 py-4">
        <RouterLink class="font-semibold tracking-wide text-cyan-400" to="/">Appointment Desk</RouterLink>
        <nav class="flex flex-wrap items-center gap-2 text-sm text-slate-300" aria-label="Staff navigation">
          <RouterLink v-for="link in links" :key="link.to" class="rounded-lg px-3 py-2 hover:bg-slate-800 hover:text-white" :to="link.to">{{ link.label }}</RouterLink>
          <button class="rounded-lg border border-slate-700 px-3 py-2 hover:border-cyan-400" type="button" @click="signOut">Log out</button>
        </nav>
      </div>
    </header>
    <main class="mx-auto max-w-7xl px-6 py-8"><slot /></main>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'

import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const router = useRouter()
const links = [
  { label: 'Dashboard', to: '/' },
  { label: 'Appointments', to: '/appointments' },
  { label: 'Clients', to: '/clients' },
  { label: 'Services', to: '/services' },
]

async function signOut() {
  await auth.logout()
  router.push('/login')
}
</script>
