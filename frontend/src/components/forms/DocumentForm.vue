<script setup>
import { reactive, ref } from 'vue'
import { api, formErrors } from '@/api'
import { toast } from '@/utils/feedback'
import { DOC_STATUSES, DOC_TYPES } from '@/utils/constants'
import BaseModal from '@/components/BaseModal.vue'
import FormField from '@/components/FormField.vue'

const props = defineProps({ motorId: { type: Number, required: true }, doc: { type: Object, default: null } })
const emit = defineEmits(['close', 'saved'])

const d = props.doc || {}
const f = reactive({ doc_type: d.doc_type || 'STNK', status: d.status || 'Lengkap', doc_number: d.doc_number || '', expiry_date: d.expiry_date || '', note: d.note || '' })
const errors = ref({})
const error = ref('')
const saving = ref(false)

async function submit() {
  saving.value = true
  errors.value = {}
  error.value = ''
  try {
    const r = props.doc ? await api.put(`/documents/${props.doc.id}`, f) : await api.post(`/motors/${props.motorId}/documents`, f)
    toast('Dokumen disimpan.')
    emit('saved', r)
  } catch (e) {
    ({ fields: errors.value, message: error.value } = formErrors(e))
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <BaseModal :title="doc ? 'Edit dokumen' : 'Tambah dokumen'" @close="emit('close')">
    <form class="row g-3" @submit.prevent="submit">
      <div class="col-sm-6">
        <FormField label="Jenis dokumen" :error="errors.doc_type">
          <select v-model="f.doc_type" class="form-select"><option v-for="t in DOC_TYPES" :key="t">{{ t }}</option></select>
        </FormField>
      </div>
      <div class="col-sm-6">
        <FormField label="Status" :error="errors.status">
          <select v-model="f.status" class="form-select"><option v-for="s in DOC_STATUSES" :key="s">{{ s }}</option></select>
        </FormField>
      </div>
      <div class="col-sm-6"><FormField label="Nomor dokumen" :error="errors.doc_number"><input v-model="f.doc_number" class="form-control" /></FormField></div>
      <div class="col-sm-6"><FormField label="Berlaku sampai" :error="errors.expiry_date" hint="Kosongkan kalau tidak ada"><input v-model="f.expiry_date" type="date" class="form-control" /></FormField></div>
      <div class="col-12"><FormField label="Catatan" :error="errors.note"><input v-model="f.note" class="form-control" /></FormField></div>
      <div v-if="error" class="col-12"><div class="alert alert-danger py-2 mb-0">{{ error }}</div></div>
      <div class="col-12 d-flex justify-content-end gap-2">
        <button type="button" class="btn btn-light" @click="emit('close')">Batal</button>
        <button class="btn btn-primary" :disabled="saving">Simpan dokumen</button>
      </div>
    </form>
  </BaseModal>
</template>
