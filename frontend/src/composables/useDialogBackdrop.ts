import { ref, type Ref } from 'vue'

/** Native dialog backdrops target the dialog itself; its padding must not dismiss it. */
export function useDialogBackdrop(dialog: Ref<HTMLDialogElement | undefined>, close: () => void) {
  const pressedOutside = ref(false)
  function outside(event: MouseEvent) {
    const element = dialog.value
    if (!element || event.target !== element) return false
    const bounds = element.getBoundingClientRect()
    return event.clientX < bounds.left || event.clientX > bounds.right
      || event.clientY < bounds.top || event.clientY > bounds.bottom
  }
  return {
    pointerdown: (event: PointerEvent) => { pressedOutside.value = event.button === 0 && outside(event) },
    pointercancel: () => { pressedOutside.value = false },
    click: (event: MouseEvent) => {
      if (pressedOutside.value && outside(event)) close()
      pressedOutside.value = false
    },
  }
}
