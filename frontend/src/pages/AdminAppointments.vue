<script setup>
import { computed, h, onMounted, ref, watch } from "vue";
import { useRouter } from "vue-router";

import AppConfirm from "../components/AppConfirm.vue";
import AppEmpty from "../components/AppEmpty.vue";
import AppError from "../components/AppError.vue";
import AppLoading from "../components/AppLoading.vue";
import AppBreadcrumbs from "../components/AppBreadcrumbs.vue";
import AppPagination from "../components/AppPagination.vue";
import AppPageHeader from "../components/AppPageHeader.vue";
import AppointmentTableActions from "../components/AppointmentTableActions.vue";
import EnumBadge from "../components/EnumBadge.vue";
import { useAppointmentStore } from "../stores/appointments";
import { useClientStore } from "../stores/clients";
import { useServiceStore } from "../stores/services";

const appointments = useAppointmentStore();
const clients = useClientStore();
const services = useServiceStore();
const toast = useToast();
const router = useRouter();
const search = ref("");
const status = ref("");
const priority = ref("");
const clientId = ref("");
const serviceId = ref("");
const clientSearchTerm = ref("");
const serviceSearchTerm = ref("");
const pendingAction = ref(null);
let searchTimer;
let clientSearchTimer;
let serviceSearchTimer;
const clientOptions = computed(() =>
  clients.items.map((client) => ({ label: client.name, value: String(client.id) })),
);
const serviceOptions = computed(() =>
  services.items.map((service) => ({ label: service.name, value: String(service.id) })),
);
const tableRows = computed(() =>
  appointments.items.map((appointment) => ({
    appointment: `${formatDate(appointment.appointmentDate)} ${appointment.startTime} - ${appointment.endTime}`,
    client: appointment.client?.name || "Unknown client",
    service: appointment.service?.name || "Unknown service",
    priority: appointment.priority,
    status: appointment.status,
    id: appointment.id,
  })),
);
const columns = [
  {
    accessorKey: "appointment",
    header: "Appointment",
    cell: ({ row }) =>
      h("div", { class: "min-w-0 truncate" }, row.original.appointment),
  },
  {
    accessorKey: "client",
    header: "Client",
    cell: ({ row }) =>
      h("div", { class: "min-w-0 truncate" }, row.original.client),
  },
  {
    accessorKey: "service",
    header: "Service",
    cell: ({ row }) =>
      h("div", { class: "min-w-0 truncate" }, row.original.service),
  },
  {
    accessorKey: "status",
    header: "Status",
    cell: ({ row }) =>
      h(EnumBadge, { value: row.original.status, kind: "status" }),
  },
  {
    accessorKey: "priority",
    header: "Priority",
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
    appointments.fetchList(),
    clients.fetchList({ per_page: 10 }),
    services.fetchAll({ limit: 10 }),
  ]);
});

