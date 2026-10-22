// Pembungkus fetch ke backend. Semua path relatif ke /api (diteruskan Vite ke port 8080).
import { auth } from '@/stores/auth'

export class ApiError extends Error {
  constructor(message, status, fields = null) {
    super(message)
    this.status = status
    this.fields = fields
  }
}

let onUnauthorized = () => {}
export const setUnauthorizedHandler = (fn) => { onUnauthorized = fn }

export async function request(path, { method = 'GET', body, raw = false } = {}) {
  const headers = { Accept: 'application/json' }
  if (body !== undefined) headers['Content-Type'] = 'application/json'
  if (auth.token) headers.Authorization = `Bearer ${auth.token}`

  let res
  try {
    res = await fetch('/api' + path, { method, headers, body: body !== undefined ? JSON.stringify(body) : undefined })
  } catch {
    throw new ApiError('Tidak bisa terhubung ke server. Pastikan backend berjalan (php spark serve).', 0)
  }

  if (!res.ok) {
    let data = null
    try { data = await res.json() } catch { /* bukan JSON */ }
    const fields = data?.messages && typeof data.messages === 'object' ? data.messages : null
    const message = fields ? Object.values(fields).join(' ') : (data?.message || `Permintaan gagal (kode ${res.status}).`)
    if (res.status === 401 && auth.token) onUnauthorized()
    throw new ApiError(message, res.status, fields)
  }
  if (raw) return res
  if (res.status === 204) return null
  return res.json()
}

export const api = {
  get: (p) => request(p),
  post: (p, b = {}) => request(p, { method: 'POST', body: b }),
  put: (p, b = {}) => request(p, { method: 'PUT', body: b }),
  del: (p) => request(p, { method: 'DELETE' })
}

// Unduh file (laporan Excel/PDF, Tahap 5) dengan token login.
export async function download(path, fallbackName = 'nexa-laporan') {
  const res = await request(path, { raw: true })
  const cd = res.headers.get('Content-Disposition') || ''
  const name = (cd.match(/filename="?([^"]+)"?/) || [])[1] || fallbackName
  const url = URL.createObjectURL(await res.blob())
  const a = document.createElement('a')
  a.href = url
  a.download = name
  document.body.appendChild(a)
  a.click()
  a.remove()
  setTimeout(() => URL.revokeObjectURL(url), 1000)
}
