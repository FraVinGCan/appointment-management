<script setup>
import { onMounted, ref, watch } from "vue";
import { useAuthStore } from "../stores/auth";

import AppError from "../components/AppError.vue";
import AppLoading from "../components/AppLoading.vue";
import AppPagination from "../components/AppPagination.vue";
import { useServiceStore } from "../stores/services";

const services = useServiceStore();
const auth = useAuthStore();
const search = ref("");
const category = ref("");
const page = ref(1);
let searchTimer;

onMounted(async () => {
  if (auth.isClient) await services.fetchActiveCategories();
  await services.fetchActive(query());
});

watch([search, category], () => {
  window.clearTimeout(searchTimer);
  searchTimer = window.setTimeout(() => {
    page.value = 1;
    services.fetchActive(query());
  }, 250);
});

function query(currentPage = page.value) {
  return {
    page: currentPage,
    ...(search.value.trim() ? { search: search.value.trim() } : {}),
    ...(auth.isClient && category.value ? { category: category.value } : {}),
  };
}
async function goToPage(value) {
  page.value = value;
  await services.fetchActive(query());
}
</script>

<template>
  <main
    class="min-h-screen bg-slate-950 px-4 py-10 sm:px-6 sm:py-12 text-slate-100"
  >
    <div class="mx-auto max-w-5xl space-y-8">
      <section
        class="rounded-2xl border border-slate-800 bg-slate-900 p-5 sm:p-8"
      >
        <p
          class="text-sm font-semibold uppercase tracking-[0.25em] text-cyan-400"
        >
          Appointment Desk
        </p>
        <h1 class="mt-3 text-2xl sm:text-3xl font-semibold">
          Our service marketplace
        </h1>
        <p class="mt-2 max-w-2xl text-slate-400">
          Browse everything we offer, then choose a service to view its details
          and request an appointment.
        </p>
      </section>

      <AppLoading
        v-if="services.isLoading"
        message="Loading available services..."
      />
      <AppError
        v-else-if="services.error"
        :message="services.error"
        :retry="true"
        @retry="services.fetchActive(query())"
      />
      <template v-else>
        <div class="grid gap-3 sm:flex sm:flex-wrap sm:items-end">
          <UInput
            v-model="search"
            icon="i-lucide-search"
            placeholder="Search services..."
            class="w-full sm:max-w-sm"
          />
          <UFormField v-if="auth.isClient" label="Category">
            <USelect
              v-model="category"
              placeholder="All categories"
              :items="services.categories"
              class="w-full sm:w-48"
            />
          </UFormField>
        </div>

        <div
          v-if="services.items.length"
          class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
        >
          <UCard
            v-for="service in services.items"
            :key="service.id"
            variant="subtle"
            class="flex flex-col transition-colors hover:border-slate-600"
          >
            <div class="flex h-full flex-col">
              <div class="flex items-start justify-between gap-3">
                <h2 class="font-semibold text-slate-100">{{ service.name }}</h2>
                <UBadge v-if="service.category" color="primary" variant="subtle">
                  {{ service.category }}
                </UBadge>
              </div>
              <p class="mt-2 flex-1 text-sm text-slate-400">
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
        <p v-else class="text-center text-slate-400">
          {{ search || category ? "No services match your filters. Try different criteria." : "No services are available right now. Please check back soon." }}
        </p>
        <AppPagination
          :current-page="services.pagination?.current_page"
          :last-page="services.pagination?.last_page"
          :is-loading="services.isLoading"
          @change="goToPage"
        />
      </template>
    </div>
  </main>
</template>
