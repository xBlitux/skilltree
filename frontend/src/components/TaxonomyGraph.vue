<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { labels, type TreeNode } from '../domain/taxonomy'
import { taxonomyGraph } from '../domain/taxonomyGraph'

const props = defineProps<{ nodes: TreeNode[]; expanded: Set<number>; personal: boolean }>()
const emit = defineEmits<{ toggle: [id: number]; open: [id: number] }>()
const graph = computed(() => taxonomyGraph(props.nodes, props.expanded))
const canvas = ref<SVGSVGElement>()
const width = ref(900)
const height = ref(380)
const scale = ref(1)
const offset = ref({ x: 450, y: 190 })
let observer: ResizeObserver | undefined
let drag: { x: number; y: number; moved: boolean } | undefined
let suppressClick = false
function fit() {
  const nodes = graph.value.nodes
  if (!nodes.length) return
  const left = Math.min(...nodes.map(n => n.x)) - 115
  const right = Math.max(...nodes.map(n => n.x)) + 115
  const top = Math.min(...nodes.map(n => n.y)) - 55
  const bottom = Math.max(...nodes.map(n => n.y)) + 110
  scale.value = Math.min(1, width.value / (right - left), height.value / (bottom - top))
  offset.value = { x: width.value / 2 - (left + right) / 2 * scale.value, y: height.value / 2 - (top + bottom) / 2 * scale.value }
}
function zoom(factor: number, x = width.value / 2, y = height.value / 2) {
  const next = Math.min(3, Math.max(.03, scale.value * factor))
  const ratio = next / scale.value
  offset.value = { x: x - (x - offset.value.x) * ratio, y: y - (y - offset.value.y) * ratio }
  scale.value = next
}
function wheel(event: WheelEvent) {
  const bounds = canvas.value!.getBoundingClientRect()
  zoom(event.deltaY < 0 ? 1.12 : 1 / 1.12, event.clientX - bounds.left, event.clientY - bounds.top)
}
function down(event: PointerEvent) {
  if (event.button !== 0) return
  suppressClick = false
  drag = { x: event.clientX, y: event.clientY, moved: false }
}
function move(event: PointerEvent) {
  if (!drag) return
  const x = event.clientX - drag.x, y = event.clientY - drag.y
  if (!drag.moved && Math.hypot(x, y) < 5) return
  drag.moved = true
  canvas.value?.setPointerCapture(event.pointerId)
  offset.value = { x: offset.value.x + x, y: offset.value.y + y }
  drag.x = event.clientX; drag.y = event.clientY
}
function up() { suppressClick = drag?.moved ?? false; drag = undefined }
function activate(node: TreeNode) {
  if (suppressClick) { suppressClick = false; return }
  node.children.length ? emit('toggle', node.id) : emit('open', node.id)
}
function keyboard(event: KeyboardEvent) {
  const directions: Record<string, [number, number]> = { ArrowLeft: [40, 0], ArrowRight: [-40, 0], ArrowUp: [0, 40], ArrowDown: [0, -40] }
  const direction = directions[event.key]
  if (direction) { event.preventDefault(); offset.value = { x: offset.value.x + direction[0], y: offset.value.y + direction[1] } }
  if (event.key === '+' || event.key === '-') { event.preventDefault(); zoom(event.key === '+' ? 1.2 : 1 / 1.2) }
}
function nameLines(name: string) {
  const lines: string[] = ['']
  for (const word of name.split(/\s+/)) {
    if (lines[lines.length - 1]!.length + word.length > 25 && lines[lines.length - 1]) lines.push(word)
    else lines[lines.length - 1] += (lines[lines.length - 1] ? ' ' : '') + word
  }
  return lines
}
function description(node: TreeNode) {
  const status = props.personal ? (node.color === 'green' ? 'Vorhanden' : node.color === 'red' ? 'Nicht vollständig vorhanden' : 'Keine Skills') : labels[node.color]
  return `${node.name}: ${node.count} Skills · ${status} · ${node.children.length ? 'Untergruppen auf- oder zuklappen' : 'Skill-Liste öffnen'}`
}
watch(graph, () => nextTick(fit))
onMounted(() => {
  observer = new ResizeObserver(entries => {
    const bounds = entries[0]!.contentRect
    width.value = bounds.width; height.value = bounds.height; fit()
  })
  if (canvas.value) observer.observe(canvas.value)
})
onBeforeUnmount(() => observer?.disconnect())
defineExpose({ reset: fit })
</script>