watch(search, (value) => {
  window.clearTimeout(searchTimer);
  searchTimer = window.setTimeout(() => appointments.fetchList(query(1)), 250);
});
watch([status, priority, clientId, serviceId], () => appointments.fetchList(query(1)));
watch(clientSearchTerm, (value) => {
  window.clearTimeout(clientSearchTimer);
  clientSearchTimer = window.setTimeout(
    () => clients.fetchList({ search: value.trim(), per_page: 10 }),
    250,
  );
});
watch(serviceSearchTerm, (value) => {
  window.clearTimeout(serviceSearchTimer);
  serviceSearchTimer = window.setTimeout(
    () => services.fetchAll(value.trim() ? { search: value.trim(), limit: 10 } : { limit: 10 }),
    250,
  );
});

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
function query(page) {
  return {
    page,
    ...(search.value ? { search: search.value } : {}),
    ...(status.value ? { status: status.value } : {}),
    ...(priority.value ? { priority: priority.value } : {}),
    ...(clientId.value ? { client_id: clientId.value } : {}),
    ...(serviceId.value ? { service_id: serviceId.value } : {}),
  };
}
async function goToPage(page) {
  await appointments.fetchList(query(page));
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

    <label class="block max-w-xl text-sm font-medium">
      Search appointments
      <UInput
        v-model="search"
        icon="i-lucide-search"
        placeholder="Client, service, status, or priority"
        class="mt-2 w-full"
      />
    </label>
    <div
      class="grid grid-cols-1 gap-3 sm:flex sm:flex-wrap sm:items-end sm:gap-4"
    >
      <UFormField label="Status">
        <USelect
          v-model="status"
          placeholder="All statuses"
          :items="['Requested', 'Confirmed', 'Completed', 'Cancelled']"
          class="w-full sm:w-48"
        />
      </UFormField>
      <UFormField label="Priority">
        <USelect
          v-model="priority"
          placeholder="All priorities"
          :items="['Low', 'Medium', 'High']"
          class="w-full sm:w-48"
        />
      </UFormField>
      <UFormField label="Client">
        <USelectMenu
          v-model="clientId"
          v-model:search-term="clientSearchTerm"
          value-key="value"
          :items="clientOptions"
          placeholder="All clients"
          :search-input="{ placeholder: 'Search clients...', variant: 'none' }"
          clear
          class="w-full sm:w-56"
        />
      </UFormField>
      <UFormField label="Service">
        <USelectMenu
          v-model="serviceId"
          v-model:search-term="serviceSearchTerm"
          value-key="value"
          :items="serviceOptions"
          placeholder="All services"
          :search-input="{ placeholder: 'Search services...', variant: 'none' }"
          clear
          class="w-full sm:w-56"
        />
      </UFormField>
      <UButton
        v-if="status || priority || clientId || serviceId"
        color="neutral"
        variant="ghost"
        @click="
          status = '';
          priority = '';
          clientId = '';
          serviceId = '';
        "
        >Clear filters</UButton
      >
    </div>

    <AppLoading
      v-if="appointments.isLoading"
      message="Loading appointments..."
    />
    <AppError
      v-else-if="appointments.error"
      :message="appointments.error"
      :retry="true"
      @retry="
        appointments.fetchList(
          query(appointments.pagination?.current_page || 1),
        )
      "
    />
    <AppEmpty
      v-else-if="!appointments.items.length"
      message="No appointments found."
      ><UButton class="mt-4" to="/appointments/create" variant="link"
        >Create appointment</UButton
      ></AppEmpty
    >

    <div v-else class="space-y-4">
      <div
        class="hidden sm:block overflow-x-auto rounded-2xl border border-slate-800 bg-slate-900 p-2"
      >
        <UTable
          :data="tableRows"
          :columns="columns"
          :on-select="selectRow"
          :ui="{ tr: 'cursor-pointer' }"
          class="min-w-full"
        />
      </div>

      <div class="sm:hidden space-y-3">
        <div
          v-for="appointment in appointments.items"
          :key="appointment.id"
          class="rounded-xl border border-slate-800 bg-slate-900 p-4"
        >
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0 flex-1">
              <p class="font-medium truncate">
                {{ formatDate(appointment.appointmentDate) }}
                {{ formatTime(appointment.startTime) }} -
                {{ formatTime(appointment.endTime) }}
              </p>
              <p class="mt-1 text-sm text-slate-400 truncate">
                {{ appointment.client?.name || "Unknown client" }}
              </p>
              <p class="mt-1 text-sm text-slate-400 truncate">
                {{ appointment.service?.name || "Unknown service" }}
              </p>
              <div class="mt-2 flex flex-wrap items-center gap-2">
                <EnumBadge :value="appointment.status" kind="status" />
                <EnumBadge :value="appointment.priority" kind="priority" />
              </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
              <UButton
                v-if="canConfirm(appointment)"
                size="sm"
                color="success"
                @click.stop="pendingAction = { id: appointment.id, action: 'confirm' }"
              >
                Confirm
              </UButton>
              <UButton
                v-else-if="canComplete(appointment)"
                size="sm"
                color="primary"
                @click.stop="pendingAction = { id: appointment.id, action: 'complete' }"
              >
                Complete
              </UButton>
              <UButton
                v-else-if="canCancel(appointment)"
                size="sm"
                color="error"
                @click.stop="pendingAction = { id: appointment.id, action: 'cancel' }"
              >
                Cancel
              </UButton>
              <UButton
                size="sm"
                variant="outline"
                @click.stop="selectRow(null, { original: appointment })"
              >
                View
              </UButton>
            </div>
          </div>
        </div>
      </div>

      <AppPagination
        :current-page="appointments.pagination?.current_page"
        :total="appointments.pagination?.total"
        :per-page="appointments.pagination?.per_page"
        :is-loading="appointments.isLoading"
        @change="goToPage"
      />
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
