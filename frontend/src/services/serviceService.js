import api from "./api";

export async function listActive(params = {}) {
  const { data } = await api.get("/services", { params });
  return data;
}

export async function listAll(params = {}) {
  const { data } = await api.get("/management/services", { params });
  return data;
}

export async function listCategories(params = {}) {
  const { data } = await api.get("/management/services/categories", { params });
  return data.data;
}

export async function listActiveCategories(params = {}) {
  const { data } = await api.get("/services/categories", { params: { active: 1, ...params } });
  return data.data;
}

export async function get(id) {
  const { data } = await api.get(`/services/${id}`);
  return data.data;
}

export async function create(payload) {
  const { data } = await api.post("/services", payload);
  return data.data;
}

export async function update(id, payload) {
  const { data } = await api.put(`/services/${id}`, payload);
  return data.data;
}

export async function deactivate(id) {
  const { data } = await api.patch(`/services/${id}/deactivate`);
  return data.data;
}

export async function activate(id) {
  const { data } = await api.patch(`/services/${id}/activate`);
  return data.data;
}
