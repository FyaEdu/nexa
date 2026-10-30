<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { api } from '@/api'
import { can } from '@/stores/auth'
import { rupiah, tanggal } from '@/utils/format'
import EmptyState from '@/components/EmptyState.vue'
import LoadingBlock from '@/components/LoadingBlock.vue'
import ItemForm from '@/components/forms/ItemForm.vue'
import StockInForm from '@/components/forms/StockInForm.vue'
import StockOutForm from '@/components/forms/StockOutForm.vue'

const LEVEL_TEXT = { bad: 'Di bawah/batas minimum', warn: 'Mendekati minimum', good: 'Aman' }
const tab = ref('stok')
const q = ref('')
const data = ref(null)
const trx = ref(null)
const error = ref('')
const modal = ref(null) // { type: 'item'|'in'|'out', item?, itemId? }

async function loadItems() {
  error.value = ''
  try {
    data.value = await api.get('/inventory' + (q.value.trim() ? '?q=' + encodeURIComponent(q.value.trim()) : ''))
  } catch (e) {
    error.value = e.message
  }
}
async function loadTrx() {
  try {
    trx.value = (await api.get('/inventory/transactions')).transactions
  } catch (e) {
    error.value = e.message
  }
}
onMounted(loadItems)
let timer = null
watch(q, () => { clearTimeout(timer); timer = setTimeout(loadItems, 250) })
watch(tab, (t) => { if (t === 'kartu') loadTrx() })

const nextCode = computed(() => {
  const n = Math.max(0, ...(data.value?.items || []).map((i) => Number(String(i.code).replace(/\D/g, '')) || 0)) + 1
  return 'SP-' + String(n).padStart(3, '0')
})

function saved() {
  modal.value = null
  loadItems()
  if (tab.value === 'kartu') loadTrx()
}
</script>

<template>
  <div v-if="data" class="nx-strip" style="--n: 4">
    <div><div class="v">{{ data.summary.item_count }}</div><div class="l">Jenis barang</div></div>
    <div><div class="v money">{{ rupiah(data.summary.total_value) }}</div><div class="l">Nilai inventaris</div></div>
    <div><div class="v" :class="{ 'text-danger': data.summary.bad }">{{ data.summary.bad }}</div><div class="l">Di bawah atau di batas minimum</div></div>
    <div><div class="v" :class="{ 'nx-text-warn': data.summary.warn }">{{ data.summary.warn }}</div><div class="l">Mendekati minimum</div></div>
  </div>

  <div class="d-flex flex-wrap gap-2 align-items-center">
    <div class="d-flex gap-2 flex-grow-1">
      <button class="nx-chip" :class="{ on: tab === 'stok' }" @click="tab = 'stok'">Stok barang</button>
      <button class="nx-chip" :class="{ on: tab === 'kartu' }" @click="tab = 'kartu'">Kartu stok (masuk/keluar)</button>
    </div>
    <template v-if="can('inv.edit')">
      <button class="btn btn-light border" @click="modal = { type: 'in' }"><i class="bi bi-box-arrow-in-down me-1"></i>Barang masuk</button>
      <button class="btn btn-light border" @click="modal = { type: 'out' }"><i class="bi bi-box-arrow-up me-1"></i>Barang keluar</button>
      <button class="btn btn-primary" @click="modal = { type: 'item', item: null }"><i class="bi bi-plus-lg me-1"></i>Barang baru</button>
    </template>
  </div>

  <div v-if="error" class="alert alert-danger">{{ error }}</div>

  <template v-if="tab === 'stok'">
    <div class="nx-search">
      <i class="bi bi-search"></i>
      <input v-model="q" type="search" class="form-control" placeholder="Cari kode, nama barang, atau lokasi rak" aria-label="Cari barang" />
    </div>
    <section class="card nx-card">
      <LoadingBlock v-if="!data" />
      <EmptyState v-else-if="!data.items.length" text="Barang tidak ditemukan." />
      <div v-else class="table-responsive">
        <table class="table nx-table align-middle mb-0">
          <thead><tr><th>Barang</th><th>Lokasi</th><th class="text-end">Stok</th><th class="text-end">Minimum</th><th>Keterangan</th><th class="text-end">Harga satuan</th><th class="text-end">Nilai</th><th v-if="can('inv.edit')"></th></tr></thead>
          <tbody>
            <tr v-for="i in data.items" :key="i.id" :class="i.level !== 'good' ? `nx-low-${i.level}` : ''">
              <td><div class="fw-semibold">{{ i.name }}</div><div class="small text-body-secondary">{{ i.code }}</div></td>
              <td>{{ i.location || '–' }}</td>
              <td class="text-end"><b>{{ i.stock }}</b> {{ i.unit }}</td>
              <td class="text-end">{{ i.min_stock }}</td>
              <td><span class="nx-stock" :class="i.level"><i></i>{{ LEVEL_TEXT[i.level] }}</span></td>
              <td class="text-end text-nowrap">{{ rupiah(i.unit_price) }}/{{ i.unit }}</td>
              <td class="text-end text-nowrap">{{ rupiah(i.value) }}</td>
              <td v-if="can('inv.edit')" class="text-end text-nowrap">
                <button class="btn btn-sm btn-light" title="Barang masuk" :aria-label="`Barang masuk ${i.name}`" @click="modal = { type: 'in', itemId: i.id }"><i class="bi bi-box-arrow-in-down"></i></button>
                <button class="btn btn-sm btn-light ms-1" title="Edit barang" :aria-label="`Edit ${i.name}`" @click="modal = { type: 'item', item: i }"><i class="bi bi-pencil"></i></button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </template>

  <section v-else class="card nx-card">
    <LoadingBlock v-if="!trx" />
    <EmptyState v-else-if="!trx.length" text="Belum ada transaksi." />
    <div v-else class="table-responsive">
      <table class="table nx-table align-middle mb-0">
        <thead><tr><th>Tanggal</th><th>Barang</th><th>Jenis</th><th class="text-end">Jumlah</th><th>Supplier / dipakai untuk</th><th class="text-end">Nilai</th><th>Oleh</th></tr></thead>
        <tbody>
          <tr v-for="t in trx" :key="t.id">
            <td class="text-nowrap">{{ tanggal(t.trx_date) }}</td>
            <td><div class="fw-semibold">{{ t.item_name }}</div><div class="small text-body-secondary">{{ t.item_code }}</div></td>
            <td><span class="nx-pill" :class="t.type === 'in' ? 'tone-good' : 'tone-warn'">{{ t.type === 'in' ? 'Masuk' : 'Keluar' }}</span></td>
            <td class="text-end text-nowrap">{{ t.type === 'in' ? '+' : '−' }}{{ t.qty }} {{ t.unit }}</td>
            <td>
              <template v-if="t.type === 'in'">{{ t.supplier || '–' }}</template>
              <router-link v-else-if="t.motor_id" :to="`/kendaraan/${t.motor_id}/sparepart`">{{ t.motor_code }} {{ t.motor_model }}</router-link>
              <template v-else>{{ t.note || 'Pemakaian umum' }}</template>
            </td>
            <td class="text-end text-nowrap">{{ rupiah(t.total) }}</td>
            <td class="text-body-secondary">{{ t.created_by_name || '–' }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>

  <ItemForm v-if="modal?.type === 'item'" :item="modal.item" :next-code="nextCode" @close="modal = null" @saved="saved" />
  <StockInForm v-if="modal?.type === 'in'" :item-id="modal.itemId || null" @close="modal = null" @saved="saved" />
  <StockOutForm v-if="modal?.type === 'out'" @close="modal = null" @saved="saved" />
</template>
