<template>
  <div class="min-h-screen bg-slate-950 text-slate-100">
    <USidebar title="Appointment Desk" description="Staff workspace" collapsible="icon" rail close class="z-30">
      <template #default>
        <UNavigationMenu :items="links" orientation="vertical" highlight class="w-full" />
      </template>
      <template #footer>
        <UButton color="neutral" variant="outline" block :loading="auth.isLoading" @click="signOut">Log out</UButton>
      </template>
    </USidebar>
    <main class="min-h-screen px-6 py-8 lg:pl-72"><div class="mx-auto max-w-7xl"><slot /></div></main>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'

import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const router = useRouter()
const links = [
  { label: 'Dashboard', to: '/', icon: 'i-lucide-house' },
  { label: 'Appointments', to: '/appointments', icon: 'i-lucide-calendar' },
  { label: 'Clients', to: '/clients', icon: 'i-lucide-users' },
  { label: 'Services', to: '/services', icon: 'i-lucide-briefcase-business' },
]

async function signOut() {
  await auth.logout()
  router.push('/login')
}
</script>
