<script setup>
import { reactive } from 'vue'
import { useRouter } from 'vue-router'

import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const router = useRouter()
const form = reactive({ email: '', password: '' })

async function submit() {
  try {
    await auth.login(form)
    router.push('/')
  } catch {
    // The store exposes the message and field errors to the template.
  }
}
</script>

<template>
  <main class="min-h-screen bg-slate-950 px-6 py-16 text-slate-100">
    <section class="mx-auto max-w-md rounded-2xl border border-slate-800 bg-slate-900 p-8 shadow-2xl">
      <p class="text-sm font-semibold uppercase tracking-[0.25em] text-cyan-400">Appointment Desk</p>
      <h1 class="mt-4 text-3xl font-semibold">Sign in</h1>
      <p class="mt-2 text-slate-400">Access your appointment workspace.</p>

      <form class="mt-8 space-y-5" @submit.prevent="submit">
        <label class="block text-sm font-medium">Email
          <input v-model="form.email" type="email" autocomplete="email" required class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 outline-none focus:border-cyan-400" />
          <span v-if="auth.validationErrors.email" class="mt-1 block text-sm text-rose-400">{{ auth.validationErrors.email[0] }}</span>
        </label>
        <label class="block text-sm font-medium">Password
          <input v-model="form.password" type="password" autocomplete="current-password" required class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 outline-none focus:border-cyan-400" />
          <span v-if="auth.validationErrors.password" class="mt-1 block text-sm text-rose-400">{{ auth.validationErrors.password[0] }}</span>
        </label>
        <p v-if="auth.error" class="rounded-lg bg-rose-950/60 px-3 py-2 text-sm text-rose-300">{{ auth.error }}</p>
        <button type="submit" :disabled="auth.isLoading" class="w-full rounded-lg bg-cyan-400 px-4 py-2 font-semibold text-slate-950 disabled:cursor-not-allowed disabled:opacity-50">{{ auth.isLoading ? 'Signing in...' : 'Sign in' }}</button>
      </form>

      <p class="mt-6 text-center text-sm text-slate-400">Need a client account? <RouterLink to="/register" class="font-semibold text-cyan-400 hover:text-cyan-300">Register</RouterLink></p>
    </section>
  </main>
</template>
