<script setup>
// Pil warna untuk status dokumen / pembayaran
const TONE = { Lengkap: 'good', 'Dalam proses': 'warn', 'Tidak ada': 'bad', Lunas: 'good', 'Belum lunas': 'warn', Booking: 'violet', Batal: 'mute' }
defineProps({ value: { type: String, required: true } })
</script>

<template>
  <span class="nx-pill" :class="`tone-${TONE[value] || 'mute'}`">{{ value }}</span>
</template>
