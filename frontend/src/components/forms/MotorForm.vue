<script setup>
import { reactive, ref } from 'vue'
import { api, formErrors } from '@/api'
import { toast } from '@/utils/feedback'
import { today } from '@/utils/format'
import BaseModal from '@/components/BaseModal.vue'
import FormField from '@/components/FormField.vue'

// Tambah motor masuk (motor = null) atau edit data motor
const props = defineProps({ motor: { type: Object, default: null } })
const emit = defineEmits(['close', 'saved'])

const m = props.motor || {}
const f = reactive({
  brand: m.brand || '', model: m.model || '', year: m.year || '', plate_number: m.plate_number || '',
  color: m.color || '', purchase_source: m.purchase_source || '', frame_number: m.frame_number || '',
  engine_number: m.engine_number || '', purchase_date: m.purchase_date || today(),
  purchase_price: m.purchase_price || '', target_price: m.target_price || '', condition_note: m.condition_note || ''
})
const errors = ref({})
const error = ref('')
const saving = ref(false)

async function submit() {
  saving.value = true
  errors.value = {}
  error.value = ''
  try {
    const r = props.motor ? await api.put(`/motors/${props.motor.id}`, f) : await api.post('/motors', f)
    toast(props.motor ? 'Data motor disimpan.' : `Motor dicatat dengan kode ${r.code}.`)
    emit('saved', r)
  } catch (e) {
    ({ fields: errors.value, message: error.value } = formErrors(e))
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <BaseModal :title="motor ? 'Edit data motor' : 'Tambah motor masuk'" wide @close="emit('close')">
    <form class="row g-3" @submit.prevent="submit">
      <div class="col-sm-6"><FormField label="Merek" :error="errors.brand"><input v-model="f.brand" class="form-control" list="dl-brand" placeholder="Honda" required /></FormField></div>
      <div class="col-sm-6"><FormField label="Tipe" :error="errors.model"><input v-model="f.model" class="form-control" placeholder="Vario 150" required /></FormField></div>
      <div class="col-sm-6"><FormField label="Tahun" :error="errors.year"><input v-model="f.year" type="number" min="1950" class="form-control" required /></FormField></div>
      <div class="col-sm-6"><FormField label="Nomor polisi" :error="errors.plate_number"><input v-model="f.plate_number" class="form-control" placeholder="B 1234 XYZ" required /></FormField></div>
      <div class="col-sm-6"><FormField label="Warna" :error="errors.color"><input v-model="f.color" class="form-control" /></FormField></div>
      <div class="col-sm-6"><FormField label="Asal pembelian" :error="errors.purchase_source"><input v-model="f.purchase_source" class="form-control" placeholder="Perorangan, Bekasi" /></FormField></div>
      <div class="col-sm-6"><FormField label="Nomor rangka" :error="errors.frame_number"><input v-model="f.frame_number" class="form-control" /></FormField></div>
      <div class="col-sm-6"><FormField label="Nomor mesin" :error="errors.engine_number"><input v-model="f.engine_number" class="form-control" /></FormField></div>
      <div class="col-sm-4"><FormField label="Tanggal beli" :error="errors.purchase_date"><input v-model="f.purchase_date" type="date" class="form-control" required /></FormField></div>
      <div class="col-sm-4"><FormField label="Harga beli (Rp)" :error="errors.purchase_price"><input v-model="f.purchase_price" type="number" min="1" class="form-control" required /></FormField></div>
      <div class="col-sm-4"><FormField label="Harga jual target (Rp)" :error="errors.target_price"><input v-model="f.target_price" type="number" min="0" class="form-control" /></FormField></div>
      <div class="col-12"><FormField label="Catatan kondisi awal" :error="errors.condition_note"><textarea v-model="f.condition_note" class="form-control" rows="2"></textarea></FormField></div>
      <div v-if="error" class="col-12"><div class="alert alert-danger py-2 mb-0">{{ error }}</div></div>
      <div class="col-12 d-flex justify-content-end gap-2">
        <button type="button" class="btn btn-light" @click="emit('close')">Batal</button>
        <button class="btn btn-primary" :disabled="saving">{{ motor ? 'Simpan perubahan' : 'Simpan motor' }}</button>
      </div>
    </form>
    <datalist id="dl-brand"><option v-for="b in ['Honda', 'Yamaha', 'Suzuki', 'Kawasaki', 'Vespa', 'TVS']" :key="b" :value="b"></option></datalist>
  </BaseModal>
</template>
