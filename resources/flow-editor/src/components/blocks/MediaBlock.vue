<script setup lang="ts">
import { computed } from 'vue'
import type { BlockContent, BlockConfig, FlowBlockType } from '@/types/flow'

// `config` объявлен, но не используется этим компонентом — см.
// аналогичный комментарий в ConditionBlock.vue.
const props = defineProps<{ content?: BlockContent; config?: BlockConfig; type?: FlowBlockType }>()

const ICONS: Partial<Record<FlowBlockType, string>> = {
    image: '🖼️',
    video: '🎬',
    audio: '🎵',
    file: '📎',
}

const EMPTY_LABELS: Partial<Record<FlowBlockType, string>> = {
    image: 'Ссылка на изображение не задана',
    video: 'Ссылка на видео не задана',
    audio: 'Ссылка на аудио не задана',
    file: 'Ссылка на файл не задана',
}

const icon = computed(() => (props.type ? ICONS[props.type] ?? '📎' : '📎'))

const displayText = computed(() => {
    const caption = props.content?.translations?.ru
    if (props.type === 'file' && props.content?.mediaFileName) return props.content.mediaFileName
    if (caption) return caption
    if (props.content?.mediaUrl) return props.content.mediaUrl
    return (props.type && EMPTY_LABELS[props.type]) || 'Медиа не задано'
})

const isEmpty = computed(() => !props.content?.mediaUrl)
</script>

<template>
    <div class="block media-block">
        <span class="block-icon" aria-hidden="true">{{ icon }}</span>
        <span class="block-text" :class="{ muted: isEmpty }">{{ displayText }}</span>
    </div>
</template>

<style scoped>
.block { display: flex; align-items: center; gap: var(--space-2); padding: 6px 8px; border-radius: var(--radius-sm); }
.block-icon { flex-shrink: 0; font-size: var(--font-size-sm); }
.block-text { font-size: var(--font-size-sm); color: var(--color-text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.block-text.muted { color: var(--color-text-muted); font-style: italic; }
</style>
