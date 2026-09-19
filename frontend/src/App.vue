<script setup lang="ts">
import { computed, nextTick, onMounted, ref } from 'vue'
import { loadAnalysis } from './api/client'
import { labels, taxonomyView, type Analysis, type Status } from './domain/taxonomy'
import TaxonomyBranch from './components/TaxonomyBranch.vue'
import StatusDot from './components/StatusDot.vue'
import MaskIcon from './components/MaskIcon.vue'

type View = { kind: 'taxonomy' } | { kind: 'group'; id: number } | { kind: 'category'; status: Status }
type Snapshot = { view: View; expanded: Set<number>; scroll: number; mask: boolean; person: number | null }
const data = ref<Analysis | null>(null)
const loading = ref(true)
const error = ref('')
const mask = ref(true)
const person = ref<number | null>(null)
const expanded = ref(new Set<number>())
const view = ref<View>({ kind: 'taxonomy' })
const history = ref<Snapshot[]>([])
const panel = ref<HTMLElement>()
const heading = ref<HTMLElement>()
const tree = computed(() => data.value ? taxonomyView(data.value, mask.value, person.value) : null)
const group = computed(() => view.value.kind === 'group' ? tree.value?.nodes.get(view.value.id) : null)
const title = computed(() => view.value.kind === 'taxonomy' ? 'Skill-Taxonomie' : view.value.kind === 'category' ? labels[view.value.status] : group.value?.name ?? 'Skillgruppe')
const category = computed(() => {
  const current = view.value
  return current.kind === 'category' ? data.value?.skills.filter(skill => skill.status === current.status) ?? [] : []
})
const greenRows = computed(() => data.value?.employees.map(employee => ({ employee, skills: category.value.filter(skill => skill.employee_ids.includes(employee.id)) })) ?? [])
const statuses: Status[] = ['red', 'yellow', 'green']
const names = (ids: number[]) => data.value?.employees.filter(employee => ids.includes(employee.id)).map(employee => `${employee.first_name} ${employee.last_name}`).join(', ')
async function load() {
  loading.value = true
  error.value = ''
  try {
    const result = await loadAnalysis()
    taxonomyView(result, false, null)
    data.value = result
  } catch (cause) { error.value = cause instanceof Error ? cause.message : 'Die Analyse konnte nicht geladen werden.' }
  finally { loading.value = false }
}
async function position(scroll = 0) {
  await nextTick()
  heading.value?.focus({ preventScroll: true })
  if (panel.value) panel.value.scrollTop = scroll
}
function open(next: View) {
  history.value.push({ view: view.value, expanded: new Set(expanded.value), scroll: panel.value?.scrollTop ?? 0, mask: mask.value, person: person.value })
  view.value = next
  void position()
}
function back() {
  const previous = history.value.pop()
  if (!previous) return
  view.value = previous.view; expanded.value = previous.expanded
  mask.value = previous.mask; person.value = previous.person
  void position(previous.scroll)
}
function toggle(id: number) { expanded.value.has(id) ? expanded.value.delete(id) : expanded.value.add(id) }
function reset() { expanded.value = new Set(); void position() }
function start() {
  view.value = { kind: 'taxonomy' }; history.value = []; mask.value = true; person.value = null
  reset(); window.scrollTo({ top: 0 })
}
onMounted(load)
</script>

