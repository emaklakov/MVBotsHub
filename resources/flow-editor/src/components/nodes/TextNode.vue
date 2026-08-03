<script setup lang="ts">
import { Handle, Position } from '@vue-flow/core'

defineProps<{ data: { content?: { translations?: Record<string, string> } } }>()
</script>

<template>
    <div class="flow-node text-node">
        <div class="node-header">Text</div>
        <div class="node-body">{{ data.content?.translations?.ru || '...' }}</div>
        <Handle type="target" :position="Position.Top" />
        <Handle type="source" :position="Position.Bottom" />
    </div>
</template>

<style scoped>
.flow-node { background: white; border: 2px solid #3b82f6; border-radius: 8px; padding: 10px; width: 180px; font-size: 12px; }
.node-header { font-weight: bold; color: #3b82f6; margin-bottom: 4px; text-transform: uppercase; font-size: 10px; }
.node-body { color: #333; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
</style>
