// Rumus Asset Health Score. Sama dengan backend/application/libraries/Health_score.php
import { CHECKLIST } from './constants'

const VAL = { baik: 1, perlu: 0.5, rusak: 0 }

export function fullItems(items = {}) {
  const out = {}
  CHECKLIST.forEach((g) => g.items.forEach(([k]) => { out[k] = items[k] || 'baik' }))
  return out
}

export function groupScore(group, items) {
  const sum = group.items.reduce((s, [k]) => s + (VAL[items[k]] ?? 1), 0)
  return Math.round((100 * sum) / group.items.length)
}

export function calcScore(items) {
  const total = CHECKLIST.reduce((t, g) => t + (g.weight * g.items.reduce((s, [k]) => s + (VAL[items[k]] ?? 1), 0)) / g.items.length, 0)
  return Math.round(total)
}

export const healthClass = (s) => (s == null ? 'none' : s >= 80 ? 'good' : s >= 60 ? 'warn' : 'bad')
export const healthLabel = (s) => (s == null ? 'Belum diperiksa' : s >= 80 ? 'Sehat' : s >= 60 ? 'Perlu perhatian' : 'Kritis')
