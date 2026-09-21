<script setup lang="ts">
import { ref, watch, onMounted, onBeforeUnmount } from 'vue'
import type { Simulation, SimulationFilters } from '../domain/simulation'
import { useDialogBackdrop } from '../composables/useDialogBackdrop'
const props = defineProps<{ simulation: Simulation; busy: boolean; error: string }>()
const emit = defineEmits<{ toggle: [kind: 'task' | 'employee', id: number]; change: [filters: SimulationFilters]; close: []; retry: [] }>()
const dialog = ref<HTMLDialogElement>()
const backdrop = useDialogBackdrop(dialog, () => emit('close'))
const includedTasks = ref<number[]>([])
const includedEmployees = ref<number[]>([])
// Native checkbox state is optimistic while pending; always reconcile on success/failure.
watch(() => [props.simulation, props.busy], () => {
  if (props.busy) return
  includedTasks.value = props.simulation.tasks.map(task => task.id).filter(id => !props.simulation.excluded_task_ids.includes(id))
  includedEmployees.value = props.simulation.employees.map(employee => employee.id).filter(id => !props.simulation.excluded_employee_ids.includes(id))
}, { immediate: true })
let origin: HTMLElement | null = null
onMounted(() => {
  origin = document.activeElement instanceof HTMLElement ? document.activeElement : null
  dialog.value?.showModal()
})
onBeforeUnmount(() => { dialog.value?.close(); origin?.focus({ preventScroll: true }) })
</script>
<template>
  <dialog ref="dialog" class="simulation-dialog" aria-labelledby="simulation-title" v-on="backdrop" @cancel.prevent="emit('close')">
    <div class="simulation-heading"><h2 id="simulation-title">Ausfall simulieren</h2><button autofocus aria-label="Simulation schließen" @click="emit('close')">×</button></div>
    <p>Häkchen = eingeschlossen. Änderungen wirken sofort; Stammdaten bleiben unverändert.</p>
    <p v-if="busy" role="status">Simulation wird berechnet …</p>
    <p v-if="error" class="notice" role="alert">{{ error }} Der letzte erfolgreich berechnete Stand bleibt erhalten. <button @click="emit('retry')">Erneut versuchen</button></p>
    <fieldset :disabled="busy" class="simulation-fields">
      <div class="scenario-buttons"><button v-for="(scenario, index) in (simulation.scenarios ?? []).slice(0, 9)" :key="scenario.id" :title="`Szenario ${scenario.name} aktivieren`" :aria-label="`Szenario ${scenario.name} aktivieren`" @click="emit('change', { ...simulation, excluded_task_ids: simulation.tasks.filter(task => !scenario.task_ids.includes(task.id)).map(task => task.id) })">{{ index + 1 }}</button></div>
      <div><h3>Aufgaben</h3>
        <div class="simulation-actions"><button @click="emit('change', { ...simulation, excluded_task_ids: [] })">Alle Aufgaben einschließen</button><button @click="emit('change', { ...simulation, excluded_task_ids: simulation.tasks.map(task => task.id) })">Alle Aufgaben ausschließen</button></div>
        <p v-if="!simulation.tasks.length">Leere Liste</p>
        <label v-for="task in simulation.tasks" :key="task.id"><input v-model="includedTasks" type="checkbox" :value="task.id" @change="emit('toggle', 'task', task.id)"/>{{ task.name }}</label>
      </div>
      <div><h3>Mitarbeitende</h3>
        <div class="simulation-actions"><button @click="emit('change', { ...simulation, excluded_employee_ids: [] })">Alle Mitarbeitenden einschließen</button><button @click="emit('change', { ...simulation, excluded_employee_ids: simulation.employees.map(employee => employee.id) })">Alle Mitarbeitenden ausschließen</button></div>
        <p v-if="!simulation.employees.length">Leere Liste</p>
        <label v-for="employee in simulation.employees" :key="employee.id"><input v-model="includedEmployees" type="checkbox" :value="employee.id" @change="emit('toggle', 'employee', employee.id)"/>{{ employee.first_name }} {{ employee.last_name }}</label>
      </div>
    </fieldset>
    <p class="view-hint">Schließen erhält die Filter. Start stellt den Ausgangszustand wieder her.</p>
    <button @click="emit('close')">Schließen</button>
  </dialog>
</template>
