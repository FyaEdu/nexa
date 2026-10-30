<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api, formErrors } from '@/api'
import { auth } from '@/stores/auth'
import { toast } from '@/utils/feedback'
import { CHECKLIST, RESULT_LABELS, statusLabel } from '@/utils/constants'
import { today } from '@/utils/format'
import { calcScore, fullItems } from '@/utils/health'
import HealthGauge from '@/components/HealthGauge.vue'
import GroupBars from '@/components/GroupBars.vue'
import FormField from '@/components/FormField.vue'
import LoadingBlock from '@/components/LoadingBlock.vue'

const route = useRoute()
const router = useRouter()
const motors = ref(null)
const f = reactive({
  motor_id: route.params.motorId ? Number(route.params.motorId) : '',
  inspection_date: today(),
  inspector: auth.user?.name || '',
  notes: '',
  to_repair: true,
  items: fullItems()
})
const errors = ref({})
const error = ref('')
const saving = ref(false)
const score = computed(() => calcScore(f.items))
const backTo = computed(() => (route.params.motorId ? `/kendaraan/${route.params.motorId}/inspeksi` : '/inspeksi'))

onMounted(async () => {
  try {
    motors.value = (await api.get('/motors')).motors.filter((m) => !['booking', 'terjual'].includes(m.status))
  } catch (e) {
    error.value = e.message
  }
})

const labelFor = (g, v) => (g.labels || RESULT_LABELS)[v]

async function submit() {
  saving.value = true
  errors.value = {}
  error.value = ''
  try {
    const r = await api.post('/inspections', { ...f, motor_id: Number(f.motor_id) })
    toast(`Inspeksi disimpan. Health Score ${r.score} (${r.label}). Status: ${statusLabel(r.motor_status)}.`)
    router.push(`/kendaraan/${f.motor_id}/inspeksi`)
  } catch (e) {
    ({ fields: errors.value, message: error.value } = formErrors(e))
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div><router-link :to="backTo" class="btn btn-sm btn-link px-0 text-decoration-none"><i class="bi bi-chevron-left"></i> Kembali</router-link></div>
  <LoadingBlock v-if="!motors && !error" />
  <form v-else class="nx-insp" @submit.prevent="submit">
    <section class="card nx-card">
      <div class="card-body row g-3 border-bottom">
        <div class="col-12">
          <FormField label="Motor yang diperiksa" :error="errors.motor_id">
            <select v-model="f.motor_id" class="form-select" required>
              <option value="" disabled>Pilih motor</option>
              <option v-for="m in motors" :key="m.id" :value="m.id">{{ m.code }}, {{ m.brand }} {{ m.model }} {{ m.year }}, {{ m.plate_number }} ({{ statusLabel(m.status) }})</option>
            </select>
          </FormField>
        </div>
        <div class="col-sm-6"><FormField label="Tanggal inspeksi" :error="errors.inspection_date"><input v-model="f.inspection_date" type="date" class="form-control" required /></FormField></div>
        <div class="col-sm-6"><FormField label="Pemeriksa / mekanik" :error="errors.inspector"><input v-model="f.inspector" class="form-control" required /></FormField></div>
      </div>

      <div v-for="g in CHECKLIST" :key="g.key" class="nx-cl-group">
        <h3 class="h6 d-flex justify-content-between align-items-baseline px-3 pt-3 mb-2">{{ g.label }} <small class="text-body-secondary fw-normal">bobot {{ g.weight }}%</small></h3>
        <div v-for="[k, label] in g.items" :key="k" class="d-flex flex-wrap justify-content-between align-items-center gap-2 px-3 py-2">
          <span :id="`lbl-${k}`">{{ label }}</span>
          <div class="nx-seg" role="radiogroup" :aria-labelledby="`lbl-${k}`">
            <label v-for="v in ['baik', 'perlu', 'rusak']" :key="v">
              <input v-model="f.items[k]" type="radio" :name="k" :value="v" />
              <span :data-v="v">{{ labelFor(g, v) }}</span>
            </label>
          </div>
        </div>
      </div>

      <div class="card-body border-top">
        <FormField label="Catatan pemeriksa"><textarea v-model="f.notes" class="form-control" rows="2" placeholder="Contoh: rembes oli di gasket, kampas rem depan tinggal 1 mm"></textarea></FormField>
      </div>
    </section>

    <aside class="card nx-card nx-insp-side">
      <div class="card-header"><h3 class="h6 mb-0">Asset Health Score</h3></div>
      <div class="card-body">
        <HealthGauge :score="score" />
        <div class="mt-3"><GroupBars :items="f.items" /></div>
      </div>
      <div class="card-body border-top">
        <label class="form-check mb-3">
          <input v-model="f.to_repair" type="checkbox" class="form-check-input" />
          <span class="form-check-label small">Pindahkan status ke Dalam perbaikan jika ada temuan</span>
        </label>
        <div v-if="error" class="alert alert-danger py-2">{{ error }}</div>
        <button class="btn btn-primary w-100" :disabled="saving"><i class="bi bi-check-lg me-1"></i>Simpan hasil inspeksi</button>
      </div>
    </aside>
  </form>
</template>
