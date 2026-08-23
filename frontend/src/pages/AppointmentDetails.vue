<script setup>
import { computed, onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";

import AppConfirm from "../components/AppConfirm.vue";
import AppError from "../components/AppError.vue";
import AppLoading from "../components/AppLoading.vue";
import AppNotFound from "../components/AppNotFound.vue";
import AppBreadcrumbs from "../components/AppBreadcrumbs.vue";
import EnumBadge from "../components/EnumBadge.vue";
import { useAppointmentStore } from "../stores/appointments";
import { useNotificationStore } from "../stores/notifications";

const route = useRoute();
const router = useRouter();
const appointments = useAppointmentStore();
const notifications = useNotificationStore();
const confirmation = ref(null);
onMounted(() => appointments.fetch(route.params.id));
const appointment = computed(() => appointments.current);
const canConfirm = computed(() => appointment.value?.status === "Requested");
const canComplete = computed(() => appointment.value?.status === "Confirmed");
const canCancel = computed(() =>
  ["Requested", "Confirmed"].includes(appointment.value?.status),
);
const confirmationCopy = {
  confirm: {
    title: "Confirm appointment?",
    message: "The client will see this booking as confirmed.",
    confirmLabel: "Confirm appointment",
  },
  complete: {
    title: "Complete appointment?",
    message:
      "This marks the appointment as completed and cannot be changed afterwards.",
    confirmLabel: "Complete appointment",
  },
  cancel: {
    title: "Cancel appointment?",
    message:
      "This appointment will be marked as cancelled and cannot be restored.",
    confirmLabel: "Cancel appointment",
  },
  delete: {
    title: "Delete appointment?",
    message: "This appointment will be permanently deleted.",
    confirmLabel: "Delete",
  },
};
const confirmationConfig = computed(
  () => confirmationCopy[confirmation.value] ?? null,
);
function formatDate(date) {
  return new Intl.DateTimeFormat(undefined, { dateStyle: "long" }).format(
    new Date(`${date}T00:00:00`),
  );
}
const actionPastTense = {
  confirm: "confirmed",
  complete: "completed",
  cancel: "cancelled",
};
async function workflow(action) {
  const updated = await appointments[action](appointment.value.id);
  appointments.updateItem(updated);
  notifications.notify(`Appointment ${actionPastTense[action]} successfully.`);
  confirmation.value = null;
}
async function remove() {
  await appointments.remove(appointment.value.id);
  appointments.removeItem(appointment.value.id);
  notifications.notify("Appointment deleted.");
  router.push("/appointments");
}
async function runConfirmation() {
  if (confirmation.value === "delete") await remove();
  else await workflow(confirmation.value);
}
</script>

<template>
  <section class="space-y-6">
    <AppBreadcrumbs
      :items="[
        { label: 'Appointments', to: '/appointments' },
        { label: 'View' },
      ]"
    />
    <div class="flex flex-wrap items-end justify-between gap-4">
      <div>
        <p
          class="text-xs font-semibold uppercase tracking-[0.25em] text-cyan-400"
        >
          Admin workspace
        </p>
        <h1 class="mt-2 text-2xl sm:text-3xl font-semibold">
          Appointment details
        </h1>
      </div>
      <div v-if="appointment" class="flex flex-wrap gap-2">
        <UButton
          color="neutral"
          variant="outline"
          class="w-full sm:w-auto"
          :to="`/appointments/${appointment.id}/edit`"
          >Edit</UButton
        >
        <UButton
          color="error"
          variant="outline"
          class="w-full sm:w-auto"
          @click="confirmation = 'delete'"
          >Delete</UButton
        >
      </div>
    </div>
    <AppLoading
      v-if="appointments.isLoading"
      message="Loading appointment..."
    />
    <AppError
      v-else-if="appointments.error && appointments.errorStatus !== 404"
      :message="appointments.error"
    />
    <AppNotFound
      v-else-if="appointments.errorStatus === 404"
      resource="Appointment"
      back-to="/appointments"
    />
    <div
      v-else-if="appointment"
      class="max-w-3xl rounded-2xl border border-slate-800 bg-slate-900 p-4 sm:p-6"
    >
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
          <h2 class="text-xl sm:text-2xl font-semibold truncate">
            {{ appointment.service?.name }}
          </h2>
          <p class="mt-1 text-slate-400">
            {{ formatDate(appointment.appointmentDate) }} ·
            {{ appointment.startTime }} - {{ appointment.endTime }}
          </p>
        </div>
        <EnumBadge :value="appointment.status" kind="status" />
      </div>
      <dl class="mt-8 grid gap-5 border-t border-slate-800 pt-6 sm:grid-cols-2">
        <div>
          <dt class="text-xs uppercase tracking-wide text-slate-500">Client</dt>
          <dd class="mt-1 truncate">{{ appointment.client?.name }}</dd>
        </div>
        <div>
          <dt class="text-xs uppercase tracking-wide text-slate-500">
            Priority
          </dt>
          <dd class="mt-1">
            <EnumBadge :value="appointment.priority" kind="priority" />
          </dd>
        </div>
        <div>
          <dt class="text-xs uppercase tracking-wide text-slate-500">
            Service
          </dt>
          <dd class="mt-1 truncate">{{ appointment.service?.name }}</dd>
        </div>
        <div>
          <dt class="text-xs uppercase tracking-wide text-slate-500">Notes</dt>
          <dd class="mt-1 text-slate-300">
            {{ appointment.notes || "No notes" }}
          </dd>
        </div>
      </dl>
      <div class="mt-8 flex flex-wrap gap-3 border-t border-slate-800 pt-6">
        <UButton
          v-if="canConfirm"
          class="w-full sm:w-auto"
          @click="confirmation = 'confirm'"
          >Confirm</UButton
        >
        <UButton
          v-if="canComplete"
          color="success"
          class="w-full sm:w-auto"
          @click="confirmation = 'complete'"
          >Complete</UButton
        >
        <UButton
          v-if="canCancel"
          color="error"
          variant="outline"
          class="w-full sm:w-auto"
          @click="confirmation = 'cancel'"
          >Cancel appointment</UButton
        >
      </div>
    </div>
    <AppConfirm
      :open="Boolean(confirmation)"
      v-bind="confirmationConfig"
      :loading="appointments.isSaving"
      @cancel="confirmation = null"
      @confirm="runConfirmation"
    />
  </section>
</template>
