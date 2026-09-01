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
import { useClientStore } from "../stores/clients";

const route = useRoute();
const clients = useClientStore();
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

onMounted(() => clients.fetch(route.params.id));

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
      clients.current.appointments = clients.current.appointments.filter(
        (item) => item.id !== id,
      );
    } else {
      const updated = await appointments[action](id);
      const index = clients.current.appointments.findIndex(
        (item) => item.id === id,
      );
      if (index >= 0) clients.current.appointments.splice(index, 1, updated);
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
      :items="[{ label: 'Clients', to: '/clients' }, { label: 'View' }]"
    />
    <AppPageHeader title="Client details">
      <template v-if="clients.current" #actions>
        <UButton
          color="neutral"
          variant="outline"
          class="w-full sm:w-auto"
          :to="`/clients/${clients.current.id}/edit`"
          >Edit</UButton
        >
      </template>
    </AppPageHeader>
    <AppLoading v-if="clients.isLoading" message="Loading client..." />
    <AppError
      v-else-if="clients.error && clients.errorStatus !== 404"
      :message="clients.error"
    />
    <AppNotFound
      v-else-if="clients.errorStatus === 404"
      resource="Client"
      back-to="/clients"
    />
    <div v-else-if="clients.current" class="space-y-8">
      <div
        class="max-w-2xl rounded-2xl border border-default bg-default p-4 sm:p-6"
      >
        <h2 class="truncate text-xl font-semibold sm:text-2xl">
          {{ clients.current.name }}
        </h2>
        <dl class="mt-6 space-y-4 border-t border-default pt-6">
          <div>
            <dt class="text-xs uppercase tracking-wide text-muted">Email</dt>
            <dd class="mt-1 break-all truncate">{{ clients.current.email }}</dd>
          </div>
          <div>
            <dt class="text-xs uppercase tracking-wide text-muted">Phone</dt>
            <dd class="mt-1">{{ clients.current.phone || "Not provided" }}</dd>
          </div>
          <div>
            <dt class="text-xs uppercase tracking-wide text-muted">Status</dt>
            <dd class="mt-1">
              <EnumBadge
                :value="clients.current.active === false ? 'Inactive' : 'Active'"
                kind="active"
              />
            </dd>
          </div>
          <div>
            <dt class="text-xs uppercase tracking-wide text-muted">Created</dt>
            <dd class="mt-1 text-default">
              {{ formatDateTime(clients.current.createdAt) }}
            </dd>
          </div>
          <div>
            <dt class="text-xs uppercase tracking-wide text-muted">Last updated</dt>
            <dd class="mt-1 text-default">
              {{ formatDateTime(clients.current.updatedAt) }}
            </dd>
          </div>
        </dl>
      </div>

      <div class="w-full">
        <AppointmentRelationshipManager
          :appointments="clients.current.appointments"
          relationship="client"
          @action="handleAppointmentAction"
        >
          <template #actions>
            <UButton
              :to="{ path: '/appointments/create', query: { clientId: String(clients.current.id) } }"
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
