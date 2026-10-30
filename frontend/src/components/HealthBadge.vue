<script setup>
import { healthClass, healthLabel } from '@/utils/health'
defineProps({ score: { type: Number, default: null } })
</script>

<template>
  <span v-if="score == null" class="nx-hs none">Belum diperiksa</span>
  <span v-else class="nx-hs" :class="healthClass(score)"><i></i>{{ score }}<small>{{ healthLabel(score) }}</small></span>
</template>
