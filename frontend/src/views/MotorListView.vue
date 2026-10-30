<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api } from '@/api'
import { can } from '@/stores/auth'
import { STATUSES } from '@/utils/constants'
import { rupiah, tanggal } from '@/utils/format'
import StatusBadge from '@/components/StatusBadge.vue'
import HealthBadge from '@/components/HealthBadge.vue'
import EmptyState from '@/components/EmptyState.vue'
import LoadingBlock from '@/components/LoadingBlock.vue'
import MotorForm from '@/components/forms/MotorForm.vue'

const route = useRoute()
const router = useRouter()
const q = ref(route.query.q || '')
const status = ref(route.query.status || '')
const motors = ref(null)
const counts = ref({})
const error = ref('')
const showForm = ref(false)

let timer = null
async function load() {
  error.value = ''
  try {
    const params = new URLSearchParams()
    if (q.value.trim()) params.set('q', q.value.trim())
    if (status.value) params.set('status', status.value)
    const r = await api.get('/motors?' + params.toString())
    motors.value = r.motors
    counts.value = r.counts
  } catch (e) {
    error.value = e.message
  }
}
onMounted(load)
watch(status, () => { router.replace({ query: { ...route.query, status: status.value || undefined } }); load() })
watch(q, () => { clearTimeout(timer); timer = setTimeout(load, 250) })

const chips = computed(() => STATUSES.filter((s) => s.key in counts.value))
const total = computed(() => Object.values(counts.value).reduce((a, b) => a + b, 0))

function onSaved(m) {
  showForm.value = false
  router.push(`/kendaraan/${m.id}`)
}
</script>

<template>
  <div class="d-flex flex-wrap gap-2 align-items-center">
    <div class="nx-search flex-grow-1">
      <i class="bi bi-search"></i>
      <input v-model="q" type="search" class="form-control" placeholder="Cari merek, tipe, kode, atau nomor polisi" aria-label="Cari kendaraan" />
    </div>
    <button v-if="can('motor.edit')" class="btn btn-primary" @click="showForm = true"><i class="bi bi-plus-lg me-1"></i>Tambah motor</button>
  </div>

  <div class="d-flex flex-wrap gap-2">
    <button class="nx-chip" :class="{ on: !status }" @click="status = ''">Semua<span>{{ total }}</span></button>
    <button v-for="s in chips" :key="s.key" class="nx-chip" :class="{ on: status === s.key }" @click="status = s.key">{{ s.label }}<span>{{ counts[s.key] }}</span></button>
  </div>

  <div v-if="error" class="alert alert-danger">{{ error }}</div>
  <section v-else class="card nx-card">
    <LoadingBlock v-if="!motors" />
    <EmptyState v-else-if="!motors.length" :text="q || status ? 'Tidak ada motor yang cocok dengan pencarian atau filter ini.' : 'Belum ada motor. Catat motor pertama yang masuk.'" />
    <div v-else class="table-responsive">
      <table class="table nx-table table-hover align-middle mb-0">
        <thead>
          <tr><th>Motor</th><th>No. polisi</th><th>Status</th><th>Kondisi</th><th class="text-end">Total modal</th><th class="text-end">Harga jual/target</th><th class="text-end">Laba (est.)</th></tr>
        </thead>
        <tbody>
          <tr v-for="m in motors" :key="m.id" class="nx-click" tabindex="0" @click="router.push(`/kendaraan/${m.id}`)" @keydown.enter="router.push(`/kendaraan/${m.id}`)">
            <td><div class="fw-semibold">{{ m.brand }} {{ m.model }} {{ m.year }}</div><div class="small text-body-secondary">{{ m.code }}, masuk {{ tanggal(m.purchase_date) }}</div></td>
            <td><span class="nx-plate">{{ m.plate_number || '–' }}</span></td>
            <td><StatusBadge :status="m.status" /></td>
            <td><HealthBadge :score="m.health_score" /></td>
            <td class="text-end text-nowrap">{{ rupiah(m.total_modal) }}</td>
            <td class="text-end text-nowrap">{{ m.price ? rupiah(m.price) : '–' }}</td>
            <td class="text-end text-nowrap" :class="m.profit >= 0 ? 'text-success' : 'text-danger'">{{ m.profit != null ? rupiah(m.profit) : '–' }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>

  <MotorForm v-if="showForm" @close="showForm = false" @saved="onSaved" />
</template>
