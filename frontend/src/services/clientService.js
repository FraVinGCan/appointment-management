import api from './api'

export async function list(params = {}) {
  const { data } = await api.get('/clients', { params })
  return data
}

export async function get(id) {
  const { data } = await api.get(`/clients/${id}`)
  return data.data
}

export async function create(payload) {
  const { data } = await api.post('/clients', payload)
  return data.data
}

export async function update(id, payload) {
  const { data } = await api.put(`/clients/${id}`, payload)
  return data.data
}

export async function deactivate(id) {
  const { data } = await api.patch(`/clients/${id}/deactivate`)
  return data.data
}
