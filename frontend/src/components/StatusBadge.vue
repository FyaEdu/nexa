<script setup>
import { statusLabel } from '@/utils/constants'
defineProps({ status: { type: String, required: true } })
</script>

<template>
  <span class="nx-pill" :class="`st-${status}`">{{ statusLabel(status) }}</span>
</template>
