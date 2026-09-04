<script setup>
import { computed, onMounted, ref } from "vue";
import { useAuthStore } from "@/stores/auth";

import AppError from "@/components/ui/AppError.vue";
import AppLoading from "@/components/ui/AppLoading.vue";
import AppPagination from "@/components/ui/AppPagination.vue";
import { useServiceStore } from "@/stores/services";
import { useDebouncedWatch } from "@/composables/useDebouncedWatch";
import { useUrlState, integerRange } from "@/composables/useUrlState";

const services = useServiceStore();
const auth = useAuthStore();
const urlState = useUrlState({
  search: "",
  category: "",
  page: { default: 1, sanitize: integerRange(1) },
});
const categorySearchTerm = ref("");
const search = computed({
  get: () => urlState.search.value,
  set: (value) => updateState({ search: value, page: 1 }),
});
const category = computed({
  get: () => urlState.category.value,
  set: (value) => updateState({ category: value, page: 1 }),
});
const page = computed({
  get: () => urlState.page.value,
  set: (value) => updateState({ page: value }, true),
});

onMounted(async () => {
  if (auth.isClient) await services.fetchActiveCategories();
  await services.fetchActive(query());
});

useDebouncedWatch(categorySearchTerm, (value) => {
  if (auth.isClient) {
    services.fetchActiveCategories(value.trim() ? { search: value.trim() } : {});
  }
});

useDebouncedWatch(
  [() => search.value, () => category.value],
  () => updateState({ page: 1, search: search.value, category: category.value }, true),
);

function query(currentPage = page.value) {
  return {
    page: currentPage,
    ...(search.value.trim() ? { search: search.value.trim() } : {}),
    ...(auth.isClient && category.value ? { category: category.value } : {}),
  };
}
async function goToPage(value) {
  updateState({ page: value }, true);
}
function clearFilters() {
  updateState({ search: "", category: "", page: 1 }, true);
}
function updateState(updates, fetch = false) {
  if (updates.search !== undefined) urlState.search.value = updates.search;
  if (updates.category !== undefined) urlState.category.value = updates.category;
  if (updates.page !== undefined) urlState.page.value = updates.page;

  if (fetch) {
    services.fetchActive(query());
  }
}
</script>

<template>
  <div class="space-y-8">
    <section
      class="rounded-2xl border border-default bg-elevated p-5 sm:p-8"
    >
      <p
        class="text-sm font-semibold uppercase tracking-[0.25em] text-primary"
      >
        Appointment Desk
      </p>
      <h1 class="mt-3 text-2xl sm:text-3xl font-semibold">
        Our service marketplace
      </h1>
      <p class="mt-2 max-w-2xl text-muted">
        Browse everything we offer, then choose a service to view its details
        and request an appointment.
      </p>
    </section>

      <AppLoading
        v-if="services.isLoading"
        message="Loading available services..."
      />
      <template v-else>
        <div class="grid gap-3 sm:flex sm:flex-wrap sm:items-end">
          <UInput
            v-model="search"
            icon="i-lucide-search"
            placeholder="Search by name or short description"
            class="w-full sm:max-w-sm"
          />
          <UFormField v-if="auth.isClient" label="Category">
             <USelectMenu
               v-model="category"
               v-model:search-term="categorySearchTerm"
               placeholder="All categories"
               :items="services.categories"
               ignore-filter
               :search-input="{ placeholder: 'Search categories...', variant: 'none' }"
               clear
               class="w-full sm:w-48"
             />
          </UFormField>
          <UButton
            v-if="search || category"
            color="neutral"
            variant="ghost"
            @click="clearFilters"
          >
            Clear filters
          </UButton>
        </div>

        <AppError
          v-if="services.error"
          :message="services.error"
          :retry="true"
          @retry="services.fetchActive(query())"
          class="mt-6"
        />
        <div
          v-else-if="services.items.length"
          class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
        >
          <UCard
            v-for="service in services.items"
            :key="service.id"
            variant="subtle"
            class="flex flex-col transition-colors hover:border-default"
          >
            <div class="flex h-full flex-col">
              <div class="flex items-start justify-between gap-3">
                <h2 class="font-semibold">{{ service.name }}</h2>
                <UBadge v-if="service.category" color="primary" variant="subtle">
                  {{ service.category }}
                </UBadge>
              </div>
              <p class="mt-2 flex-1 text-sm text-muted">
                {{
                  service.shortDescription ||
                  "No description provided for this service yet."
                }}
              </p>
              <UButton
                class="mt-4 w-full sm:w-auto self-start"
                :to="
                  auth.isClient
                    ? { name: 'client-service-details', params: { id: service.id } }
                    : { name: 'public-service-details', params: { id: service.id } }
                "
                >View service</UButton
              >
            </div>
          </UCard>
        </div>
        <p v-else class="text-center text-muted">
          {{ search || category ? "No services match your filters. Try different criteria." : "No services are available right now. Please check back soon." }}
        </p>
        <AppPagination
          :current-page="services.pagination?.current_page"
          :total="services.pagination?.total"
          :per-page="services.pagination?.per_page"
          :is-loading="services.isLoading"
          @change="goToPage"
        />
      </template>
    </div>
</template>
