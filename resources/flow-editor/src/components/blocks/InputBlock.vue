<script setup lang="ts">
import type { BlockContent, BlockConfig } from '@/types/flow'

defineProps<{ content?: BlockContent; config?: BlockConfig }>()
</script>

<template>
    <div class="block input-block">
        <span class="block-icon" aria-hidden="true">✏️</span>
        <span class="block-text">
            {{ content?.translations?.ru || 'Вопрос без текста' }}
            <span class="var-tag" v-if="config?.variable">→ {{ config.variable }}</span>
        </span>
    </div>
</template>

<style scoped>
.block { display: flex; align-items: center; gap: 6px; padding: 6px 8px; border-radius: 6px; }
.block-icon { flex-shrink: 0; font-size: 12px; }
.block-text { font-size: 12px; color: #334155; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.var-tag { color: #10b981; font-weight: 600; font-size: 11px; }
</style>