<template>
  <a class="skip-link" href="#mainframe">Zum Inhalt</a>
  <header class="topbar">
    <div class="brand">skill<span>tree</span><small>Personalentwicklung</small></div>
    <label class="organisation-select">Organisationseinheit
      <select disabled aria-label="Organisationseinheit"><option>{{ data?.organisation.name ?? 'Organisation wird geladen …' }}</option></select>
    </label>
    <button class="start-button" @click="start">Start <span aria-hidden="true">↗</span></button>
  </header>
  <main>
    <section v-if="loading" class="feedback" role="status">Die Skill-Analyse wird geladen …</section>
    <section v-else-if="error" class="feedback" role="alert"><h1>Analyse nicht verfügbar</h1><p>{{ error }}</p><button @click="load">Erneut versuchen</button></section>
    <template v-else-if="data && tree">
      <section class="dashboard" aria-label="Organisationsbezogene Skillbewertung">
        <div class="dashboard-heading"><p class="eyebrow">Organisation im Überblick</p><h1>Skills. Wissen. Handlungsbedarf.</h1><p>{{ data.summary.required_skill_count }} benötigte Skills · {{ data.summary.employee_count }} Mitarbeitende · {{ data.summary.task_count }} Aufgaben</p></div>
        <div class="metrics">
          <button v-for="status in statuses" :key="status" class="metric" :class="status" :aria-label="`${labels[status]}: ${data.summary.counts[status]} Skills anzeigen`" @click="open({ kind: 'category', status })">
            <span class="metric-number">{{ data.summary.counts[status].toLocaleString('de-DE') }}</span><span class="metric-label">{{ labels[status] }}</span>
            <span class="metric-description">{{ status === 'red' ? 'Kein Wissensträger' : status === 'yellow' ? 'Ein Wissensträger' : 'Mindestens zwei Wissensträger' }}</span>
          </button>
        </div>
      </section>
      <section id="mainframe" class="mainframe" aria-label="Taxonomie und Skillübersichten">
        <div class="toolbar">
          <button class="mask-button" :class="{ active: mask }" :aria-pressed="mask" aria-label="Organisationsmaske" :title="mask ? 'Organisationsmaske aktiv: nur Soll-Skills' : 'Organisationsmaske aus: gesamter Katalog'" @click="mask = !mask"><MaskIcon name="organisation"/><span>Organisation</span></button>
          <label class="person-control" :class="{ active: person !== null }" title="Persönlichen Skillbesitz anzeigen"><MaskIcon name="person"/><select v-model="person" aria-label="Mitarbeitermaske"><option :value="null">Keiner</option><option v-for="employee in data.employees" :key="employee.id" :value="employee.id">{{ employee.first_name }} {{ employee.last_name }}</option></select></label>
          <span title="Simulation folgt in einem späteren Entwicklungsschritt"><button class="mask-button" disabled aria-label="Simulation noch nicht verfügbar"><MaskIcon name="simulation"/><span>Simulation</span></button></span>
          <button class="back-button" :disabled="!history.length" @click="back">← Zurück</button>
        </div>
        <div ref="panel" class="mainframe-content" tabindex="0" aria-label="Scrollbarer Inhaltsbereich">
          <div class="view-heading"><p class="eyebrow">{{ view.kind === 'category' ? 'Organisationsbezogene Übersicht' : 'Hierarchisches Inhaltsverzeichnis' }}</p><h2 ref="heading" tabindex="-1"><StatusDot v-if="group" :color="group.color" :personal="tree.personal" />{{ title }}</h2></div>
          <p v-if="!data.summary.required_skill_count" class="notice">Kein Soll-Bedarf vorhanden. Die vollständige Taxonomie bleibt zugänglich.</p>
          <p v-if="view.kind !== 'category'" class="view-hint">{{ person !== null ? 'Persönlicher Besitz: Grün = vorhanden, Rot = nicht vorhanden.' : 'Organisationsbewertung: Rot vor Gelb vor Grün; ohne Soll-Bedarf neutral.' }} {{ mask && data.required_skill_ids.length ? 'Nur benötigte Skills.' : 'Gesamter Skillkatalog.' }}</p>
          <template v-if="view.kind === 'taxonomy'"><TaxonomyBranch :nodes="tree.roots" :expanded="expanded" :personal="tree.personal" @toggle="toggle" @open="open({ kind: 'group', id: $event })"/><p v-if="!tree.roots.length" class="empty">Leere Liste</p></template>
          <template v-else-if="view.kind === 'group'">
            <ul v-if="group?.skills.length" class="skill-list"><li v-for="skill in group.skills" :key="skill.id"><StatusDot :color="tree.color(skill.id)" :personal="tree.personal"/><span>{{ skill.name }}</span></li></ul><p v-else class="empty">Leere Liste</p>
          </template>
          <template v-else>
            <p class="view-hint">Die Organisationsübersicht bleibt unabhängig von Organisations- und Mitarbeitermaske.</p>
            <p v-if="!category.length" class="empty">Leere Liste</p>
            <table v-else-if="view.status === 'green'"><thead><tr><th>Mitarbeitende</th><th>Redundant vorhandene Soll-Skills</th></tr></thead><tbody><tr v-for="row in greenRows" :key="row.employee.id"><td>{{ row.employee.first_name }} {{ row.employee.last_name }}</td><td>{{ row.skills.map(skill => skill.name).join(', ') }}</td></tr></tbody></table>
            <ul v-else class="skill-list category-list"><li v-for="skill in category" :key="skill.id"><StatusDot :color="skill.status"/><div><strong>{{ skill.name }}</strong><p>{{ skill.status === 'red' ? 'Kein Wissensträger vorhanden' : `Einziger Wissensträger: ${names(skill.employee_ids)}` }}</p></div></li></ul>
          </template>
        </div>
        <div class="frame-footer"><span>{{ view.kind === 'category' ? `${category.length} Skills` : 'Gruppen aufklappen · Untergruppe öffnen' }}</span><button @click="reset" title="Ansicht zurücksetzen; Masken bleiben unverändert">↺ Reset</button></div>
      </section>
    </template>
  </main>
  <footer class="page-footer"><span>skilltree · Skillbasierte Personalentwicklung</span><span>Vorbereitete Daten · Nur lesende Auswertung</span></footer>
</template>
