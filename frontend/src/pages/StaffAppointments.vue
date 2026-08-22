<script setup>
import { onMounted, ref, watch } from "vue";

import AppEmpty from "../components/AppEmpty.vue";
import AppError from "../components/AppError.vue";
import AppLoading from "../components/AppLoading.vue";
import { useAppointmentStore } from "../stores/appointments";

const appointments = useAppointmentStore();
const search = ref("");
let searchTimer;

onMounted(() => appointments.fetchList());

watch(search, (value) => {
  window.clearTimeout(searchTimer);
  searchTimer = window.setTimeout(
    () => appointments.fetchList({ search: value, page: 1 }),
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
function formatDate(date) {
  return new Intl.DateTimeFormat(undefined, { dateStyle: "medium" }).format(
    new Date(`${date}T00:00:00`),
  );
}
function query(page) {
  return { page, ...(search.value ? { search: search.value } : {}) };
}
async function goToPage(page) {
  await appointments.fetchList(query(page));
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
        <h1 class="mt-2 text-3xl font-semibold">Appointments</h1>
      </div>
      <RouterLink
        class="rounded-lg bg-cyan-400 px-4 py-2 text-sm font-semibold text-slate-950"
        to="/appointments/create"
        >Create appointment</RouterLink
      >
    </div>

    <label class="block max-w-xl text-sm font-medium"
      >Search appointments
      <input
        v-model="search"
        type="search"
        placeholder="Client, service, status, or priority"
        class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-slate-100 outline-none focus:border-cyan-400" />
    </label>

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
      ><RouterLink
        class="mt-4 inline-block font-semibold text-cyan-400"
        to="/appointments/create"
        >Create appointment</RouterLink
      ></AppEmpty
    >
    <div
      v-else
      class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900">
      <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
          <thead
            class="border-b border-slate-800 text-xs uppercase tracking-wide text-slate-500">
            <tr>
              <th class="px-5 py-4">Appointment</th>
              <th class="px-5 py-4">Client</th>
              <th class="px-5 py-4">Service</th>
              <th class="px-5 py-4">Priority</th>
              <th class="px-5 py-4">Status</th>
              <th class="px-5 py-4">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800">
            <tr
              v-for="appointment in appointments.items"
              :key="appointment.id"
              class="text-slate-300">
              <td class="whitespace-nowrap px-5 py-4">
                <div>{{ formatDate(appointment.appointmentDate) }}</div>
                <div class="text-xs text-slate-500">
                  {{ appointment.startTime }} - {{ appointment.endTime }}
                </div>
              </td>
              <td class="px-5 py-4">
                {{ appointment.client?.name || "Unknown client" }}
              </td>
              <td class="px-5 py-4">
                {{ appointment.service?.name || "Unknown service" }}
              </td>
              <td class="px-5 py-4">{{ appointment.priority }}</td>
              <td class="px-5 py-4">
                <span
                  class="rounded-full bg-slate-800 px-3 py-1 text-xs font-semibold"
                  >{{ appointment.status }}</span
                >
              </td>
              <td class="whitespace-nowrap px-5 py-4">
                <div class="flex flex-wrap gap-2 text-xs">
                  <RouterLink
                    class="font-semibold text-cyan-300"
                    :to="`/appointments/${appointment.id}`"
                    >View</RouterLink
                  ><RouterLink
                    class="font-semibold text-slate-300"
                    :to="`/appointments/${appointment.id}/edit`"
                    >Edit</RouterLink
                  ><span v-if="canConfirm(appointment)" class="text-slate-600"
                    >Confirm in details</span
                  ><span
                    v-else-if="
                      canComplete(appointment) || canCancel(appointment)
                    "
                    class="text-slate-600"
                    >Manage in details</span
                  >
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div
        v-if="appointments.pagination?.last_page > 1"
        class="flex items-center justify-between border-t border-slate-800 px-5 py-4 text-sm text-slate-400">
        <button
          class="rounded-lg border border-slate-700 px-3 py-2 disabled:opacity-40"
          type="button"
          :disabled="
            appointments.pagination.current_page <= 1 || appointments.isLoading
          "
          @click="goToPage(appointments.pagination.current_page - 1)">
          Previous</button
        ><span
          >Page {{ appointments.pagination.current_page }} of
          {{ appointments.pagination.last_page }}</span
        ><button
          class="rounded-lg border border-slate-700 px-3 py-2 disabled:opacity-40"
          type="button"
          :disabled="
            appointments.pagination.current_page >=
              appointments.pagination.last_page || appointments.isLoading
          "
          @click="goToPage(appointments.pagination.current_page + 1)">
          Next
        </button>
      </div>
    </div>
  </section>
</template>
