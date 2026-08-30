import { computed, reactive, watch } from "vue";
import { useRoute, useRouter } from "vue-router";

/**
 * Keep simple component state in sync with URL query parameters.
 *
 * @param {Object} config - Map of parameter names to default values.
 *   Supported default types: string, number, boolean, null.
 * @returns {{ [key: string]: import('vue').WritableComputedRef<any> }}
 */
export function useUrlState(config) {
  const route = useRoute();
  const router = useRouter();

  const entries = Object.entries(config);
  const state = reactive(
    Object.fromEntries(
      entries.map(([key, defaultValue]) => [
        key,
        parseValue(route.query[key], defaultValue),
      ]),
    ),
  );

  // Keep browser back/forward navigation reflected in the controls.
  watch(
    () => route.query,
    (query) => {
      entries.forEach(([key, defaultValue]) => {
        state[key] = parseValue(query[key], defaultValue);
      });
    },
  );

  return Object.fromEntries(
    entries.map(([key, defaultValue]) => [
      key,
      computed({
        get: () => state[key],
        set: (value) => {
          state[key] = value;
          updateQuery(router, route, state, entries);
        },
      }),
    ]),
  );
}

function parseValue(raw, defaultValue) {
  if (raw === undefined || raw === null || raw === "") {
    return defaultValue;
  }

  if (typeof defaultValue === "boolean") {
    return raw === "true" || raw === "1";
  }

  if (typeof defaultValue === "number") {
    const parsed = Number(raw);
    return Number.isFinite(parsed) ? parsed : defaultValue;
  }

  return raw;
}

function updateQuery(router, route, state, entries) {
  const query = { ...route.query };

  entries.forEach(([key, defaultValue]) => {
    const serialized = serializeValue(state[key], defaultValue);

    if (serialized === undefined) delete query[key];
    else query[key] = serialized;
  });

  router.replace({ query });
}

function serializeValue(value, defaultValue) {
  if (value === defaultValue || value === "" || value === null || value === undefined) {
    return undefined;
  }

  if (typeof value === "boolean") {
    return value ? "true" : "false";
  }

  return String(value);
}
