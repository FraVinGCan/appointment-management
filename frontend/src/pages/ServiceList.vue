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
        <p
          class="text-xs font-semibold uppercase tracking-[0.25em] text-cyan-400">
          Staff workspace
        </p>
        <h1 class="mt-2 text-3xl font-semibold">Services</h1>
      </div>
      <RouterLink
        class="rounded-lg bg-cyan-400 px-4 py-2 text-sm font-semibold text-slate-950"
        to="/services/create"
        >Add service</RouterLink
      >
    </div>
    <AppLoading
      v-if="services.isLoading"
      message="Loading services..." /><AppError
      v-else-if="services.error"
      :message="services.error"
      :retry="true"
      @retry="services.fetchAll()" /><AppEmpty
      v-else-if="!services.items.length"
      message="No services found." />
    <div v-else class="grid gap-4 md:grid-cols-2">
      <article
        v-for="service in services.items"
        :key="service.id"
        class="rounded-2xl border border-slate-800 bg-slate-900 p-5">
        <div class="flex items-start justify-between gap-3">
          <h2 class="font-semibold">{{ service.name }}</h2>
          <span
            :class="service.active ? 'text-emerald-300' : 'text-slate-500'"
            class="text-xs"
            >{{ service.active ? "Active" : "Inactive" }}</span
          >
        </div>
        <p class="mt-2 text-sm text-slate-400">
          {{ service.description || "No description" }}
        </p>
        <p class="mt-4 text-sm text-slate-300">
          {{ service.durationMinutes }} minutes
        </p>
        <div class="mt-5 flex gap-3 text-xs">
          <RouterLink
            class="font-semibold text-cyan-300"
            :to="`/services/${service.id}`"
            >View</RouterLink
          ><RouterLink
            class="font-semibold text-slate-300"
            :to="`/services/${service.id}/edit`"
            >Edit</RouterLink
          ><button
            v-if="service.active"
            class="font-semibold text-rose-300"
            type="button"
            @click="selected = service">
            Deactivate
          </button>
        </div>
      </article>
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
