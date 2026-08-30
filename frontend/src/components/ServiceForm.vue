<script setup>
import { onMounted, reactive } from "vue";
import { useServiceStore } from "../stores/services";
import { useDebouncedWatch } from "../composables/useDebouncedWatch";

const props = defineProps({ service: { type: Object, default: null } });
const emit = defineEmits(["saved"]);
const services = useServiceStore();
const form = reactive({
  name: props.service?.name || "",
  shortDescription: props.service?.shortDescription || "",
  category: props.service?.category || "",
  description: props.service?.description || "",
  active: props.service?.active ?? true,
});
function error(field) {
  return services.validationErrors[field]?.[0];
}

function categoryValue() {
  const category = form.category.trim();
  const existingCategory = services.categories.find(
    (value) => value.toLowerCase() === category.toLowerCase(),
  );

  return existingCategory || category;
}

async function submit() {
  try {
    const payload = { ...form, category: categoryValue() };

    emit(
      "saved",
      await (props.service
        ? services.update(props.service.id, payload)
        : services.create(payload)),
    );
  } catch {
    /* Store state is rendered below. */
  }
}

onMounted(() => services.fetchCategories().catch(() => {}));
useDebouncedWatch(
  () => form.category,
  (value) => services.fetchCategories(value.trim() ? { search: value.trim() } : {}),
);
</script>

<template>
  <UForm class="max-w-2xl" :state="form" @submit="submit">
    <UCard variant="subtle">
      <div class="space-y-6">
        <UFormField label="Name" name="name" required :error="error('name')"
          ><UInput v-model="form.name" class="w-full"
        /></UFormField>
        <div class="grid gap-5 sm:grid-cols-2">
          <UFormField
            label="Short description"
            name="shortDescription"
            :error="error('shortDescription')"
          ><UInput v-model="form.shortDescription" class="w-full" /></UFormField>
          <UFormField
            label="Category"
            name="category"
            :error="error('category')"
          ><UInputMenu
            v-model="form.category"
             mode="autocomplete"
             create-item
             ignore-filter
             clear
            :items="services.categories"
            placeholder="Select or type a category"
            class="w-full"
          /></UFormField>
        </div>
        <UFormField
          label="Description"
          name="description"
          :error="error('description')"
          ><UTextarea v-model="form.description" :rows="4" class="w-full"
        /></UFormField>
        <USwitch v-model="form.active" label="Active status" />
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
