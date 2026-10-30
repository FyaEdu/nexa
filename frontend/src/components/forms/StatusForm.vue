<script setup>
import { computed, reactive, ref } from 'vue'
import { api, formErrors } from '@/api'
import { toast } from '@/utils/feedback'
import { MANUAL_STATUSES, statusLabel } from '@/utils/constants'
import { today } from '@/utils/format'
import BaseModal from '@/components/BaseModal.vue'
import FormField from '@/components/FormField.vue'

const props = defineProps({ motor: { type: Object, required: true } })
const emit = defineEmits(['close', 'saved'])

const choices = computed(() => MANUAL_STATUSES.filter((s) => s !== props.motor.status))
const next = MANUAL_STATUSES[MANUAL_STATUSES.indexOf(props.motor.status) + 1]
const f = reactive({ status: next || choices.value[0], date: today(), note: '' })
const errors = ref({})
const error = ref('')
const saving = ref(false)

async function submit() {
  saving.value = true
  errors.value = {}
  error.value = ''
  try {
    const r = await api.post(`/motors/${props.motor.id}/status`, f)
    toast(`Status diubah menjadi ${statusLabel(r.status)}.`)
    emit('saved', r)
  } catch (e) {
    ({ fields: errors.value, message: error.value } = formErrors(e))
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <BaseModal title="Ubah status motor" @close="emit('close')">
    <form class="row g-3" @submit.prevent="submit">
      <p class="col-12 text-body-secondary mb-0">
        Sekarang: <b>{{ statusLabel(motor.status) }}</b>. Status Booking dan Terjual diatur lewat menu Penjualan (Tahap 5).
      </p>
      <div class="col-sm-6">
        <FormField label="Status baru" :error="errors.status">
          <select v-model="f.status" class="form-select">
            <option v-for="s in choices" :key="s" :value="s">{{ statusLabel(s) }}</option>
          </select>
        </FormField>
      </div>
      <div class="col-sm-6"><FormField label="Tanggal" :error="errors.date"><input v-model="f.date" type="date" class="form-control" required /></FormField></div>
      <div class="col-12"><FormField label="Keterangan" hint="Opsional, tampil di Asset Timeline"><input v-model="f.note" class="form-control" /></FormField></div>
      <div v-if="error" class="col-12"><div class="alert alert-danger py-2 mb-0">{{ error }}</div></div>
      <div class="col-12 d-flex justify-content-end gap-2">
        <button type="button" class="btn btn-light" @click="emit('close')">Batal</button>
        <button class="btn btn-primary" :disabled="saving">Simpan status</button>
      </div>
    </form>
  </BaseModal>
</template>
