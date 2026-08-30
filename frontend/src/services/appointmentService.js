import api from "./api";

export async function list(params = {}) {
  const { data } = await api.get("/appointments", { params });
  return data;
}

export async function get(id) {
  const { data } = await api.get(`/appointments/${id}`);
  return data.data;
}

export async function create(payload) {
  const { data } = await api.post("/appointments", payload);
  return data.data;
}

export async function update(id, payload) {
  const { data } = await api.put(`/appointments/${id}`, payload);
  return data.data;
}

export async function remove(id) {
  await api.delete(`/appointments/${id}`);
}

export async function confirm(id) {
  const { data } = await api.post(`/appointments/${id}/confirm`);
  return data.data;
}

export async function complete(id) {
  const { data } = await api.post(`/appointments/${id}/complete`);
  return data.data;
}

export async function cancel(id) {
  const { data } = await api.post(`/appointments/${id}/cancel`);
  return data.data;
}

export async function listClient(params = {}) {
  const { data } = await api.get("/client/appointments", { params });
  return data;
}

export async function getClientDashboard() {
  const { data } = await api.get("/client/dashboard");
  return data;
}

export async function createBooking(payload) {
  const { data } = await api.post("/booking-requests", payload);
  return data.data;
}

export async function cancelClient(id) {
  const { data } = await api.patch(`/client/appointments/${id}/cancel`);
  return data.data;
}
