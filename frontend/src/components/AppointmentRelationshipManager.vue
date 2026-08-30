<script setup>
import { computed, h } from "vue";
import { parseDate } from "@internationalized/date";
import { RouterLink, useRouter } from "vue-router";

import AppEmpty from "./AppEmpty.vue";
import AppDateRangePicker from "./AppDateRangePicker.vue";
import AppPagination from "./AppPagination.vue";
import AppointmentTableActions from "./AppointmentTableActions.vue";
import EnumBadge from "./EnumBadge.vue";
import { useUrlState } from "../composables/useUrlState";

const props = defineProps({
  appointments: { type: Array, default: () => [] },
  relationship: { type: String, required: true },
});

const emit = defineEmits(["action"]);
const router = useRouter();
const urlState = useUrlState({
  appointment_search: "",
  appointment_status: "",
  appointment_priority: "",
  appointment_date_from: "",
  appointment_date_to: "",
  appointment_page: 1,
});
const search = computed({
  get: () => urlState.appointment_search.value,
  set: (value) => updateState({ appointment_search: value, appointment_page: 1 }),
});
const status = computed({
  get: () => urlState.appointment_status.value,
  set: (value) => updateState({ appointment_status: value, appointment_page: 1 }),
});
const priority = computed({
  get: () => urlState.appointment_priority.value,
  set: (value) => updateState({ appointment_priority: value, appointment_page: 1 }),
});
const dateRange = computed({
  get: () => {
    const start = urlState.appointment_date_from.value;
    const end = urlState.appointment_date_to.value;

    if (!start) return null;

    return {
      start: parseDateValue(start),
      end: end ? parseDateValue(end) : undefined,
    };
  },
  set: (value) =>
    updateState({
      appointment_date_from: value?.start?.toString() || "",
      appointment_date_to: value?.end?.toString() || "",
      appointment_page: 1,
    }),
});
const page = computed({
  get: () => urlState.appointment_page.value,
  set: (value) => updateState({ appointment_page: value }),
});
const showClient = computed(() => props.relationship === "service");

const filteredAppointments = computed(() => {
  const term = search.value.trim().toLowerCase();

  return props.appointments.filter((appointment) => {
    const relatedName = showClient.value
      ? appointment.client?.name
      : appointment.service?.name;
    const matchesSearch = !term || [
      relatedName,
      appointment.status,
      appointment.priority,
      appointment.appointmentDate,
    ].some((value) => String(value || "").toLowerCase().includes(term));

    return (
      matchesSearch &&
      (!status.value || appointment.status === status.value) &&
      (!priority.value || appointment.priority === priority.value) &&
      (!dateRange.value?.start || appointment.appointmentDate >= dateRange.value.start.toString()) &&
      (!dateRange.value?.end || appointment.appointmentDate <= dateRange.value.end.toString())
    );
  });
});

const tableRows = computed(() =>
  paginatedAppointments.value.map((appointment) => ({
    appointment: `${formatDate(appointment.appointmentDate)} ${formatTime(appointment.startTime)} - ${formatTime(appointment.endTime)}`,
    client: appointment.client?.name || "Unknown client",
    clientId: appointment.client?.id ?? appointment.clientId,
    service: appointment.service?.name || "Unknown service",
    serviceId: appointment.service?.id ?? appointment.serviceId,
    priority: appointment.priority,
    status: appointment.status,
    id: appointment.id,
  })),
);

const paginatedAppointments = computed(() => {
  const start = (page.value - 1) * 10;
  return filteredAppointments.value.slice(start, start + 10);
});

function parseDateValue(value) {
  if (!value || typeof value !== "string") return undefined;

  try {
    return parseDate(value);
  } catch {
    return undefined;
  }
}

function updateState(updates) {
  Object.entries(updates).forEach(([key, value]) => {
    urlState[key].value = value;
  });
}

