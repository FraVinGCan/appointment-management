<script setup>
import { computed, onMounted, ref } from "vue";
import AppConfirm from "@/components/ui/AppConfirm.vue";
import AppDataTable from "@/components/ui/AppDataTable.vue";
import AppError from "@/components/ui/AppError.vue";
import AppPageHeader from "@/components/ui/AppPageHeader.vue";
import { useServiceStore } from "@/stores/services";
import { useDebouncedWatch } from "@/composables/useDebouncedWatch";
import { useUrlState, integerRange, oneOf } from "@/composables/useUrlState";

const services = useServiceStore();
const toast = useToast();
const urlState = useUrlState({
  search: "",
  category: "",
  active: { default: "", sanitize: oneOf(["", "true", "false"]) },
  page: { default: 1, sanitize: integerRange(1) },
  per_page: { default: 10, sanitize: integerRange(1, 100) },
});
const selected = ref(null);
const categorySearchTerm = ref("");
const draftCategory = ref("");
const draftActive = ref("");
const search = computed({
  get: () => urlState.search.value,
  set: (value) => updateState({ search: value, page: 1 }),
});
const category = computed({
  get: () => urlState.category.value,
  set: (value) => updateState({ category: value, page: 1 }),
});
const active = computed({
  get: () => urlState.active.value,
  set: (value) => updateState({ active: value, page: 1 }),
});
const page = computed({
  get: () => urlState.page.value,
  set: (value) => updateState({ page: value }, true),
});
const perPage = computed({
  get: () => urlState.per_page.value,
  set: (value) => updateState({ per_page: value, page: 1 }, true),
});

onMounted(async () => {
  await Promise.all([services.fetchCategories(), services.fetchAll(query())]);
});

useDebouncedWatch(categorySearchTerm, (value) =>
  services.fetchCategories(value.trim() ? { search: value.trim() } : {}),
);

useDebouncedWatch(
  [() => search.value, () => category.value, () => active.value],
  () => updateState({ page: 1, search: search.value, category: category.value, active: active.value }, true),
);

function query(currentPage = page.value) {
  return {
    page: currentPage,
    per_page: perPage.value,
    ...(search.value.trim() ? { search: search.value.trim() } : {}),
    ...(category.value ? { category: category.value } : {}),
    ...(active.value !== "" ? { active: active.value === "true" ? 1 : 0 } : {}),
  };
}
async function goToPage(value) {
  updateState({ page: value }, true);
}
async function deactivate() {
  try {
    const item = await services.deactivate(selected.value.id);
    services.updateItem(item);
    toast.add({
      title: "Service deactivated",
      description: "The service is no longer bookable by clients.",
      color: "success",
    });
    selected.value = null;
  } catch {
    /* Store state is rendered below. */
  }
}
async function activate(service) {
  try {
    const item = await services.activate(service.id);
    services.updateItem(item);
    toast.add({
      title: "Service activated",
      description: "The service is now bookable by clients.",
      color: "success",
    });
  } catch {
    /* Store state is rendered below. */
  }
}
function clearFilters() {
  draftCategory.value = "";
  draftActive.value = "";
  updateState({ search: "", category: "", active: "", page: 1 }, true);
}
function syncFilterDraft(open) {
  if (!open) return;

  draftCategory.value = category.value;
  draftActive.value = active.value;
}
function applyFilters() {
  updateState({
    category: draftCategory.value,
    active: draftActive.value,
    page: 1,
  });
}
function updateState(updates, fetch = false) {
  if (updates.search !== undefined) urlState.search.value = updates.search;
  if (updates.category !== undefined) urlState.category.value = updates.category;
  if (updates.active !== undefined) urlState.active.value = updates.active;
  if (updates.page !== undefined) urlState.page.value = updates.page;
  if (updates.per_page !== undefined) urlState.per_page.value = updates.per_page;

  if (fetch) {
    services.fetchAll(query());
  }
}
</script>
<template>
  <section class="space-y-6">
    <AppPageHeader
      :breadcrumbs="[{ label: 'Dashboard', to: '/' }, { label: 'Services' }]"
      title="Services"
    >
      <template #actions>
        <UButton
          to="/services/create"
          trailing-icon="i-lucide-plus"
          class="w-full sm:w-auto"
          >Add service</UButton
        >
      </template>
    </AppPageHeader>
    <AppError
      v-if="services.error"
      :message="services.error"
      :retry="true"
      @retry="services.fetchAll(query())"
    />

    <div v-else class="space-y-4">
      <AppDataTable
        layout="grid"
        :data="services.items"
        v-model:search="search"
        search-placeholder="Search by name or short description"
        :filter-count="Number(Boolean(category)) + Number(Boolean(active))"
        :current-page="services.pagination?.current_page || page"
        :total="services.pagination?.total || 0"
        v-model:per-page="perPage"
        :is-loading="services.isLoading"
        empty-message="No services found."
        empty-icon="i-lucide-briefcase-business"
        :empty-action="{ to: '/services/create', label: 'Add service', icon: 'i-lucide-plus' }"
        @change="goToPage"
        @clear-filters="clearFilters"
        @filters-open="syncFilterDraft"
        @apply-filters="applyFilters"
      >
        <template #filters>
          <UFormField label="Category">
            <USelectMenu
              v-model="draftCategory"
              v-model:search-term="categorySearchTerm"
              placeholder="All categories"
              :items="services.categories"
              ignore-filter
              :search-input="{ placeholder: 'Search categories...', variant: 'none' }"
              clear
              class="w-full"
            />
          </UFormField>
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
            <UBadge v-if="item.category" class="mt-3" color="primary" variant="subtle">
              {{ item.category }}
            </UBadge>
            <p v-if="item.shortDescription" class="mt-3 text-sm text-default">
              {{ item.shortDescription }}
            </p>
            <p class="mt-3 line-clamp-2 text-sm text-muted">
              {{ item.description || "No description" }}
            </p>
            <div class="mt-5 flex flex-wrap gap-2">
              <UButton size="sm" variant="link" :to="`/services/${item.id}`"
                >View</UButton
              >
              <UButton
                size="sm"
                color="neutral"
                variant="link"
                :to="`/services/${item.id}/edit`"
                >Edit</UButton
              >
              <UButton
                v-if="item.active"
                size="sm"
                color="error"
                variant="link"
                @click="selected = item"
                >Deactivate</UButton
              >
              <UButton
                v-else
                size="sm"
                color="success"
                variant="link"
                :disabled="services.isSaving"
                @click="activate(item)"
                >Activate</UButton
              >
            </div>
          </UCard>
        </template>
      </AppDataTable>
    </div>
    <AppConfirm
      :open="Boolean(selected)"
      title="Deactivate service?"
      description="The service will stop appearing in new client bookings while historical appointments remain available."
      confirm-label="Deactivate"
      variant="warning"
      :loading="services.isSaving"
      @cancel="selected = null"
      @confirm="deactivate"
    />
  </section>
</template>
