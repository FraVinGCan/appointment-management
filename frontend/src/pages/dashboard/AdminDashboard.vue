<script setup>
import { computed, h, onMounted } from "vue";
import { RouterLink, useRouter } from "vue-router";

import AppDataTable from "@/components/ui/AppDataTable.vue";
import AppError from "@/components/ui/AppError.vue";
import AppLoading from "@/components/ui/AppLoading.vue";
import AppPageHeader from "@/components/ui/AppPageHeader.vue";
import DashboardStatCard from "@/components/dashboard/DashboardStatCard.vue";
import EnumBadge from "@/components/EnumBadge.vue";
import { useDashboardStore } from "@/stores/dashboard";

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
    clientId: appointment.client?.id ?? appointment.clientId,
    service: appointment.service?.name || "Unknown service",
    serviceId: appointment.service?.id ?? appointment.serviceId,
    status: appointment.status,
    priority: appointment.priority,
  })),
);

const columns = [
  {
    accessorKey: "slot",
    header: "Date & time",
    cell: ({ row }) =>
      h(
        "div",
        { class: "min-w-0 truncate font-semibold text-primary" },
        row.original.slot,
      ),
  },
  {
    accessorKey: "client",
    header: "Client",
    cell: ({ row }) =>
      relationshipCell(row.original.client, row.original.clientId, "clients"),
  },
  {
    accessorKey: "service",
    header: "Service",
    cell: ({ row }) =>
      relationshipCell(row.original.service, row.original.serviceId, "services"),
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

function relationshipCell(label, id, resource) {
  return id
    ? h(
        RouterLink,
        {
          to: `/${resource}/${id}/edit`,
          class: "min-w-0 truncate text-primary-400 hover:underline",
          onClick: (event) => event.stopPropagation(),
        },
        () => label,
      )
    : h("div", { class: "min-w-0 truncate" }, label);
}

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
              color: "var(--ui-text-muted)",
            },
            value: {
              color: "var(--ui-text)",
            },
          },
        },
      },
    },
    theme: { mode: "light", monochrome: { enabled: false } },
    stroke: { show: true, colors: ["var(--ui-bg)"], width: 2 },
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
        <UCard variant="subtle" class="border-default bg-default">
          <h3 class="text-base font-semibold text-default">
            Appointment status
          </h3>
          <apexchart
            type="donut"
            :options="statusOptions"
            :series="dashboard.stats.statusDistribution.series"
            height="320"
          />
        </UCard>

        <UCard variant="subtle" class="border-default bg-default">
          <h3 class="text-base font-semibold text-default">
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
          class="border-default bg-default lg:col-span-2"
        >
          <h3 class="text-base font-semibold text-default">
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

        <UCard variant="subtle" class="border-default bg-default">
          <h3 class="text-base font-semibold text-default">Top services</h3>
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

      <div class="space-y-4">
        <h3 class="text-base font-semibold text-default">
          Upcoming & open appointments
        </h3>
        <AppDataTable
          :data="upcomingRows"
          :columns="columns"
          :on-select="selectRow"
          :show-toolbar="false"
          :show-pagination="false"
          table-class="hidden sm:block"
          empty-message="No upcoming or open appointments found."
          empty-icon="i-lucide-calendar-clock"
        >
          <template #item="{ item }">
            <UCard
              variant="subtle"
              class="cursor-pointer"
              @click="router.push(`/appointments/${item.id}`)"
            >
              <h2 class="font-semibold truncate">{{ item.slot }}</h2>
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
            </UCard>
          </template>
        </AppDataTable>
      </div>
    </div>
  </section>
</template>
