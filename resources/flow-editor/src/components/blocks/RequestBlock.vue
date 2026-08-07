<script setup lang="ts">
import { computed } from 'vue'
import type { BlockContent, BlockConfig, FlowBlockType } from '@/types/flow'

const props = defineProps<{ content?: BlockContent; config?: BlockConfig; type?: FlowBlockType }>()

const ICONS: Partial<Record<FlowBlockType, string>> = {
    geolocation: '📍',
    contact: '☎️',
}
const FALLBACK_TEXT: Partial<Record<FlowBlockType, string>> = {
    geolocation: 'Запрос геолокации',
    contact: 'Запрос контакта',
}

const icon = computed(() => (props.type ? ICONS[props.type] ?? '📍' : '📍'))
const fallbackText = computed(() => (props.type && FALLBACK_TEXT[props.type]) || 'Запрос')
</script>

<template>
    <div class="block request-block">
        <span class="block-icon" aria-hidden="true">{{ icon }}</span>
        <span class="block-text">
            {{ content?.translations?.ru || fallbackText }}
            <span class="var-tag" v-if="config?.variable">→ {{ config.variable }}</span>
        </span>
    </div>
</template>

<style scoped>
.block { display: flex; align-items: center; gap: var(--space-2); padding: 6px 8px; border-radius: var(--radius-sm); }
.block-icon { flex-shrink: 0; font-size: var(--font-size-sm); }
.block-text { font-size: var(--font-size-sm); color: var(--color-text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.var-tag { color: var(--color-success-text); font-weight: 600; font-size: var(--font-size-xs); }
</style>
