<script setup>
import { computed, h, onMounted, ref, resolveComponent } from "vue";
import { parseDate } from "@internationalized/date";
import { RouterLink, useRouter } from "vue-router";

import AppConfirm from "@/components/ui/AppConfirm.vue";
import AppDataTable from "@/components/ui/AppDataTable.vue";
import AppDateRangePicker from "@/components/ui/AppDateRangePicker.vue";
import AppError from "@/components/ui/AppError.vue";
import AppBreadcrumbs from "@/components/ui/AppBreadcrumbs.vue";
import AppPageHeader from "@/components/ui/AppPageHeader.vue";
import AppointmentTableActions from "@/components/appointments/AppointmentTableActions.vue";
import EnumBadge from "@/components/EnumBadge.vue";
import { useAppointmentStore } from "@/stores/appointments";
import { useClientStore } from "@/stores/clients";
import { useServiceStore } from "@/stores/services";
import { useDebouncedWatch } from "@/composables/useDebouncedWatch";
import { useUrlState, dateOnly, integerRange, oneOf } from "@/composables/useUrlState";

const appointments = useAppointmentStore();
const UButton = resolveComponent("UButton");
const clients = useClientStore();
const services = useServiceStore();
const toast = useToast();
const router = useRouter();
const urlState = useUrlState({
  search: "",
  status: { default: "", sanitize: oneOf(["Requested", "Confirmed", "Completed", "Cancelled"]) },
  priority: { default: "", sanitize: oneOf(["Low", "Medium", "High"]) },
  client_id: { default: "", sanitize: integerRange(1) },
  service_id: { default: "", sanitize: integerRange(1) },
  date_from: { default: "", sanitize: dateOnly() },
  date_to: { default: "", sanitize: dateOnly() },
  page: { default: 1, sanitize: integerRange(1) },
  per_page: { default: 10, sanitize: integerRange(1, 100) },
  sort_by: { default: "appointment_date", sanitize: oneOf(["appointment_date", "client", "service", "status", "priority"]) },
  sort_direction: { default: "asc", sanitize: oneOf(["asc", "desc"]) },
});

