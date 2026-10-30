<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { api } from '@/api'
import { can } from '@/stores/auth'
import { confirmDialog, toast } from '@/utils/feedback'
import { CHECKLIST, RESULT_LABELS } from '@/utils/constants'
import { rupiah, tanggal } from '@/utils/format'
import StatusBadge from '@/components/StatusBadge.vue'
import HealthBadge from '@/components/HealthBadge.vue'
import HealthGauge from '@/components/HealthGauge.vue'
import GroupBars from '@/components/GroupBars.vue'
import LifecycleRoad from '@/components/LifecycleRoad.vue'
import ActivityTimeline from '@/components/ActivityTimeline.vue'
import TonePill from '@/components/TonePill.vue'
import EmptyState from '@/components/EmptyState.vue'
import LoadingBlock from '@/components/LoadingBlock.vue'
import MotorForm from '@/components/forms/MotorForm.vue'
import StatusForm from '@/components/forms/StatusForm.vue'
import DocumentForm from '@/components/forms/DocumentForm.vue'
import CostForm from '@/components/forms/CostForm.vue'
import StockOutForm from '@/components/forms/StockOutForm.vue'

const route = useRoute()
const data = ref(null)
const error = ref('')
const modal = ref(null) // { type: 'edit'|'status'|'doc'|'cost'|'part', doc? }

const TABS = [
  { key: 'timeline', label: 'Asset timeline' },
  { key: 'inspeksi', label: 'Inspeksi' },
  { key: 'biaya', label: 'Biaya' },
  { key: 'sparepart', label: 'Sparepart' },
  { key: 'dokumen', label: 'Dokumen' },
  { key: 'penjualan', label: 'Penjualan' }
]
const tab = computed(() => (TABS.some((t) => t.key === route.params.tab) ? route.params.tab : 'timeline'))

async function load() {
  error.value = ''
  try {
    data.value = await api.get(`/motors/${route.params.id}`)
  } catch (e) {
    error.value = e.message
  }
}
watch(() => route.params.id, () => { data.value = null; load() }, { immediate: true })

const m = computed(() => data.value?.motor)
const s = computed(() => data.value?.summary)
const sold = computed(() => m.value?.status === 'terjual')
const lastIns = computed(() => data.value?.inspections?.[0])
const priceLabel = computed(() => ({ terjual: 'Harga terjual', disepakati: 'Harga disepakati' })[s.value?.price_source] || 'Harga target')

function done() {
  modal.value = null
  load()
}
async function deleteCost(c) {
  if (!(await confirmDialog({ title: 'Hapus biaya?', text: `${c.item} sebesar ${rupiah(c.amount)} akan dihapus dan modal motor berkurang. Catatan di timeline tetap ada.`, yes: 'Hapus biaya' }))) return
  try { await api.del(`/costs/${c.id}`); toast('Biaya dihapus.'); load() } catch (e) { toast(e.message) }
}
async function deleteDoc(d) {
  if (!(await confirmDialog({ title: 'Hapus dokumen?', text: `Data ${d.doc_type} untuk motor ini akan dihapus.`, yes: 'Hapus dokumen' }))) return
  try { await api.del(`/documents/${d.id}`); toast('Dokumen dihapus.'); load() } catch (e) { toast(e.message) }
}
const resultTone = (v) => (v === 'baik' ? 'good' : v === 'perlu' ? 'warn' : 'bad')
const resultLabel = (g, v) => (g.labels || RESULT_LABELS)[v]
</script>

