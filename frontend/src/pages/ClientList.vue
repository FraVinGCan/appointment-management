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
import { useUrlState, integerRange, oneOf } from "../composables/useUrlState";

const clients = useClientStore();
const UButton = resolveComponent("UButton");
const toast = useToast();
const router = useRouter();
const route = useRoute();
const urlState = useUrlState({
  search: "",
  active: { default: "", sanitize: oneOf(["", "true", "false"]) },
  page: { default: 1, sanitize: integerRange(1) },
  per_page: { default: 10, sanitize: integerRange(1, 100) },
  sort_by: { default: "name", sanitize: oneOf(["name", "email", "phone", "active"]) },
  sort_direction: { default: "asc", sanitize: oneOf(["asc", "desc"]) },
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
              value-key="value"
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
        <template #item="{ item }">
          <UCard variant="subtle">
            <div class="flex flex-wrap items-start justify-between gap-3">
              <h2 class="font-semibold truncate">{{ item.name }}</h2>
              <UBadge
                :color="item.active ? 'success' : 'neutral'"
                variant="subtle"
              >
                {{ item.active ? "Active" : "Inactive" }}
              </UBadge>
            </div>
            <p class="mt-3 text-sm text-slate-400 truncate break-all">
              {{ item.email }}
            </p>
            <p class="mt-1 text-sm text-slate-400">
              {{ item.phone || "Not provided" }}
            </p>
            <div class="mt-5 flex flex-wrap gap-2">
              <UButton size="sm" variant="link" :to="`/clients/${item.id}`"
                >View</UButton
              >
              <UButton
                size="sm"
                color="neutral"
                variant="link"
                :to="`/clients/${item.id}/edit`"
                >Edit</UButton
              >
              <UButton
                v-if="item.active"
                size="sm"
                color="error"
                variant="link"
                @click="toggleActive(item, false)"
                >Deactivate</UButton
              >
              <UButton
                v-else
                size="sm"
                color="success"
                variant="link"
                :disabled="clients.isSaving"
                @click="toggleActive(item, true)"
                >Activate</UButton
              >
            </div>
          </UCard>
        </template>
      </AppDataTable>

    </div>

    <AppConfirm
      :open="Boolean(selected)"
      title="Deactivate client?"
      description="This client will no longer be able to use client booking routes."
      confirm-label="Deactivate"
      variant="warning"
      :loading="clients.isSaving"
      @cancel="selected = null"
      @confirm="updateActive(selected, false)"
    />
  </section>
</template>
