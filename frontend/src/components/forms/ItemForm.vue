<script setup>
import { reactive, ref } from 'vue'
import { api, formErrors } from '@/api'
import { toast } from '@/utils/feedback'
import { UNITS } from '@/utils/constants'
import BaseModal from '@/components/BaseModal.vue'
import FormField from '@/components/FormField.vue'

// Barang baru (item = null) atau edit barang. Stok hanya bisa diubah lewat barang masuk/keluar.
const props = defineProps({ item: { type: Object, default: null }, nextCode: { type: String, default: '' } })
const emit = defineEmits(['close', 'saved'])

const it = props.item || {}
const f = reactive({
  code: it.code || props.nextCode, name: it.name || '', unit: it.unit || 'pcs', unit_price: it.unit_price ?? '',
  min_stock: it.min_stock ?? 2, location: it.location || '', stock: 0
})
const errors = ref({})
const error = ref('')
const saving = ref(false)

async function submit() {
  saving.value = true
  errors.value = {}
  error.value = ''
  try {
    const r = props.item ? await api.put(`/inventory/${props.item.id}`, f) : await api.post('/inventory', f)
    toast(props.item ? 'Barang disimpan.' : 'Barang ditambahkan ke inventaris.')
    emit('saved', r)
  } catch (e) {
    ({ fields: errors.value, message: error.value } = formErrors(e))
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <BaseModal :title="item ? 'Edit barang' : 'Barang baru'" @close="emit('close')">
    <form class="row g-3" @submit.prevent="submit">
      <div class="col-sm-4"><FormField label="Kode" :error="errors.code"><input v-model="f.code" class="form-control" required /></FormField></div>
      <div class="col-sm-8"><FormField label="Nama barang" :error="errors.name"><input v-model="f.name" class="form-control" required /></FormField></div>
      <div class="col-sm-4">
        <FormField label="Satuan" :error="errors.unit">
          <select v-model="f.unit" class="form-select"><option v-for="u in UNITS" :key="u">{{ u }}</option></select>
        </FormField>
      </div>
      <div class="col-sm-8"><FormField label="Harga satuan (Rp)" :error="errors.unit_price"><input v-model="f.unit_price" type="number" min="0" class="form-control" required /></FormField></div>
      <div v-if="!item" class="col-sm-4"><FormField label="Stok awal" :error="errors.stock"><input v-model="f.stock" type="number" min="0" class="form-control" /></FormField></div>
      <div class="col-sm-4"><FormField label="Stok minimum" :error="errors.min_stock"><input v-model="f.min_stock" type="number" min="0" class="form-control" required /></FormField></div>
      <div class="col-sm-4"><FormField label="Lokasi rak" :error="errors.location"><input v-model="f.location" class="form-control" placeholder="Rak A1" /></FormField></div>
      <div v-if="error" class="col-12"><div class="alert alert-danger py-2 mb-0">{{ error }}</div></div>
      <div class="col-12 d-flex justify-content-end gap-2">
        <button type="button" class="btn btn-light" @click="emit('close')">Batal</button>
        <button class="btn btn-primary" :disabled="saving">{{ item ? 'Simpan perubahan' : 'Simpan barang' }}</button>
      </div>
    </form>
  </BaseModal>
</template>
