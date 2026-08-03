<script setup lang="ts">
import { Handle, Position } from '@vue-flow/core'

defineProps<{ data: { config?: { variable?: string } } }>()
</script>

<template>
    <div class="flow-node input-node">
        <div class="node-header">Input</div>
        <div class="node-body">Var: {{ data.config?.variable || '—' }}</div>
        <Handle type="target" :position="Position.Top" />
        <Handle type="source" :position="Position.Bottom" />
    </div>
</template>

<style scoped>
.flow-node { background: white; border: 2px solid #10b981; border-radius: 8px; padding: 10px; width: 180px; font-size: 12px; }
.node-header { font-weight: bold; color: #10b981; margin-bottom: 4px; text-transform: uppercase; font-size: 10px; }
</style>
