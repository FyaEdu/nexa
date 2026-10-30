<script setup>
import { computed } from 'vue'
import { CHECKLIST } from '@/utils/constants'
import { fullItems, groupScore, healthClass } from '@/utils/health'

// Nilai per grup checklist (mesin, kelistrikan, rem & body, dokumen)
const props = defineProps({ items: { type: Object, required: true } })
const rows = computed(() => {
  const full = fullItems(props.items)
  return CHECKLIST.map((g) => ({ key: g.key, label: g.label, score: groupScore(g, full) }))
})
</script>

<template>
  <div class="d-grid gap-2">
    <div v-for="r in rows" :key="r.key" class="nx-gbar" :class="healthClass(r.score)">
      <span>{{ r.label }}</span><b>{{ r.score }}</b>
      <div class="tr"><i :style="{ width: r.score + '%' }"></i></div>
    </div>
  </div>
</template>
