export function normalizeApiError(error) {
  const status = error.response?.status || 0
  const response = error.response?.data || {}

  return Object.assign(error, {
    status,
    message: response.message || messageForStatus(status),
    validationErrors: response.errors || {},
  })
}

export function messageForStatus(status) {
  return {
    401: 'Your session has expired. Please sign in again.',
    404: 'The requested resource was not found.',
    409: 'This action cannot be completed right now.',
    422: 'Please review the highlighted fields.',
    500: 'The server encountered an error. Please try again.',
  }[status] || 'Something went wrong. Please try again.'
}

export function validationErrors(error) {
  return error.validationErrors || error.response?.data?.errors || {}
}

export function errorMessage(error) {
  return error.message || error.response?.data?.message || messageForStatus(error.response?.status)
}
