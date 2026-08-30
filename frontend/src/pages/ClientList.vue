<script setup>
import { computed, h, onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";

import AppBreadcrumbs from "../components/AppBreadcrumbs.vue";
import AppConfirm from "../components/AppConfirm.vue";
import AppEmpty from "../components/AppEmpty.vue";
import AppError from "../components/AppError.vue";
import AppLoading from "../components/AppLoading.vue";
import AppPageHeader from "../components/AppPageHeader.vue";
import AppPagination from "../components/AppPagination.vue";
import ClientActiveToggle from "../components/ClientActiveToggle.vue";
import ClientTableActions from "../components/ClientTableActions.vue";
import { useClientStore } from "../stores/clients";
import { useDebouncedWatch } from "../composables/useDebouncedWatch";
import { useUrlState } from "../composables/useUrlState";

const clients = useClientStore();
const toast = useToast();
const router = useRouter();
const route = useRoute();
const urlState = useUrlState({
  search: "",
  active: "",
  page: 1,
});
const search = computed({
  get: () => urlState.search.value,
  set: (value) => updateState({ search: value, page: 1 }),
});
const active = computed({
  get: () => urlState.active.value,
  set: (value) => updateState({ active: value, page: 1 }),
});
const page = computed({
  get: () => urlState.page.value,
  set: (value) => updateState({ page: value }),
});
const selected = ref(null);
const rows = computed(() => clients.items);
const columns = [
  {
    accessorKey: "name",
    header: "Name",
    cell: ({ row }) =>
      h("div", { class: "min-w-0 truncate" }, row.original.name),
  },
  {
    accessorKey: "email",
    header: "Email",
    cell: ({ row }) =>
      h("div", { class: "min-w-0 truncate break-all" }, row.original.email),
  },
  {
    accessorKey: "phone",
    header: "Phone",
    cell: ({ row }) =>
      h(
        "div",
        { class: "min-w-0 truncate" },
        row.original.phone || "Not provided",
      ),
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

onMounted(() => clients.fetchList(query()));

useDebouncedWatch(
  [() => search.value, () => active.value],
  () => updateState({ page: 1, search: search.value, active: active.value }, true),
);

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
    toast.add({
      title: "Success",
      description: `Client ${value ? "activated" : "deactivated"}.`,
      color: "success",
    });
    selected.value = null;
  } catch {
    /* Store state is rendered below. */
  }
}
async function goToPage(value) {
  updateState({ page: value }, true);
}
function query() {
  return {
    page: page.value,
    ...(search.value.trim() ? { search: search.value.trim() } : {}),
    ...(active.value !== "" ? { active: active.value === "true" ? 1 : 0 } : {}),
  };
}
function clearFilters() {
  updateState({ search: "", active: "", page: 1 }, true);
}
function updateState(updates, fetch = false) {
  if (updates.search !== undefined) urlState.search.value = updates.search;
  if (updates.active !== undefined) urlState.active.value = updates.active;
  if (updates.page !== undefined) urlState.page.value = updates.page;

  if (fetch) {
    clients.fetchList(query());
  }
}
function selectRow(_event, row) {
  router.push(`/clients/${row.original.id}`);
}
</script>

<template>
  <section class="space-y-6">
    <AppPageHeader
      :breadcrumbs="[{ label: 'Dashboard', to: '/' }, { label: 'Clients' }]"
      title="Clients"
    >
      <template #actions>
        <UButton
          to="/clients/create"
          trailing-icon="i-lucide-plus"
          class="w-full sm:w-auto"
          >Add client</UButton
        >
      </template>
    </AppPageHeader>
    <UInput
      v-model="search"
      icon="i-lucide-search"
      placeholder="Search by name, email, or phone"
      class="w-full max-w-xl"
    />
    <div class="flex flex-wrap items-end gap-4">
      <UFormField label="Status">
         <USelectMenu
           v-model="active"
          placeholder="All statuses"
          :items="[
            { label: 'Active', value: 'true' },
            { label: 'Inactive', value: 'false' },
           ]"
           clear
           class="w-full sm:w-40"
         />
      </UFormField>
      <UButton
        v-if="search || active"
        color="neutral"
        variant="ghost"
        @click="clearFilters"
        >Clear filters</UButton
      >
    </div>
    <AppLoading v-if="clients.isLoading" message="Loading clients..." />
    <AppError
      v-else-if="clients.error"
      :message="clients.error"
      :retry="true"
      @retry="clients.fetchList(query())"
    />
    <AppEmpty v-else-if="!clients.items.length" message="No clients found." />

    <div v-else class="space-y-4">
      <div
        class="hidden sm:block overflow-x-auto rounded-2xl border border-slate-800 bg-slate-900 p-2"
      >
        <UTable
          :data="rows"
          :columns="columns"
          :on-select="selectRow"
          :ui="{ tr: 'cursor-pointer', separator: 'z-0' }"
          class="min-w-full"
        />
      </div>

      <div class="sm:hidden space-y-3">
        <div
          v-for="client in clients.items"
          :key="client.id"
          class="rounded-xl border border-slate-800 bg-slate-900 p-4"
        >
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0 flex-1">
              <p class="font-medium truncate">{{ client.name }}</p>
              <p class="mt-1 text-sm text-slate-400 truncate break-all">
                {{ client.email }}
              </p>
              <p class="mt-1 text-sm text-slate-400">
                {{ client.phone || "Not provided" }}
              </p>
              <ClientActiveToggle
                :model-value="client.active"
                :disabled="clients.isSaving"
                @toggle="toggleActive(client, $event)"
              />
            </div>
            <div class="flex items-center gap-2 shrink-0">
              <UButton
                size="sm"
                variant="outline"
                @click.stop="selectRow(null, { original: client })"
              >
                View
              </UButton>
              <UButton
                size="sm"
                color="error"
                variant="outline"
                @click.stop="toggleActive(client, false)"
              >
                Deactivate
              </UButton>
            </div>
          </div>
        </div>
      </div>

      <AppPagination
        :current-page="clients.pagination?.current_page"
        :total="clients.pagination?.total"
        :per-page="clients.pagination?.per_page"
        :is-loading="clients.isLoading"
        @change="goToPage"
      />
    </div>

    <AppConfirm
      :open="Boolean(selected)"
      title="Deactivate client?"
      message="This client will no longer be able to use client booking routes."
      confirm-label="Deactivate"
      :loading="clients.isSaving"
      @cancel="selected = null"
      @confirm="updateActive(selected, false)"
    />
  </section>
</template>
