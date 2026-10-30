<script setup>
import { ACTIVITY_ICONS } from '@/utils/constants'
import { tanggal } from '@/utils/format'
import EmptyState from './EmptyState.vue'

// Asset Timeline. showMotor = tampilkan tautan motor (dipakai di dashboard).
defineProps({ items: { type: Array, required: true }, showMotor: Boolean })
</script>

<template>
  <EmptyState v-if="!items.length" text="Belum ada aktivitas." />
  <ol v-else class="nx-tl">
    <li v-for="a in items" :key="a.id">
      <span class="ti" :class="`ty-${a.type}`"><i class="bi" :class="ACTIVITY_ICONS[a.type] || 'bi-flag'"></i></span>
      <div>
        <div class="fw-semibold">{{ a.title }}</div>
        <div v-if="a.detail" class="small text-body-secondary">{{ a.detail }}</div>
        <div class="small text-body-secondary">
          {{ tanggal(a.activity_date) }}, oleh {{ a.created_by_name || 'Sistem' }}<template v-if="showMotor">,
            <router-link :to="`/kendaraan/${a.motor_id}`">{{ a.motor_code }} {{ a.model }}</router-link></template>
        </div>
      </div>
    </li>
  </ol>
</template>
