<script setup lang="ts">
import { Handle, Position } from '@vue-flow/core'

defineProps<{ data: { content?: { buttons?: string[] } } }>()
</script>

<template>
    <div class="flow-node button-node">
        <div class="node-header">Buttons</div>
        <div class="node-body">
            <span v-for="(btn, i) in data.content?.buttons || []" :key="i" class="tag">{{ btn }}</span>
        </div>
        <Handle type="target" :position="Position.Top" />
        <Handle type="source" :position="Position.Bottom" />
    </div>
</template>

<style scoped>
.flow-node { background: white; border: 2px solid #f59e0b; border-radius: 8px; padding: 10px; width: 180px; font-size: 12px; }
.node-header { font-weight: bold; color: #f59e0b; margin-bottom: 4px; text-transform: uppercase; font-size: 10px; }
.node-body { display: flex; flex-wrap: wrap; gap: 4px; }
.tag { background: #fef3c7; padding: 2px 6px; border-radius: 4px; font-size: 10px; }
</style>
