<script setup lang="ts">
import { computed } from 'vue'
import type { BlockContent, BlockConfig, FlowBlockType } from '@/types/flow'

const props = defineProps<{ content?: BlockContent; config?: BlockConfig; type?: FlowBlockType }>()

// Валидируемые варианты 'input' (Фаза 2) — тот же компонент, только
// другая иконка, чтобы визуально отличать их на канвасе.
const ICONS: Partial<Record<FlowBlockType, string>> = {
    input: '✏️',
    number: '🔢',
    email: '📧',
    phone: '📱',
    date: '📅',
}
const icon = computed(() => (props.type ? ICONS[props.type] ?? '✏️' : '✏️'))
</script>

<template>
    <div class="block input-block">
        <span class="block-icon" aria-hidden="true">{{ icon }}</span>
        <span class="block-text">
            {{ content?.translations?.ru || 'Вопрос без текста' }}
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
