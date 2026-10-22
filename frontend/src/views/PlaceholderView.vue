<script setup>
import { computed } from 'vue'
import { MENU, STAGES } from '@/utils/constants'

// Halaman sementara untuk menu yang belum dikerjakan.
// Hapus pemakaiannya di router.js saat tahap terkait selesai.
const props = defineProps({ menuKey: { type: String, required: true } })
const menu = computed(() => MENU.find((m) => m.key === props.menuKey))
</script>

<template>
  <section class="card nx-card">
    <div class="card-body p-4">
      <span class="badge text-bg-warning mb-3">Belum dikerjakan</span>
      <h2 class="h4">Halaman {{ menu.label }} dikerjakan di Tahap {{ menu.stage }}</h2>
      <p class="text-body-secondary">{{ STAGES[menu.stage] }}. Rincian tugas, endpoint API, dan kriteria selesai ada di <code>PANDUAN-TAHAP.md</code>.</p>
      <h3 class="h6 mt-4">Yang akan ada di halaman ini</h3>
      <ul class="mb-4">
        <li v-for="t in menu.todo" :key="t">{{ t }}</li>
      </ul>
      <p class="small text-body-secondary mb-0">Contoh tampilan jadi ada di <code>referensi/prototype-nexa.html</code> (buka langsung di browser).</p>
    </div>
  </section>
</template>
