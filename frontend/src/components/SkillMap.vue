<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { skillMap } from '../domain/skillMap'
import { labels, statusLinkTitle, type Analysis, type Status } from '../domain/taxonomy'
import StatusDot from './StatusDot.vue'
const props = defineProps<{ data: Analysis; mask: boolean; people: number[]; scroll: { x: number; y: number } }>()
const employees = computed(() => props.data.employees.filter(employee => !props.people.length || props.people.includes(employee.id)))
const emit = defineEmits<{ path: [id: number, person: number | null]; category: [status: Status, id: number]; scroll: [position: { x: number; y: number }] }>()
const groups = computed(() => skillMap(props.data, props.mask))
const viewport = ref<HTMLElement>()
function remember() { if (viewport.value) emit('scroll', { x: viewport.value.scrollLeft, y: viewport.value.scrollTop }) }
function reset() { viewport.value?.scrollTo(0, 0) }
onMounted(() => viewport.value?.scrollTo(props.scroll.x, props.scroll.y))
defineExpose({ reset })
</script>
<template>
  <p class="view-hint">✓ vorhanden · × fehlt · Benötigt: zwei Wissensträger je Soll-Skill, sonst null. Skillname: allgemeiner Pfad; Zuordnung: persönlicher Pfad.</p>
  <div ref="viewport" class="skill-map-scroll" tabindex="0" role="region" aria-label="Skillmatrix" @scroll="remember">
    <table v-if="groups.length" class="skill-map">
      <caption class="sr-only">Skills nach Gruppe mit Wissensträgern und Besetzungslücke</caption>
      <thead><tr><th scope="col" class="map-group">Gruppe</th><th scope="col" class="map-skill">Skill</th><th scope="col" title="Gesamtlänge des allgemeinen Entwicklungspfad"><span class="tooltip-label">Pfad</span></th><th v-for="employee in employees" :key="employee.id" scope="col" class="map-person"><span>{{ employee.first_name }} {{ employee.last_name }}</span></th><th scope="col">Vorhanden</th><th scope="col">Benötigt</th><th scope="col">Lücke</th></tr></thead>
      <tbody v-for="group in groups" :key="group.id">
        <tr v-for="(row, index) in group.rows" :key="row.skill.id" :data-map-skill="row.skill.id">
          <th v-if="index === 0" scope="rowgroup" :rowspan="group.rows.length" class="map-group"><span class="map-group-name">{{ group.name }}</span></th>
          <th scope="row" class="map-skill"><div class="map-skill-label"><button v-if="row.color !== 'neutral'" class="map-status" :title="statusLinkTitle(row.color)" :aria-label="`${row.skill.name}: ${labels[row.color]} öffnen`" @click="emit('category', row.color, row.skill.id)"><StatusDot :color="row.color" :tooltip="statusLinkTitle(row.color)" /></button><StatusDot v-else color="neutral" /><button class="text-link" :title="row.skill.description?.trim() || undefined" @click="emit('path', row.skill.id, null)">{{ row.skill.name }}</button></div></th>
          <td class="map-path">{{ row.pathLength ?? '–' }}</td>
          <td v-for="cell in row.possession.filter(cell => !people.length || people.includes(cell.employee.id))" :key="cell.employee.id" class="map-cell"><button class="possession" :class="{ owned: cell.owned }" :title="cell.owned ? 'Vorhanden' : 'Nicht vorhanden'" :aria-label="`${row.skill.name} · ${cell.employee.first_name} ${cell.employee.last_name}: ${cell.owned ? 'vorhanden' : 'fehlt'} – persönlichen Pfad öffnen`" @click="emit('path', row.skill.id, cell.employee.id)">{{ cell.owned ? '✓' : '×' }}</button></td>
          <td class="map-available">{{ row.available }}</td><td class="map-needed">{{ row.needed }}</td><td class="map-gap" :class="{ shortage: row.gap > 0 }">{{ row.gap }}</td>
        </tr>
      </tbody>
    </table><p v-else class="empty">Leere Liste</p>
  </div>
</template>
<style scoped>
.tooltip-label { text-decoration: underline; text-underline-offset: 3px; cursor: help; }
.skill-map-scroll { max-height: 440px; overflow: auto; border: 1px solid var(--border); border-radius: 6px; --group-width: 130px; --skill-width: 230px; }
.skill-map { border-collapse: separate; border-spacing: 0; width: 100%; font-size: .82rem; }
.skill-map th, .skill-map td { padding: .65rem .7rem; min-width: 75px; border-bottom: 1px solid var(--border-soft); text-align: center; background: white; vertical-align: middle; }
.skill-map thead th { position: sticky; top: 0; z-index: 3; background: var(--surface-muted); height: 140px; }
.skill-map .map-group { position: sticky; left: 0; min-width: var(--group-width); width: var(--group-width); max-width: var(--group-width); text-align: left; white-space: normal; overflow-wrap: anywhere; background: var(--surface-muted); z-index: 2; }
.skill-map .map-skill { position: sticky; left: var(--group-width); min-width: var(--skill-width); width: var(--skill-width); max-width: var(--skill-width); text-align: left; z-index: 2; border-right: 1px solid var(--border); }
.skill-map thead .map-group, .skill-map thead .map-skill { z-index: 4; background: var(--surface-muted); }
.map-person span { writing-mode: vertical-rl; transform: rotate(180deg); white-space: nowrap; }
.map-skill-label { display: flex; align-items: center; gap: .6rem; }
.map-skill-label .text-link { min-width: 0; overflow-wrap: anywhere; }
.skill-map tbody .map-group { vertical-align: top; }
.map-group-name { display: block; position: sticky; top: 155px; }
.map-status { padding: .4rem; border: 0; background: transparent; flex: none; display: flex; }
.map-skill-label > .status-dot { margin: .4rem; flex: none; }
.possession { border: 2px solid var(--neutral); border-radius: 50%; width: 30px; height: 30px; padding: 0; line-height: 24px; font-size: 24px; color: var(--neutral); background: white; }
.possession.owned { color: #26704a; border-color: #53a578; background: #eef8f1; }
.skill-map .shortage { color: #ad3038; font-weight: bold; }
.skill-map tbody + tbody tr:first-child > * { border-top: 2px solid var(--border); }
.sr-only { position: absolute; width: 1px; height: 1px; overflow: hidden; clip-path: inset(50%); }
@media (max-width: 700px) { .skill-map-scroll { --group-width: 85px; --skill-width: 150px; } .skill-map th, .skill-map td { padding: .45rem; } .skill-map { font-size: .75rem; } }
</style>
