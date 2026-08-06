<script setup lang="ts">
import { computed } from 'vue'
import { getBlockDefinition } from '@/blocks'
import type { FlowBlockType, BlockContent, BlockConfig } from '@/types/flow'

const props = defineProps<{
    block: { id: string; type: FlowBlockType; content?: BlockContent; config?: BlockConfig }
    selected?: boolean
}>()

const emit = defineEmits<{ select: [blockId: string] }>()

// Компонент отображения берётся из реестра блоков (src/blocks) — этот
// файл больше не знает о конкретных типах и не меняется при добавлении
// нового типа блока.
const component = computed(() => getBlockDefinition(props.block.type).renderComponent)
</script>

<template>
    <div class="block-slot" :class="{ selected }" @click.stop="emit('select', block.id)">
        <component :is="component" :content="block.content" :config="block.config" />
    </div>
</template>

<style scoped>
.block-slot { border: 2px solid transparent; border-radius: var(--radius-sm); cursor: pointer; }
.block-slot:hover { background: var(--color-surface-50); }
.block-slot.selected { border-color: var(--color-accent); background: color-mix(in oklch, var(--color-accent) 10%, transparent); }
</style>
