<script setup>
import { computed, onMounted, ref } from 'vue'
import { api } from '@/api'
import { auth } from '@/stores/auth'
import { STATUSES } from '@/utils/constants'
import { rupiah, tanggal, today } from '@/utils/format'

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

const ICON = { masuk: 'bi-box-arrow-in-down', status: 'bi-flag', inspeksi: 'bi-clipboard-check', biaya: 'bi-wallet2', sparepart: 'bi-wrench', dokumen: 'bi-file-text', booking: 'bi-tag', terjual: 'bi-check-lg', batal: 'bi-arrow-counterclockwise' }
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
          <div v-for="s in segments" :key="s.key" class="nx-seg" :class="`st-${s.key}`" :style="{ flex: s.n }">
            <b>{{ s.n }}</b><span>{{ s.label }}</span>
          </div>
        </div>
      </div>
    </section>

    <div class="row g-4">
      <div class="col-lg-7">
        <section class="card nx-card h-100">
          <div class="card-header"><h2 class="h5 mb-0">Aktivitas terbaru</h2></div>
          <div class="card-body">
            <ol class="nx-tl">
              <li v-for="a in data.recent" :key="a.id">
                <span class="ti" :class="`ty-${a.type}`"><i class="bi" :class="ICON[a.type] || 'bi-flag'"></i></span>
                <div>
                  <div class="fw-semibold">{{ a.title }}</div>
                  <div v-if="a.detail" class="small text-body-secondary">{{ a.detail }}</div>
                  <div class="small text-body-secondary">{{ tanggal(a.activity_date) }}, oleh {{ a.created_by_name || 'Sistem' }}, {{ a.motor_code }} {{ a.model }}</div>
                </div>
              </li>
            </ol>
          </div>
        </section>
      </div>
      <div class="col-lg-5">
        <section class="card nx-card h-100">
          <div class="card-header"><h2 class="h5 mb-0">Status pengembangan</h2></div>
          <div class="card-body small">
            <p>Tahap 1 selesai: database dan data dummy, login dengan 4 role, layout, dan ringkasan dashboard ini.</p>
            <p class="mb-0 text-body-secondary">Grafik modal vs harga jual, komposisi biaya, dan notifikasi ditambahkan di Tahap 5. Menu bertanda T2–T5 di samping masih placeholder.</p>
          </div>
        </section>
      </div>
    </div>
  </template>
</template>
