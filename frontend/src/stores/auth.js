// State login sederhana (tanpa Pinia). Disimpan di localStorage supaya tidak hilang saat reload.
import { reactive } from 'vue'
import { api } from '@/api'

const KEY = 'nexa-auth'
const saved = (() => {
  try { return JSON.parse(localStorage.getItem(KEY)) } catch { return null }
})()

export const auth = reactive({
  token: saved?.token ?? null,
  user: saved?.user ?? null,
  permissions: saved?.permissions ?? []
})

function persist() {
  localStorage.setItem(KEY, JSON.stringify({ token: auth.token, user: auth.user, permissions: auth.permissions }))
}

export function clearAuth() {
  auth.token = null
  auth.user = null
  auth.permissions = []
  localStorage.removeItem(KEY)
}

/** Cek hak akses aksi, mis. can('motor.edit'). Daftar kunci di backend Config/Nexa.php */
export const can = (permission) => auth.permissions.includes(permission)

/** Cek role untuk akses halaman. roles kosong = semua role boleh. */
export const hasRole = (roles) => !roles || roles.length === 0 || roles.includes(auth.user?.role)

export async function refreshMe() {
  const r = await api.get('/auth/me')
  auth.user = r.user
  auth.permissions = r.permissions
  persist()
}

export async function login(username, password) {
  const r = await api.post('/auth/login', { username, password })
  auth.token = r.token
  auth.user = r.user
  persist()
  await refreshMe()
}

export async function logout() {
  try { await api.post('/auth/logout') } catch { /* tetap keluar */ }
  clearAuth()
}
