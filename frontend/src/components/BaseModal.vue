<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'

// Modal sederhana (tanpa JavaScript Bootstrap). Tutup dengan Esc, klik latar, atau tombol X.
defineProps({ title: { type: String, required: true }, wide: Boolean })
const emit = defineEmits(['close'])
const box = ref(null)

function onKey(e) {
  if (e.key === 'Escape') emit('close')
}
onMounted(() => {
  document.addEventListener('keydown', onKey)
  document.body.classList.add('overflow-hidden')
  const first = box.value?.querySelector('input:not([type=hidden]):not([disabled]), select, textarea')
  if (first) first.focus()
})
onBeforeUnmount(() => {
  document.removeEventListener('keydown', onKey)
  document.body.classList.remove('overflow-hidden')
})
</script>

<template>
  <div class="nx-overlay" @click.self="emit('close')">
    <div ref="box" class="nx-modal" :class="{ wide }" role="dialog" aria-modal="true" :aria-label="title">
      <div class="nx-modal-h">
        <h2 class="h5 mb-0">{{ title }}</h2>
        <button type="button" class="btn-close" aria-label="Tutup" @click="emit('close')"></button>
      </div>
      <div class="nx-modal-b">
        <slot />
      </div>
    </div>
  </div>
</template>
