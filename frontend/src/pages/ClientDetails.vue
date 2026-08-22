<script setup>
import { onMounted } from "vue";
import { useRoute } from "vue-router";
import AppError from "../components/AppError.vue";
import AppLoading from "../components/AppLoading.vue";
import AppNotFound from "../components/AppNotFound.vue";
import { useClientStore } from "../stores/clients";
const route = useRoute();
const clients = useClientStore();
onMounted(() => clients.fetch(route.params.id));
</script>
<template>
  <section class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
      <div>
        <p
          class="text-xs font-semibold uppercase tracking-[0.25em] text-cyan-400">
          Staff workspace
        </p>
        <h1 class="mt-2 text-3xl font-semibold">Client details</h1>
      </div>
      <RouterLink
        v-if="clients.current"
        class="rounded-lg border border-slate-700 px-4 py-2 text-sm"
        :to="`/clients/${clients.current.id}/edit`"
        >Edit</RouterLink
      >
    </div>
    <AppLoading v-if="clients.isLoading" message="Loading client..." /><AppError
      v-else-if="clients.error && clients.errorStatus !== 404"
      :message="clients.error" />
    <AppNotFound
      v-else-if="clients.errorStatus === 404"
      resource="Client"
      back-to="/clients" />
    <div
      v-else-if="clients.current"
      class="max-w-2xl rounded-2xl border border-slate-800 bg-slate-900 p-6">
      <h2 class="text-2xl font-semibold">{{ clients.current.name }}</h2>
      <dl class="mt-6 space-y-4 border-t border-slate-800 pt-6">
        <div>
          <dt class="text-xs uppercase tracking-wide text-slate-500">Email</dt>
          <dd class="mt-1">{{ clients.current.email }}</dd>
        </div>
        <div>
          <dt class="text-xs uppercase tracking-wide text-slate-500">Phone</dt>
          <dd class="mt-1">{{ clients.current.phone || "Not provided" }}</dd>
        </div>
        <div>
          <dt class="text-xs uppercase tracking-wide text-slate-500">Status</dt>
          <dd class="mt-1">
            {{ clients.current.active === false ? "Inactive" : "Active" }}
          </dd>
        </div>
      </dl>
    </div>
  </section>
</template>
