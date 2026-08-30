<script setup>
import { computed, h, ref } from "vue";

import AppEmpty from "./AppEmpty.vue";
import AppDateRangePicker from "./AppDateRangePicker.vue";
import AppointmentTableActions from "./AppointmentTableActions.vue";
import EnumBadge from "./EnumBadge.vue";

const props = defineProps({
  appointments: { type: Array, default: () => [] },
  relationship: { type: String, required: true },
});

const emit = defineEmits(["action"]);
const search = ref("");
const status = ref("");
const priority = ref("");
const dateRange = ref(null);
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
  filteredAppointments.value.map((appointment) => ({
    appointment: `${formatDate(appointment.appointmentDate)} ${formatTime(appointment.startTime)} - ${formatTime(appointment.endTime)}`,
    client: appointment.client?.name || "Unknown client",
    service: appointment.service?.name || "Unknown service",
    priority: appointment.priority,
    status: appointment.status,
    id: appointment.id,
  })),
);

const columns = computed(() => [
  {
    accessorKey: "appointment",
    header: "Appointment",
    cell: ({ row }) => h("div", { class: "min-w-0 truncate" }, row.original.appointment),
  },
  {
    accessorKey: showClient.value ? "client" : "service",
    header: showClient.value ? "Client" : "Service",
    cell: ({ row }) =>
      h("div", { class: "min-w-0 truncate" }, row.original[showClient.value ? "client" : "service"]),
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
        :placeholder="showClient ? 'Client, status, or priority' : 'Service, status, or priority'"
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
        @click="search = ''; status = ''; priority = ''; dateRange = null"
      >
        Clear filters
      </UButton>
    </div>

    <AppEmpty
      v-if="!filteredAppointments.length"
      :message="appointments.length ? 'No appointments match these filters.' : 'No related appointments found.'"
    />
    <div v-else class="overflow-x-auto rounded-2xl border border-slate-800 bg-slate-900 p-2">
      <UTable
        :data="tableRows"
        :columns="columns"
        :ui="{ separator: 'z-0' }"
        class="min-w-full"
      />
    </div>
  </section>
</template>
