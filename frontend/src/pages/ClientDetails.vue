<script setup>
import { onMounted } from "vue";
import { useRoute } from "vue-router";
import AppError from "../components/AppError.vue";
import AppLoading from "../components/AppLoading.vue";
import AppNotFound from "../components/AppNotFound.vue";
import AppBreadcrumbs from "../components/AppBreadcrumbs.vue";
import EnumBadge from "../components/EnumBadge.vue";
import { useClientStore } from "../stores/clients";
const route = useRoute();
const clients = useClientStore();
onMounted(() => clients.fetch(route.params.id));
</script>
<template>
  <section class="space-y-6">
    <AppBreadcrumbs :items="[{ label: 'Clients', to: '/clients' }, { label: 'View' }]" />
    <div class="flex flex-wrap items-end justify-between gap-4">
      <div>
        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-cyan-400">
          Staff workspace
        </p>
        <h1 class="mt-2 text-2xl sm:text-3xl font-semibold">Client details</h1>
      </div>
      <UButton v-if="clients.current" color="neutral" variant="outline" class="w-full sm:w-auto" :to="`/clients/${clients.current.id}/edit`">Edit</UButton>
    </div>
    <AppLoading v-if="clients.isLoading" message="Loading client..." />
    <AppError v-else-if="clients.error && clients.errorStatus !== 404" :message="clients.error" />
    <AppNotFound v-else-if="clients.errorStatus === 404" resource="Client" back-to="/clients" />
    <div v-else-if="clients.current" class="max-w-2xl rounded-2xl border border-slate-800 bg-slate-900 p-4 sm:p-6">
      <h2 class="text-xl sm:text-2xl font-semibold truncate">{{ clients.current.name }}</h2>
      <dl class="mt-6 space-y-4 border-t border-slate-800 pt-6">
        <div>
          <dt class="text-xs uppercase tracking-wide text-slate-500">Email</dt>
          <dd class="mt-1 truncate break-all">{{ clients.current.email }}</dd>
        </div>
        <div>
          <dt class="text-xs uppercase tracking-wide text-slate-500">Phone</dt>
          <dd class="mt-1">{{ clients.current.phone || "Not provided" }}</dd>
        </div>
        <div>
          <dt class="text-xs uppercase tracking-wide text-slate-500">Status</dt>
          <dd class="mt-1">
            <EnumBadge :value="clients.current.active === false ? 'Inactive' : 'Active'" kind="active" />
          </dd>
        </div>
      </dl>
    </div>
  </section>
</template>
