<script setup>
import { computed, onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";

import AppConfirm from "../components/AppConfirm.vue";
import AppError from "../components/AppError.vue";
import AppLoading from "../components/AppLoading.vue";
import AppNotFound from "../components/AppNotFound.vue";
import AppBreadcrumbs from "../components/AppBreadcrumbs.vue";
import AppPageHeader from "../components/AppPageHeader.vue";
import EnumBadge from "../components/EnumBadge.vue";
import { useAppointmentStore } from "../stores/appointments";

const route = useRoute();
const router = useRouter();
const appointments = useAppointmentStore();
const toast = useToast();
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

function formatTime(time) {
  if (!time) return "";

  const [hours, minutes] = String(time).split(":").map(Number);
  if (!Number.isInteger(hours) || !Number.isInteger(minutes)) return String(time);

  return new Intl.DateTimeFormat(undefined, {
    hour: "numeric",
    minute: "2-digit",
  }).format(new Date(1970, 0, 1, hours, minutes));
}
function formatDateTime(date) {
  if (!date) return "Not available";

  return new Intl.DateTimeFormat(undefined, {
    dateStyle: "medium",
    timeStyle: "short",
  }).format(new Date(date));
}
const actionPastTense = {
  confirm: "confirmed",
  complete: "completed",
  cancel: "cancelled",
};
async function workflow(action) {
  const updated = await appointments[action](appointment.value.id);
  appointments.updateItem(updated);
  toast.add({
    title: "Success",
    description: `Appointment ${actionPastTense[action]} successfully.`,
    color: "success",
  });
  confirmation.value = null;
}
async function remove() {
  await appointments.remove(appointment.value.id);
  appointments.removeItem(appointment.value.id);
  toast.add({
    title: "Appointment deleted",
    description: "The appointment was removed permanently.",
    color: "success",
  });
  router.push("/appointments");
}
async function runConfirmation() {
  if (confirmation.value === "delete") await remove();
  else await workflow(confirmation.value);
}
</script>

<template>
  <section class="space-y-6">
    <AppPageHeader
      :breadcrumbs="[
        { label: 'Appointments', to: '/appointments' },
        { label: 'View' },
      ]"
      title="Appointment details"
    >
      <template v-if="appointment" #actions>
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
      </template>
    </AppPageHeader>
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
            <RouterLink
              v-if="appointment.service?.id"
              :to="`/services/${appointment.service.id}/edit`"
              class="text-primary-400 hover:underline"
            >
              {{ appointment.service.name }}
            </RouterLink>
            <span v-else>{{ appointment.service?.name }}</span>
          </h2>
          <p class="mt-1 font-semibold text-cyan-300">
            {{ formatDate(appointment.appointmentDate) }} ·
            {{ formatTime(appointment.startTime) }} - {{ formatTime(appointment.endTime) }}
          </p>
        </div>
        <EnumBadge :value="appointment.status" kind="status" />
      </div>
      <dl class="mt-8 grid gap-5 border-t border-slate-800 pt-6 sm:grid-cols-2">
        <div>
          <dt class="text-xs uppercase tracking-wide text-slate-500">Client</dt>
          <dd class="mt-1 truncate">
            <RouterLink
              v-if="appointment.client?.id"
              :to="`/clients/${appointment.client.id}/edit`"
              class="text-primary-400 hover:underline"
            >
              {{ appointment.client.name }}
            </RouterLink>
            <span v-else>{{ appointment.client?.name }}</span>
          </dd>
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
          <dd class="mt-1 truncate">
            <RouterLink
              v-if="appointment.service?.id"
              :to="`/services/${appointment.service.id}/edit`"
              class="text-primary-400 hover:underline"
            >
              {{ appointment.service.name }}
            </RouterLink>
            <span v-else>{{ appointment.service?.name }}</span>
          </dd>
        </div>
        <div>
          <dt class="text-xs uppercase tracking-wide text-slate-500">Notes</dt>
          <dd class="mt-1 text-slate-300">
            {{ appointment.notes || "No notes" }}
          </dd>
        </div>
        <div>
          <dt class="text-xs uppercase tracking-wide text-slate-500">
            Created
          </dt>
          <dd class="mt-1 text-slate-300">
            {{ formatDateTime(appointment.createdAt) }}
          </dd>
        </div>
        <div>
          <dt class="text-xs uppercase tracking-wide text-slate-500">
            Last updated
          </dt>
          <dd class="mt-1 text-slate-300">
            {{ formatDateTime(appointment.updatedAt) }}
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
