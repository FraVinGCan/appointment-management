<script setup>
import { computed, h, onMounted, ref, watch } from "vue";
import { useRouter } from "vue-router";

import AppEmpty from "../components/AppEmpty.vue";
import AppError from "../components/AppError.vue";
import AppLoading from "../components/AppLoading.vue";
import AppBreadcrumbs from "../components/AppBreadcrumbs.vue";
import AppointmentTableActions from "../components/AppointmentTableActions.vue";
import EnumBadge from "../components/EnumBadge.vue";
import { useAppointmentStore } from "../stores/appointments";

const appointments = useAppointmentStore();
const router = useRouter();
const search = ref("");
const status = ref("");
const priority = ref("");
let searchTimer;
const tableRows = computed(() => appointments.items.map((appointment) => ({
  appointment: `${formatDate(appointment.appointmentDate)} ${appointment.startTime} - ${appointment.endTime}`,
  client: appointment.client?.name || "Unknown client",
  service: appointment.service?.name || "Unknown service",
  priority: appointment.priority,
  status: appointment.status,
  id: appointment.id,
})));
const columns = [
  { accessorKey: "appointment", header: "Appointment" },
  { accessorKey: "client", header: "Client" },
  { accessorKey: "service", header: "Service" },
  { accessorKey: "status", header: "Status", cell: ({ row }) => h(EnumBadge, { value: row.original.status, kind: "status" }) },
  { accessorKey: "priority", header: "Priority", cell: ({ row }) => h(EnumBadge, { value: row.original.priority, kind: "priority" }) },
  { id: "actions", header: "Actions", cell: ({ row }) => h(AppointmentTableActions, { id: row.original.id }) },
];

onMounted(() => appointments.fetchList());

watch(search, (value) => {
  window.clearTimeout(searchTimer);
  searchTimer = window.setTimeout(
  () => appointments.fetchList(query(1)),
    250,
  );
});
watch([status, priority], () => appointments.fetchList(query(1)));

function canConfirm(appointment) {
  return appointment.status === "Requested";
}
function canComplete(appointment) {
  return appointment.status === "Confirmed";
}
function canCancel(appointment) {
  return ["Requested", "Confirmed"].includes(appointment.status);
}
function formatDate(date) {
  return new Intl.DateTimeFormat(undefined, { dateStyle: "medium" }).format(
    new Date(`${date}T00:00:00`),
  );
}
function query(page) {
  return { page, ...(search.value ? { search: search.value } : {}), ...(status.value ? { status: status.value } : {}), ...(priority.value ? { priority: priority.value } : {}) };
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
    <AppBreadcrumbs :items="[{ label: 'Dashboard', to: '/' }, { label: 'Appointments' }]" />
    <div class="flex flex-wrap items-end justify-between gap-4">
      <div>
        <p
          class="text-xs font-semibold uppercase tracking-[0.25em] text-cyan-400">
          Staff workspace
        </p>
        <h1 class="mt-2 text-3xl font-semibold">Appointments</h1>
      </div>
      <UButton to="/appointments/create" trailing-icon="i-lucide-plus">Create appointment</UButton>
    </div>

    <label class="block max-w-xl text-sm font-medium"
      >Search appointments
      <UInput v-model="search" icon="i-lucide-search" placeholder="Client, service, status, or priority" class="mt-2 w-full" />
    </label>
    <div class="flex flex-wrap items-end gap-4"><UFormField label="Status"><USelect v-model="status" placeholder="All statuses" :items="['Requested', 'Confirmed', 'Completed', 'Cancelled']" class="w-48" /></UFormField><UFormField label="Priority"><USelect v-model="priority" placeholder="All priorities" :items="['Low', 'Medium', 'High']" class="w-48" /></UFormField><UButton v-if="status || priority" color="neutral" variant="ghost" @click="status = ''; priority = ''">Clear filters</UButton></div>

    <AppLoading
      v-if="appointments.isLoading"
      message="Loading appointments..." />
    <AppError
      v-else-if="appointments.error"
      :message="appointments.error"
      :retry="true"
      @retry="
        appointments.fetchList(
          query(appointments.pagination?.current_page || 1),
        )
      " />
    <AppEmpty
      v-else-if="!appointments.items.length"
      message="No appointments found."
      ><UButton class="mt-4" to="/appointments/create" variant="link">Create appointment</UButton></AppEmpty
    >
      <div v-else class="overflow-x-auto rounded-2xl border border-slate-800 bg-slate-900 p-2"><UTable :data="tableRows" :columns="columns" :on-select="selectRow" class="min-w-full" />
      <div
        v-if="appointments.pagination?.last_page > 1"
        class="flex items-center justify-between border-t border-slate-800 px-5 py-4 text-sm text-slate-400">
        <UButton color="neutral" variant="outline"
          :disabled="
            appointments.pagination.current_page <= 1 || appointments.isLoading
          "
          @click="goToPage(appointments.pagination.current_page - 1)">
          Previous</UButton
        ><span
          >Page {{ appointments.pagination.current_page }} of
          {{ appointments.pagination.last_page }}</span
        ><UButton color="neutral" variant="outline"
          :disabled="
            appointments.pagination.current_page >=
              appointments.pagination.last_page || appointments.isLoading
          "
          @click="goToPage(appointments.pagination.current_page + 1)">
          Next</UButton>
      </div>
    </div>
  </section>
</template>
