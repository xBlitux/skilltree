<script setup lang="ts">
defineProps<{ name: 'organisation' | 'person' | 'simulation' }>()
</script>
<template>
  <svg viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
    <template v-if="name === 'organisation'"><path d="M5 28V15h10M15 28V4h12v24M2 28h28M20 9h2M20 14h2M20 19h2M9 20h2M9 25h2" /></template>
    <template v-else-if="name === 'person'"><circle cx="16" cy="9" r="5"/><path d="M6 29v-4a10 10 0 0 1 20 0v4"/></template>
    <template v-else><path d="m16 3 12 7v13l-12 7-12-7V10zM4 10l12 7 12-7M16 17v13M16 3v13"/></template>
  </svg>
</template>
