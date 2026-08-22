<script setup>
import { useRouter } from 'vue-router'

import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const router = useRouter()

async function signOut() {
  await auth.logout()
  router.push('/login')
}
</script>

<template>
  <main class="min-h-screen bg-slate-950 px-6 py-12 text-slate-100">
    <section class="mx-auto max-w-3xl rounded-2xl border border-slate-800 bg-slate-900 p-8">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <p class="text-sm font-semibold uppercase tracking-[0.25em] text-cyan-400">Appointment Desk</p>
          <h1 class="mt-3 text-3xl font-semibold">Welcome, {{ auth.user?.name }}</h1>
          <p class="mt-2 text-slate-400">{{ auth.isStaff ? 'Staff workspace' : 'Client workspace' }}</p>
        </div>
        <button class="rounded-lg border border-slate-700 px-4 py-2 text-sm hover:border-cyan-400" @click="signOut">Log out</button>
      </div>
      <p class="mt-10 rounded-xl border border-dashed border-slate-700 p-6 text-slate-400">Your {{ auth.isStaff ? 'appointment management' : 'booking' }} tools will appear here in the next phase.</p>
    </section>
  </main>
</template>
