<script setup lang="ts">
import type { TreeNode } from '../domain/taxonomy'
import StatusDot from './StatusDot.vue'
defineProps<{ nodes: TreeNode[]; expanded: Set<number>; personal: boolean }>()
const emit = defineEmits<{ toggle: [id: number]; open: [id: number] }>()
</script>
<template>
  <ul class="taxonomy-list">
    <li v-for="node in nodes" :key="node.id">
      <button class="tree-row" :aria-expanded="node.children.length ? expanded.has(node.id) : undefined" @click="node.children.length ? emit('toggle', node.id) : emit('open', node.id)">
        <span class="chevron" aria-hidden="true">{{ node.children.length ? (expanded.has(node.id) ? '⌄' : '›') : '→' }}</span>
        <StatusDot :color="node.color" :personal="personal" />
        <span class="row-name">{{ node.name }}</span><span class="row-count">{{ node.count }} Skills</span>
      </button>
      <template v-if="expanded.has(node.id) && node.children.length">
        <button v-if="node.skills.length" class="own-skills" @click="emit('open', node.id)">Direkt zugeordnete Skills ({{ node.skills.length }})</button>
        <TaxonomyBranch :nodes="node.children" :expanded="expanded" :personal="personal" @toggle="emit('toggle', $event)" @open="emit('open', $event)" />
      </template>
    </li>
  </ul>
</template>
