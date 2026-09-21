<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue'
const emit = defineEmits<{ close: [] }>()
const dialog = ref<HTMLDialogElement>()
let origin: HTMLElement | null = null
const pressedBackdrop = ref(false)
function onBackdrop(event: MouseEvent) {
  const element = dialog.value
  if (!element || event.target !== element) return false
  const bounds = element.getBoundingClientRect()
  return event.clientX < bounds.left || event.clientX > bounds.right
    || event.clientY < bounds.top || event.clientY > bounds.bottom
}
function click(event: MouseEvent) {
  if (pressedBackdrop.value && onBackdrop(event)) emit('close')
  pressedBackdrop.value = false
}
onMounted(() => { origin = document.activeElement as HTMLElement; dialog.value?.showModal() })
onBeforeUnmount(() => { dialog.value?.close(); origin?.focus({ preventScroll: true }) })
</script>
<template><dialog ref="dialog" class="path-warning" role="alertdialog" aria-labelledby="path-warning-title" aria-describedby="path-warning-description" @pointerdown="pressedBackdrop = onBackdrop($event)" @pointercancel="pressedBackdrop = false" @click="click" @cancel.prevent="emit('close')"><h2 id="path-warning-title">Kein Entwicklungspfad</h2><p id="path-warning-description">Für diesen Skill liegt kein geschätzter Entwicklungspfad vor.</p><button autofocus @click="emit('close')">OK</button></dialog></template>
