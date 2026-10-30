<script setup>
import { onMounted, ref, watch } from 'vue'
import { api } from '@/api'
import { can } from '@/stores/auth'
import { loadMeta, meta } from '@/stores/meta'
import { confirmDialog, toast } from '@/utils/feedback'
import { rupiah, tanggal } from '@/utils/format'
import EmptyState from '@/components/EmptyState.vue'
import LoadingBlock from '@/components/LoadingBlock.vue'
import CostForm from '@/components/forms/CostForm.vue'

const motors = ref([])
const motorId = ref('')
const categoryId = ref('')
const data = ref(null)
const error = ref('')
const showForm = ref(false)

async function load() {
  error.value = ''
  try {
    const p = new URLSearchParams()
    if (motorId.value) p.set('motor_id', motorId.value)
    if (categoryId.value) p.set('category_id', categoryId.value)
    data.value = await api.get('/costs?' + p.toString())
  } catch (e) {
    error.value = e.message
  }
}
onMounted(async () => {
  try {
    await loadMeta()
    motors.value = (await api.get('/motors')).motors
  } catch (e) {
    error.value = e.message
  }
  load()
})
watch([motorId, categoryId], load)

async function remove(c) {
  if (!(await confirmDialog({ title: 'Hapus biaya?', text: `${c.item} sebesar ${rupiah(c.amount)} untuk ${c.motor_code} akan dihapus dan modal motor berkurang.`, yes: 'Hapus biaya' }))) return
  try { await api.del(`/costs/${c.id}`); toast('Biaya dihapus.'); load() } catch (e) { toast(e.message) }
}
function saved() {
  showForm.value = false
  load()
}
</script>

<template>
  <div v-if="data" class="nx-strip" :style="{ '--n': data.by_category.length }">
    <div v-for="c in data.by_category" :key="c.id"><div class="v money">{{ rupiah(c.total) }}</div><div class="l">{{ c.name }}</div></div>
  </div>

  <div class="d-flex flex-wrap gap-2 align-items-center">
    <select v-model="motorId" class="form-select w-auto" aria-label="Filter motor">
      <option value="">Semua motor</option>
      <option v-for="m in motors" :key="m.id" :value="m.id">{{ m.code }}, {{ m.brand }} {{ m.model }} {{ m.year }}</option>
    </select>
    <div class="d-flex flex-wrap gap-2 flex-grow-1">
      <button class="nx-chip" :class="{ on: !categoryId }" @click="categoryId = ''">Semua kategori</button>
      <button v-for="c in meta.cost_categories" :key="c.id" class="nx-chip" :class="{ on: Number(categoryId) === Number(c.id) }" @click="categoryId = c.id">{{ c.name }}</button>
    </div>
    <button v-if="can('cost.edit')" class="btn btn-primary" @click="showForm = true"><i class="bi bi-plus-lg me-1"></i>Catat biaya</button>
  </div>

  <div v-if="error" class="alert alert-danger">{{ error }}</div>
  <section v-else class="card nx-card">
    <LoadingBlock v-if="!data" />
    <EmptyState v-else-if="!data.costs.length" text="Belum ada biaya yang cocok dengan filter ini." />
    <div v-else class="table-responsive">
      <table class="table nx-table align-middle mb-0">
        <thead><tr><th>Tanggal</th><th>Motor</th><th>Kategori</th><th>Rincian</th><th>Dicatat oleh</th><th class="text-end">Nominal</th><th v-if="can('cost.edit')"></th></tr></thead>
        <tbody>
          <tr v-for="c in data.costs" :key="c.id">
            <td class="text-nowrap">{{ tanggal(c.cost_date) }}</td>
            <td><router-link :to="`/kendaraan/${c.motor_id}/biaya`">{{ c.motor_code }} {{ c.model }}</router-link></td>
            <td>{{ c.category_name }}</td>
            <td><div class="fw-semibold">{{ c.item }}</div><div v-if="c.note" class="small text-body-secondary">{{ c.note }}</div></td>
            <td class="text-body-secondary">{{ c.created_by_name }}</td>
            <td class="text-end text-nowrap">{{ rupiah(c.amount) }}</td>
            <td v-if="can('cost.edit')" class="text-end"><button class="btn btn-sm btn-light" title="Hapus biaya" aria-label="Hapus biaya" @click="remove(c)"><i class="bi bi-trash"></i></button></td>
          </tr>
        </tbody>
        <tfoot><tr><td colspan="5" class="fw-bold">Total</td><td class="text-end fw-bold text-nowrap">{{ rupiah(data.total) }}</td><td v-if="can('cost.edit')"></td></tr></tfoot>
      </table>
    </div>
  </section>

  <CostForm v-if="showForm" :motor-id="motorId ? Number(motorId) : null" @close="showForm = false" @saved="saved" />
</template>