function clearFilters() {
  updateState({
    appointment_search: "",
    appointment_status: "",
    appointment_priority: "",
    appointment_date_from: "",
    appointment_date_to: "",
    appointment_page: 1,
  });
}

function goToPage(value) {
  page.value = value;
}

function selectRow(_event, row) {
  router.push(`/appointments/${row.original.id}`);
}

const columns = computed(() => [
  {
    accessorKey: "appointment",
    header: "Appointment",
    cell: ({ row }) =>
      h(
        "div",
        { class: "min-w-0 truncate font-semibold text-cyan-300" },
        row.original.appointment,
      ),
  },
  {
    accessorKey: showClient.value ? "client" : "service",
    header: showClient.value ? "Client" : "Service",
    cell: ({ row }) =>
      relatedLink(row),
  },
  {
    accessorKey: "status",
    header: "Status",
    cell: ({ row }) => h(EnumBadge, { value: row.original.status, kind: "status" }),
  },
  {
    accessorKey: "priority",
    header: "Priority",
    cell: ({ row }) => h(EnumBadge, { value: row.original.priority, kind: "priority" }),
  },
  {
    id: "actions",
    header: "Actions",
    cell: ({ row }) =>
      h(AppointmentTableActions, {
        id: row.original.id,
        status: row.original.status,
        onAction: (action) => emit("action", { id: row.original.id, action }),
      }),
  },
]);

function relatedLink({ original }) {
  const isClient = showClient.value;
  const id = original[isClient ? "clientId" : "serviceId"];
  const label = original[isClient ? "client" : "service"];

  return id
    ? h(
        RouterLink,
        {
          to: `/${isClient ? "clients" : "services"}/${id}/edit`,
          class: "min-w-0 truncate text-primary-400 hover:underline",
          onClick: (event) => event.stopPropagation(),
        },
        () => label,
      )
    : h("div", { class: "min-w-0 truncate" }, label);
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
</script>

<template>
  <section class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h2 class="text-lg font-semibold">Related appointments</h2>
      </div>
      <slot name="actions" />
    </div>

    <label class="block max-w-xl text-sm font-medium">
      Search appointments
      <UInput
        v-model="search"
        icon="i-lucide-search"
        :placeholder="'Search by ' + (showClient ? 'client, status, or priority' : 'service, status, or priority')"
        class="mt-2 w-full"
      />
    </label>
    <div class="flex flex-wrap items-end gap-4">
      <UFormField label="Status">
        <USelectMenu
          v-model="status"
          placeholder="All statuses"
          :items="['Requested', 'Confirmed', 'Completed', 'Cancelled']"
          clear
          class="w-full sm:w-48"
        />
      </UFormField>
      <UFormField label="Priority">
        <USelectMenu
          v-model="priority"
          placeholder="All priorities"
          :items="['Low', 'Medium', 'High']"
          clear
          class="w-full sm:w-48"
        />
      </UFormField>
      <UFormField label="Date range">
        <AppDateRangePicker v-model="dateRange" class="w-full sm:w-64" />
      </UFormField>
      <UButton
        v-if="search || status || priority || dateRange"
        color="neutral"
        variant="ghost"
        @click="clearFilters"
      >
        Clear filters
      </UButton>
    </div>

    <AppEmpty
      v-if="!filteredAppointments.length"
      :message="appointments.length ? 'No appointments match these filters.' : 'No related appointments found.'"
    />
    <div v-else class="space-y-4">
      <div class="overflow-x-auto rounded-2xl border border-slate-800 bg-slate-900 p-2">
        <UTable
          :data="tableRows"
          :columns="columns"
          :on-select="selectRow"
          :ui="{ tr: 'cursor-pointer', separator: 'z-0' }"
          class="min-w-full"
        />
      </div>
      <AppPagination
        :current-page="page"
        :total="filteredAppointments.length"
        :per-page="10"
        @change="goToPage"
      />
    </div>
  </section>
</template>
