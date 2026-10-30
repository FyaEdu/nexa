<script setup>
import { computed } from 'vue'
import { healthClass, healthLabel } from '@/utils/health'

// Gauge setengah lingkaran untuk Asset Health Score
const props = defineProps({ score: { type: Number, default: null } })
const L = 157.08
const offset = computed(() => (L * (1 - (props.score ?? 0) / 100)).toFixed(2))
</script>

<template>
  <div class="text-center">
    <svg viewBox="0 0 120 70" class="nx-gauge" :class="healthClass(score)" role="img" :aria-label="`Asset Health Score ${score ?? 'belum ada'}`">
      <path d="M10 62 A50 50 0 0 1 110 62" class="g-track" />
      <path v-if="score != null" d="M10 62 A50 50 0 0 1 110 62" class="g-val" :style="{ strokeDasharray: L, strokeDashoffset: offset }" />
      <text x="60" y="58" text-anchor="middle" class="g-num">{{ score ?? '–' }}</text>
    </svg>
    <div class="nx-hs fw-bold" :class="healthClass(score)">{{ healthLabel(score) }}</div>
  </div>
</template>
