<script setup lang="ts">
import { computed } from 'vue'
import TextBlock from './blocks/TextBlock.vue'
import InputBlock from './blocks/InputBlock.vue'
import ButtonBlock from './blocks/ButtonBlock.vue'
import type { FlowBlockType, BlockContent, BlockConfig } from '@/types/flow'

const props = defineProps<{
    block: { id: string; type: FlowBlockType; content?: BlockContent; config?: BlockConfig }
    selected?: boolean
}>()

const emit = defineEmits<{ select: [blockId: string] }>()

const componentMap: Record<FlowBlockType, unknown> = {
    text: TextBlock,
    input: InputBlock,
    button: ButtonBlock,
}

const component = computed(() => componentMap[props.block.type])
</script>

<template>
    <div class="block-slot" :class="{ selected }" @click.stop="emit('select', block.id)">
        <component :is="component" :content="block.content" :config="block.config" />
    </div>
</template>

<style scoped>
.block-slot { border: 2px solid transparent; border-radius: 6px; cursor: pointer; }
.block-slot:hover { background: #f8fafc; }
.block-slot.selected { border-color: #3b82f6; background: #eff6ff; }
</style>
