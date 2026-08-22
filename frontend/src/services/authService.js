import api from './api'

const backendBaseUrl = () => (api.defaults.baseURL || '').replace(/\/api\/?$/, '')

export async function csrfCookie() {
  await api.get('/sanctum/csrf-cookie', { baseURL: backendBaseUrl() })
}

export async function login(credentials) {
  await csrfCookie()
  const { data } = await api.post('/login', credentials)
  return data.user
}

export async function registerClient(payload) {
  await csrfCookie()
  const { data } = await api.post('/client/register', payload)
  return data.user
}

export async function currentUser() {
  const { data } = await api.get('/user')
  return data.user
}

export async function logout() {
  await api.post('/logout')
}

export function validationErrors(error) {
  return error.response?.data?.errors || {}
}

export function errorMessage(error) {
  return error.response?.data?.message || 'Something went wrong. Please try again.'
}
