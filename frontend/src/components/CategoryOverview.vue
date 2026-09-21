<script setup lang="ts">
import { computed } from 'vue'
import type { Analysis, Status } from '../domain/taxonomy'
import StatusDot from './StatusDot.vue'
const props = defineProps<{ data: Analysis; status: Status; expanded: Set<number>; person: number | null }>()
const emit = defineEmits<{ toggle: [id: number]; path: [skill: number, person: number | null] }>()
const skills = computed(() => props.data.skills.filter(skill => skill.status === props.status).sort((a, b) => a.name.localeCompare(b.name, 'de')))
const candidates = computed(() => new Map(props.data.development.candidates.map(item => [item.skill_id, item])))
const names = (ids: number[]) => props.data.employees.filter(employee => ids.includes(employee.id)).map(employee => `${employee.first_name} ${employee.last_name}`).join(', ')
</script>

<template>
  <p v-if="!skills.length" class="empty">Leere Liste</p>
  <table v-else-if="status === 'green'" class="green-table">
    <thead><tr><th>Skill-Bezeichnung</th><th>Mitarbeitende</th></tr></thead>
    <tbody><tr v-for="skill in skills" :key="skill.id" :data-category-skill="skill.id" tabindex="-1"><td><button class="text-link" @click="emit('path', skill.id, null)">{{ skill.name }}</button></td><td>{{ names(skill.employee_ids) }}</td></tr></tbody>
  </table>
  <div v-else class="candidate-list">
    <article v-for="skill in skills" :key="skill.id" class="candidate-card" :data-category-skill="skill.id" tabindex="-1" :aria-label="skill.name">
      <div class="candidate-header">
        <StatusDot :color="skill.status" />
        <button v-if="expanded.has(skill.id)" class="text-link" @click="emit('path', skill.id, person)">{{ skill.name }}</button>
        <button v-else class="candidate-title" @click="emit('toggle', skill.id)" :aria-expanded="false">{{ skill.name }}</button>
        <button class="disclosure" :aria-label="`${skill.name}: Details`" :aria-expanded="expanded.has(skill.id)" :aria-controls="`candidates-${skill.id}`" @click="emit('toggle', skill.id)">{{ expanded.has(skill.id) ? '−' : '⌄' }}</button>
      </div>
      <div v-if="expanded.has(skill.id)" :id="`candidates-${skill.id}`" class="candidate-body">
        <p v-if="status === 'yellow'">Einziger Wissensträger: <strong>{{ names(skill.employee_ids) }}</strong></p>
        <h3>Entwicklungskandidaten</h3>
        <table><thead><tr><th>Nr.</th><th>Kandidat</th><th>Distanz</th><th>Pfad</th></tr></thead>
          <tbody><tr v-for="(candidate, index) in candidates.get(skill.id)?.slots" :key="index">
            <td>{{ index + 1 }}</td>
            <template v-if="candidate"><td><button class="text-link" @click="emit('path', skill.id, candidate.employee_id)">{{ candidate.first_name }} {{ candidate.last_name }}</button></td><td>{{ candidate.distance }}</td><td><button class="text-link" :aria-label="`Pfad für ${candidate.first_name} ${candidate.last_name}`" @click="emit('path', skill.id, candidate.employee_id)">Pfad öffnen</button></td></template>
            <template v-else><td>Unbesetzt – keine weitere interne Person</td><td></td><td></td></template>
          </tr></tbody>
        </table>
        <p v-if="candidates.get(skill.id)?.recommendations.distance_threshold" class="notice">Die kleinste Fertigkeitsdistanz beträgt mindestens 3. Zusätzlichen Personaleinsatz oder externe Fertigkeitsgewinnung prüfen.</p>
        <p v-if="candidates.get(skill.id)?.recommendations.unfilled_slots" class="notice">Nicht alle Kandidatenplätze sind besetzt. Weitere Maßnahmen prüfen, da zusätzliche interne Personen fehlen.</p>
      </div>
    </article>
  </div>
</template>
