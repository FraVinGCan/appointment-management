<script setup>
import { computed, ref, watch } from "vue";
import { CalendarDate, getLocalTimeZone, today } from "@internationalized/date";

import * as appointmentService from "@/services/appointmentService";

const currentDate = today(getLocalTimeZone());
const selectedDate = ref(currentDate);
const appointmentsByDate = ref({});

const startOfMonth = computed(() => {
  const d = selectedDate.value;
  return new CalendarDate(d.year, d.month, 1).toString();
});

const endOfMonth = computed(() => {
  const d = selectedDate.value;
  const lastDay = new Date(d.year, d.month, 0).getDate();
  return new CalendarDate(d.year, d.month, lastDay).toString();
});

watch(
  [startOfMonth, endOfMonth],
  async ([start, end]) => {
    try {
      const response = await appointmentService.list({
        date_from: start,
        date_to: end,
        per_page: 100,
      });
      const grouped = {};
      for (const appointment of response.data) {
        const date = appointment.appointmentDate;
        grouped[date] = (grouped[date] || 0) + 1;
      }
      appointmentsByDate.value = grouped;
    } catch {
      appointmentsByDate.value = {};
    }
  },
  { immediate: true },
);

function getChipColor(date) {
  const dateStr = `${date.year}-${String(date.month).padStart(2, "0")}-${String(date.day).padStart(2, "0")}`;
  const count = appointmentsByDate.value[dateStr] || 0;
  if (count === 0) return undefined;
  if (count <= 2) return "info";
  if (count <= 4) return "warning";
  return "error";
}

function hasAppointments(date) {
  const dateStr = `${date.year}-${String(date.month).padStart(2, "0")}-${String(date.day).padStart(2, "0")}`;
  return (appointmentsByDate.value[dateStr] || 0) > 0;
}
</script>

<template>
  <UCalendar v-model="selectedDate" :fixed-weeks="true">
    <template #day="{ day }">
      <UChip
        :show="hasAppointments(day)"
        :color="getChipColor(day)"
        size="2xs"
      >
        {{ day.day }}
      </UChip>
    </template>
  </UCalendar>
</template>
