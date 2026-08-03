<script setup lang="ts">
const emit = defineEmits<{ add: [type: string] }>()
const items = [
    { type: 'text', label: 'Text', color: '#3b82f6' },
    { type: 'input', label: 'Input', color: '#10b981' },
    { type: 'button', label: 'Buttons', color: '#f59e0b' },
]
</script>

<template>
    <div class="sidebar">
        <h3>Blocks</h3>
        <div
            v-for="item in items"
            :key="item.type"
            class="sidebar-item"
            :style="{ borderColor: item.color }"
            @click="emit('add', item.type)"
        >
            {{ item.label }}
        </div>
    </div>
</template>

<style scoped>
.sidebar { width: 200px; background: #f8fafc; border-right: 1px solid #e2e8f0; padding: 16px; }
.sidebar-item { padding: 12px; margin-bottom: 8px; background: white; border-radius: 6px; border-left: 4px solid; cursor: pointer; font-weight: 500; }
.sidebar-item:hover { background: #f1f5f9; }
</style>
