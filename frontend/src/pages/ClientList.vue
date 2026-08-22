<script setup>
import { computed, h, onMounted, ref, watch } from "vue";
import { useRouter } from "vue-router";

import AppBreadcrumbs from "../components/AppBreadcrumbs.vue";
import AppConfirm from "../components/AppConfirm.vue";
import AppEmpty from "../components/AppEmpty.vue";
import AppError from "../components/AppError.vue";
import AppLoading from "../components/AppLoading.vue";
import ClientActiveToggle from "../components/ClientActiveToggle.vue";
import ClientTableActions from "../components/ClientTableActions.vue";
import { useClientStore } from "../stores/clients";
import { useNotificationStore } from "../stores/notifications";

const clients = useClientStore();
const notifications = useNotificationStore();
const router = useRouter();
const search = ref("");
const selected = ref(null);
let timer;
const rows = computed(() => clients.items);
const columns = [
  { accessorKey: "name", header: "Name" },
  { accessorKey: "email", header: "Email" },
  {
    accessorKey: "phone",
    header: "Phone",
    cell: ({ row }) => row.original.phone || "Not provided",
  },
  {
    id: "active",
    header: "Active",
    cell: ({ row }) =>
      h(ClientActiveToggle, {
        modelValue: row.original.active,
        disabled: clients.isSaving,
        onToggle: (value) => toggleActive(row.original, value),
      }),
  },
  {
    id: "actions",
    header: "Actions",
    cell: ({ row }) => h(ClientTableActions, { id: row.original.id }),
  },
];
onMounted(() => clients.fetchList());
watch(search, (value) => {
  window.clearTimeout(timer);
  timer = window.setTimeout(
    () => clients.fetchList({ search: value, page: 1 }),
    250,
  );
});
const pendingActive = ref(true);
async function toggleActive(client, value) {
  if (!value) {
    selected.value = client;
    pendingActive.value = false;
    return;
  }
  await updateActive(client, true);
}
async function updateActive(client, value) {
  try {
    const item = await (value
      ? clients.activate(client.id)
      : clients.deactivate(client.id));
    clients.updateItem(item);
    notifications.notify(`Client ${value ? "activated" : "deactivated"}.`);
    selected.value = null;
  } catch {
    /* Store state is rendered below. */
  }
}
async function page(value) {
  await clients.fetchList({
    page: value,
    ...(search.value ? { search: search.value } : {}),
  });
}
function selectRow(_event, row) {
  router.push(`/clients/${row.original.id}`);
}
</script>

<template>
  <section class="space-y-6">
    <AppBreadcrumbs
      :items="[{ label: 'Dashboard', to: '/' }, { label: 'Clients' }]" />
    <div class="flex flex-wrap items-end justify-between gap-4">
      <div>
        <p
          class="text-xs font-semibold uppercase tracking-[0.25em] text-cyan-400">
          Staff workspace
        </p>
        <h1 class="mt-2 text-3xl font-semibold">Clients</h1>
      </div>
      <UButton to="/clients/create" trailing-icon="i-lucide-plus"
        >Add client</UButton
      >
    </div>
    <UInput
      v-model="search"
      icon="i-lucide-search"
      placeholder="Search clients"
      class="w-full max-w-xl" /><AppLoading
      v-if="clients.isLoading"
      message="Loading clients..." /><AppError
      v-else-if="clients.error"
      :message="clients.error"
      :retry="true"
      @retry="clients.fetchList()" /><AppEmpty
      v-else-if="!clients.items.length"
      message="No clients found." />
    <div
      v-else
      class="overflow-x-auto rounded-2xl border border-slate-800 bg-slate-900 p-2">
      <UTable
        :data="rows"
        :columns="columns"
        :on-select="selectRow"
        class="min-w-full" />
      <div
        v-if="clients.pagination?.last_page > 1"
        class="flex items-center justify-between px-3 py-4 text-sm text-muted">
        <UButton
          color="neutral"
          variant="outline"
          :disabled="clients.pagination.current_page <= 1 || clients.isLoading"
          @click="page(clients.pagination.current_page - 1)"
          >Previous</UButton
        ><span
          >Page {{ clients.pagination.current_page }} of
          {{ clients.pagination.last_page }}</span
        ><UButton
          color="neutral"
          variant="outline"
          :disabled="
            clients.pagination.current_page >= clients.pagination.last_page ||
            clients.isLoading
          "
          @click="page(clients.pagination.current_page + 1)"
          >Next</UButton
        >
      </div>
    </div>
    <AppConfirm
      :open="Boolean(selected)"
      title="Deactivate client?"
      message="This client will no longer be able to use client booking routes."
      confirm-label="Deactivate"
      :loading="clients.isSaving"
      @cancel="selected = null"
      @confirm="updateActive(selected, false)" />
  </section>
</template>
