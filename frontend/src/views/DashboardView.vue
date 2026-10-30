<script setup>
import { computed, onMounted, ref } from 'vue'
import { api } from '@/api'
import { auth } from '@/stores/auth'
import { STATUSES } from '@/utils/constants'
import { rupiah, tanggal, today } from '@/utils/format'
import ActivityTimeline from '@/components/ActivityTimeline.vue'

const data = ref(null)
const error = ref('')

onMounted(async () => {
  try {
    data.value = await api.get('/dashboard')
  } catch (e) {
    error.value = e.message
  }
})

const firstName = computed(() => (auth.user?.name || '').split(' ')[0])
const segments = computed(() =>
  data.value ? STATUSES.map((s) => ({ ...s, n: data.value.status_counts[s.key] || 0 })).filter((s) => s.n > 0) : []
)
const diTangan = computed(() => (data.value ? data.value.total_motor - (data.value.status_counts.terjual || 0) : 0))

</script>

<template>
  <p class="nx-hello">Halo, <b>{{ firstName }}</b>. Ini kondisi usaha per {{ tanggal(today()) }}.</p>

  <div v-if="error" class="alert alert-danger">{{ error }}</div>
  <div v-else-if="!data" class="text-body-secondary py-5 text-center"><span class="spinner-border spinner-border-sm me-2"></span>Memuat data…</div>

  <template v-else>
    <div class="nx-strip">
      <div><div class="v">{{ diTangan }}</div><div class="l">Motor di tangan</div></div>
      <div><div class="v">{{ data.status_counts.terjual || 0 }}</div><div class="l">Motor terjual</div></div>
      <div><div class="v money">{{ rupiah(data.modal_tertanam) }}</div><div class="l">Modal tertanam di motor belum terjual</div></div>
      <div><div class="v money" :class="data.laba_terjual >= 0 ? 'text-success' : 'text-danger'">{{ rupiah(data.laba_terjual) }}</div><div class="l">Laba dari motor terjual</div></div>
      <div><div class="v money">{{ rupiah(data.nilai_inventaris) }}</div><div class="l">Nilai stok sparepart ({{ data.stok_minimum }} di batas minimum)</div></div>
    </div>

    <section class="card nx-card">
      <div class="card-header"><h2 class="h5 mb-0">Posisi kendaraan</h2></div>
      <div class="card-body">
        <div class="nx-pipe">
          <router-link v-for="s in segments" :key="s.key" :to="{ path: '/kendaraan', query: { status: s.key } }" class="nx-seg" :class="`st-${s.key}`" :style="{ flex: s.n }" :title="`Lihat motor ${s.label.toLowerCase()}`">
            <b>{{ s.n }}</b><span>{{ s.label }}</span>
          </router-link>
        </div>
      </div>
    </section>

    <div class="row g-4">
      <div class="col-lg-7">
        <section class="card nx-card h-100">
          <div class="card-header"><h2 class="h5 mb-0">Aktivitas terbaru</h2></div>
          <div class="card-body">
            <ActivityTimeline :items="data.recent" show-motor />
          </div>
        </section>
      </div>
      <div class="col-lg-5">
        <section class="card nx-card h-100">
          <div class="card-header"><h2 class="h5 mb-0">Status pengembangan</h2></div>
          <div class="card-body small">
            <p>Tahap 1–4 selesai: login dan hak akses, data kendaraan dengan Asset Timeline, inspeksi dengan Health Score, pencatatan biaya, serta inventaris dan sparepart.</p>
            <p class="mb-0 text-body-secondary">Tahap 5 berisi penjualan, laporan PDF/Excel, manajemen pengguna, grafik dashboard, dan notifikasi. Menu bertanda T5 di samping masih placeholder.</p>
          </div>
        </section>
      </div>
    </div>
  </template>
</template>
