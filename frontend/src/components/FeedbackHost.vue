<script setup>
import { confirmState, closeConfirm, toastState } from '@/utils/feedback'
import BaseModal from './BaseModal.vue'
</script>

<template>
  <div class="nx-toast" role="status" aria-live="polite">
    <div v-if="toastState.message">{{ toastState.message }}</div>
  </div>
  <BaseModal v-if="confirmState.open" :title="confirmState.title" @close="closeConfirm(false)">
    <p>{{ confirmState.text }}</p>
    <div class="d-flex justify-content-end gap-2">
      <button type="button" class="btn btn-light" @click="closeConfirm(false)">Batal</button>
      <button type="button" class="btn btn-danger" @click="closeConfirm(true)">{{ confirmState.yes }}</button>
    </div>
  </BaseModal>
</template>
