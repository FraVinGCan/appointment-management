import { onUnmounted, watch } from "vue";

export function useDebouncedWatch(source, callback, delay = 250) {
  let timer;

  const stop = watch(source, (...args) => {
    window.clearTimeout(timer);
    timer = window.setTimeout(() => callback(...args), delay);
  });

  onUnmounted(() => {
    window.clearTimeout(timer);
    stop();
  });

  return () => {
    window.clearTimeout(timer);
    stop();
  };
}
