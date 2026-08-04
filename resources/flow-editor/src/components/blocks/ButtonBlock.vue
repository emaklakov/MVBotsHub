<script setup lang="ts">
import type { BlockContent } from '@/types/flow'

defineProps<{ content?: BlockContent }>()
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
.block { display: flex; align-items: flex-start; gap: 6px; padding: 6px 8px; border-radius: 6px; }
.block-icon { flex-shrink: 0; font-size: 12px; margin-top: 2px; }
.block-text { font-size: 12px; color: #94a3b8; }
.tags { display: flex; flex-wrap: wrap; gap: 4px; }
.tag { background: #fef3c7; padding: 2px 6px; border-radius: 4px; font-size: 10px; color: #92400e; }
</style>
