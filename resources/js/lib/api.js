import axios from 'axios'

export const api = axios.create({
  baseURL: '/',
  withCredentials: true, // send/receive cookies
  headers: { 'X-Requested-With': 'XMLHttpRequest' },
  xsrfCookieName: 'XSRF-TOKEN',
  xsrfHeaderName: 'X-XSRF-TOKEN',
})

// Get CSRF cookie before first state-changing request
export async function ensureSanctum() {
  await axios.get('/sanctum/csrf-cookie', { withCredentials: true })
}

// Convenience wrapper that ensures CSRF and passes an idempotency key
export async function post(url, data, opts = {}) {
  await ensureSanctum()
  const idem = opts.headers?.['Idempotency-Key'] ?? crypto.randomUUID()
  return api.post(url, data, {
    ...opts,
    headers: { ...(opts.headers || {}), 'Idempotency-Key': idem },
  })
}
export async function get(url, data, opts = {}) {
  await ensureSanctum()
  const idem = opts.headers?.['Idempotency-Key'] ?? crypto.randomUUID()
  return api.get(url, data, {
    ...opts,
    headers: { ...(opts.headers || {}), 'Idempotency-Key': idem },
  })
}

export async function put(url, data, opts = {}) {
  await ensureSanctum()
  const idem = opts.headers?.['Idempotency-Key'] ?? crypto.randomUUID()
  return api.put(url, data, {
    ...opts,
    headers: { ...(opts.headers || {}), 'Idempotency-Key': idem },
  })
}

export async function destroy(url, opts = {}) {
  await ensureSanctum()
  const idem = opts.headers?.['Idempotency-Key'] ?? crypto.randomUUID()
  return api.delete(url, {
    ...opts,
    headers: { ...(opts.headers || {}), 'Idempotency-Key': idem },
  })
}
