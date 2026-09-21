<script setup lang="ts">
import { computed, defineAsyncComponent, nextTick, onMounted, ref } from 'vue'
import { useAnalysis } from './composables/useAnalysis'
import { emptyFilters, toggleExclusion, type SimulationFilters } from './domain/simulation'
import { labels, taxonomyView, type Status } from './domain/taxonomy'
import TaxonomyBranch from './components/TaxonomyBranch.vue'
import TaxonomyGraph from './components/TaxonomyGraph.vue'
import SkillMap from './components/SkillMap.vue'
import StatusDot from './components/StatusDot.vue'
import MaskIcon from './components/MaskIcon.vue'
import CategoryOverview from './components/CategoryOverview.vue'
import SimulationMenu from './components/SimulationMenu.vue'
const DevelopmentGraph = defineAsyncComponent(() => import('./components/DevelopmentGraph.vue'))

type View = { kind: 'taxonomy' } | { kind: 'group'; id: number } | { kind: 'category'; status: Status } | { kind: 'path'; id: number; full: boolean }
type Snapshot = { view: View; expanded: Set<number>; details: Set<number>; scroll: number; mask: boolean; person: number | null; taxonomyMode: 'tree' | 'graph' | 'map'; mapScroll: { x: number; y: number } }
const taxonomyMode = ref<'tree' | 'graph' | 'map'>('graph')
const mapScroll = ref({ x: 0, y: 0 })
const mapView = ref<{ reset: () => void }>()
const isMap = computed(() => view.value.kind === 'taxonomy' && taxonomyMode.value === 'map')
const taxonomyGraph = ref<{ reset: () => void }>()
const { data, busy, error, requested, load: requestAnalysis } = useAnalysis()
const loading = computed(() => !data.value && busy.value)
const simulationOpen = ref(false)
const simulationActive = computed(() => !!data.value && (data.value.simulation.excluded_task_ids.length + data.value.simulation.excluded_employee_ids.length > 0))
const mask = ref(true)
const person = ref<number | null>(null)
const expanded = ref(new Set<number>())
const view = ref<View>({ kind: 'taxonomy' })
const history = ref<Snapshot[]>([])
const panel = ref<HTMLElement>()
const heading = ref<HTMLElement>()
const pathGraph = ref<{ reset: () => void }>()
const details = ref(new Set<number>())
const warning = ref('')
const warningBox = ref<HTMLElement>()
let warningOrigin: HTMLElement | null = null
const tree = computed(() => data.value ? taxonomyView(data.value, mask.value, person.value) : null)
const group = computed(() => view.value.kind === 'group' ? tree.value?.nodes.get(view.value.id) : null)
const title = computed(() => {
  const current = view.value
  if (current.kind === 'path') return data.value?.taxonomy.skills.find(skill => skill.id === current.id)?.name ?? 'Entwicklungspfad'
  return current.kind === 'taxonomy' ? 'Skill-Taxonomie' : current.kind === 'category' ? labels[current.status] : group.value?.name ?? 'Skillgruppe'
})
const category = computed(() => {
  const current = view.value
  return current.kind === 'category' ? data.value?.skills.filter(skill => skill.status === current.status) ?? [] : []
})
const statuses: Status[] = ['red', 'yellow', 'green']
async function load(filters: SimulationFilters = emptyFilters()) {
  if (await requestAnalysis(filters)) normalizeView()
}
function normalizeView() {
  if (person.value !== null && !data.value?.employees.some(employee => employee.id === person.value)) pathPerson(null)
  const current = view.value
  if ((current.kind === 'path' && !data.value?.development.estimated_skill_ids.includes(current.id))
    || (current.kind === 'group' && !data.value?.taxonomy.groups.some(group => group.id === current.id))) {
    view.value = { kind: 'taxonomy' }
  }
}
function simulate(kind: 'task' | 'employee', id: number) {
  if (data.value) void load(toggleExclusion(data.value.simulation, kind, id))
}
async function position(scroll = 0) {
  await nextTick()
  heading.value?.focus({ preventScroll: true })
  if (panel.value) panel.value.scrollTop = scroll
}
function open(next: View) {
  history.value.push({ view: { ...view.value }, expanded: new Set(expanded.value), details: new Set(details.value), scroll: panel.value?.scrollTop ?? 0, mask: mask.value, person: person.value, taxonomyMode: taxonomyMode.value, mapScroll: { ...mapScroll.value } })
  warning.value = ''
  view.value = next
  void position()
}
function back() {
  const previous = history.value.pop()
  if (!previous) return
  view.value = previous.view; expanded.value = previous.expanded
  details.value = previous.details; warning.value = ''
  mask.value = previous.mask; person.value = previous.person
  taxonomyMode.value = previous.taxonomyMode
  mapScroll.value = previous.mapScroll
  normalizeView()
  void position(previous.scroll)
}
async function openCategory(status: Status, id: number) {
  open({ kind: 'category', status })
  if (status !== 'green') details.value.add(id)
  await position()
  const target = panel.value?.querySelector<HTMLElement>(`[data-category-skill="${id}"]`)
  target?.focus({ preventScroll: true })
  target?.scrollIntoView({ block: 'nearest' })
  target?.animate([{ backgroundColor: '#fff0b5' }, { backgroundColor: 'transparent' }], { duration: 2200 })
}
function toggle(id: number) { expanded.value.has(id) ? expanded.value.delete(id) : expanded.value.add(id) }
function toggleDetail(id: number) { details.value.has(id) ? details.value.delete(id) : details.value.add(id) }
function openPath(id: number, employee: number | null) {
  if (!data.value?.development.estimated_skill_ids.includes(id)) {
    warningOrigin = document.activeElement instanceof HTMLElement ? document.activeElement : null
    warning.value = 'Für diesen Skill liegt kein geschätzter Entwicklungspfad vor.'
    void nextTick(() => warningBox.value?.focus())
    return
  }
  open({ kind: 'path', id, full: employee === null })
  person.value = employee
}
function closeWarning() { warning.value = ''; warningOrigin?.focus() }
function pathPerson(id: number | null) {
  person.value = id
  if (view.value.kind === 'path') view.value = { ...view.value, full: id === null }
}
function reset() {
  if (view.value.kind === 'path') pathGraph.value?.reset()
  else if (isMap.value) { mapScroll.value = { x: 0, y: 0 }; mapView.value?.reset() }
  else if (view.value.kind === 'taxonomy') { expanded.value = new Set(); void nextTick(() => taxonomyGraph.value?.reset()) }
  else if (view.value.kind === 'category') details.value = new Set()
  void position()
}
function start() {
  view.value = { kind: 'taxonomy' }; history.value = []; mask.value = true; person.value = null
  details.value = new Set(); warning.value = ''
  simulationOpen.value = false
  taxonomyMode.value = 'graph'
  mapScroll.value = { x: 0, y: 0 }
  reset(); window.scrollTo({ top: 0 })
  void load(emptyFilters())
}
onMounted(() => load())
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
    <section v-else-if="error && !data" class="feedback" role="alert"><h1>Analyse nicht verfügbar</h1><p>{{ error }}</p><button @click="load(requested)">Erneut versuchen</button></section>
    <template v-else-if="data && tree">
      <div v-if="error && !simulationOpen" class="notice" role="alert">{{ error }} Der letzte erfolgreich berechnete Stand bleibt erhalten. <button @click="load(requested)">Erneut versuchen</button></div>
      <p v-if="busy && !simulationOpen" class="notice" role="status">Analyse wird neu berechnet …</p>
      <section class="dashboard" :inert="busy" :aria-busy="busy" aria-label="Organisationsbezogene Skillbewertung">
        <div class="dashboard-heading"><p class="eyebrow">Organisation im Überblick</p><h1>Skills. Wissen. Handlungsbedarf.</h1><p>{{ data.summary.required_skill_count }} benötigte Skills · {{ data.summary.employee_count }} Mitarbeitende · {{ data.summary.task_count }} Aufgaben</p></div>
        <div class="metrics">
          <button v-for="status in statuses" :key="status" class="metric" :class="status" :aria-label="`${labels[status]}: ${data.summary.counts[status]} Skills anzeigen`" @click="open({ kind: 'category', status })">
            <span class="metric-number">{{ data.summary.counts[status].toLocaleString('de-DE') }}</span><span class="metric-label">{{ labels[status] }}</span>
            <span class="metric-description">{{ status === 'red' ? 'Kein Wissensträger' : status === 'yellow' ? 'Ein Wissensträger' : 'Mindestens zwei Wissensträger' }}</span>
          </button>
        </div>
      </section>
      <section id="mainframe" class="mainframe" :inert="busy" :aria-busy="busy" aria-label="Taxonomie und Skillübersichten">
        <div class="toolbar">
          <button class="mask-button" :class="{ active: mask }" :aria-pressed="mask" aria-label="Organisationsmaske" :title="mask ? 'Organisationsmaske aktiv: nur Soll-Skills' : 'Organisationsmaske aus: gesamter Katalog'" @click="mask = !mask"><MaskIcon name="organisation"/><span>Organisation</span></button>
          <label v-if="!isMap" class="person-control" :class="{ active: person !== null }" title="Persönlichen Skillbesitz anzeigen"><MaskIcon name="person"/><select v-model="person" aria-label="Mitarbeitermaske" @change="pathPerson(person)"><option :value="null">Keiner</option><option v-for="employee in data.employees" :key="employee.id" :value="employee.id">{{ employee.first_name }} {{ employee.last_name }}</option></select></label>
          <button class="mask-button" :class="{ active: simulationActive }" :aria-pressed="simulationActive" :aria-expanded="simulationOpen" aria-label="Simulation" aria-haspopup="dialog" :title="simulationActive ? 'Simulation aktiv: Aufgaben oder Mitarbeitende ausgeschlossen' : 'Simulation: alle Aufgaben und Mitarbeitenden eingeschlossen'" @click="simulationOpen = true"><MaskIcon name="simulation"/><span>Simulation{{ simulationActive ? ' · aktiv' : '' }}</span></button>
          <button class="back-button" :disabled="!history.length" @click="back">← Zurück</button>
        </div>
        <div ref="panel" class="mainframe-content" tabindex="0" aria-label="Scrollbarer Inhaltsbereich">
          <div class="view-heading"><p class="eyebrow">{{ view.kind === 'path' ? 'Entwicklungspfad' : view.kind === 'category' ? 'Organisationsbezogene Übersicht' : view.kind === 'group' ? 'Skill-Liste' : taxonomyMode === 'graph' ? 'Wissensgraph · Gruppen erkunden' : taxonomyMode === 'map' ? 'Skillkarte · Wissensträger im Überblick' : 'Hierarchisches Inhaltsverzeichnis' }}</p><h2 ref="heading" tabindex="-1" :class="view.kind === 'category' ? `category-heading ${view.status}` : ''"><StatusDot v-if="group" :color="group.color" :personal="tree.personal" />{{ title }}</h2></div>
          <div v-if="view.kind === 'taxonomy'" class="taxonomy-switch" role="group" aria-label="Taxonomieansicht"><button :aria-pressed="taxonomyMode === 'tree'" @click="taxonomyMode = 'tree'">Baum</button><button :aria-pressed="taxonomyMode === 'graph'" @click="taxonomyMode = 'graph'">Graph</button><button :aria-pressed="taxonomyMode === 'map'" @click="taxonomyMode = 'map'">Karte</button></div>
          <div v-if="warning" ref="warningBox" class="notice warning" role="alert" tabindex="-1">{{ warning }}<button aria-label="Warnung schließen" @click="closeWarning">×</button></div>
          <p v-if="!data.summary.required_skill_count" class="notice">Kein Soll-Bedarf vorhanden. Die vollständige Taxonomie bleibt zugänglich.</p>
          <p v-if="view.kind === 'taxonomy' || view.kind === 'group'" class="view-hint">{{ person !== null && !isMap ? 'Persönlicher Besitz: Grün = vorhanden, Rot = nicht vorhanden.' : 'Organisationsbewertung: Rot vor Gelb vor Grün; ohne Soll-Bedarf neutral.' }} {{ mask && data.required_skill_ids.length ? 'Nur benötigte Skills.' : 'Gesamter Skillkatalog.' }}</p>
          <template v-if="view.kind === 'taxonomy'"><SkillMap v-if="isMap" ref="mapView" :data="data" :mask="mask" :scroll="mapScroll" @scroll="mapScroll = $event" @path="openPath" @category="openCategory"/><TaxonomyGraph v-else-if="taxonomyMode === 'graph'" ref="taxonomyGraph" :nodes="tree.roots" :expanded="expanded" :personal="tree.personal" @toggle="toggle" @open="open({ kind: 'group', id: $event })"/><TaxonomyBranch v-else :nodes="tree.roots" :expanded="expanded" :personal="tree.personal" @toggle="toggle" @open="open({ kind: 'group', id: $event })"/><p v-if="!tree.roots.length" class="empty">Leere Liste</p></template>
          <template v-else-if="view.kind === 'group'">
            <ul v-if="group?.skills.length" class="skill-list"><li v-for="skill in group.skills" :key="skill.id"><StatusDot :color="tree.color(skill.id)" :personal="tree.personal"/><button class="text-link" @click="openPath(skill.id, person)">{{ skill.name }}</button></li></ul><p v-else class="empty">Leere Liste</p>
          </template>
          <template v-else-if="view.kind === 'category'">
            <p class="view-hint">Die Organisationsübersicht bleibt unabhängig von Organisations- und Mitarbeitermaske.</p>
            <CategoryOverview :data="data" :status="view.status" :expanded="details" :person="person" @toggle="toggleDetail" @path="openPath" />
          </template>
          <DevelopmentGraph v-else-if="view.kind === 'path'" ref="pathGraph" :data="data" :target="view.id" :person="person" :full="view.full" @person="pathPerson" @full="view = { ...view, full: $event }" />
        </div>
        <div class="frame-footer"><span>{{ view.kind === 'category' ? `${category.length} Skills` : view.kind === 'path' ? 'DAG verschieben · Zoomen · Pfadumfang wählen' : isMap ? 'Skillkarte · Status und Pfade öffnen' : 'Gruppen aufklappen · Untergruppe öffnen' }}</span><button @click="reset" title="Ansicht zurücksetzen; Masken bleiben unverändert">↺ Reset</button></div>
      </section>
      <SimulationMenu v-if="simulationOpen" :simulation="data.simulation" :busy="busy" :error="error" @toggle="simulate" @change="load" @retry="load(requested)" @close="simulationOpen = false" />
    </template>
  </main>
  <footer class="page-footer"><span>skilltree · Skillbasierte Personalentwicklung</span><span>Vorbereitete Daten · Nur lesende Auswertung</span></footer>
</template>
