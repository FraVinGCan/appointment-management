<script setup>
import { reactive } from "vue";

import { useClientStore } from "../stores/clients";

const props = defineProps({ client: { type: Object, default: null } });
const emit = defineEmits(["saved"]);
const clients = useClientStore();
const form = reactive({
  name: props.client?.name || "",
  email: props.client?.email || "",
  phone: props.client?.phone || "",
  active: props.client?.active ?? true,
  password: "",
  password_confirmation: "",
});

function error(field) {
  return clients.validationErrors[field]?.[0];
}
async function submit() {
  try {
    const payload = { ...form };
    if (props.client) {
      delete payload.password;
      delete payload.password_confirmation;
    }
    emit(
      "saved",
      await (props.client
        ? clients.update(props.client.id, payload)
        : clients.create(payload)),
    );
  } catch {
    /* Store state is rendered below. */
  }
}
</script>

<template>
  <UForm class="max-w-2xl" :state="form" @submit="submit">
    <UCard variant="subtle">
      <div class="space-y-6">
        <UFormField label="Name" name="name" required :error="error('name')"
          ><UInput v-model="form.name" autocomplete="name" class="w-full"
        /></UFormField>
        <UFormField label="Email" name="email" required :error="error('email')"
          ><UInput
            v-model="form.email"
            type="email"
            autocomplete="email"
            class="w-full"
        /></UFormField>
        <UFormField label="Phone" name="phone" :error="error('phone')"
          ><UInput
            v-model="form.phone"
            type="tel"
            autocomplete="tel"
            class="w-full"
        /></UFormField>
        <UFormField label="Account status" name="active"
          ><USwitch
            v-model="form.active"
            label="Active client"
            description="Inactive clients cannot access booking routes."
        /></UFormField>
        <div v-if="!client" class="grid gap-5 sm:grid-cols-2">
          <UFormField
            label="Temporary password"
            name="password"
            required
            :error="error('password')"
            ><UInput
              v-model="form.password"
              required
              type="password"
              class="w-full" /></UFormField
          ><UFormField
            label="Confirm password"
            name="password_confirmation"
            required
            :error="error('password_confirmation')"
            ><UInput
              v-model="form.password_confirmation"
              required
              type="password"
              class="w-full"
          /></UFormField>
        </div>
        <UAlert
          v-if="clients.error"
          color="error"
          variant="soft"
          :description="clients.error"
        />
        <div class="flex flex-wrap gap-3">
          <UButton type="submit" :loading="clients.isSaving">{{
            client ? "Save changes" : "Create client"
          }}</UButton>
        </div>
      </div>
    </UCard>
  </UForm>
</template>
