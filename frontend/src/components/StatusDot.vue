<script setup lang="ts">
import { labels, type Color } from '../domain/taxonomy'
defineProps<{ color: Color; personal?: boolean; tooltip?: string }>()
</script>
<template>
  <span class="status-dot" :class="color" role="img" :aria-label="personal ? (color === 'green' ? 'Vorhanden' : color === 'red' ? 'Nicht vorhanden' : 'Keine Skills') : labels[color]" :title="tooltip ?? (personal ? (color === 'green' ? 'Vorhanden' : color === 'red' ? 'Nicht vorhanden' : 'Keine Skills') : labels[color])"></span>
</template>
