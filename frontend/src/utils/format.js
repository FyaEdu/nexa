export const rupiah = (n) => {
  const v = Math.round(Number(n) || 0)
  return (v < 0 ? '−' : '') + 'Rp ' + Math.abs(v).toLocaleString('id-ID')
}

export const tanggal = (d) => {
  if (!d) return '–'
  const t = new Date(String(d).slice(0, 10) + 'T00:00:00')
  return isNaN(t) ? d : t.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

export const today = () => {
  const d = new Date()
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
}
