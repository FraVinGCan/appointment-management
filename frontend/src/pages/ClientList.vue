<script setup>
import { computed, h, onMounted, ref, resolveComponent } from "vue";
import { useRoute, useRouter } from "vue-router";

import AppBreadcrumbs from "../components/AppBreadcrumbs.vue";
import AppConfirm from "../components/AppConfirm.vue";
import AppDataTable from "../components/AppDataTable.vue";
import AppError from "../components/AppError.vue";
import AppPageHeader from "../components/AppPageHeader.vue";
import ClientActiveToggle from "../components/ClientActiveToggle.vue";
import ClientTableActions from "../components/ClientTableActions.vue";
import { useClientStore } from "../stores/clients";
import { useDebouncedWatch } from "../composables/useDebouncedWatch";
import { useUrlState } from "../composables/useUrlState";

const clients = useClientStore();
const UButton = resolveComponent("UButton");
const toast = useToast();
const router = useRouter();
const route = useRoute();
const urlState = useUrlState({
  search: "",
  active: "",
  page: 1,
  per_page: 10,
  sort_by: "name",
  sort_direction: "asc",
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
const perPage = computed({
  get: () => urlState.per_page.value,
  set: (value) => updateState({ per_page: value, page: 1 }, true),
});
const sortBy = computed(() => urlState.sort_by.value);
const sortDirection = computed(() => urlState.sort_direction.value);
const selected = ref(null);
const draftActive = ref("");
const rows = computed(() => clients.items);
const columns = [
  {
    accessorKey: "name",
    header: () => sortableHeader("Name", "name"),
    cell: ({ row }) =>
      h("div", { class: "min-w-0 truncate" }, row.original.name),
  },
  {
    accessorKey: "email",
    header: () => sortableHeader("Email", "email"),
    cell: ({ row }) =>
      h("div", { class: "min-w-0 truncate break-all" }, row.original.email),
  },
  {
    accessorKey: "phone",
    header: () => sortableHeader("Phone", "phone"),
    cell: ({ row }) =>
      h(
        "div",
        { class: "min-w-0 truncate" },
        row.original.phone || "Not provided",
      ),
  },
  {
    id: "active",
    header: () => sortableHeader("Active", "active"),
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
    per_page: perPage.value,
    sort_by: sortBy.value,
    sort_direction: sortDirection.value,
    ...(search.value.trim() ? { search: search.value.trim() } : {}),
    ...(active.value !== "" ? { active: active.value === "true" ? 1 : 0 } : {}),
  };
}
function clearFilters() {
  draftActive.value = "";
  updateState({ search: "", active: "", page: 1 }, true);
}
function syncFilterDraft(open) {
  if (open) draftActive.value = active.value;
}
function applyFilters() {
  updateState({ active: draftActive.value, page: 1 }, true);
}
function sortableHeader(label, key) {
  const isSorted = sortBy.value === key;

  return h(
    UButton,
    {
      color: "neutral",
      variant: "ghost",
      label,
      icon: isSorted
        ? sortDirection.value === "asc"
          ? "i-lucide-arrow-up-narrow-wide"
          : "i-lucide-arrow-down-wide-narrow"
        : "i-lucide-arrow-up-down",
      class: "-mx-2.5",
      onClick: () => setSort(key),
    },
  );
}
function setSort(key) {
  updateState(
    {
      sort_by: key,
      sort_direction:
        sortBy.value === key && sortDirection.value === "asc" ? "desc" : "asc",
      page: 1,
    },
    true,
  );
}
function updateState(updates, fetch = false) {
  if (updates.search !== undefined) urlState.search.value = updates.search;
  if (updates.active !== undefined) urlState.active.value = updates.active;
  if (updates.page !== undefined) urlState.page.value = updates.page;
  if (updates.per_page !== undefined) urlState.per_page.value = updates.per_page;
  if (updates.sort_by !== undefined) urlState.sort_by.value = updates.sort_by;
  if (updates.sort_direction !== undefined) urlState.sort_direction.value = updates.sort_direction;

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
    <AppError
      v-if="clients.error"
      :message="clients.error"
      :retry="true"
      @retry="clients.fetchList(query())"
    />

    <div v-else class="space-y-4">
      <AppDataTable
        :data="rows"
        :columns="columns"
        :on-select="selectRow"
        v-model:search="search"
        :search-placeholder="'Search by name, email, or phone'"
        :filter-count="active ? 1 : 0"
        :current-page="clients.pagination?.current_page || page"
        :total="clients.pagination?.total || 0"
        v-model:per-page="perPage"
        :is-loading="clients.isLoading"
        empty-message="No clients found."
        empty-icon="i-lucide-users"
        :empty-action="{ to: '/clients/create', label: 'Add client', icon: 'i-lucide-plus' }"
        table-class="hidden sm:block"
        @change="goToPage"
        @clear-filters="clearFilters"
        @filters-open="syncFilterDraft"
        @apply-filters="applyFilters"
      >
        <template #filters>
          <UFormField label="Status">
            <USelectMenu
              v-model="draftActive"
              placeholder="All statuses"
              :items="[
                { label: 'Active', value: 'true' },
                { label: 'Inactive', value: 'false' },
              ]"
              clear
              class="w-full"
            />
          </UFormField>
        </template>
      </AppDataTable>

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
