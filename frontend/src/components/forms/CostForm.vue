<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { api, formErrors } from '@/api'
import { loadMeta, meta } from '@/stores/meta'
import { toast } from '@/utils/feedback'
import { COST_SUGGESTIONS } from '@/utils/constants'
import { rupiah, today } from '@/utils/format'
import BaseModal from '@/components/BaseModal.vue'
import FormField from '@/components/FormField.vue'

// Catat biaya. Kalau motorId diisi, motor sudah terpilih dan tidak bisa diganti.
const props = defineProps({ motorId: { type: Number, default: null } })
const emit = defineEmits(['close', 'saved'])

const motors = ref([])
const f = reactive({ motor_id: props.motorId || '', category_id: '', item: '', amount: '', cost_date: today(), note: '' })
const errors = ref({})
const error = ref('')
const saving = ref(false)

const categoryName = computed(() => meta.cost_categories.find((c) => Number(c.id) === Number(f.category_id))?.name)
const suggestions = computed(() => COST_SUGGESTIONS[categoryName.value] || [])
watch(() => f.category_id, () => { f.item = '' })

onMounted(async () => {
  try {
    await loadMeta()
    if (!f.category_id && meta.cost_categories.length) f.category_id = meta.cost_categories[0].id
    if (!props.motorId) motors.value = (await api.get('/motors')).motors
  } catch (e) {
    error.value = e.message
  }
})

async function submit() {
  saving.value = true
  errors.value = {}
  error.value = ''
  try {
    const r = await api.post('/costs', { ...f, motor_id: Number(f.motor_id), category_id: Number(f.category_id), amount: Number(f.amount) })
    toast(`Biaya ${rupiah(r.amount)} dicatat dan masuk ke modal motor.`)
    emit('saved', r)
  } catch (e) {
    ({ fields: errors.value, message: error.value } = formErrors(e))
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <BaseModal title="Catat biaya" @close="emit('close')">
    <form class="row g-3" @submit.prevent="submit">
      <div v-if="!motorId" class="col-12">
        <FormField label="Motor" :error="errors.motor_id">
          <select v-model="f.motor_id" class="form-select" required>
            <option value="" disabled>Pilih motor</option>
            <option v-for="m in motors" :key="m.id" :value="m.id">{{ m.code }}, {{ m.brand }} {{ m.model }} {{ m.year }}, {{ m.plate_number }}</option>
          </select>
        </FormField>
      </div>
      <div class="col-sm-6">
        <FormField label="Kategori" :error="errors.category_id">
          <select v-model="f.category_id" class="form-select" required>
            <option v-for="c in meta.cost_categories" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
        </FormField>
      </div>
      <div class="col-sm-6"><FormField label="Tanggal" :error="errors.cost_date"><input v-model="f.cost_date" type="date" class="form-control" required /></FormField></div>
      <div class="col-12">
        <FormField label="Rincian biaya" :error="errors.item">
          <input v-model="f.item" class="form-control" list="dl-cost-item" placeholder="Pilih atau ketik rincian" required />
        </FormField>
        <datalist id="dl-cost-item"><option v-for="s in suggestions" :key="s" :value="s"></option></datalist>
      </div>
      <div class="col-sm-6"><FormField label="Nominal (Rp)" :error="errors.amount"><input v-model="f.amount" type="number" min="1" class="form-control" required /></FormField></div>
      <div class="col-sm-6"><FormField label="Keterangan" :error="errors.note"><input v-model="f.note" class="form-control" placeholder="Opsional" /></FormField></div>
      <p class="col-12 small text-body-secondary mb-0">Biaya kecil seperti bensin dan uang makan juga dihitung sebagai modal motor.</p>
      <div v-if="error" class="col-12"><div class="alert alert-danger py-2 mb-0">{{ error }}</div></div>
      <div class="col-12 d-flex justify-content-end gap-2">
        <button type="button" class="btn btn-light" @click="emit('close')">Batal</button>
        <button class="btn btn-primary" :disabled="saving">Simpan biaya</button>
      </div>
    </form>
  </BaseModal>
</template>
