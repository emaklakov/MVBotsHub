<script setup lang="ts">
import type { BlockContent } from '@/types/flow'

defineProps<{ content?: BlockContent }>()
</script>

<template>
    <div class="block text-block">
        <span class="block-icon" aria-hidden="true">💬</span>
        <span class="block-text">{{ content?.translations?.ru || 'Пустой текст…' }}</span>
    </div>
</template>

<style scoped>
.block { display: flex; align-items: center; gap: var(--space-2); padding: 6px 8px; border-radius: var(--radius-sm); }
.block-icon { flex-shrink: 0; font-size: var(--font-size-sm); }
.block-text { font-size: var(--font-size-sm); color: var(--color-text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
</style>
