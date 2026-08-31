import { computed, reactive, watch } from "vue";
import { useRoute, useRouter } from "vue-router";

import { parseDate } from "@internationalized/date";

/**
 * Keep component state in sync with URL query params.
 *
 * Config values are defaults or `{ default, sanitize }` definitions. A sanitize
 * fn returns the valid value, or `undefined` to fall back to the default and
 * drop the param from the URL, so a malformed query never 422s the API and
 * blanks the page.
 *
 * @typedef {any|{default:any, sanitize?:Function}} UrlStateDefinition
 * @param {Object<string, UrlStateDefinition>} config
 * @returns {{ [key: string]: import('vue').WritableComputedRef<any> }}
 */
export function useUrlState(config) {
  const route = useRoute();
  const router = useRouter();

  const definitions = Object.entries(config).map(([key, value]) => {
    const isDefinition =
      value !== null &&
      typeof value === "object" &&
      !Array.isArray(value) &&
      "default" in value;
    return {
      key,
      definition: isDefinition ? value : { default: value },
    };
  });

  const state = reactive(
    Object.fromEntries(
      definitions.map(({ key, definition }) => [
        key,
        sanitize(route.query[key], definition),
      ]),
    ),
  );

  // Drop invalid/redundant params from the URL once the router settles its
  // initial navigation.
  const invalidOnLoad = definitions
    .filter(({ key, definition }) => {
      const raw = route.query[key];
      return rawPresent(raw) && state[key] === definition.default;
    })
    .map(({ key }) => key);

  if (invalidOnLoad.length) {
    void router.isReady().then(() => {
      const nextQuery = { ...router.currentRoute.value.query };
      invalidOnLoad.forEach((key) => delete nextQuery[key]);
      router.replace({ query: nextQuery });
    });
  }

  // Keep back/forward navigation reflected and prune invalid params.
  watch(
    () => route.query,
    (query) => {
      pruneInvalid(query, definitions, state);
    },
  );

  return Object.fromEntries(
    definitions.map(({ key, definition }) => [
      key,
      computed({
        get: () => state[key],
        set: (value) => {
          state[key] = value;
          updateQuery(router, route, state, definitions);
        },
      }),
    ]),
  );
}

function rawPresent(raw) {
  return raw !== undefined && raw !== null && raw !== "";
}

/** Resolve a raw query value for one definition using its optional sanitizer. */
function sanitize(raw, definition) {
  const { default: defaultValue, sanitize: san } = definition;

  if (!rawPresent(raw)) return defaultValue;

  if (typeof san === "function") {
    const result = san(raw, defaultValue, definition);
    return result === undefined ? defaultValue : result;
  }

  if (typeof defaultValue === "boolean") return raw === "true" || raw === "1";

  if (typeof defaultValue === "number") {
    const parsed = Number(raw);
    return Number.isFinite(parsed) ? parsed : defaultValue;
  }

  return raw;
}

/** Update state and prune params that are invalid or equal to their default. */
function pruneInvalid(query, definitions, state) {
  const invalid = [];

  definitions.forEach(({ key, definition }) => {
    const raw = query[key];
    const parsed = sanitize(raw, definition);
    state[key] = parsed;

    if (rawPresent(raw) && parsed === definition.default) invalid.push(key);
  });

  if (invalid.length) {
    const nextQuery = { ...query };
    invalid.forEach((key) => delete nextQuery[key]);
    return nextQuery;
  }

  return query;
}

function updateQuery(router, route, state, definitions) {
  const query = { ...route.query };

  definitions.forEach(({ key, definition }) => {
    const defaultValue = definition.default;
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

/** Reusable sanitizers; return `undefined` to drop the param and use its default. */

/** Keeps one of `allowed`, else default. */
export function oneOf(allowed) {
  return (raw) => (allowed.includes(raw) ? raw : undefined);
}

/** Accepts an integer within `[min, max]`, else default. */
export function integerRange(min = 1, max = Infinity) {
  return (raw) => {
    const parsed = Number(raw);
    return Number.isInteger(parsed) && parsed >= min && parsed <= max
      ? parsed
      : undefined;
  };
}

/** Accepts a strict `Y-m-d` date (format + real calendar validity). */
export function dateOnly() {
  const IS_DATE = /^\d{4}-\d{2}-\d{2}$/;
  return (raw) => {
    if (typeof raw !== "string" || !IS_DATE.test(raw)) return undefined;
    try {
      parseDate(raw);
    } catch {
      return undefined;
    }
    return raw;
  };
}
