<script setup>
import { onMounted } from "vue";
import { useRoute } from "vue-router";
import AppError from "../components/AppError.vue";
import AppLoading from "../components/AppLoading.vue";
import AppNotFound from "../components/AppNotFound.vue";
import AppBreadcrumbs from "../components/AppBreadcrumbs.vue";
import AppPageHeader from "../components/AppPageHeader.vue";
import EnumBadge from "../components/EnumBadge.vue";
import { useClientStore } from "../stores/clients";
const route = useRoute();
const clients = useClientStore();
onMounted(() => clients.fetch(route.params.id));
function formatDateTime(date) {
  if (!date) return "Not available";

  return new Intl.DateTimeFormat(undefined, {
    dateStyle: "medium",
    timeStyle: "short",
  }).format(new Date(date));
}
</script>
<template>
  <section class="space-y-6">
    <AppBreadcrumbs
      :items="[{ label: 'Clients', to: '/clients' }, { label: 'View' }]"
    />
    <AppPageHeader title="Client details">
      <template v-if="clients.current" #actions>
        <UButton
          color="neutral"
          variant="outline"
          class="w-full sm:w-auto"
          :to="`/clients/${clients.current.id}/edit`"
          >Edit</UButton
        >
      </template>
    </AppPageHeader>
    <AppLoading v-if="clients.isLoading" message="Loading client..." />
    <AppError
      v-else-if="clients.error && clients.errorStatus !== 404"
      :message="clients.error"
    />
    <AppNotFound
      v-else-if="clients.errorStatus === 404"
      resource="Client"
      back-to="/clients"
    />
    <div
      v-else-if="clients.current"
      class="max-w-2xl rounded-2xl border border-slate-800 bg-slate-900 p-4 sm:p-6"
    >
      <h2 class="text-xl sm:text-2xl font-semibold truncate">
        {{ clients.current.name }}
      </h2>
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
            <EnumBadge
              :value="clients.current.active === false ? 'Inactive' : 'Active'"
              kind="active"
            />
          </dd>
        </div>
        <div>
          <dt class="text-xs uppercase tracking-wide text-slate-500">
            Created
          </dt>
          <dd class="mt-1 text-slate-300">
            {{ formatDateTime(clients.current.createdAt) }}
          </dd>
        </div>
        <div>
          <dt class="text-xs uppercase tracking-wide text-slate-500">
            Last updated
          </dt>
          <dd class="mt-1 text-slate-300">
            {{ formatDateTime(clients.current.updatedAt) }}
          </dd>
        </div>
      </dl>
    </div>
  </section>
</template>
