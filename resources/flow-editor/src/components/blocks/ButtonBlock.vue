<script setup lang="ts">
import type { BlockContent, BlockConfig, FlowBlockType } from '@/types/flow'

// `config`/`type` объявлены, но не используются этим компонентом — см.
// аналогичный комментарий в ConditionBlock.vue.
defineProps<{ content?: BlockContent; config?: BlockConfig; type?: FlowBlockType }>()
</script>

<template>
    <div class="block button-block">
        <span class="block-icon" aria-hidden="true">🔘</span>
        <div class="tags">
            <span v-if="!content?.buttons?.length" class="block-text">Нет кнопок</span>
            <span v-for="(btn, i) in content?.buttons || []" :key="i" class="tag">{{ btn }}</span>
        </div>
    </div>
</template>

<style scoped>
.block { display: flex; align-items: flex-start; gap: var(--space-2); padding: 6px 8px; border-radius: var(--radius-sm); }
.block-icon { flex-shrink: 0; font-size: var(--font-size-sm); margin-top: 2px; }
.block-text { font-size: var(--font-size-sm); color: var(--color-text-muted); }
.tags { display: flex; flex-wrap: wrap; gap: 4px; }
.tag { background: color-mix(in oklch, var(--color-warning) 20%, var(--color-surface)); padding: 2px 6px; border-radius: var(--radius-sm); font-size: var(--font-size-xs); color: var(--color-warning-text); }
</style>
