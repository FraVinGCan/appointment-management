import api from "./api";

import {
  errorMessage as getErrorMessage,
  validationErrors as getValidationErrors,
} from "./error";

const backendBaseUrl = () =>
  (api.defaults.baseURL || "").replace(/\/api\/?$/, "");

export async function csrfCookie() {
  await api.get("/sanctum/csrf-cookie", { baseURL: backendBaseUrl() });
}

async function postWithCsrfRetry(url, payload) {
  await csrfCookie();
  try {
    return await api.post(url, payload);
  } catch (error) {
    if (error.response?.status !== 419) throw error;
    await csrfCookie();
    return await api.post(url, payload);
  }
}

export async function login(credentials) {
  const { data } = await postWithCsrfRetry("/login", credentials);
  return data.user;
}

export async function registerClient(payload) {
  const { data } = await postWithCsrfRetry("/client/register", payload);
  return data.user;
}

export async function currentUser() {
  const { data } = await api.get("/user");
  return data.user ?? null;
}

export async function logout() {
  try {
    await postWithCsrfRetry("/logout");
  } catch (error) {
    if (![401, 419].includes(error.response?.status)) throw error;
  }
}

export function validationErrors(error) {
  return getValidationErrors(error);
}

export function errorMessage(error) {
  return getErrorMessage(error);
}
