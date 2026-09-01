<script setup>
import { computed, onMounted } from "vue";
import { useRouter } from "vue-router";

import AppEmpty from "../components/AppEmpty.vue";
import AppError from "../components/AppError.vue";
import AppLoading from "../components/AppLoading.vue";
import EnumBadge from "../components/EnumBadge.vue";
import AdminDashboard from "./AdminDashboard.vue";
import { useAuthStore } from "../stores/auth";
import { useAppointmentStore } from "../stores/appointments";

const auth = useAuthStore();
const appointments = useAppointmentStore();
const router = useRouter();

onMounted(() => {
  if (!auth.isAdmin) appointments.fetchClientDashboard();
});

const pendingAppointments = computed(
  () => appointments.clientDashboard?.pending || 0,
);
const completedAppointments = computed(
  () => appointments.clientDashboard?.completed || 0,
);
const upcomingAppointments = computed(() => appointments.clientDashboard?.upcoming || []);

function formatDate(date) {
  if (!date) return "Date unavailable";

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

function formatTimeRange(appointment) {
  if (!appointment.startTime || !appointment.endTime) return "Time unavailable";

  return `${formatTime(appointment.startTime)} - ${formatTime(appointment.endTime)}`;
}

async function signOut() {
  await auth.logout();
  router.push("/login");
}
</script>

<template>
  <AdminDashboard v-if="auth.isAdmin" />

  <section v-else class="space-y-6">
    <div
      class="rounded-2xl border border-primary-800/60 bg-gradient-to-br from-primary-950/70 via-default to-default p-5 sm:p-8"
    >
      <div class="flex flex-wrap items-end justify-between gap-5">
        <div>
          <p class="text-sm font-semibold uppercase tracking-[0.25em] text-primary">
            Client overview
          </p>
          <h1 class="mt-3 text-2xl font-semibold sm:text-3xl">
            Welcome back, {{ auth.user?.name }}
          </h1>
          <p class="mt-2 max-w-xl text-default">
            Keep track of your visits and find the next service that fits your schedule.
          </p>
        </div>
        <UButton
          to="/client/marketplace"
          icon="i-lucide-search"
          class="w-full sm:w-auto"
        >
          Browse services
        </UButton>
      </div>
    </div>

    <AppLoading
      v-if="appointments.isLoading"
      message="Loading your appointment overview..."
    />
    <AppError
      v-else-if="appointments.error"
      :message="appointments.error"
      :retry="true"
      @retry="appointments.fetchClientDashboard()"
    />
    <template v-else>
      <div class="grid gap-4 sm:grid-cols-3">
        <UCard variant="subtle" class="border-default bg-default">
          <p class="text-sm font-medium text-muted">Upcoming visits</p>
          <p class="mt-2 text-3xl font-semibold text-primary">
            {{ upcomingAppointments.length }}
          </p>
          <p class="mt-1 text-xs text-dimmed">Requested or confirmed</p>
        </UCard>
        <UCard variant="subtle" class="border-default bg-default">
          <p class="text-sm font-medium text-muted">Pending requests</p>
          <p class="mt-2 text-3xl font-semibold text-warning">
            {{ pendingAppointments }}
          </p>
          <p class="mt-1 text-xs text-dimmed">Waiting for confirmation</p>
        </UCard>
        <UCard variant="subtle" class="border-default bg-default">
          <p class="text-sm font-medium text-muted">Completed visits</p>
          <p class="mt-2 text-3xl font-semibold text-success">
            {{ completedAppointments }}
          </p>
          <p class="mt-1 text-xs text-dimmed">From your appointment history</p>
        </UCard>
      </div>

      <div class="grid gap-6 lg:grid-cols-[1.4fr_0.8fr]">
        <UCard variant="subtle" class="border-default bg-default">
          <div class="flex items-start justify-between gap-4">
            <div>
              <h2 class="text-lg font-semibold">Your next visits</h2>
              <p class="mt-1 text-sm text-muted">Your closest upcoming appointments.</p>
            </div>
            <UButton to="/client/appointments" variant="link" trailing-icon="i-lucide-arrow-right">
              View all
            </UButton>
          </div>

          <div v-if="upcomingAppointments.length" class="mt-5 divide-y divide-default">
            <div
              v-for="appointment in upcomingAppointments"
              :key="appointment.id"
              class="flex flex-wrap items-center justify-between gap-4 py-4 first:pt-0 last:pb-0"
            >
              <div class="min-w-0">
                <p class="truncate font-semibold text-default">
                  {{ appointment.service?.name || "Appointment" }}
                </p>
                <p class="mt-1 text-sm font-semibold text-primary">
                  {{ formatDate(appointment.appointmentDate) }} · {{ formatTimeRange(appointment) }}
                </p>
              </div>
              <EnumBadge :value="appointment.status" />
            </div>
          </div>
        <AppEmpty v-else class="mt-5" message="No upcoming appointments found." :action="{ to: '/client/marketplace', label: 'Find a service', icon: 'i-lucide-search' }" />
        </UCard>

        <UCard variant="subtle" class="border-default bg-default">
          <h2 class="text-lg font-semibold">Plan your next visit</h2>
          <p class="mt-2 text-sm leading-6 text-muted">
            Browse available services and send a booking request in just a few steps.
          </p>
          <div class="mt-6 space-y-3">
            <UButton to="/client/marketplace" block trailing-icon="i-lucide-arrow-right">
              Explore services
            </UButton>
            <UButton to="/client/appointments" block color="neutral" variant="outline">
              Review appointments
            </UButton>
          </div>
        </UCard>
      </div>
    </template>
  </section>
</template>
