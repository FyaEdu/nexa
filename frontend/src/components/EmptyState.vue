<script setup>
defineProps({ text: { type: String, required: true } })
</script>

<template>
  <div class="text-center text-body-secondary py-5 px-3">
    <p class="mb-3">{{ text }}</p>
    <slot />
  </div>
</template>
