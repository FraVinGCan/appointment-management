<script setup>
import { reactive } from 'vue'
import { useRouter } from 'vue-router'

import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const router = useRouter()
const form = reactive({ name: '', email: '', phone: '', password: '', password_confirmation: '' })

async function submit() {
  try {
    await auth.register(form)
    router.push('/')
  } catch {
    // The store exposes the message and field errors to the template.
  }
}
</script>

<template>
  <main class="min-h-screen bg-slate-950 px-6 py-12 text-slate-100">
    <section class="mx-auto max-w-md rounded-2xl border border-slate-800 bg-slate-900 p-8 shadow-2xl">
      <p class="text-sm font-semibold uppercase tracking-[0.25em] text-cyan-400">Appointment Desk</p>
      <h1 class="mt-4 text-3xl font-semibold">Create client account</h1>
      <p class="mt-2 text-slate-400">Register to request and manage your appointments.</p>

      <form class="mt-8 space-y-4" @submit.prevent="submit">
        <label v-for="field in [['name', 'Name', 'text'], ['email', 'Email', 'email'], ['phone', 'Phone', 'tel'], ['password', 'Password', 'password'], ['password_confirmation', 'Confirm password', 'password']]" :key="field[0]" class="block text-sm font-medium">{{ field[1] }}
          <input v-model="form[field[0]]" :type="field[2]" :autocomplete="field[0].includes('password') ? 'new-password' : field[0]" :required="field[0] !== 'phone'" class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 outline-none focus:border-cyan-400" />
          <span v-if="auth.validationErrors[field[0]]" class="mt-1 block text-sm text-rose-400">{{ auth.validationErrors[field[0]][0] }}</span>
        </label>
        <p v-if="auth.error" class="rounded-lg bg-rose-950/60 px-3 py-2 text-sm text-rose-300">{{ auth.error }}</p>
        <button type="submit" :disabled="auth.isLoading" class="w-full rounded-lg bg-cyan-400 px-4 py-2 font-semibold text-slate-950 disabled:cursor-not-allowed disabled:opacity-50">{{ auth.isLoading ? 'Creating account...' : 'Create account' }}</button>
      </form>

      <p class="mt-6 text-center text-sm text-slate-400">Already registered? <RouterLink to="/login" class="font-semibold text-cyan-400 hover:text-cyan-300">Sign in</RouterLink></p>
    </section>
  </main>
</template>
