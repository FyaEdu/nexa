// Data referensi dari GET /api/meta (kategori biaya, dll.), dimuat sekali lalu disimpan.
import { reactive } from 'vue'
import { api } from '@/api'

export const meta = reactive({ loaded: false, cost_categories: [], roles: [] })
let pending = null

export function loadMeta() {
  if (meta.loaded) return Promise.resolve(meta)
  if (!pending) {
    pending = api.get('/meta').then((r) => {
      meta.cost_categories = r.cost_categories
      meta.roles = r.roles
      meta.loaded = true
      return meta
    }).finally(() => { pending = null })
  }
  return pending
}
