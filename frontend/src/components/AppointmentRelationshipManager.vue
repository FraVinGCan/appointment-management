<script setup>
import { computed, h, ref } from "vue";
import { parseDate } from "@internationalized/date";
import { RouterLink, useRouter } from "vue-router";

import AppDataTable from "./AppDataTable.vue";
import AppDateRangePicker from "./AppDateRangePicker.vue";
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
  appointment_per_page: 10,
  appointment_sort_by: "appointment_date",
  appointment_sort_direction: "desc",
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
const perPage = computed({
  get: () => urlState.appointment_per_page.value,
  set: (value) => updateState({ appointment_per_page: value, appointment_page: 1 }),
});
const sortBy = computed(() => urlState.appointment_sort_by.value);
const sortDirection = computed(() => urlState.appointment_sort_direction.value);
const draftStatus = ref("");
const draftPriority = ref("");
const draftDateRange = ref(null);
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
  const sorted = [...filteredAppointments.value].sort((first, second) => {
    const getValue = (appointment) => {
      if (sortBy.value === "appointment_date") return `${appointment.appointmentDate} ${appointment.startTime}`;
      if (sortBy.value === "related") return (showClient.value ? appointment.client?.name : appointment.service?.name) || "";
      return appointment[sortBy.value] || "";
    };
    const comparison = String(getValue(first)).localeCompare(String(getValue(second)), undefined, { numeric: true });
    return sortDirection.value === "asc" ? comparison : -comparison;
  });
  const start = (page.value - 1) * perPage.value;
  return sorted.slice(start, start + perPage.value);
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
  draftStatus.value = "";
  draftPriority.value = "";
  draftDateRange.value = null;
  updateState({
    appointment_search: "",
    appointment_status: "",
    appointment_priority: "",
    appointment_date_from: "",
    appointment_date_to: "",
    appointment_page: 1,
  });
}
function syncFilterDraft(open) {
  if (!open) return;

  draftStatus.value = status.value;
  draftPriority.value = priority.value;
  draftDateRange.value = dateRange.value;
}
function applyFilters() {
  updateState({
    appointment_status: draftStatus.value,
    appointment_priority: draftPriority.value,
    appointment_date_from: draftDateRange.value?.start?.toString() || "",
    appointment_date_to: draftDateRange.value?.end?.toString() || "",
    appointment_page: 1,
  });
}

function sortableHeader(label, key) {
  const isSorted = sortBy.value === key;

  return h(
    "button",
    {
      type: "button",
      class: "-mx-2.5 inline-flex items-center gap-1 rounded-md px-2.5 py-1.5 font-medium hover:bg-muted",
      onClick: () => setSort(key),
    },
    [label, h("span", { class: "text-xs text-muted" }, isSorted ? (sortDirection.value === "asc" ? "↑" : "↓") : "↕")],
  );
}
function setSort(key) {
  updateState({
    appointment_sort_by: key,
    appointment_sort_direction: sortBy.value === key && sortDirection.value === "asc" ? "desc" : "asc",
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
      header: () => sortableHeader("Appointment", "appointment_date"),
    cell: ({ row }) =>
      h(
        "div",
        { class: "min-w-0 truncate font-semibold text-primary" },
        row.original.appointment,
      ),
  },
  {
    accessorKey: showClient.value ? "client" : "service",
    header: () => sortableHeader(showClient.value ? "Client" : "Service", "related"),
    cell: ({ row }) =>
      relatedLink(row),
  },
  {
    accessorKey: "status",
    header: () => sortableHeader("Status", "status"),
    cell: ({ row }) => h(EnumBadge, { value: row.original.status, kind: "status" }),
  },
  {
    accessorKey: "priority",
    header: () => sortableHeader("Priority", "priority"),
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

    <div class="space-y-4">
      <AppDataTable
        :data="tableRows"
        :columns="columns"
        :on-select="selectRow"
        v-model:search="search"
        :search-placeholder="'Search by ' + (showClient ? 'client, status, or priority' : 'service, status, or priority')"
        :filter-count="Number(Boolean(status)) + Number(Boolean(priority)) + Number(Boolean(dateRange))"
        :current-page="page"
        :total="filteredAppointments.length"
        :empty-message="appointments.length ? 'No appointments match the selected filters.' : 'No appointments found.'"
        v-model:per-page="perPage"
        @change="goToPage"
        @clear-filters="clearFilters"
        @filters-open="syncFilterDraft"
        @apply-filters="applyFilters"
      >
        <template #filters>
          <UFormField label="Status">
            <USelectMenu v-model="draftStatus" placeholder="All statuses" :items="['Requested', 'Confirmed', 'Completed', 'Cancelled']" clear class="w-full" />
          </UFormField>
          <UFormField label="Priority">
            <USelectMenu v-model="draftPriority" placeholder="All priorities" :items="['Low', 'Medium', 'High']" clear class="w-full" />
          </UFormField>
          <UFormField label="Date range">
            <AppDateRangePicker v-model="draftDateRange" class="w-full" />
          </UFormField>
        </template>
      </AppDataTable>
    </div>
  </section>
</template>
