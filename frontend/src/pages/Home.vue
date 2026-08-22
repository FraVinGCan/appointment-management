<script setup>
import { useRouter } from "vue-router";

import { useAuthStore } from "../stores/auth";

const auth = useAuthStore();
const router = useRouter();

async function signOut() {
  await auth.logout();
  router.push("/login");
}
</script>

<template>
  <main
    class="min-h-screen bg-slate-950 px-4 py-10 sm:px-6 sm:py-12 text-slate-100">
    <section
      class="mx-auto max-w-3xl rounded-2xl border border-slate-800 bg-slate-900 p-5 sm:p-8">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <p
            class="text-sm font-semibold uppercase tracking-[0.25em] text-cyan-400">
            Appointment Desk
          </p>
          <h1 class="mt-3 text-2xl sm:text-3xl font-semibold">
            Welcome, {{ auth.user?.name }}
          </h1>
          <p class="mt-2 text-slate-400">
            {{ auth.isStaff ? "Staff workspace" : "Client workspace" }}
          </p>
        </div>
        <UButton
          color="neutral"
          variant="outline"
          class="w-full sm:w-auto"
          :loading="auth.isLoading"
          @click="signOut"
          >Log out</UButton
        >
      </div>
      <div v-if="auth.isClient" class="mt-10 grid gap-4 sm:grid-cols-2">
        <ULink to="/book"
          ><UCard variant="subtle" class="border-cyan-900 bg-cyan-950/30">
            <span class="font-semibold text-cyan-300">Book an appointment</span>
            <span class="mt-2 block text-sm text-slate-400"
              >Request a time from the available services.</span
            >
          </UCard>
        </ULink>
        <ULink to="/client/appointments">
          <UCard variant="subtle" class="border-slate-700">
            <span class="font-semibold text-slate-200">My appointments</span>
            <span class="mt-2 block text-sm text-slate-400"
              >View and manage your booking requests.</span
            >
          </UCard>
        </ULink>
      </div>
    </section>
  </main>
</template>
