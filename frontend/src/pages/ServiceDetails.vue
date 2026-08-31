<script setup>
import { computed, onMounted, ref } from "vue";
import { useRoute } from "vue-router";

import AppBreadcrumbs from "../components/AppBreadcrumbs.vue";
import AppConfirm from "../components/AppConfirm.vue";
import AppError from "../components/AppError.vue";
import AppLoading from "../components/AppLoading.vue";
import AppNotFound from "../components/AppNotFound.vue";
import AppPageHeader from "../components/AppPageHeader.vue";
import AppointmentRelationshipManager from "../components/AppointmentRelationshipManager.vue";
import EnumBadge from "../components/EnumBadge.vue";
import { useAppointmentStore } from "../stores/appointments";
import { useServiceStore } from "../stores/services";

const route = useRoute();
const services = useServiceStore();
const appointments = useAppointmentStore();
const toast = useToast();
const pendingAction = ref(null);
const appointmentActions = {
  confirm: {
    title: "Confirm appointment?",
    description: "The client will see this booking as confirmed.",
    confirmLabel: "Confirm appointment",
    variant: "info",
  },
  complete: {
    title: "Complete appointment?",
    description: "This marks the appointment as completed and cannot be changed afterwards.",
    confirmLabel: "Complete appointment",
    variant: "success",
  },
  cancel: {
    title: "Cancel appointment?",
    description: "This appointment will be marked as cancelled and cannot be restored.",
    confirmLabel: "Cancel appointment",
    variant: "warning",
  },
  delete: {
    title: "Delete appointment?",
    description: "This appointment will be permanently deleted.",
    confirmLabel: "Delete",
    variant: "error",
  },
};
const pendingActionConfig = computed(
  () => appointmentActions[pendingAction.value?.action] ?? null,
);

onMounted(() => services.fetch(route.params.id));

function formatDateTime(date) {
  if (!date) return "Not available";

  return new Intl.DateTimeFormat(undefined, {
    dateStyle: "medium",
    timeStyle: "short",
  }).format(new Date(date));
}

function handleAppointmentAction(action) {
  pendingAction.value = action;
}

async function runPendingAction() {
  const { id, action } = pendingAction.value;

  try {
    if (action === "delete") {
      await appointments.remove(id);
      services.current.appointments = services.current.appointments.filter(
        (item) => item.id !== id,
      );
    } else {
      const updated = await appointments[action](id);
      const index = services.current.appointments.findIndex(
        (item) => item.id === id,
      );
      if (index >= 0) services.current.appointments.splice(index, 1, updated);
    }

    const pastTense = {
      confirm: "confirmed",
      complete: "completed",
      cancel: "cancelled",
      delete: "deleted",
    };
    toast.add({
      title: "Success",
      description: `Appointment ${pastTense[action]} successfully.`,
      color: "success",
    });
  } catch {
    // Store state is rendered below.
  } finally {
    pendingAction.value = null;
  }
}
</script>

<template>
  <section class="space-y-6">
    <AppBreadcrumbs
      :items="[{ label: 'Services', to: '/services' }, { label: 'View' }]"
    />
    <AppPageHeader title="Service details">
      <template v-if="services.current" #actions>
        <UButton
          color="neutral"
          variant="outline"
          class="w-full sm:w-auto"
          :to="`/services/${services.current.id}/edit`"
          >Edit</UButton
        >
      </template>
    </AppPageHeader>
    <AppLoading v-if="services.isLoading" message="Loading service..." />
    <AppError
      v-else-if="services.error && services.errorStatus !== 404"
      :message="services.error"
    />
    <AppNotFound
      v-else-if="services.errorStatus === 404"
      resource="Service"
      back-to="/services"
    />
    <div v-else-if="services.current" class="space-y-8">
      <div
        class="max-w-2xl rounded-2xl border border-slate-800 bg-slate-900 p-4 sm:p-6"
      >
        <div class="flex flex-wrap items-start justify-between gap-3">
          <h2 class="truncate text-xl font-semibold sm:text-2xl">
            {{ services.current.name }}
          </h2>
          <EnumBadge
            :value="services.current.active ? 'Active' : 'Inactive'"
            kind="active"
          />
        </div>
        <div class="mt-4 flex flex-wrap gap-2">
          <UBadge v-if="services.current.category" color="primary" variant="subtle">
            {{ services.current.category }}
          </UBadge>
        </div>
        <p v-if="services.current.shortDescription" class="mt-4 text-slate-300">
          {{ services.current.shortDescription }}
        </p>
        <p class="mt-5 text-slate-400">
          {{ services.current.description || "No description" }}
        </p>
        <dl class="mt-6 grid gap-5 border-t border-slate-800 pt-6 sm:grid-cols-2">
          <div>
            <dt class="text-xs uppercase tracking-wide text-slate-500">Created</dt>
            <dd class="mt-1 text-slate-300">
              {{ formatDateTime(services.current.createdAt) }}
            </dd>
          </div>
          <div>
            <dt class="text-xs uppercase tracking-wide text-slate-500">Last updated</dt>
            <dd class="mt-1 text-slate-300">
              {{ formatDateTime(services.current.updatedAt) }}
            </dd>
          </div>
        </dl>
      </div>

      <div class="w-full">
        <AppointmentRelationshipManager
          :appointments="services.current.appointments"
          relationship="service"
          @action="handleAppointmentAction"
        >
          <template #actions>
            <UButton
              :to="{ path: '/appointments/create', query: { serviceId: String(services.current.id) } }"
              trailing-icon="i-lucide-plus"
            >
              Create appointment
            </UButton>
          </template>
        </AppointmentRelationshipManager>
      </div>
    </div>
    <UAlert
      v-if="appointments.error"
      color="error"
      variant="soft"
      :description="appointments.error"
    />
    <AppConfirm
      :open="Boolean(pendingAction)"
      v-bind="pendingActionConfig"
      :loading="appointments.isSaving"
      @cancel="pendingAction = null"
      @confirm="runPendingAction"
    />
  </section>
</template>
