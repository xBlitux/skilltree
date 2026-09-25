<script setup lang="ts">
import { computed, ref, onMounted, onBeforeUnmount } from 'vue'
import type { Simulation } from '../domain/simulation'
import { useDialogBackdrop } from '../composables/useDialogBackdrop'

const props = defineProps<{ target: number; skillName: string; organisationName: string; simulation: Simulation }>()
const emit = defineEmits<{ close: [] }>()
const tasks = computed(() => props.simulation.tasks.filter(task => task.required_skill_ids.includes(props.target)))
const dialog = ref<HTMLDialogElement>()
let origin: HTMLElement | null = null
const backdrop = useDialogBackdrop(dialog, () => emit('close'))
onMounted(() => { origin = document.activeElement as HTMLElement; dialog.value?.showModal() })
onBeforeUnmount(() => { dialog.value?.close(); origin?.focus({ preventScroll: true }) })
</script>

<template>
  <dialog ref="dialog" class="path-warning task-assignments" aria-labelledby="task-assignments-title" aria-describedby="task-assignments-context" v-on="backdrop" @cancel.prevent="emit('close')">
    <h2 id="task-assignments-title">Aufgabenzuordnung</h2>
    <div class="task-assignment-content" tabindex="0" role="region" aria-label="Zugeordnete Aufgaben">
    <p id="task-assignments-context"><strong>{{ skillName }}</strong><br>Direkt oder implizit zugeordnete Aufgaben · {{ organisationName }}</p>
    <ul v-if="tasks.length" class="task-assignment-list">
      <li v-for="task in tasks" :key="task.id">Aufgabe {{ task.id }} • {{ task.name?.trim() || 'kein Aufgabenname hinterlegt' }}<span v-if="simulation.excluded_task_ids.includes(task.id)" class="task-exclusion">in Simulation ausgeschlossen</span></li>
    </ul>
    <p v-else>Diesem Skill sind in der ausgewählten Organisationseinheit keine Aufgaben direkt oder implizit zugeordnet.</p>
    </div>
    <button autofocus @click="emit('close')">Schließen</button>
  </dialog>
</template>
