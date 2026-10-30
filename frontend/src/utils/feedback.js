// Toast dan dialog konfirmasi sederhana. Komponennya dipasang sekali di App.vue.
import { reactive } from 'vue'

export const toastState = reactive({ message: '', timer: null })
export function toast(message) {
  clearTimeout(toastState.timer)
  toastState.message = message
  toastState.timer = setTimeout(() => { toastState.message = '' }, 3000)
}

export const confirmState = reactive({ open: false, title: '', text: '', yes: 'Ya', resolve: null })
/** const ok = await confirmDialog({ title, text, yes }) */
export function confirmDialog({ title, text, yes = 'Ya, lanjutkan' }) {
  return new Promise((resolve) => {
    Object.assign(confirmState, { open: true, title, text, yes, resolve })
  })
}
export function closeConfirm(answer) {
  const r = confirmState.resolve
  confirmState.open = false
  confirmState.resolve = null
  if (r) r(answer)
}
