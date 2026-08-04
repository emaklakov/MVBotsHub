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
.block { display: flex; align-items: center; gap: 6px; padding: 6px 8px; border-radius: 6px; }
.block-icon { flex-shrink: 0; font-size: 12px; }
.block-text { font-size: 12px; color: #334155; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
</style>
