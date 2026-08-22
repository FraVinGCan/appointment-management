<script setup>
import { computed, onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";

import AppConfirm from "../components/AppConfirm.vue";
import AppError from "../components/AppError.vue";
import AppLoading from "../components/AppLoading.vue";
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
function formatDate(date) {
  return new Intl.DateTimeFormat(undefined, { dateStyle: "long" }).format(
    new Date(`${date}T00:00:00`),
  );
}
async function workflow(action) {
  const updated = await appointments[action](appointment.value.id);
  appointments.updateItem(updated);
  notifications.notify(
    `Appointment ${action === "cancel" ? "cancelled" : `${action}d`} successfully.`,
  );
  confirmation.value = null;
}
async function remove() {
  await appointments.remove(appointment.value.id);
  appointments.removeItem(appointment.value.id);
  notifications.notify("Appointment deleted.");
  router.push("/appointments");
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
        <h1 class="mt-2 text-3xl font-semibold">Appointment details</h1>
      </div>
      <div v-if="appointment" class="flex gap-2">
        <RouterLink
          class="rounded-lg border border-slate-700 px-4 py-2 text-sm"
          :to="`/appointments/${appointment.id}/edit`"
          >Edit</RouterLink
        ><button
          class="rounded-lg border border-rose-800 px-4 py-2 text-sm text-rose-300"
          type="button"
          @click="confirmation = 'delete'">
          Delete
        </button>
      </div>
    </div>
    <AppLoading
      v-if="appointments.isLoading"
      message="Loading appointment..." /><AppError
      v-else-if="appointments.error"
      :message="appointments.error" />
    <div
      v-else-if="appointment"
      class="max-w-3xl rounded-2xl border border-slate-800 bg-slate-900 p-6">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h2 class="text-2xl font-semibold">
            {{ appointment.service?.name }}
          </h2>
          <p class="mt-1 text-slate-400">
            {{ formatDate(appointment.appointmentDate) }} ·
            {{ appointment.startTime }} - {{ appointment.endTime }}
          </p>
        </div>
        <span
          class="rounded-full bg-slate-800 px-3 py-1 text-sm font-semibold text-cyan-300"
          >{{ appointment.status }}</span
        >
      </div>
      <dl class="mt-8 grid gap-5 border-t border-slate-800 pt-6 sm:grid-cols-2">
        <div>
          <dt class="text-xs uppercase tracking-wide text-slate-500">Client</dt>
          <dd class="mt-1">{{ appointment.client?.name }}</dd>
        </div>
        <div>
          <dt class="text-xs uppercase tracking-wide text-slate-500">
            Priority
          </dt>
          <dd class="mt-1">{{ appointment.priority }}</dd>
        </div>
        <div>
          <dt class="text-xs uppercase tracking-wide text-slate-500">
            Service
          </dt>
          <dd class="mt-1">{{ appointment.service?.name }}</dd>
        </div>
        <div>
          <dt class="text-xs uppercase tracking-wide text-slate-500">Notes</dt>
          <dd class="mt-1 text-slate-300">
            {{ appointment.notes || "No notes" }}
          </dd>
        </div>
      </dl>
      <div class="mt-8 flex flex-wrap gap-3 border-t border-slate-800 pt-6">
        <button
          v-if="canConfirm"
          class="rounded-lg bg-cyan-400 px-4 py-2 text-sm font-semibold text-slate-950 disabled:opacity-50"
          type="button"
          :disabled="appointments.isSaving"
          @click="workflow('confirm')">
          Confirm</button
        ><button
          v-if="canComplete"
          class="rounded-lg bg-emerald-500 px-4 py-2 text-sm font-semibold text-slate-950 disabled:opacity-50"
          type="button"
          :disabled="appointments.isSaving"
          @click="workflow('complete')">
          Complete</button
        ><button
          v-if="canCancel"
          class="rounded-lg border border-rose-800 px-4 py-2 text-sm font-semibold text-rose-300 disabled:opacity-50"
          type="button"
          :disabled="appointments.isSaving"
          @click="confirmation = 'cancel'">
          Cancel appointment
        </button>
      </div>
    </div>
    <AppConfirm
      :open="confirmation === 'delete'"
      title="Delete appointment?"
      message="This appointment will be permanently deleted."
      confirm-label="Delete"
      :loading="appointments.isSaving"
      @cancel="confirmation = null"
      @confirm="remove" /><AppConfirm
      :open="confirmation === 'cancel'"
      title="Cancel appointment?"
      message="This appointment will be marked as cancelled."
      confirm-label="Cancel appointment"
      :loading="appointments.isSaving"
      @cancel="confirmation = null"
      @confirm="workflow('cancel')" />
  </section>
</template>
