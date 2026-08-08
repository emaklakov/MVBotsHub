<script setup lang="ts">
import { computed } from 'vue'
import type { BlockContent, BlockConfig, FlowBlockType } from '@/types/flow'

const props = defineProps<{ content?: BlockContent; config?: BlockConfig; type?: FlowBlockType }>()

// 'poll' (Фаза 2) переиспользует этот же компонент — та же форма
// контента (translations + buttons-как-варианты-ответа), отличие только
// в иконке и в том, что у опроса сам вопрос важно видеть на канвасе
// (у button вопрос обычно уже виден в предыдущем текстовом блоке).
const isPoll = computed(() => props.type === 'poll')
const icon = computed(() => (isPoll.value ? '📊' : '🔘'))
const emptyLabel = computed(() => (isPoll.value ? 'Нет вариантов ответа' : 'Нет кнопок'))
</script>

<template>
    <div class="block button-block">
        <span class="block-icon" aria-hidden="true">{{ icon }}</span>
        <div class="body">
            <span v-if="isPoll && content?.translations?.ru" class="poll-question">{{ content.translations.ru }}</span>
            <div class="tags">
                <span v-if="!content?.buttons?.length" class="block-text">{{ emptyLabel }}</span>
                <span v-for="(btn, i) in content?.buttons || []" :key="i" class="tag">{{ btn.label || '(без текста)' }}</span>
            </div>
        </div>
    </div>
</template>

<style scoped>
.block { display: flex; align-items: flex-start; gap: var(--space-2); padding: 6px 8px; border-radius: var(--radius-sm); }
.block-icon { flex-shrink: 0; font-size: var(--font-size-sm); margin-top: 2px; }
.body { display: flex; flex-direction: column; gap: 4px; min-width: 0; }
.poll-question { font-size: var(--font-size-sm); color: var(--color-text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.block-text { font-size: var(--font-size-sm); color: var(--color-text-muted); }
.tags { display: flex; flex-wrap: wrap; gap: 4px; }
.tag { background: color-mix(in oklch, var(--color-warning) 20%, var(--color-surface)); width: 100%; border: 1px solid; border-color: var(--color-warning-text); padding: 2px 6px; border-radius: var(--radius-sm); font-size: var(--font-size-xs); color: var(--color-warning-text); }
</style>
