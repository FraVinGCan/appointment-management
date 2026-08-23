<script setup>
import { reactive } from "vue";
import { useRouter } from "vue-router";

import { useAuthStore } from "../stores/auth";

const auth = useAuthStore();
const router = useRouter();
const form = reactive({
  name: "",
  email: "",
  phone: "",
  password: "",
  password_confirmation: "",
});

async function submit() {
  try {
    await auth.register(form);
    router.push(router.currentRoute.value.query.redirect || "/");
  } catch {
    // The store exposes the message and field errors to the template.
  }
}
</script>

<template>
  <main
    class="min-h-screen bg-slate-950 px-4 py-10 sm:px-6 sm:py-12 text-slate-100"
  >
    <UCard class="mx-auto max-w-md" variant="subtle">
      <p
        class="text-sm font-semibold uppercase tracking-[0.25em] text-cyan-400"
      >
        Appointment Desk
      </p>
      <h1 class="mt-4 text-2xl sm:text-3xl font-semibold">
        Create client account
      </h1>
      <p class="mt-2 text-slate-400">
        Register to request and manage your appointments.
      </p>

      <UForm class="mt-8 space-y-4" :state="form" @submit="submit">
        <UFormField
          label="Name"
          name="name"
          required
          :error="auth.validationErrors.name?.[0]"
          ><UInput v-model="form.name" autocomplete="name" class="w-full"
        /></UFormField>
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
          label="Phone"
          name="phone"
          :error="auth.validationErrors.phone?.[0]"
          ><UInput
            v-model="form.phone"
            type="tel"
            autocomplete="tel"
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
            autocomplete="new-password"
            class="w-full"
        /></UFormField>
        <UFormField
          label="Confirm password"
          name="password_confirmation"
          required
          :error="auth.validationErrors.password_confirmation?.[0]"
          ><UInput
            v-model="form.password_confirmation"
            type="password"
            autocomplete="new-password"
            class="w-full"
        /></UFormField>
        <UAlert
          v-if="auth.error"
          color="error"
          variant="soft"
          :description="auth.error"
        />
        <UButton type="submit" block :loading="auth.isLoading"
          >Create account</UButton
        >
      </UForm>

      <p class="mt-6 text-center text-sm text-slate-400">
        Already registered?
        <ULink to="/login" class="font-semibold text-cyan-400">Sign in</ULink>
      </p>
    </UCard>
  </main>
</template>
