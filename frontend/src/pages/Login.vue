<script setup>
import { reactive } from "vue";
import { useRouter } from "vue-router";

import { useAuthStore } from "../stores/auth";

const auth = useAuthStore();
const router = useRouter();
const form = reactive({ email: "", password: "" });

async function submit() {
  try {
    await auth.login(form);
    router.push(router.currentRoute.value.query.redirect || "/");
  } catch {
    // The store exposes the message and field errors to the template.
  }
}
</script>

<template>
  <main
    class="min-h-screen bg-slate-950 px-4 py-10 sm:px-6 sm:py-16 text-slate-100"
  >
    <UCard class="mx-auto max-w-md" variant="subtle">
      <p
        class="text-sm font-semibold uppercase tracking-[0.25em] text-cyan-400"
      >
        Appointment Desk
      </p>
      <h1 class="mt-4 text-2xl sm:text-3xl font-semibold">Sign in</h1>
      <p class="mt-2 text-slate-400">Access your appointment workspace.</p>

      <UForm class="mt-8 space-y-5" :state="form" @submit="submit">
        <UFormField
          label="Email"
          name="email"
          required
          :error="auth.validationErrors.email?.[0]"
          ><UInput
            v-model="form.email"
            type="email"
            autocomplete="email"
            class="w-full"
        /></UFormField>
        <UFormField
          label="Password"
          name="password"
          required
          :error="auth.validationErrors.password?.[0]"
          ><UInput
            v-model="form.password"
            type="password"
            autocomplete="current-password"
            class="w-full"
        /></UFormField>
        <UAlert
          v-if="auth.error"
          color="error"
          variant="soft"
          :description="auth.error"
        />
        <UButton type="submit" block :loading="auth.isLoading">Sign in</UButton>
      </UForm>

      <p class="mt-6 text-center text-sm text-slate-400">
        Need a client account?
        <ULink to="/register" class="font-semibold text-cyan-400"
          >Register</ULink
        >
      </p>
    </UCard>
  </main>
</template>
