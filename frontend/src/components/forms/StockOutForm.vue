<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { api, formErrors } from '@/api'
import { toast } from '@/utils/feedback'
import { rupiah, today } from '@/utils/format'
import BaseModal from '@/components/BaseModal.vue'
import FormField from '@/components/FormField.vue'

// Barang keluar. Kalau motor dipilih, nilainya masuk ke modal motor dan tercatat di Asset Timeline.
const props = defineProps({ motorId: { type: Number, default: null }, itemId: { type: Number, default: null } })
const emit = defineEmits(['close', 'saved'])

const items = ref([])
const motors = ref([])
const f = reactive({ item_id: props.itemId || '', qty: 1, motor_id: props.motorId || '', trx_date: today(), note: '' })
const errors = ref({})
const error = ref('')
const saving = ref(false)

const selected = computed(() => items.value.find((i) => i.id === Number(f.item_id)))

onMounted(async () => {
  try {
    items.value = (await api.get('/inventory')).items.filter((i) => i.stock > 0).sort((a, b) => a.code.localeCompare(b.code))
    if (!props.motorId) motors.value = (await api.get('/motors')).motors.filter((m) => m.status !== 'terjual')
  } catch (e) {
    error.value = e.message
  }
})

async function submit() {
  saving.value = true
  errors.value = {}
  error.value = ''
  try {
    const r = await api.post('/inventory/out', { ...f, item_id: Number(f.item_id), qty: Number(f.qty), motor_id: f.motor_id ? Number(f.motor_id) : null })
    toast(r.low_stock ? `Tercatat. Stok ${r.item.name} sekarang di batas minimum.` : 'Barang keluar dicatat.')
    emit('saved', r)
  } catch (e) {
    ({ fields: errors.value, message: error.value } = formErrors(e))
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <BaseModal :title="motorId ? 'Pakai sparepart untuk motor ini' : 'Barang keluar'" @close="emit('close')">
    <form class="row g-3" @submit.prevent="submit">
      <div class="col-12">
        <FormField label="Barang" :error="errors.item_id">
          <select v-model="f.item_id" class="form-select" required>
            <option value="" disabled>Pilih barang</option>
            <option v-for="i in items" :key="i.id" :value="i.id">{{ i.code }}, {{ i.name }} (stok {{ i.stock }} {{ i.unit }}, {{ rupiah(i.unit_price) }})</option>
          </select>
        </FormField>
      </div>
      <div class="col-sm-6">
        <FormField label="Jumlah" :error="errors.qty" :hint="selected ? `Tersedia ${selected.stock} ${selected.unit}` : ''">
          <input v-model="f.qty" type="number" min="1" class="form-control" required />
        </FormField>
      </div>
      <div class="col-sm-6"><FormField label="Tanggal" :error="errors.trx_date"><input v-model="f.trx_date" type="date" class="form-control" required /></FormField></div>
      <div v-if="!motorId" class="col-12">
        <FormField label="Dipakai untuk motor" :error="errors.motor_id">
          <select v-model="f.motor_id" class="form-select">
            <option value="">Pemakaian umum (tanpa motor)</option>
            <option v-for="m in motors" :key="m.id" :value="m.id">{{ m.code }}, {{ m.brand }} {{ m.model }} {{ m.year }}</option>
          </select>
        </FormField>
      </div>
      <div class="col-12"><FormField label="Keterangan" :error="errors.note"><input v-model="f.note" class="form-control" placeholder="Opsional" /></FormField></div>
      <p v-if="selected" class="col-12 small text-body-secondary mb-0">
        Nilai: {{ rupiah(selected.unit_price * (Number(f.qty) || 0)) }}<template v-if="f.motor_id">, otomatis ditambahkan ke modal motor.</template>
      </p>
      <div v-if="error" class="col-12"><div class="alert alert-danger py-2 mb-0">{{ error }}</div></div>
      <div class="col-12 d-flex justify-content-end gap-2">
        <button type="button" class="btn btn-light" @click="emit('close')">Batal</button>
        <button class="btn btn-primary" :disabled="saving">Simpan</button>
      </div>
    </form>
  </BaseModal>
</template>
