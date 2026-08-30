<script setup>
import { onMounted, ref, watch } from "vue";
import AppConfirm from "../components/AppConfirm.vue";
import AppEmpty from "../components/AppEmpty.vue";
import AppError from "../components/AppError.vue";
import AppLoading from "../components/AppLoading.vue";
import AppPagination from "../components/AppPagination.vue";
import AppPageHeader from "../components/AppPageHeader.vue";
import { useServiceStore } from "../stores/services";
const services = useServiceStore();
const toast = useToast();
const selected = ref(null);
const search = ref("");
const category = ref("");
const active = ref("");
const page = ref(1);
let searchTimer;

onMounted(async () => {
  await Promise.all([services.fetchCategories(), services.fetchAll(query())]);
});

watch(search, () => {
  window.clearTimeout(searchTimer);
  searchTimer = window.setTimeout(() => {
    page.value = 1;
    services.fetchAll(query());
  }, 250);
});
watch([category, active], () => {
  page.value = 1;
  services.fetchAll(query());
});

function query(currentPage = page.value) {
  return {
    page: currentPage,
    ...(search.value.trim() ? { search: search.value.trim() } : {}),
    ...(category.value ? { category: category.value } : {}),
    ...(active.value !== "" ? { active: active.value === "true" } : {}),
  };
}
async function goToPage(value) {
  page.value = value;
  await services.fetchAll(query());
}
async function deactivate() {
  try {
    const item = await services.deactivate(selected.value.id);
    const index = services.items.findIndex((service) => service.id === item.id);
    if (index >= 0) services.items.splice(index, 1, item);
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
    <div class="grid grid-cols-1 gap-3 sm:flex sm:flex-wrap sm:items-end sm:gap-4">
      <label class="block min-w-64 flex-1 text-sm font-medium">
        Search services
        <UInput
          v-model="search"
          icon="i-lucide-search"
          placeholder="Search by service name"
          class="mt-2 w-full"
        />
      </label>
      <UFormField label="Category">
        <USelect
          v-model="category"
          placeholder="All categories"
          :items="services.categories"
          class="w-full sm:w-48"
        />
      </UFormField>
      <UFormField label="Status">
        <USelect
          v-model="active"
          placeholder="All statuses"
          :items="[
            { label: 'Active', value: 'true' },
            { label: 'Inactive', value: 'false' },
          ]"
          class="w-full sm:w-40"
        />
      </UFormField>
      <UButton
        v-if="search || category || active"
        color="neutral"
        variant="ghost"
        @click="search = ''; category = ''; active = ''"
        >Clear filters</UButton
      >
    </div>
    <AppLoading v-if="services.isLoading" message="Loading services..." />
    <AppError
      v-else-if="services.error"
      :message="services.error"
      :retry="true"
      @retry="services.fetchAll(query())"
    />
    <AppEmpty v-else-if="!services.items.length" message="No services found." />
    <div v-else class="grid gap-4 md:grid-cols-2">
      <UCard
        v-for="service in services.items"
        :key="service.id"
        variant="subtle"
      >
        <div class="flex flex-wrap items-start justify-between gap-3">
          <h2 class="font-semibold truncate">{{ service.name }}</h2>
          <UBadge
            :color="service.active ? 'success' : 'neutral'"
            variant="subtle"
          >
            {{ service.active ? "Active" : "Inactive" }}
          </UBadge>
        </div>
        <UBadge v-if="service.category" class="mt-3" color="primary" variant="subtle">
          {{ service.category }}
        </UBadge>
        <p v-if="service.shortDescription" class="mt-3 text-sm text-slate-300">
          {{ service.shortDescription }}
        </p>
        <p class="mt-3 line-clamp-2 text-sm text-slate-400">
          {{ service.description || "No description" }}
        </p>
        <div class="mt-5 flex flex-wrap gap-2">
          <UButton size="sm" variant="link" :to="`/services/${service.id}`"
            >View</UButton
          >
          <UButton
            size="sm"
            color="neutral"
            variant="link"
            :to="`/services/${service.id}/edit`"
            >Edit</UButton
          >
          <UButton
            v-if="service.active"
            size="sm"
            color="error"
            variant="link"
            @click="selected = service"
            >Deactivate</UButton
          >
        </div>
      </UCard>
    </div>
    <AppPagination
      :current-page="services.pagination?.current_page"
      :total="services.pagination?.total"
      :per-page="services.pagination?.per_page"
      :is-loading="services.isLoading"
      @change="goToPage"
    />
    <AppConfirm
      :open="Boolean(selected)"
      title="Deactivate service?"
      message="The service will stop appearing in new client bookings while historical appointments remain available."
      confirm-label="Deactivate"
      :loading="services.isSaving"
      @cancel="selected = null"
      @confirm="deactivate"
    />
  </section>
</template>
