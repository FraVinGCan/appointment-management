<script setup>
import { computed, h, onMounted } from "vue";
import { useRouter } from "vue-router";

import AppEmpty from "../components/AppEmpty.vue";
import AppError from "../components/AppError.vue";
import AppLoading from "../components/AppLoading.vue";
import AppPageHeader from "../components/AppPageHeader.vue";
import DashboardStatCard from "../components/dashboard/DashboardStatCard.vue";
import EnumBadge from "../components/EnumBadge.vue";
import { useDashboardStore } from "../stores/dashboard";

const dashboard = useDashboardStore();
const router = useRouter();

onMounted(() => dashboard.fetchStats());

const statusOptions = computed(() =>
  donutOptions(dashboard.stats?.statusDistribution, "Appointments"),
);
const priorityOptions = computed(() =>
  donutOptions(dashboard.stats?.priorityDistribution, "Appointments"),
);
const overTimeOptions = computed(() =>
  barOptions(dashboard.stats?.appointmentsOverTime?.labels, "#06b6d4"),
);
const topServicesOptions = computed(() =>
  horizontalBarOptions(dashboard.stats?.topServices?.labels, "#8b5cf6"),
);

const upcomingRows = computed(() =>
  (dashboard.stats?.upcoming || []).map((appointment) => ({
    id: appointment.id,
    slot: `${formatDate(appointment.appointmentDate)} ${formatTime(appointment.startTime)} - ${formatTime(appointment.endTime)}`,
    client: appointment.client?.name || "Unknown client",
    service: appointment.service?.name || "Unknown service",
    status: appointment.status,
    priority: appointment.priority,
  })),
);

