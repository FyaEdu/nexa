<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '@/api'
import { statusLabel } from '@/utils/constants'
import { tanggal } from '@/utils/format'
import HealthBadge from '@/components/HealthBadge.vue'
import EmptyState from '@/components/EmptyState.vue'
import LoadingBlock from '@/components/LoadingBlock.vue'

const router = useRouter()
const data = ref(null)
const error = ref('')

onMounted(async () => {
  try {
    data.value = await api.get('/inspections')
  } catch (e) {
    error.value = e.message
  }
})
</script>

<template>
  <div class="d-flex flex-wrap gap-2 align-items-center">
    <p class="flex-grow-1 mb-0 text-body-secondary">Checklist inspeksi menghitung Asset Health Score secara otomatis.</p>
    <router-link to="/inspeksi/baru" class="btn btn-primary"><i class="bi bi-clipboard-check me-1"></i>Inspeksi baru</router-link>
  </div>

  <div v-if="error" class="alert alert-danger">{{ error }}</div>
  <LoadingBlock v-else-if="!data" />
  <template v-else>
    <section v-if="data.waiting.length" class="card nx-card">
      <div class="card-header d-flex justify-content-between align-items-center"><h2 class="h5 mb-0">Menunggu inspeksi</h2><span class="nx-pill tone-warn">{{ data.waiting.length }}</span></div>
      <ul class="list-group list-group-flush">
        <li v-for="m in data.waiting" :key="m.id" class="list-group-item d-flex flex-wrap gap-2 align-items-center">
          <span class="flex-grow-1"><b>{{ m.brand }} {{ m.model }} {{ m.year }}</b> <span class="text-body-secondary small">{{ m.code }}, {{ m.plate_number }}, {{ statusLabel(m.status) }}, masuk {{ tanggal(m.purchase_date) }}</span></span>
          <router-link :to="`/inspeksi/baru/${m.id}`" class="btn btn-sm btn-light border">Mulai inspeksi</router-link>
        </li>
      </ul>
    </section>

    <section class="card nx-card">
      <div class="card-header"><h2 class="h5 mb-0">Riwayat inspeksi</h2></div>
      <EmptyState v-if="!data.inspections.length" text="Belum ada inspeksi." />
      <div v-else class="table-responsive">
        <table class="table nx-table table-hover align-middle mb-0">
          <thead><tr><th>Tanggal</th><th>Motor</th><th>Pemeriksa</th><th>Temuan</th><th>Health Score</th></tr></thead>
          <tbody>
            <tr v-for="i in data.inspections" :key="i.id" class="nx-click" @click="router.push(`/kendaraan/${i.motor_id}/inspeksi`)">
              <td class="text-nowrap">{{ tanggal(i.inspection_date) }}</td>
              <td><div class="fw-semibold">{{ i.brand }} {{ i.model }} {{ i.year }}</div><div class="small text-body-secondary">{{ i.motor_code }}</div></td>
              <td>{{ i.inspector }}</td>
              <td>{{ i.findings ? `${i.findings} item perlu tindakan` : 'Semua baik' }}</td>
              <td><HealthBadge :score="i.score" /></td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </template>
</template>
