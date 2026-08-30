<script setup>
import { CalendarDate } from "@internationalized/date";
import { computed, onMounted, reactive } from "vue";
import { useRoute, useRouter } from "vue-router";

import AppDateTimePicker from "../components/AppDateTimePicker.vue";
import AppError from "../components/AppError.vue";
import AppLoading from "../components/AppLoading.vue";
import AppPageHeader from "../components/AppPageHeader.vue";
import { useAppointmentStore } from "../stores/appointments";
import { useServiceStore } from "../stores/services";

const appointments = useAppointmentStore();
const services = useServiceStore();
const route = useRoute();
const toast = useToast();
const router = useRouter();
const form = reactive({
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
const appointmentDateTime = computed({
  get: () => ({
    date: form.appointmentDate,
    startTime: form.startTime,
    endTime: form.endTime,
  }),
  set: (value) => {
    form.appointmentDate = value?.date || null;
    form.startTime = value?.startTime || null;
    form.endTime = value?.endTime || null;
  },
});

onMounted(() => services.fetch(route.params.id));

function fieldError(field) {
  return appointments.validationErrors[field]?.[0];
}

const dateTimeError = computed(
  () =>
    fieldError("appointmentDate") ||
    fieldError("startTime") ||
    fieldError("endTime"),
);

function formatTime(time) {
  return `${String(time.hour).padStart(2, "0")}:${String(time.minute).padStart(2, "0")}`;
}

async function submit() {
  try {
    await appointments.createBooking({
      ...form,
      serviceId: Number(route.params.id),
      appointmentDate: form.appointmentDate?.toString() || "",
      startTime: form.startTime ? formatTime(form.startTime) : "",
      endTime: form.endTime ? formatTime(form.endTime) : "",
    });
    toast.add({
      title: "Success",
      description: "Booking request submitted successfully.",
      color: "success",
    });
    router.push({ name: "client-appointments" });
  } catch {
    // The store exposes request and field errors to the template.
  }
}
</script>

<template>
  <section class="space-y-6">
    <AppPageHeader
      :breadcrumbs="[
        { label: 'Service marketplace', to: '/client/marketplace' },
        { label: 'Service details' },
      ]"
      :title="services.current?.name || 'Service details'"
      description="Review the service and request a time that works for you."
    />

    <AppLoading v-if="services.isLoading" message="Loading service..." />
    <AppError
      v-else-if="services.error"
      :message="services.error"
      :retry="true"
      @retry="services.fetch(route.params.id)"
    />
    <div v-else-if="services.current" class="max-w-2xl space-y-6">
      <UCard variant="subtle">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <h2 class="text-lg font-semibold">About this service</h2>
          <UBadge v-if="services.current.category" color="primary" variant="subtle">
            {{ services.current.category }}
          </UBadge>
        </div>
        <p v-if="services.current.shortDescription" class="mt-2 text-slate-300">
          {{ services.current.shortDescription }}
        </p>
        <p class="text-slate-400">
          {{
            services.current.description ||
            "No description provided for this service yet."
          }}
        </p>
      </UCard>
      <UForm class="space-y-5" :state="form" @submit="submit">
        <UCard variant="subtle" class="p-4 sm:p-6">
          <div class="space-y-6">
            <div class="grid gap-5 sm:grid-cols-2">
              <UFormField
                label="Date & time"
                name="appointmentDateTime"
                required
                :error="dateTimeError"
                class="sm:col-span-2"
              >
                <AppDateTimePicker
                  v-model="appointmentDateTime"
                  :min-value="today"
                />
              </UFormField>
            </div>

            <UFormField
              label="Notes"
              name="notes"
              hint="Optional"
              :error="fieldError('notes')"
            >
              <UTextarea v-model="form.notes" :rows="4" class="w-full" />
            </UFormField>

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
              >
                Submit booking request
              </UButton>
            </div>
          </div>
        </UCard>
      </UForm>
    </div>
  </section>
</template>
