<script setup>
import { computed } from 'vue'
import { STATUSES } from '@/utils/constants'
import { tanggal } from '@/utils/format'

// Jalur 6 status lifecycle. Tanggal diambil dari aktivitas pertama yang berisi status tersebut.
const props = defineProps({ status: { type: String, required: true }, activities: { type: Array, default: () => [] } })
const steps = computed(() => {
  const ci = STATUSES.findIndex((s) => s.key === props.status)
  const reached = {}
  props.activities.forEach((a) => { if (a.status && !reached[a.status]) reached[a.status] = a.activity_date })
  return STATUSES.map((s, i) => ({ ...s, cls: i < ci ? 'done' : i === ci ? 'cur' : 'todo', date: i <= ci ? reached[s.key] : null }))
})
</script>

<template>
  <div class="nx-road-wrap">
    <ol class="nx-road" aria-label="Tahapan siklus motor">
      <li v-for="s in steps" :key="s.key" :class="s.cls" :aria-current="s.cls === 'cur' ? 'step' : undefined">
        <span class="dot"></span>
        <b>{{ s.label }}</b>
        <small>{{ s.date ? tanggal(s.date) : '\u00a0' }}</small>
      </li>
    </ol>
  </div>
</template>
