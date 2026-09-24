<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { useDialogBackdrop } from '../composables/useDialogBackdrop'
withDefaults(defineProps<{ heading?: string; message?: string; buttonLabel?: string; dialogId?: string }>(), {
  heading: 'Kein Entwicklungspfad',
  message: 'Für diesen Skill liegt kein geschätzter Entwicklungspfad vor.',
  buttonLabel: 'OK',
  dialogId: 'path-warning',
})
const emit = defineEmits<{ close: [] }>()
const dialog = ref<HTMLDialogElement>()
let origin: HTMLElement | null = null
const backdrop = useDialogBackdrop(dialog, () => emit('close'))
onMounted(() => { origin = document.activeElement as HTMLElement; dialog.value?.showModal() })
onBeforeUnmount(() => { dialog.value?.close(); origin?.focus({ preventScroll: true }) })
</script>
<template><dialog ref="dialog" class="path-warning" role="alertdialog" :aria-labelledby="`${dialogId}-title`" :aria-describedby="`${dialogId}-description`" v-on="backdrop" @cancel.prevent="emit('close')"><h2 :id="`${dialogId}-title`">{{ heading }}</h2><p :id="`${dialogId}-description`">{{ message }}</p><button autofocus @click="emit('close')">{{ buttonLabel }}</button></dialog></template>
