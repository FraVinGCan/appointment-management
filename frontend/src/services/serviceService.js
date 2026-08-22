import api from './api'

export async function listActive() {
  const { data } = await api.get('/services')
  return data.data
}

export async function listAll() {
  const { data } = await api.get('/management/services')
  return data.data
}

export async function get(id) {
  const { data } = await api.get(`/services/${id}`)
  return data.data
}

export async function create(payload) {
  const { data } = await api.post('/services', payload)
  return data.data
}

export async function update(id, payload) {
  const { data } = await api.put(`/services/${id}`, payload)
  return data.data
}

export async function deactivate(id) {
  const { data } = await api.patch(`/services/${id}/deactivate`)
  return data.data
}
