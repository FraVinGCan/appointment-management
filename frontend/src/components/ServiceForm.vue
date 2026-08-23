<script setup>
import { reactive } from "vue";
import { useServiceStore } from "../stores/services";

const props = defineProps({ service: { type: Object, default: null } });
const emit = defineEmits(["saved"]);
const services = useServiceStore();
const form = reactive({
  name: props.service?.name || "",
  description: props.service?.description || "",
  active: props.service?.active ?? true,
});
function error(field) {
  return services.validationErrors[field]?.[0];
}
async function submit() {
  try {
    emit(
      "saved",
      await (props.service
        ? services.update(props.service.id, { ...form })
        : services.create({ ...form })),
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
          ><UInput v-model="form.name" class="w-full"
        /></UFormField>
        <UFormField
          label="Description"
          name="description"
          :error="error('description')"
          ><UTextarea v-model="form.description" :rows="4" class="w-full"
        /></UFormField>
        <UCheckbox v-model="form.active" label="Active service" />
        <UAlert
          v-if="services.error"
          color="error"
          variant="soft"
          :description="services.error"
        />
        <div class="flex flex-wrap gap-3">
          <UButton type="submit" :loading="services.isSaving">{{
            service ? "Save changes" : "Create service"
          }}</UButton>
        </div>
      </div>
    </UCard>
  </UForm>
</template>
