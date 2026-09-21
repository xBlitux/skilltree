<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import cytoscape, { type Core } from 'cytoscape'
import { developmentPath } from '../domain/development'
import type { Analysis } from '../domain/taxonomy'

const props = defineProps<{ data: Analysis; target: number; person: number | null; full: boolean }>()
const emit = defineEmits<{ person: [id: number | null]; full: [full: boolean] }>()
const canvas = ref<HTMLElement>()
const path = computed(() => developmentPath(props.data, props.target, props.person, props.full))
let graph: Core | undefined
let resize: ResizeObserver | undefined
function render() {
  if (!canvas.value || !path.value) return
  graph?.destroy()
  graph = cytoscape({
    container: canvas.value,
    elements: [
      ...path.value.nodes.map(node => ({ data: { id: String(node.id), label: node.name, color: node.owned === null ? '#f7f7f7' : node.owned ? '#e1f1e8' : '#f9e4e5', border: node.owned === null ? '#777777' : node.owned ? '#26704a' : '#b43138' }, position: { x: node.x, y: node.y }, classes: node.target ? 'target' : '' })),
      ...path.value.edges.map(edge => ({ data: { id: `${edge.source}-${edge.target}`, source: String(edge.source), target: String(edge.target) } })),
    ],
    style: [
      { selector: 'node', style: { label: 'data(label)', 'background-color': 'data(color)', 'border-color': 'data(border)', 'border-width': 2, shape: 'round-rectangle', width: 180, height: 54, color: '#262626', 'font-family': 'Roboto, Arial, sans-serif', 'font-size': 13, 'text-wrap': 'wrap', 'text-max-width': '160px', 'text-valign': 'center', 'text-halign': 'center' } },
      { selector: 'node.target', style: { 'border-width': 4, 'font-weight': 'bold' } },
      { selector: 'edge', style: { width: 2, 'line-color': '#999999', 'target-arrow-color': '#999999', 'target-arrow-shape': 'triangle', 'curve-style': 'bezier' } },
    ],
    layout: { name: 'preset', fit: true, padding: 35 },
    minZoom: .1, maxZoom: 3, autoungrabify: true, boxSelectionEnabled: false,
  })
  // Avoid a single node being enlarged to the full canvas.
  if (graph.zoom() > 1) { graph.zoom(1); graph.center() }
}
function reset() { graph?.fit(undefined, 35); if (graph && graph.zoom() > 1) { graph.zoom(1); graph.center() } }
function zoom(factor: number) { if (graph) graph.zoom({ level: graph.zoom() * factor, renderedPosition: { x: graph.width() / 2, y: graph.height() / 2 } }) }
function keyboard(event: KeyboardEvent) {
  if (!graph) return
  const moves: Record<string, { x: number; y: number }> = { ArrowLeft: { x: 40, y: 0 }, ArrowRight: { x: -40, y: 0 }, ArrowUp: { x: 0, y: 40 }, ArrowDown: { x: 0, y: -40 } }
  if (moves[event.key]) { event.preventDefault(); graph.panBy(moves[event.key]!) }
  if (event.key === '+' || event.key === '-') { event.preventDefault(); zoom(event.key === '+' ? 1.2 : 1 / 1.2) }
}
onMounted(async () => {
  // Canvas labels need the local font before Cytoscape measures their text.
  await document.fonts.load('13px Roboto').catch(() => [])
  if (!canvas.value) return
  render()
  resize = new ResizeObserver(() => graph?.resize())
  if (canvas.value) resize.observe(canvas.value)
})
watch(path, render, { flush: 'post' })
onBeforeUnmount(() => { resize?.disconnect(); graph?.destroy() })
defineExpose({ reset })
</script>

<template>
  <div class="path-controls">
    <label>Entwicklungspfad für
      <select :value="person ?? ''" aria-label="Person im Entwicklungspfad" @change="emit('person', ($event.target as HTMLSelectElement).value === '' ? null : Number(($event.target as HTMLSelectElement).value))">
        <option value="">Allgemein</option><option v-for="employee in data.employees" :key="employee.id" :value="employee.id">{{ employee.first_name }} {{ employee.last_name }}</option>
      </select>
    </label>
    <label v-if="person !== null">Pfadumfang<select :value="full ? 'full' : 'remaining'" aria-label="Pfadumfang" @change="emit('full', ($event.target as HTMLSelectElement).value === 'full')"><option value="remaining">Verbleibend</option><option value="full">Vollständig</option></select></label>
    <p v-if="path?.distance !== null" class="distance">Fertigkeitsdistanz: <strong>{{ path?.distance }}</strong></p>
  </div>
  <p class="view-hint">Voraussetzung → abhängiger Skill · Umrandeter Zielskill · {{ person === null ? 'Allgemeiner Pfad ohne Besitzbewertung' : 'Grün: vorhanden · Rot: fehlt · Distanz = eindeutige fehlende Skills, keine Lernzeit' }}</p>
  <div class="graph-frame">
    <div ref="canvas" class="dag-canvas" tabindex="0" role="region" aria-label="Entwicklungs-DAG; mit Maus verschieben und zoomen, mit Pfeiltasten verschieben" @keydown="keyboard"></div>
    <div class="graph-zoom"><button aria-label="DAG verkleinern" @click="zoom(1 / 1.2)">−</button><button aria-label="DAG vergrößern" @click="zoom(1.2)">+</button></div>
  </div>
  <details class="graph-description"><summary>Pfad als Text ({{ path?.nodes.length }} Skills)</summary><ul><li v-for="node in path?.nodes" :key="node.id">{{ node.name }}{{ node.target ? ' (Zielskill)' : '' }}{{ node.owned === null ? '' : node.owned ? ' – vorhanden' : ' – fehlt' }}</li></ul><p v-for="edge in path?.edges" :key="`${edge.source}-${edge.target}`">{{ path?.nodes.find(node => node.id === edge.source)?.name }} → {{ path?.nodes.find(node => node.id === edge.target)?.name }}</p></details>
</template>
