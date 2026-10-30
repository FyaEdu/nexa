<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { api, formErrors } from '@/api'
import { toast } from '@/utils/feedback'
import { today } from '@/utils/format'
import BaseModal from '@/components/BaseModal.vue'
import FormField from '@/components/FormField.vue'

const props = defineProps({ itemId: { type: Number, default: null } })
const emit = defineEmits(['close', 'saved'])

const items = ref([])
const f = reactive({ item_id: props.itemId || '', qty: '', unit_price: '', supplier: '', trx_date: today(), note: '' })
const errors = ref({})
const error = ref('')
const saving = ref(false)
const selected = computed(() => items.value.find((i) => i.id === Number(f.item_id)))
watch(selected, (i) => { if (i && f.unit_price === '') f.unit_price = i.unit_price })

onMounted(async () => {
  try {
    items.value = (await api.get('/inventory')).items.sort((a, b) => a.code.localeCompare(b.code))
  } catch (e) {
    error.value = e.message
  }
})

async function submit() {
  saving.value = true
  errors.value = {}
  error.value = ''
  try {
    const r = await api.post('/inventory/in', { ...f, item_id: Number(f.item_id), qty: Number(f.qty), unit_price: f.unit_price === '' ? 0 : Number(f.unit_price) })
    toast(`Stok ${r.name} sekarang ${r.stock} ${r.unit}.`)
    emit('saved', r)
  } catch (e) {
    ({ fields: errors.value, message: error.value } = formErrors(e))
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <BaseModal title="Barang masuk dari supplier" @close="emit('close')">
    <form class="row g-3" @submit.prevent="submit">
      <div class="col-12">
        <FormField label="Barang" :error="errors.item_id">
          <select v-model="f.item_id" class="form-select" required>
            <option value="" disabled>Pilih barang</option>
            <option v-for="i in items" :key="i.id" :value="i.id">{{ i.code }}, {{ i.name }} (stok {{ i.stock }} {{ i.unit }})</option>
          </select>
        </FormField>
      </div>
      <div class="col-sm-6"><FormField label="Jumlah" :error="errors.qty"><input v-model="f.qty" type="number" min="1" class="form-control" required /></FormField></div>
      <div class="col-sm-6"><FormField label="Harga satuan (Rp)" :error="errors.unit_price" hint="Harga barang ikut diperbarui"><input v-model="f.unit_price" type="number" min="0" class="form-control" /></FormField></div>
      <div class="col-sm-6"><FormField label="Supplier" :error="errors.supplier"><input v-model="f.supplier" class="form-control" placeholder="Nama toko/supplier" required /></FormField></div>
      <div class="col-sm-6"><FormField label="Tanggal" :error="errors.trx_date"><input v-model="f.trx_date" type="date" class="form-control" required /></FormField></div>
      <div v-if="error" class="col-12"><div class="alert alert-danger py-2 mb-0">{{ error }}</div></div>
      <div class="col-12 d-flex justify-content-end gap-2">
        <button type="button" class="btn btn-light" @click="emit('close')">Batal</button>
        <button class="btn btn-primary" :disabled="saving">Simpan barang masuk</button>
      </div>
    </form>
  </BaseModal>
</template>
