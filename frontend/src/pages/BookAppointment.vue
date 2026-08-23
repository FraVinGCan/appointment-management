<script setup>
import { CalendarDate, Time } from "@internationalized/date";
import { computed, onMounted, reactive } from "vue";
import { useRouter } from "vue-router";

import AppDatePicker from "../components/AppDatePicker.vue";
import AppError from "../components/AppError.vue";
import AppLoading from "../components/AppLoading.vue";
import AppPageHeader from "../components/AppPageHeader.vue";
import { useAppointmentStore } from "../stores/appointments";
import { useNotificationStore } from "../stores/notifications";
import { useServiceStore } from "../stores/services";

const appointments = useAppointmentStore();
const services = useServiceStore();
const notifications = useNotificationStore();
const router = useRouter();
const form = reactive({
  serviceId: "",
  appointmentDate: null,
  startTime: null,
  endTime: null,
  notes: "",
});
const today = computed(
  () =>
    new CalendarDate(
      new Date().getFullYear(),
      new Date().getMonth() + 1,
      new Date().getDate(),
    ),
);

onMounted(() => services.fetchActive());

function fieldError(field) {
  return appointments.validationErrors[field]?.[0];
}

function formatTime(time) {
  return `${String(time.hour).padStart(2, "0")}:${String(time.minute).padStart(2, "0")}`;
}

async function submit() {
  try {
    await appointments.createBooking({
      ...form,
      serviceId: Number(form.serviceId),
      appointmentDate: form.appointmentDate?.toString() || "",
      startTime: form.startTime ? formatTime(form.startTime) : "",
      endTime: form.endTime ? formatTime(form.endTime) : "",
    });
    notifications.notify("Booking request submitted successfully.");
    router.push({ name: "client-appointments" });
  } catch {
    // The store exposes request and field errors to the template.
  }
}
</script>

<template>
  <section class="space-y-6">
    <AppPageHeader
      title="Book an appointment"
      description="Choose a service and request a time that works for you."
    />

    <AppLoading
      v-if="services.isLoading"
      message="Loading available services..."
    />
    <AppError
      v-else-if="services.error"
      :message="services.error"
      :retry="true"
      @retry="services.fetchActive()"
    />
    <UForm v-else class="max-w-2xl space-y-5" :state="form" @submit="submit">
      <UCard variant="subtle" class="p-4 sm:p-6"
        ><div class="space-y-6">
          <UFormField
            label="Service"
            name="serviceId"
            required
            :error="fieldError('serviceId')"
            ><USelect
              v-model="form.serviceId"
              placeholder="Select a service"
              value-key="value"
              :items="
                services.items.map((service) => ({
                  label: service.name,
                  value: String(service.id),
                }))
              "
              class="w-full"
          /></UFormField>

          <div class="grid gap-5 sm:grid-cols-2">
            <UFormField
              label="Date"
              name="appointmentDate"
              required
              :error="fieldError('appointmentDate')"
              ><AppDatePicker
                v-model="form.appointmentDate"
                :min-value="today"
              /></UFormField>
            <UFormField
              label="Start time"
              name="startTime"
              required
              :error="fieldError('startTime')"
              ><UInputTime
                v-model="form.startTime"
                hide-time-zone
                class="w-full"
            /></UFormField>
            <UFormField
              label="End time"
              name="endTime"
              required
              :error="fieldError('endTime')"
              ><UInputTime
                v-model="form.endTime"
                hide-time-zone
                class="w-full"
            /></UFormField>
          </div>

          <UFormField
            label="Notes"
            name="notes"
            hint="Optional"
            :error="fieldError('notes')"
            ><UTextarea v-model="form.notes" :rows="4" class="w-full"
          /></UFormField>

          <UAlert
            v-if="appointments.error"
            color="error"
            variant="soft"
            :description="appointments.error"
          />
          <div class="flex flex-wrap gap-3">
            <UButton
              type="submit"
              class="w-full sm:w-auto"
              :loading="appointments.isSaving"
              >Submit booking request</UButton
            >
          </div>
        </div>
      </UCard>
    </UForm>
  </section>
</template>