<template>
  <div><router-link to="/kendaraan" class="btn btn-sm btn-link px-0 text-decoration-none"><i class="bi bi-chevron-left"></i> Daftar kendaraan</router-link></div>

  <div v-if="error" class="alert alert-danger">{{ error }}</div>
  <LoadingBlock v-else-if="!data" />

  <template v-else>
    <div class="d-flex flex-wrap gap-3 align-items-start">
      <div class="flex-grow-1">
        <h2 class="nx-d-title">{{ m.brand }} {{ m.model }} {{ m.year }}</h2>
        <div class="d-flex flex-wrap gap-2 align-items-center text-body-secondary small mt-1">
          <span class="nx-plate">{{ m.plate_number || '–' }}</span><span>{{ m.code }}</span><span>{{ m.color }}</span><StatusBadge :status="m.status" />
        </div>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <router-link v-if="can('inspect') && !['booking', 'terjual'].includes(m.status)" :to="`/inspeksi/baru/${m.id}`" class="btn btn-light border"><i class="bi bi-clipboard-check me-1"></i>Inspeksi baru</router-link>
        <button v-if="can('motor.status') && !['booking', 'terjual'].includes(m.status)" class="btn btn-light border" @click="modal = { type: 'status' }"><i class="bi bi-flag me-1"></i>Ubah status</button>
        <button v-if="can('motor.edit')" class="btn btn-light border" @click="modal = { type: 'edit' }"><i class="bi bi-pencil me-1"></i>Edit data</button>
      </div>
    </div>

    <section class="card nx-card"><div class="card-body"><LifecycleRoad :status="m.status" :activities="data.activities" /></div></section>

    <div class="row g-4">
      <div class="col-xl-3 col-md-6">
        <section class="card nx-card h-100">
          <div class="card-header"><h3 class="h6 mb-0">Asset Health Score</h3></div>
          <div class="card-body">
            <HealthGauge :score="m.health_score" />
            <template v-if="lastIns">
              <div class="mt-3"><GroupBars :items="lastIns.items" /></div>
              <p class="small text-body-secondary mt-2 mb-0 text-center">Inspeksi terakhir {{ tanggal(lastIns.inspection_date) }}</p>
            </template>
            <p v-else class="small text-body-secondary mt-2 mb-0 text-center">Skor muncul setelah checklist inspeksi diisi.</p>
          </div>
        </section>
      </div>
      <div class="col-xl-4 col-md-6">
        <section class="card nx-card h-100">
          <div class="card-header"><h3 class="h6 mb-0">Identitas kendaraan</h3></div>
          <div class="card-body">
            <dl class="nx-kv">
              <dt>Merek / tipe</dt><dd>{{ m.brand }} {{ m.model }}</dd>
              <dt>Tahun</dt><dd>{{ m.year }}</dd>
              <dt>Nomor rangka</dt><dd>{{ m.frame_number || '–' }}</dd>
              <dt>Nomor mesin</dt><dd>{{ m.engine_number || '–' }}</dd>
              <dt>Tanggal beli</dt><dd>{{ tanggal(m.purchase_date) }}</dd>
              <dt>Asal pembelian</dt><dd>{{ m.purchase_source || '–' }}</dd>
              <dt>Catatan kondisi</dt><dd>{{ m.condition_note || '–' }}</dd>
            </dl>
          </div>
        </section>
      </div>
      <div class="col-xl-5">
        <section class="card nx-card h-100">
          <div class="card-header"><h3 class="h6 mb-0">Ringkasan modal</h3></div>
          <div class="card-body">
            <dl class="nx-kv">
              <dt>Harga beli</dt><dd>{{ rupiah(s.purchase_price) }}</dd>
              <template v-for="c in s.costs" :key="c.id"><template v-if="c.total > 0"><dt>Biaya {{ c.name.toLowerCase() }}</dt><dd>{{ rupiah(c.total) }}</dd></template></template>
              <dt>Sparepart dari gudang</dt><dd>{{ rupiah(s.parts_total) }}</dd>
              <dt class="tot">Total modal</dt><dd class="tot">{{ rupiah(s.total_modal) }}</dd>
              <dt>{{ priceLabel }}</dt><dd>{{ s.price ? rupiah(s.price) : '–' }}</dd>
              <dt>{{ sold ? 'Laba/rugi' : 'Laba (estimasi)' }}</dt>
              <dd class="fw-bold" :class="s.profit >= 0 ? 'text-success' : 'text-danger'">{{ s.profit != null ? rupiah(s.profit) : '–' }}</dd>
            </dl>
          </div>
        </section>
      </div>
    </div>

    <section class="card nx-card">
      <nav class="nx-tabs" aria-label="Bagian detail motor">
        <router-link v-for="t in TABS" :key="t.key" :to="`/kendaraan/${m.id}/${t.key}`" :aria-current="tab === t.key ? 'page' : undefined" replace>{{ t.label }}</router-link>
      </nav>
      <div class="card-body">
        <!-- Asset timeline -->
        <ActivityTimeline v-if="tab === 'timeline'" :items="data.activities" />

        <!-- Inspeksi -->
        <template v-else-if="tab === 'inspeksi'">
          <EmptyState v-if="!data.inspections.length" text="Motor ini belum pernah diinspeksi.">
            <router-link v-if="can('inspect') && !sold" :to="`/inspeksi/baru/${m.id}`" class="btn btn-primary">Isi checklist inspeksi</router-link>
          </EmptyState>
          <details v-for="(ins, i) in data.inspections" :key="ins.id" class="card nx-card mb-2" :open="i === 0">
            <summary class="card-header d-flex justify-content-between align-items-center gap-2 nx-click">
              <span><b>{{ tanggal(ins.inspection_date) }}</b> <span class="text-body-secondary">oleh {{ ins.inspector }}</span></span>
              <HealthBadge :score="ins.score" />
            </summary>
            <div class="card-body">
              <div class="row g-4">
                <div v-for="g in CHECKLIST" :key="g.key" class="col-md-6">
                  <h4 class="h6">{{ g.label }}</h4>
                  <div v-for="[k, label] in g.items" :key="k" class="d-flex justify-content-between py-1">
                    <span>{{ label }}</span>
                    <span class="nx-pill" :class="`tone-${resultTone(ins.items[k] || 'baik')}`">{{ resultLabel(g, ins.items[k] || 'baik') }}</span>
                  </div>
                </div>
              </div>
              <p v-if="ins.notes" class="mt-3 mb-0"><b>Catatan:</b> {{ ins.notes }}</p>
            </div>
          </details>
        </template>

        <!-- Biaya -->
        <template v-else-if="tab === 'biaya'">
          <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
            <p class="flex-grow-1 mb-0 text-body-secondary">Total biaya di luar harga beli dan sparepart: <b class="text-body">{{ rupiah(s.costs_total) }}</b></p>
            <button v-if="can('cost.edit')" class="btn btn-primary" @click="modal = { type: 'cost' }"><i class="bi bi-plus-lg me-1"></i>Catat biaya</button>
          </div>
          <p v-if="!can('cost.view')" class="text-body-secondary">Rincian biaya hanya bisa dilihat Administrator, Pemilik, dan Staff Operasional.</p>
          <EmptyState v-else-if="!data.costs.length" text="Belum ada biaya untuk motor ini. Catat juga biaya kecil seperti bensin dan uang makan." />
          <div v-else class="table-responsive">
            <table class="table nx-table align-middle mb-0">
              <thead><tr><th>Tanggal</th><th>Kategori</th><th>Rincian</th><th>Dicatat oleh</th><th class="text-end">Nominal</th><th v-if="can('cost.edit')"></th></tr></thead>
              <tbody>
                <tr v-for="c in data.costs" :key="c.id">
                  <td class="text-nowrap">{{ tanggal(c.cost_date) }}</td><td>{{ c.category_name }}</td>
                  <td><div class="fw-semibold">{{ c.item }}</div><div v-if="c.note" class="small text-body-secondary">{{ c.note }}</div></td>
                  <td class="text-body-secondary">{{ c.created_by_name }}</td><td class="text-end text-nowrap">{{ rupiah(c.amount) }}</td>
                  <td v-if="can('cost.edit')" class="text-end"><button class="btn btn-sm btn-light" title="Hapus biaya" aria-label="Hapus biaya" @click="deleteCost(c)"><i class="bi bi-trash"></i></button></td>
                </tr>
              </tbody>
            </table>
          </div>
        </template>

        <!-- Sparepart -->
        <template v-else-if="tab === 'sparepart'">
          <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
            <p class="flex-grow-1 mb-0 text-body-secondary">Sparepart diambil dari stok gudang dan otomatis masuk ke modal motor.</p>
            <button v-if="can('inv.edit') && !sold" class="btn btn-primary" @click="modal = { type: 'part' }"><i class="bi bi-wrench me-1"></i>Pakai sparepart</button>
          </div>
          <EmptyState v-if="!data.parts.length" text="Belum ada sparepart dari gudang yang dipakai untuk motor ini." />
          <div v-else class="table-responsive">
            <table class="table nx-table align-middle mb-0">
              <thead><tr><th>Tanggal</th><th>Barang</th><th class="text-end">Jumlah</th><th class="text-end">Harga satuan</th><th class="text-end">Total</th><th>Oleh</th></tr></thead>
              <tbody>
                <tr v-for="p in data.parts" :key="p.id">
                  <td class="text-nowrap">{{ tanggal(p.usage_date) }}</td>
                  <td><div class="fw-semibold">{{ p.item_name }}</div><div class="small text-body-secondary">{{ p.item_code }}<template v-if="p.note">, {{ p.note }}</template></div></td>
                  <td class="text-end">{{ p.qty }} {{ p.unit }}</td><td class="text-end text-nowrap">{{ rupiah(p.unit_price) }}</td>
                  <td class="text-end text-nowrap">{{ rupiah(p.total) }}</td><td class="text-body-secondary">{{ p.created_by_name || '–' }}</td>
                </tr>
              </tbody>
              <tfoot><tr><td colspan="4" class="fw-bold">Total</td><td class="text-end fw-bold text-nowrap">{{ rupiah(s.parts_total) }}</td><td></td></tr></tfoot>
            </table>
          </div>
        </template>

        <!-- Dokumen -->
        <template v-else-if="tab === 'dokumen'">
          <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
            <p class="flex-grow-1 mb-0 text-body-secondary">{{ data.documents.filter((d) => d.status === 'Lengkap').length }} dari {{ data.documents.length }} dokumen lengkap</p>
            <button v-if="can('doc.edit')" class="btn btn-primary" @click="modal = { type: 'doc', doc: null }"><i class="bi bi-plus-lg me-1"></i>Tambah dokumen</button>
          </div>
          <EmptyState v-if="!data.documents.length" text="Belum ada dokumen tercatat. Catat STNK, BPKB, dan faktur." />
          <div v-else class="table-responsive">
            <table class="table nx-table align-middle mb-0">
              <thead><tr><th>Dokumen</th><th>Nomor</th><th>Status</th><th>Berlaku sampai</th><th>Catatan</th><th v-if="can('doc.edit')"></th></tr></thead>
              <tbody>
                <tr v-for="d in data.documents" :key="d.id">
                  <td class="fw-semibold">{{ d.doc_type }}</td><td>{{ d.doc_number || '–' }}</td><td><TonePill :value="d.status" /></td>
                  <td>{{ d.expiry_date ? tanggal(d.expiry_date) : '–' }}</td><td class="text-body-secondary">{{ d.note }}</td>
                  <td v-if="can('doc.edit')" class="text-end text-nowrap">
                    <button class="btn btn-sm btn-light" title="Edit" aria-label="Edit dokumen" @click="modal = { type: 'doc', doc: d }"><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-sm btn-light ms-1" title="Hapus" aria-label="Hapus dokumen" @click="deleteDoc(d)"><i class="bi bi-trash"></i></button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </template>

        <!-- Penjualan (aksi dibuat di Tahap 5) -->
        <template v-else-if="tab === 'penjualan'">
          <div v-if="data.sale" class="row g-4">
            <div class="col-md-6">
              <dl class="nx-kv">
                <dt>Customer</dt><dd>{{ data.sale.customer_name }}</dd><dt>Telepon</dt><dd>{{ data.sale.customer_phone || '–' }}</dd>
                <dt>Tanggal booking</dt><dd>{{ tanggal(data.sale.booking_date) }}</dd><dt>Tanggal terjual</dt><dd>{{ tanggal(data.sale.sale_date) }}</dd>
              </dl>
            </div>
            <div class="col-md-6">
              <dl class="nx-kv">
                <dt>Harga jual</dt><dd>{{ rupiah(data.sale.price) }}</dd><dt>DP</dt><dd>{{ rupiah(data.sale.dp) }}</dd>
                <dt>Status pembayaran</dt><dd><TonePill :value="data.sale.payment_status" /></dd>
              </dl>
            </div>
          </div>
          <EmptyState v-else :text="m.status === 'siap' ? 'Motor siap dijual dan belum ada booking.' : 'Motor belum siap dijual.'" />
          <p class="small text-body-secondary mb-0 mt-3"><i class="bi bi-info-circle me-1"></i>Booking, pelunasan, dan pembatalan dikerjakan di Tahap 5.</p>
        </template>
      </div>
    </section>

    <MotorForm v-if="modal?.type === 'edit'" :motor="m" @close="modal = null" @saved="done" />
    <StatusForm v-if="modal?.type === 'status'" :motor="m" @close="modal = null" @saved="done" />
    <DocumentForm v-if="modal?.type === 'doc'" :motor-id="m.id" :doc="modal.doc" @close="modal = null" @saved="done" />
    <CostForm v-if="modal?.type === 'cost'" :motor-id="m.id" @close="modal = null" @saved="done" />
    <StockOutForm v-if="modal?.type === 'part'" :motor-id="m.id" @close="modal = null" @saved="done" />
  </template>
</template>
