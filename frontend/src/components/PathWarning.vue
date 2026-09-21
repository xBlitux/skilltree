<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { useDialogBackdrop } from '../composables/useDialogBackdrop'
const emit = defineEmits<{ close: [] }>()
const dialog = ref<HTMLDialogElement>()
let origin: HTMLElement | null = null
const backdrop = useDialogBackdrop(dialog, () => emit('close'))
onMounted(() => { origin = document.activeElement as HTMLElement; dialog.value?.showModal() })
onBeforeUnmount(() => { dialog.value?.close(); origin?.focus({ preventScroll: true }) })
</script>
<template><dialog ref="dialog" class="path-warning" role="alertdialog" aria-labelledby="path-warning-title" aria-describedby="path-warning-description" v-on="backdrop" @cancel.prevent="emit('close')"><h2 id="path-warning-title">Kein Entwicklungspfad</h2><p id="path-warning-description">Für diesen Skill liegt kein geschätzter Entwicklungspfad vor.</p><button autofocus @click="emit('close')">OK</button></dialog></template>