const columns = [
  {
    accessorKey: "slot",
    header: "Date & time",
    cell: ({ row }) =>
      h("div", { class: "min-w-0 truncate" }, row.original.slot),
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
];

function donutOptions(distribution, totalLabel) {
  if (!distribution) return {};

  return {
    chart: { type: "donut", background: "transparent", toolbar: { show: false } },
    labels: distribution.labels,
    colors: distribution.colors,
    legend: {
      position: "bottom",
      labels: { colors: "#94a3b8" },
    },
    plotOptions: {
      pie: {
        donut: {
          size: "60%",
          labels: {
            show: true,
            total: {
              show: true,
              label: totalLabel,
              color: "#e2e8f0",
            },
          },
        },
      },
    },
    theme: { mode: "dark" },
    stroke: { show: true, colors: ["#0f172a"], width: 2 },
    dataLabels: { enabled: false },
  };
}

function barOptions(labels, color) {
  return {
    chart: { type: "bar", background: "transparent", toolbar: { show: false } },
    xaxis: {
      categories: labels || [],
      labels: {
        style: { colors: "#94a3b8" },
        formatter: (value) => (value ? formatShortDate(value) : value),
      },
      axisBorder: { show: false },
      axisTicks: { show: false },
      tooltip: { enabled: false },
    },
    yaxis: {
      labels: { style: { colors: "#94a3b8" } },
    },
    colors: [color],
    theme: { mode: "dark" },
    grid: { borderColor: "#1e293b", strokeDashArray: 4 },
    dataLabels: { enabled: false },
    plotOptions: { bar: { borderRadius: 4 } },
  };
}

function horizontalBarOptions(labels, color) {
  return {
    chart: {
      type: "bar",
      background: "transparent",
      toolbar: { show: false },
    },
    plotOptions: { bar: { horizontal: true, borderRadius: 4 } },
    xaxis: {
      categories: labels || [],
      labels: { style: { colors: "#94a3b8" } },
    },
    yaxis: {
      labels: { style: { colors: "#94a3b8" } },
    },
    colors: [color],
    theme: { mode: "dark" },
    grid: { borderColor: "#1e293b", strokeDashArray: 4 },
    dataLabels: { enabled: false },
  };
}

function formatDate(date) {
  return new Intl.DateTimeFormat(undefined, { dateStyle: "medium" }).format(
    new Date(`${date}T00:00:00`),
  );
}

function formatShortDate(date) {
  return new Intl.DateTimeFormat(undefined, { month: "short", day: "numeric" }).format(
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

function selectRow(_event, row) {
  router.push(`/appointments/${row.original.id}`);
}
</script>

<template>
  <section class="space-y-6">
    <AppPageHeader title="Dashboard" />

    <AppLoading
      v-if="dashboard.isLoading"
      message="Loading dashboard..."
    />
    <AppError
      v-else-if="dashboard.error"
      :message="dashboard.error"
      :retry="true"
      @retry="dashboard.fetchStats()"
    />

    <div v-else-if="dashboard.stats" class="space-y-6">
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <DashboardStatCard
          label="Appointments"
          :value="dashboard.stats.totals.appointments"
          icon="i-lucide-calendar"
        />
        <DashboardStatCard
          label="Active clients"
          :value="dashboard.stats.totals.activeClients"
          icon="i-lucide-users"
        />
        <DashboardStatCard
          label="Active services"
          :value="dashboard.stats.totals.activeServices"
          icon="i-lucide-briefcase-business"
        />
        <DashboardStatCard
          label="Pending"
          :value="dashboard.stats.totals.pending"
          icon="i-lucide-clock"
        />
        <DashboardStatCard
          label="Urgent"
          :value="dashboard.stats.totals.urgent"
          icon="i-lucide-triangle-alert"
        />
      </div>

      <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <UCard variant="subtle" class="border-slate-800 bg-slate-900/60">
          <h3 class="text-base font-semibold text-slate-100">
            Appointment status
          </h3>
          <apexchart
            type="donut"
            :options="statusOptions"
            :series="dashboard.stats.statusDistribution.series"
            height="320"
          />
        </UCard>

        <UCard variant="subtle" class="border-slate-800 bg-slate-900/60">
          <h3 class="text-base font-semibold text-slate-100">
            Appointment priority
          </h3>
          <apexchart
            type="donut"
            :options="priorityOptions"
            :series="dashboard.stats.priorityDistribution.series"
            height="320"
          />
        </UCard>
      </div>

      <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <UCard
          variant="subtle"
          class="border-slate-800 bg-slate-900/60 lg:col-span-2"
        >
          <h3 class="text-base font-semibold text-slate-100">
            Appointments over the last 14 days
          </h3>
          <apexchart
            type="bar"
            :options="overTimeOptions"
            :series="[
              {
                name: 'Appointments',
                data: dashboard.stats.appointmentsOverTime.series,
              },
            ]"
            height="320"
          />
        </UCard>

        <UCard variant="subtle" class="border-slate-800 bg-slate-900/60">
          <h3 class="text-base font-semibold text-slate-100">Top services</h3>
          <apexchart
            type="bar"
            :options="topServicesOptions"
            :series="[
              {
                name: 'Appointments',
                data: dashboard.stats.topServices.series,
              },
            ]"
            height="320"
          />
        </UCard>
      </div>

      <UCard variant="subtle" class="border-slate-800 bg-slate-900/60">
        <h3 class="text-base font-semibold text-slate-100">
          Upcoming & open appointments
        </h3>
        <AppEmpty
          v-if="!upcomingRows.length"
          class="mt-4"
          message="No upcoming or open appointments."
        />
        <template v-else>
          <div
            class="mt-4 hidden overflow-x-auto rounded-xl border border-slate-800 sm:block"
          >
            <UTable
              :data="upcomingRows"
              :columns="columns"
              :on-select="selectRow"
              :ui="{ tr: 'cursor-pointer', separator: 'z-0' }"
              class="min-w-full"
            />
          </div>
          <div class="mt-4 space-y-3 sm:hidden">
            <div
              v-for="appointment in dashboard.stats.upcoming"
              :key="appointment.id"
              class="rounded-xl border border-slate-800 bg-slate-900 p-4"
              @click="router.push(`/appointments/${appointment.id}`)"
            >
              <p class="font-medium">
                {{ formatDate(appointment.appointmentDate) }}
                {{ formatTime(appointment.startTime) }} -
                {{ formatTime(appointment.endTime) }}
              </p>
              <p class="mt-1 truncate text-sm text-slate-400">
                {{ appointment.client?.name || "Unknown client" }}
              </p>
              <p class="truncate text-sm text-slate-400">
                {{ appointment.service?.name || "Unknown service" }}
              </p>
              <div class="mt-2 flex flex-wrap items-center gap-2">
                <EnumBadge :value="appointment.status" kind="status" />
                <EnumBadge :value="appointment.priority" kind="priority" />
              </div>
            </div>
          </div>
        </template>
      </UCard>
    </div>
  </section>
</template>
