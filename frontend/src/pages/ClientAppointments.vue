<script setup>
import { onMounted, ref } from "vue";

import AppConfirm from "../components/AppConfirm.vue";
import AppEmpty from "../components/AppEmpty.vue";
import AppError from "../components/AppError.vue";
import AppLoading from "../components/AppLoading.vue";
import AppPageHeader from "../components/AppPageHeader.vue";
import AppPagination from "../components/AppPagination.vue";
import { useAppointmentStore } from "../stores/appointments";
import { useNotificationStore } from "../stores/notifications";

const appointments = useAppointmentStore();
const notifications = useNotificationStore();
const appointmentToCancel = ref(null);

onMounted(() => appointments.fetchClientList());

function canCancel(appointment) {
  return ["Requested", "Confirmed"].includes(appointment.status);
}

function formatDate(date) {
  return new Intl.DateTimeFormat(undefined, { dateStyle: "medium" }).format(
    new Date(`${date}T00:00:00`),
  );
}

async function goToPage(page) {
  await appointments.fetchClientList({ page });
}

async function cancelAppointment() {
  try {
    const updated = await appointments.cancelClient(
      appointmentToCancel.value.id,
    );
    appointments.updateItem(updated);
    notifications.notify("Appointment cancelled.");
    appointmentToCancel.value = null;
  } catch {
    // The store exposes the backend conflict message.
  }
}
</script>

<template>
  <section class="space-y-6">
    <AppPageHeader
      title="My appointments"
      description="Review your booking requests and confirmed visits."
    >
      <template #actions>
        <UButton
          to="/book"
          trailing-icon="i-lucide-arrow-right"
          class="w-full sm:w-auto"
          >Book an appointment</UButton
        >
      </template>
    </AppPageHeader>

    <AppLoading
      v-if="appointments.isLoading"
      message="Loading your appointments..."
    />
    <AppError
      v-else-if="appointments.error"
      :message="appointments.error"
      :retry="true"
      @retry="appointments.fetchClientList()"
    />
    <AppEmpty
      v-else-if="!appointments.items.length"
      message="You do not have any appointments yet."
    >
      <UButton class="mt-4" to="/book" variant="link"
        >Request your first appointment</UButton
      >
    </AppEmpty>
    <div v-else class="space-y-4">
      <article
        v-for="appointment in appointments.items"
        :key="appointment.id"
        class="rounded-2xl border border-slate-800 bg-slate-900 p-5"
      >
        <div class="flex flex-wrap items-start justify-between gap-4">
          <div>
            <h2 class="text-lg font-semibold truncate">
              {{ appointment.service?.name || "Appointment" }}
            </h2>
            <p class="mt-1 text-sm text-slate-400">
              {{ formatDate(appointment.appointmentDate) }} ·
              {{ appointment.startTime }} - {{ appointment.endTime }}
            </p>
          </div>
          <UBadge
            :color="appointment.status === 'Cancelled' ? 'neutral' : 'primary'"
            variant="subtle"
            >{{ appointment.status }}</UBadge
          >
        </div>
        <p
          v-if="appointment.notes"
          class="mt-3 whitespace-pre-line text-sm text-slate-300"
        >
          {{ appointment.notes }}
        </p>
        <div
          class="mt-4 flex flex-wrap items-center justify-end gap-4 border-t border-slate-800 pt-4 text-sm"
        >
          <UButton
            v-if="canCancel(appointment)"
            color="error"
            variant="outline"
            size="sm"
            @click="appointmentToCancel = appointment"
            >Cancel appointment</UButton
          >
        </div>
      </article>

      <AppPagination
        :current-page="appointments.pagination?.current_page"
        :last-page="appointments.pagination?.last_page"
        :is-loading="appointments.isLoading"
        @change="goToPage"
      />
    </div>

    <AppConfirm
      :open="Boolean(appointmentToCancel)"
      title="Cancel appointment?"
      message="This booking request will be marked as cancelled and cannot be restored."
      confirm-label="Cancel appointment"
      :loading="appointments.isSaving"
      @cancel="appointmentToCancel = null"
      @confirm="cancelAppointment"
    />
  </section>
</template>