<template>
  <div class="taxonomy-graph-frame">
    <div class="taxonomy-graph-caption">TAXONOMIE <span>{{ graph.nodes.length }} sichtbare Gruppen</span></div>
    <svg ref="canvas" class="taxonomy-canvas" tabindex="0" role="group" aria-label="Taxonomiegraph" @wheel.prevent="wheel" @pointerdown="down" @pointermove="move" @pointerup="up" @pointercancel="up" @pointerleave="!drag?.moved && up()" @keydown="keyboard">
      <g :transform="`translate(${offset.x} ${offset.y}) scale(${scale})`">
        <line v-for="edge in graph.edges" :key="edge.target.group.id" :x1="edge.source.x" :y1="edge.source.y" :x2="edge.target.x" :y2="edge.target.y" class="taxonomy-edge" />
        <g v-for="node in graph.nodes" :key="node.group.id" class="taxonomy-node" :class="[node.group.color, { expanded: expanded.has(node.group.id) }]" :transform="`translate(${node.x} ${node.y})`" role="button" tabindex="0" :aria-label="description(node.group)" :aria-expanded="node.group.children.length ? expanded.has(node.group.id) : undefined" @click.stop="activate(node.group)" @keydown.enter.prevent.stop="suppressClick = false; activate(node.group)" @keydown.space.prevent.stop="suppressClick = false; activate(node.group)">
          <title>{{ description(node.group) }}</title>
          <circle class="node-halo" r="35" />
          <circle class="node-disc" r="27" />
          <text class="node-count" text-anchor="middle" dy="6">{{ node.group.count }}</text>
          <text class="node-name" text-anchor="middle"><tspan v-for="(line, i) in nameLines(node.group.name)" :key="i" x="0" :y="49 + i * 17">{{ line }}</tspan></text>
          <text v-if="node.group.children.length" class="node-indicator" x="23" y="-23" text-anchor="middle">{{ expanded.has(node.group.id) ? '−' : '+' }}</text>
        </g>
      </g>
    </svg>
    <div class="taxonomy-graph-tools"><span>Kreis anklicken · Fläche verschieben · Scrollen zum Zoomen</span><div><button aria-label="Taxonomie verkleinern" @click="zoom(1 / 1.2)">−</button><button aria-label="Taxonomie vergrößern" @click="zoom(1.2)">+</button><button @click="fit">Einpassen</button></div></div>
  </div>
  <div v-for="node in graph.nodes.filter(n => n.group.children.length && n.group.skills.length && expanded.has(n.group.id))" :key="node.group.id" class="graph-own-skills"><button class="text-link" @click="emit('open', node.group.id)">{{ node.group.name }}: Direkt zugeordnete Skills ({{ node.group.skills.length }})</button></div>
</template>

<style scoped>
.taxonomy-graph-frame { border-radius: 10px; overflow: hidden; background: #fff; color: var(--text); border: 1px solid var(--border); }
.taxonomy-graph-caption { display: flex; justify-content: space-between; padding: 16px 20px 0; color: var(--text-muted); font-size: 11px; letter-spacing: .12em; }
.taxonomy-graph-caption span { letter-spacing: 0; }
.taxonomy-canvas { display: block; width: 100%; height: 380px; touch-action: none; cursor: grab; }
.taxonomy-canvas:active { cursor: grabbing; }
.taxonomy-edge { stroke: #bcbcbc; stroke-width: 1.5; }
.taxonomy-node { cursor: pointer; outline: none; --node: var(--neutral); }
.taxonomy-node.red { --node: var(--red); } .taxonomy-node.yellow { --node: var(--yellow); } .taxonomy-node.green { --node: var(--green); }
.node-disc { fill: color-mix(in srgb, var(--node) 10%, white); stroke: var(--node); stroke-width: 2; }
.node-halo { fill: none; stroke: var(--node); opacity: 0; stroke-width: 1; }
.expanded .node-halo { opacity: .45; }
.taxonomy-node:hover .node-halo, .taxonomy-node:focus .node-halo { opacity: 1; stroke-width: 3; }
.node-count { fill: var(--node); font-size: 18px; font-weight: 700; pointer-events: none; }
.node-name { fill: var(--text); font-size: 16px; paint-order: stroke; stroke: #fff; stroke-width: 4px; stroke-linejoin: round; }
.node-indicator { fill: var(--text); font-size: 17px; font-weight: bold; paint-order: stroke; stroke: #fff; stroke-width: 4px; }
.taxonomy-graph-tools { display: flex; justify-content: space-between; align-items: center; gap: 10px; padding: 8px 14px 14px; color: var(--text-muted); font-size: 11px; }
.taxonomy-graph-tools > div { display: flex; gap: 5px; }
.taxonomy-graph-tools button { padding: 6px 10px; }
.graph-own-skills { margin-top: .6rem; }
@media (max-width: 700px) {
  .taxonomy-canvas { height: 320px; }
  .taxonomy-graph-tools { flex-wrap: wrap; }
  .taxonomy-graph-tools > span { flex-basis: 100%; }
  .taxonomy-graph-tools > div { margin-left: auto; }
}
</style>
