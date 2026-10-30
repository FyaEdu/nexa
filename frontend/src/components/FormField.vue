<script setup>
// Pembungkus label + pesan error Bootstrap. Pakai: <FormField label="Merek" :error="errors.brand"><input ...></FormField>
defineProps({ label: { type: String, required: true }, error: { type: String, default: '' }, hint: { type: String, default: '' } })
</script>

<template>
  <label class="form-label w-100 mb-0">
    <span class="nx-label">{{ label }}</span>
    <div :class="{ 'nx-invalid': error }"><slot /></div>
    <small v-if="error" class="text-danger d-block mt-1">{{ error }}</small>
    <small v-else-if="hint" class="text-body-secondary d-block mt-1">{{ hint }}</small>
  </label>
</template>
