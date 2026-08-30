<script setup>
import { computed, onMounted, ref } from "vue";
import { parseDate } from "@internationalized/date";

import AppConfirm from "../components/AppConfirm.vue";
import AppDateRangePicker from "../components/AppDateRangePicker.vue";
import AppEmpty from "../components/AppEmpty.vue";
import AppError from "../components/AppError.vue";
import AppLoading from "../components/AppLoading.vue";
import AppPageHeader from "../components/AppPageHeader.vue";
import AppPagination from "../components/AppPagination.vue";
import { useAppointmentStore } from "../stores/appointments";
import { useDebouncedWatch } from "../composables/useDebouncedWatch";
import { useUrlState } from "../composables/useUrlState";

const appointments = useAppointmentStore();
const toast = useToast();
const appointmentToCancel = ref(null);
const urlState = useUrlState({
  search: "",
  status: "",
  date_from: "",
  date_to: "",
  page: 1,
});

const search = computed({
  get: () => urlState.search.value,
  set: (value) => updateState({ search: value, page: 1 }),
});
const status = computed({
  get: () => urlState.status.value,
  set: (value) => updateState({ status: value, page: 1 }),
});
const dateRange = computed({
  get: () => {
    const start = urlState.date_from.value;
    const end = urlState.date_to.value;

    if (!start) return null;

    return {
      start: parseDateValue(start),
      end: end ? parseDateValue(end) : undefined,
    };
  },
  set: (value) => {
    const start = value?.start ? value.start.toString() : "";
    const end = value?.end ? value.end.toString() : "";
    updateState({ date_from: start, date_to: end, page: 1 });
  },
});
const page = computed({
  get: () => urlState.page.value,
  set: (value) => updateState({ page: value }, true),
});

onMounted(() => appointments.fetchClientList(query()));

useDebouncedWatch(
  [() => search.value, () => status.value, () => dateRange.value],
  () => updateState({ page: 1 }, true),
);

function parseDateValue(value) {
  if (!value || typeof value !== "string") return undefined;

  try {
    return parseDate(value);
  } catch {
    return undefined;
  }
}

function canCancel(appointment) {
  return ["Requested", "Confirmed"].includes(appointment.status);
}

function formatDate(date) {
  return new Intl.DateTimeFormat(undefined, { dateStyle: "medium" }).format(
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

async function goToPage(value) {
  updateState({ page: value }, true);
}

function query(pageValue = page.value) {
  return {
    page: pageValue,
    ...(search.value.trim() ? { search: search.value.trim() } : {}),
    ...(status.value ? { status: status.value } : {}),
    ...(urlState.date_from.value ? { date_from: urlState.date_from.value } : {}),
    ...(urlState.date_to.value ? { date_to: urlState.date_to.value } : {}),
  };
}

function clearFilters() {
  updateState(
    {
      search: "",
      status: "",
      date_from: "",
      date_to: "",
      page: 1,
    },
    true,
  );
}

function updateState(updates, fetch = false) {
  if (updates.search !== undefined) urlState.search.value = updates.search;
  if (updates.status !== undefined) urlState.status.value = updates.status;
  if (updates.date_from !== undefined) urlState.date_from.value = updates.date_from;
  if (updates.date_to !== undefined) urlState.date_to.value = updates.date_to;
  if (updates.page !== undefined) urlState.page.value = updates.page;

  if (fetch) {
    appointments.fetchClientList(query());
  }
}

async function cancelAppointment() {
  try {
    const updated = await appointments.cancelClient(
      appointmentToCancel.value.id,
    );
    appointments.updateItem(updated);
    toast.add({
      title: "Appointment cancelled",
      description: "Your appointment has been cancelled.",
      color: "success",
    });
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
          to="/client/marketplace"
          trailing-icon="i-lucide-arrow-right"
          class="w-full sm:w-auto"
          >Browse services</UButton
        >
      </template>
    </AppPageHeader>

    <div class="grid gap-3 sm:flex sm:flex-wrap sm:items-end">
      <label class="block min-w-64 flex-1 text-sm font-medium">
        Search appointments
        <UInput v-model="search" icon="i-lucide-search" placeholder="Search by service" class="mt-2 w-full" />
      </label>
      <UFormField label="Status">
      <USelectMenu v-model="status" placeholder="All statuses" :items="['Requested', 'Confirmed', 'Completed', 'Cancelled']" clear class="w-full sm:w-48" />
      </UFormField>
      <UFormField label="Date range">
        <AppDateRangePicker v-model="dateRange" class="w-full sm:w-64" />
      </UFormField>
      <UButton v-if="search || status || dateRange" color="neutral" variant="ghost" @click="clearFilters">Clear filters</UButton>
    </div>

    <AppLoading
      v-if="appointments.isLoading"
      message="Loading your appointments..."
    />
    <AppError
      v-else-if="appointments.error"
      :message="appointments.error"
      :retry="true"
      @retry="appointments.fetchClientList(query())"
    />
    <AppEmpty
      v-else-if="!appointments.items.length"
      message="You do not have any appointments yet."
    >
      <UButton class="mt-4" to="/client/marketplace" variant="link"
        >Browse services</UButton
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
            <p class="mt-1 text-sm font-semibold text-cyan-300">
              {{ formatDate(appointment.appointmentDate) }} ·
              {{ formatTime(appointment.startTime) }} - {{ formatTime(appointment.endTime) }}
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
        :total="appointments.pagination?.total"
        :per-page="appointments.pagination?.per_page"
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
