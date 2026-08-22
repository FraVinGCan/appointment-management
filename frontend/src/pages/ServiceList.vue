<script setup>
import { onMounted, ref } from "vue";
import AppConfirm from "../components/AppConfirm.vue";
import AppEmpty from "../components/AppEmpty.vue";
import AppError from "../components/AppError.vue";
import AppLoading from "../components/AppLoading.vue";
import { useNotificationStore } from "../stores/notifications";
import { useServiceStore } from "../stores/services";
const services = useServiceStore();
const notifications = useNotificationStore();
const selected = ref(null);
onMounted(() => services.fetchAll());
async function deactivate() {
  try {
    const item = await services.deactivate(selected.value.id);
    const index = services.items.findIndex((service) => service.id === item.id);
    if (index >= 0) services.items.splice(index, 1, item);
    notifications.notify("Service deactivated.");
    selected.value = null;
  } catch {
    /* Store state is rendered below. */
  }
}
</script>
<template>
  <section class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
      <div>
        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-cyan-400">
          Staff workspace
        </p>
        <h1 class="mt-2 text-2xl sm:text-3xl font-semibold">Services</h1>
      </div>
      <UButton to="/services/create" trailing-icon="i-lucide-plus" class="w-full sm:w-auto">Add service</UButton>
    </div>
    <AppLoading v-if="services.isLoading" message="Loading services..." />
    <AppError v-else-if="services.error" :message="services.error" :retry="true" @retry="services.fetchAll()" />
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
          </UBadge
          >
        </div>
        <p class="mt-3 text-sm text-slate-400">
          {{ service.description || "No description" }}
        </p>
        <p class="mt-4 text-sm">{{ service.durationMinutes }} minutes</p>
        <div class="mt-5 flex flex-wrap gap-2">
          <UButton size="sm" variant="link" :to="`/services/${service.id}`">View</UButton>
          <UButton size="sm" color="neutral" variant="link" :to="`/services/${service.id}/edit`">Edit</UButton>
          <UButton v-if="service.active" size="sm" color="error" variant="link" @click="selected = service">Deactivate</UButton>
        </div>
      </UCard>
    </div>
    <AppConfirm
      :open="Boolean(selected)"
      title="Deactivate service?"
      message="The service will stop appearing in new client bookings while historical appointments remain available."
      confirm-label="Deactivate"
      :loading="services.isSaving"
      @cancel="selected = null"
      @confirm="deactivate" />
  </section>
</template>
