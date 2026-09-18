<script setup lang="ts">
import { onMounted, ref } from 'vue'

import { healthEndpoint } from './api/client'

type HealthState = 'loading' | 'ready' | 'error'

const healthState = ref<HealthState>('loading')

onMounted(async () => {
  try {
    const response = await fetch(healthEndpoint)
    healthState.value = response.ok ? 'ready' : 'error'
  } catch {
    healthState.value = 'error'
  }
})
</script>

<template>
  <main>
    <h1>Skillbasierte Personalentwicklung</h1>
    <p>Der technische Projektrahmen ist eingerichtet.</p>
    <p class="status" :data-state="healthState">
      API-Status: {{ healthState }}
    </p>
  </main>
</template>