const search = computed({
  get: () => urlState.search.value,
  set: (value) => updateState({ search: value, page: 1 }),
});
const status = computed({
  get: () => urlState.status.value,
  set: (value) => updateState({ status: value, page: 1 }),
});
const priority = computed({
  get: () => urlState.priority.value,
  set: (value) => updateState({ priority: value, page: 1 }),
});
const clientId = computed({
  get: () => urlState.client_id.value,
  set: (value) => updateState({ client_id: value, page: 1 }),
});
const serviceId = computed({
  get: () => urlState.service_id.value,
  set: (value) => updateState({ service_id: value, page: 1 }),
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
const perPage = computed({
  get: () => urlState.per_page.value,
  set: (value) => updateState({ per_page: value, page: 1 }, true),
});
const sortBy = computed(() => urlState.sort_by.value);
const sortDirection = computed(() => urlState.sort_direction.value);

const clientSearchTerm = ref("");
const serviceSearchTerm = ref("");
const draftStatus = ref("");
const draftPriority = ref("");
const draftClientId = ref("");
const draftServiceId = ref("");
const draftDateRange = ref(null);
const pendingAction = ref(null);
const clientOptions = computed(() =>
  clients.items.map((client) => ({ label: client.name, value: String(client.id) })),
);
const serviceOptions = computed(() =>
  services.items.map((service) => ({ label: service.name, value: String(service.id) })),
);
const tableRows = computed(() =>
  appointments.items.map((appointment) => ({
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
const columns = [
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
    accessorKey: "client",
    header: () => sortableHeader("Client", "client"),
    cell: ({ row }) =>
      row.original.clientId
        ? h(
            RouterLink,
            {
              to: `/clients/${row.original.clientId}/edit`,
              class: "min-w-0 truncate text-primary-400 hover:underline",
              onClick: (event) => event.stopPropagation(),
            },
            () => row.original.client,
          )
        : h("div", { class: "min-w-0 truncate" }, row.original.client),
  },
  {
    accessorKey: "service",
    header: () => sortableHeader("Service", "service"),
    cell: ({ row }) =>
      row.original.serviceId
        ? h(
            RouterLink,
            {
              to: `/services/${row.original.serviceId}/edit`,
              class: "min-w-0 truncate text-primary-400 hover:underline",
              onClick: (event) => event.stopPropagation(),
            },
            () => row.original.service,
          )
        : h("div", { class: "min-w-0 truncate" }, row.original.service),
  },
  {
    accessorKey: "status",
    header: () => sortableHeader("Status", "status"),
    cell: ({ row }) =>
      h(EnumBadge, { value: row.original.status, kind: "status" }),
  },
  {
    accessorKey: "priority",
    header: () => sortableHeader("Priority", "priority"),
    cell: ({ row }) =>
      h(EnumBadge, { value: row.original.priority, kind: "priority" }),
  },
  {
    id: "actions",
    header: "Actions",
    cell: ({ row }) =>
      h(AppointmentTableActions, {
        id: row.original.id,
        status: row.original.status,
        onAction: (action) => {
          pendingAction.value = { id: row.original.id, action };
        },
      }),
  },
];

onMounted(async () => {
  await Promise.all([
    appointments.fetchList(query()),
    clients.fetchList({ per_page: 10 }),
    services.fetchAll({ limit: 10 }),
  ]);
});

useDebouncedWatch(
  [() => search.value, () => status.value, () => priority.value, () => clientId.value, () => serviceId.value, () => dateRange.value],
  () => updateState({ page: 1 }, true),
);
useDebouncedWatch(clientSearchTerm, (value) =>
  clients.fetchList({ search: value.trim(), per_page: 10 }),
);
useDebouncedWatch(serviceSearchTerm, (value) =>
  services.fetchAll(value.trim() ? { search: value.trim(), limit: 10 } : { limit: 10 }),
);

function parseDateValue(value) {
  if (!value || typeof value !== "string") return undefined;

  try {
    return parseDate(value);
  } catch {
    return undefined;
  }
}
function canConfirm(appointment) {
  return appointment.status === "Requested";
}
function canComplete(appointment) {
  return appointment.status === "Confirmed";
}
function canCancel(appointment) {
  return ["Requested", "Confirmed"].includes(appointment.status);
}
const actionCopy = {
  confirm: {
    title: "Confirm appointment?",
    description: "The client will see this booking as confirmed.",
    confirmLabel: "Confirm appointment",
    variant: "info",
  },
  complete: {
    title: "Complete appointment?",
    description:
      "This marks the appointment as completed and cannot be changed afterwards.",
    confirmLabel: "Complete appointment",
    variant: "success",
  },
  cancel: {
    title: "Cancel appointment?",
    description:
      "This appointment will be marked as cancelled and cannot be restored.",
    confirmLabel: "Cancel appointment",
    variant: "warning",
  },
};
const pendingActionConfig = computed(
  () => actionCopy[pendingAction.value?.action] ?? null,
);
async function runPendingAction() {
  const { id, action } = pendingAction.value;
  try {
    const updated = await appointments[action](id);
    appointments.updateItem(updated);
    const pastTense = { confirm: "confirmed", complete: "completed", cancel: "cancelled" };
    toast.add({
      title: "Success",
      description: `Appointment ${pastTense[action]} successfully.`,
      color: "success",
    });
  } catch {
    // The store exposes the backend conflict message.
  } finally {
    pendingAction.value = null;
  }
}
function formatDate(date) {
  return new Intl.DateTimeFormat(undefined, { dateStyle: "medium" }).format(
    new Date(`${date}T00:00:00`),
  );
}
function formatTime(time) {
  if (!time) return "";

  const [hours, minutes] = String(time).split(":").map(Number);
  if (!Number.isInteger(hours) || !Number.isInteger(minutes))
    return String(time);

  return new Intl.DateTimeFormat(undefined, {
    hour: "numeric",
    minute: "2-digit",
  }).format(new Date(1970, 0, 1, hours, minutes));
}
function query(pageValue = page.value) {
  return {
    page: pageValue,
    per_page: perPage.value,
    sort_by: sortBy.value,
    sort_direction: sortDirection.value,
    ...(search.value ? { search: search.value } : {}),
    ...(status.value ? { status: status.value } : {}),
    ...(priority.value ? { priority: priority.value } : {}),
    ...(clientId.value ? { client_id: clientId.value } : {}),
    ...(serviceId.value ? { service_id: serviceId.value } : {}),
    ...(dateParams() ? { ...dateParams() } : {}),
  };
}

function dateParams() {
  const start = urlState.date_from.value;
  const end = urlState.date_to.value;

  if (!start) return end ? { date_to: end } : null;

  // Drop the end date when it falls before the start date.
  if (end && parseDateValue(end)?.compare(parseDateValue(start)) < 0) {
    return { date_from: start };
  }

  return end ? { date_from: start, date_to: end } : { date_from: start };
}
async function goToPage(value) {
  updateState({ page: value }, true);
}
function clearFilters() {
  draftStatus.value = "";
  draftPriority.value = "";
  draftClientId.value = "";
  draftServiceId.value = "";
  draftDateRange.value = null;
  updateState(
    {
      search: "",
      status: "",
      priority: "",
      client_id: "",
      service_id: "",
      date_from: "",
      date_to: "",
      page: 1,
    },
    true,
  );
}
function syncFilterDraft(open) {
  if (!open) return;

  draftStatus.value = status.value;
  draftPriority.value = priority.value;
  draftClientId.value = clientId.value;
  draftServiceId.value = serviceId.value;
  draftDateRange.value = dateRange.value;
}
function applyFilters() {
  updateState(
    {
      status: draftStatus.value,
      priority: draftPriority.value,
      client_id: draftClientId.value,
      service_id: draftServiceId.value,
      date_from: draftDateRange.value?.start?.toString() || "",
      date_to: draftDateRange.value?.end?.toString() || "",
      page: 1,
    },
    true,
  );
}
function sortableHeader(label, key) {
  const isSorted = sortBy.value === key;

  return h(
    UButton,
    {
      color: "neutral",
      variant: "ghost",
      label,
      icon: isSorted
        ? sortDirection.value === "asc"
          ? "i-lucide-arrow-up-narrow-wide"
          : "i-lucide-arrow-down-wide-narrow"
        : "i-lucide-arrow-up-down",
      class: "-mx-2.5",
      onClick: () => setSort(key),
    },
  );
}
function setSort(key) {
  updateState(
    {
      sort_by: key,
      sort_direction:
        sortBy.value === key && sortDirection.value === "asc" ? "desc" : "asc",
      page: 1,
    },
    true,
  );
}
function updateState(updates, fetch = false) {
  if (updates.search !== undefined) urlState.search.value = updates.search;
  if (updates.status !== undefined) urlState.status.value = updates.status;
  if (updates.priority !== undefined) urlState.priority.value = updates.priority;
  if (updates.client_id !== undefined) urlState.client_id.value = updates.client_id;
  if (updates.service_id !== undefined) urlState.service_id.value = updates.service_id;
  if (updates.date_from !== undefined) urlState.date_from.value = updates.date_from;
  if (updates.date_to !== undefined) urlState.date_to.value = updates.date_to;
  if (updates.page !== undefined) urlState.page.value = updates.page;
  if (updates.per_page !== undefined) urlState.per_page.value = updates.per_page;
  if (updates.sort_by !== undefined) urlState.sort_by.value = updates.sort_by;
  if (updates.sort_direction !== undefined) urlState.sort_direction.value = updates.sort_direction;

  if (fetch) {
    appointments.fetchList(query());
  }
}
function selectRow(_event, row) {
  router.push(`/appointments/${row.original.id}`);
}
</script>

<template>
  <section class="space-y-6">
    <AppBreadcrumbs
      :items="[{ label: 'Dashboard', to: '/' }, { label: 'Appointments' }]"
    />
    <AppPageHeader title="Appointments">
      <template #actions>
        <UButton
          to="/appointments/create"
          trailing-icon="i-lucide-plus"
          class="w-full sm:w-auto"
          >Create appointment</UButton
        >
      </template>
    </AppPageHeader>


    <AppError
      v-if="appointments.error"
      :message="appointments.error"
      :retry="true"
      @retry="appointments.fetchList(query())"
    />

    <div v-else class="space-y-4">
      <AppDataTable
        :data="tableRows"
        :columns="columns"
        :on-select="selectRow"
        v-model:search="search"
        search-placeholder="Search by client, service, status, or priority"
        :filter-count="Number(Boolean(status)) + Number(Boolean(priority)) + Number(Boolean(clientId)) + Number(Boolean(serviceId)) + Number(Boolean(dateRange))"
        :current-page="appointments.pagination?.current_page || page"
        :total="appointments.pagination?.total || 0"
        v-model:per-page="perPage"
        :is-loading="appointments.isLoading"
        empty-message="No appointments found."
        empty-icon="i-lucide-calendar-x"
        :empty-action="{ to: '/appointments/create', label: 'Create appointment', icon: 'i-lucide-plus' }"
        table-class="hidden sm:block"
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
          <UFormField label="Client">
            <USelectMenu v-model="draftClientId" v-model:search-term="clientSearchTerm" value-key="value" :items="clientOptions" ignore-filter placeholder="All clients" :search-input="{ placeholder: 'Search clients...', variant: 'none' }" clear class="w-full" />
          </UFormField>
          <UFormField label="Service">
            <USelectMenu v-model="draftServiceId" v-model:search-term="serviceSearchTerm" value-key="value" :items="serviceOptions" ignore-filter placeholder="All services" :search-input="{ placeholder: 'Search services...', variant: 'none' }" clear class="w-full" />
          </UFormField>
          <UFormField label="Date range">
            <AppDateRangePicker v-model="draftDateRange" class="w-full" />
          </UFormField>
        </template>
        <template #item="{ item }">
          <UCard variant="subtle">
            <h2 class="font-semibold truncate">{{ item.appointment }}</h2>
            <p class="mt-3 text-sm text-muted truncate">
              <RouterLink
                v-if="item.clientId"
                :to="`/clients/${item.clientId}/edit`"
                class="text-primary-400 hover:underline"
                @click.stop
              >
                {{ item.client }}
              </RouterLink>
              <span v-else>{{ item.client || "Unknown client" }}</span>
            </p>
            <p class="mt-1 text-sm text-muted truncate">
              <RouterLink
                v-if="item.serviceId"
                :to="`/services/${item.serviceId}/edit`"
                class="text-primary-400 hover:underline"
                @click.stop
              >
                {{ item.service }}
              </RouterLink>
              <span v-else>{{ item.service || "Unknown service" }}</span>
            </p>
            <div class="mt-3 flex flex-wrap items-center gap-2">
              <EnumBadge :value="item.status" kind="status" />
              <EnumBadge :value="item.priority" kind="priority" />
            </div>
            <div class="mt-5 flex flex-wrap gap-2">
              <UButton
                v-if="canConfirm(item)"
                size="sm"
                color="success"
                variant="link"
                @click="pendingAction = { id: item.id, action: 'confirm' }"
                >Confirm</UButton
              >
              <UButton
                v-else-if="canComplete(item)"
                size="sm"
                variant="link"
                @click="pendingAction = { id: item.id, action: 'complete' }"
                >Complete</UButton
              >
              <UButton
                v-else-if="canCancel(item)"
                size="sm"
                color="error"
                variant="link"
                @click="pendingAction = { id: item.id, action: 'cancel' }"
                >Cancel</UButton
              >
              <UButton
                size="sm"
                variant="link"
                :to="`/appointments/${item.id}`"
                >View</UButton
              >
              <UButton
                size="sm"
                color="neutral"
                variant="link"
                :to="`/appointments/${item.id}/edit`"
                >Edit</UButton
              >
            </div>
          </UCard>
        </template>
      </AppDataTable>

    </div>

    <AppConfirm
      :open="Boolean(pendingAction)"
      v-bind="pendingActionConfig"
      :loading="appointments.isSaving"
      @cancel="pendingAction = null"
      @confirm="runPendingAction"
    />
  </section>
</template>
