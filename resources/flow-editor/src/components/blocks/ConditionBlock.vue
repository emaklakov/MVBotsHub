<script setup lang="ts">
import { computed } from 'vue'
import type { BlockConfig, ConditionOperator } from '@/types/flow'

const props = defineProps<{ config?: BlockConfig }>()

const operatorLabels: Record<ConditionOperator, string> = {
    '==': '=',
    '!=': '≠',
    contains: 'содержит',
    is_set: 'задана',
    is_empty: 'пустая',
}

const needsValue = (op?: ConditionOperator) => op !== 'is_set' && op !== 'is_empty'

const summary = computed(() => {
    const variable = props.config?.conditionVariable
    const operator = props.config?.conditionOperator
    if (!variable || !operator) return 'Условие не задано'

    const opLabel = operatorLabels[operator]
    if (!needsValue(operator)) return `${variable} ${opLabel}`
    return `${variable} ${opLabel} «${props.config?.conditionValue ?? ''}»`
})
</script>

<template>
    <div class="block condition-block">
        <span class="block-icon" aria-hidden="true">🔀</span>
        <span class="block-text">{{ summary }}</span>
    </div>
</template>

<style scoped>
.block { display: flex; align-items: center; gap: var(--space-2); padding: 6px 8px; border-radius: var(--radius-sm); }
.block-icon { flex-shrink: 0; font-size: var(--font-size-sm); }
.block-text { font-size: var(--font-size-sm); color: var(--color-text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
</style>
