<script setup>
import { computed, ref } from "vue";
import { useRouter } from "vue-router";

import FullCalendar from "@fullcalendar/vue3";
import classicThemePlugin from "@fullcalendar/vue3/themes/classic";
import dayGridPlugin from "@fullcalendar/vue3/daygrid";
import timeGridPlugin from "@fullcalendar/vue3/timegrid";
import interactionPlugin from "@fullcalendar/vue3/interaction";

import "@fullcalendar/vue3/skeleton.css";
import "@fullcalendar/vue3/themes/classic/theme.css";
import "@fullcalendar/vue3/themes/classic/palette.css";

import AppError from "@/components/ui/AppError.vue";
import AppLoading from "@/components/ui/AppLoading.vue";
import { useAppointmentStore } from "@/stores/appointments";

const appointments = useAppointmentStore();
const router = useRouter();
const calendarRef = ref(null);

const calendarOptions = computed(() => ({
  plugins: [classicThemePlugin, dayGridPlugin, timeGridPlugin, interactionPlugin],
  initialView: "dayGridMonth",
  headerToolbar: {
    left: "prev,next today",
    center: "title",
    right: "dayGridMonth,timeGridWeek,timeGridDay",
  },
  events: appointments.calendarEvents,
  eventClick: handleEventClick,
  datesSet: handleDatesSet,
  height: "auto",
  nowIndicator: true,
  slotMinTime: "07:00:00",
  slotMaxTime: "20:00:00",
  allDaySlot: false,
}));

function handleEventClick(info) {
  router.push(`/appointments/${info.event.id}`);
}

function handleDatesSet(info) {
  const start = info.startStr.split("T")[0];
  const end = info.endStr.split("T")[0];
  appointments.fetchCalendar({ start, end });
}
</script>

<template>
  <div>
    <AppLoading v-if="appointments.isLoading && !appointments.calendarEvents.length" message="Loading calendar..." />
    <AppError
      v-else-if="appointments.error"
      :message="appointments.error"
      :retry="true"
      @retry="calendarRef?.getApi().view && handleDatesSet({ startStr: calendarRef.getApi().view.activeStart.toISOString(), endStr: calendarRef.getApi().view.activeEnd.toISOString() })"
    />
    <div v-else class="fc-theme-classic">
      <FullCalendar ref="calendarRef" :options="calendarOptions" />
    </div>
  </div>
</template>

<style>
.fc-theme-classic {
  --fc-classic-background: var(--ui-bg);
  --fc-classic-foreground: var(--ui-text);
  --fc-classic-muted-foreground: var(--ui-text-muted);
  --fc-classic-faint-foreground: var(--ui-text-dimmed);
  --fc-classic-border: var(--ui-border);
  --fc-classic-strong-border: var(--ui-border-highlighted);
  --fc-classic-faint: var(--ui-bg-elevated);
  --fc-classic-muted: var(--ui-bg-muted);
  --fc-classic-strong: var(--ui-bg-inverted);
  --fc-classic-primary: var(--ui-primary);
  --fc-classic-primary-foreground: var(--ui-text-inverted);
  --fc-classic-highlight: color-mix(in srgb, var(--ui-primary) 20%, transparent);
  --fc-classic-today: color-mix(in srgb, var(--ui-warning) 15%, transparent);
  --fc-classic-now: var(--ui-error);
  --fc-classic-event: var(--ui-primary);
  --fc-classic-event-contrast: var(--ui-text-inverted);
  --fc-classic-button: var(--ui-bg-elevated);
  --fc-classic-button-border: var(--ui-border);
  --fc-classic-button-strong: var(--ui-primary);
  --fc-classic-button-strong-border: var(--ui-primary);
  --fc-classic-button-foreground: var(--ui-text);
}

.dark .fc-theme-classic {
  --fc-classic-background: var(--ui-bg);
  --fc-classic-foreground: var(--ui-text);
  --fc-classic-muted-foreground: var(--ui-text-muted);
  --fc-classic-faint-foreground: var(--ui-text-dimmed);
  --fc-classic-border: var(--ui-border);
  --fc-classic-strong-border: var(--ui-border-highlighted);
  --fc-classic-faint: var(--ui-bg-elevated);
  --fc-classic-muted: var(--ui-bg-muted);
  --fc-classic-strong: var(--ui-bg-inverted);
}
</style>
