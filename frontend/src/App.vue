<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import AppLayout from '@/components/AppLayout.vue'
import FeedbackHost from '@/components/FeedbackHost.vue'

const route = useRoute()
const isPublic = computed(() => route.meta.public === true)
</script>

<template>
  <router-view v-if="isPublic" />
  <AppLayout v-else>
    <router-view :key="route.path.split('/').slice(0, 3).join('/')" />
  </AppLayout>
  <FeedbackHost />
</template>
